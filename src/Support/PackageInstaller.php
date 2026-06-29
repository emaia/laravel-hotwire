<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class PackageInstaller
{
    public function detect(Filesystem $files): string
    {
        $lockFiles = [
            'bun.lock' => 'bun',
            'pnpm-lock.yaml' => 'pnpm',
            'yarn.lock' => 'yarn',
            'package-lock.json' => 'npm',
        ];

        foreach ($lockFiles as $file => $manager) {
            if ($files->exists(base_path($file))) {
                return $manager;
            }
        }

        return 'npm';
    }

    /**
     * Merge packages into the app package.json devDependencies, writing the file
     * only when something changes. Returns the entries actually written.
     *
     * With $updateExisting (default), a present package is bumped when its version
     * differs; with it false, an already-present package is left untouched.
     *
     * @param  array<string, string>  $packages  name => version
     * @return array<string, string> entries added or updated
     *
     * @throws FileNotFoundException
     */
    public function addDevDependencies(Filesystem $files, array $packages, bool $updateExisting = true): array
    {
        $path = base_path('package.json');

        if (! $files->exists($path)) {
            return [];
        }

        $content = $files->get($path);
        $json = json_decode($content, true);

        if (! is_array($json)) {
            return [];
        }

        $deps = $json['dependencies'] ?? [];
        $devDeps = $json['devDependencies'] ?? [];
        $changed = [];

        foreach ($packages as $name => $version) {
            if (array_key_exists($name, $deps)) {
                continue;
            }

            $inDev = array_key_exists($name, $devDeps);

            if ($inDev && (! $updateExisting || $devDeps[$name] === $version)) {
                continue;
            }

            $devDeps[$name] = $version;
            $changed[$name] = $version;
        }

        if ($changed !== []) {
            $json['devDependencies'] = $devDeps;
            $files->put($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }

        return $changed;
    }

    public const string VITE_ALIAS_ADDED = 'added';

    public const string VITE_ALIAS_ALREADY_PRESENT = 'already_present';

    public const string VITE_ALIAS_NO_CONFIG = 'no_config';

    public const string VITE_ALIAS_PATTERN_MISMATCH = 'pattern_mismatch';

    /**
     * Add a Vite alias to the app's vite.config.{ts,mjs,js} via regex injection.
     *
     * Detects the file in extension priority (ts > mjs > js) and short-circuits when
     * the alias key already appears anywhere in the source — idempotent like
     * addDevDependencies(). When the file matches the Laravel-stock shape
     * (`export default defineConfig({ … })`) the alias is injected inside the
     * object literal; the helper also adds `import { fileURLToPath } from 'node:url'`
     * unless that symbol is already referenced. When the shape doesn't match (custom
     * config, non-ESM, multi-export) the file is left untouched and the caller is
     * expected to print the snippet for manual paste.
     *
     * @return string one of the VITE_ALIAS_* constants
     *
     * @throws FileNotFoundException
     */
    public function addViteAlias(Filesystem $files, string $aliasKey, string $relativePath): string
    {
        $configPath = $this->findViteConfig($files);

        if ($configPath === null) {
            return self::VITE_ALIAS_NO_CONFIG;
        }

        $content = $files->get($configPath);

        if (str_contains($content, "'$aliasKey'") || str_contains($content, "\"$aliasKey\"")) {
            return self::VITE_ALIAS_ALREADY_PRESENT;
        }

        $injected = $this->injectViteAlias($content, $aliasKey, $relativePath);

        if ($injected === null) {
            return self::VITE_ALIAS_PATTERN_MISMATCH;
        }

        $files->put($configPath, $injected);

        return self::VITE_ALIAS_ADDED;
    }

    /** Find the user's vite config file, preferring TS, then MJS, then JS. */
    private function findViteConfig(Filesystem $files): ?string
    {
        foreach (['vite.config.ts', 'vite.config.mjs', 'vite.config.js'] as $name) {
            $path = base_path($name);

            if ($files->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /** Returns the modified content, or null when the file doesn't match the stock shape. */
    private function injectViteAlias(string $content, string $aliasKey, string $relativePath): ?string
    {
        if (! preg_match('/export\s+default\s+defineConfig\s*\(\s*\{\s*\r?\n/', $content, $match)) {
            return null;
        }

        $needsImport = ! str_contains($content, 'fileURLToPath');

        $resolveBlock = "    resolve: {\n"
            ."        alias: {\n"
            ."            '$aliasKey': fileURLToPath(new URL('$relativePath', import.meta.url)),\n"
            ."        },\n"
            ."    },\n";

        $injected = preg_replace(
            '/(export\s+default\s+defineConfig\s*\(\s*\{\s*\r?\n)/',
            "\$1$resolveBlock",
            $content,
            1,
        );

        if ($needsImport) {
            $injected = $this->insertFileUrlToPathImport($injected);
        }

        return $injected;
    }

    /**
     * Inject `import { fileURLToPath } from 'node:url';` after the last existing
     * import statement, or at the top of the file when there are no imports.
     */
    private function insertFileUrlToPathImport(string $content): string
    {
        $importLine = "import { fileURLToPath } from 'node:url';";
        $lines = explode("\n", $content);
        $lastImportIdx = -1;

        foreach ($lines as $i => $line) {
            if (preg_match('/^import\s/', $line)) {
                $lastImportIdx = $i;
            }
        }

        if ($lastImportIdx === -1) {
            return $importLine."\n".$content;
        }

        array_splice($lines, $lastImportIdx + 1, 0, $importLine);

        return implode("\n", $lines);
    }

    /** @return string[] */
    public function command(string $manager): array
    {
        return [$manager, 'install'];
    }

    public function install(string $manager, Command $command): int
    {
        $process = new Process($this->command($manager), base_path());
        $process->setTimeout(null);
        $exitCode = $process->run();

        if ($process->getOutput() !== '') {
            $command->getOutput()->write($process->getOutput());
        }

        if ($process->getErrorOutput() !== '') {
            $command->getOutput()->write($process->getErrorOutput());
        }

        return $exitCode;
    }
}
