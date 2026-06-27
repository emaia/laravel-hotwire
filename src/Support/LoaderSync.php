<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

final class LoaderSync
{
    private const string HEADER_PREFIX = '// @hotwire-loader v';

    private string $targetPath;

    public function __construct(?string $targetPath = null)
    {
        $this->targetPath = $targetPath ?? resource_path('js/controllers/index.js');
    }

    public function syncIfStale(): bool
    {
        if (! file_exists($this->targetPath)) {
            $this->write();

            return true;
        }

        $currentContent = file_get_contents($this->targetPath);
        $currentVersion = $this->parseVersion($currentContent);

        if ($currentVersion === $this->packageVersion()) {
            return false;
        }

        $this->write();

        return true;
    }

    public function write(): void
    {
        $registry = HotwireRegistry::make();
        $exclusions = $this->buildExclusionList($registry);
        $version = $this->packageVersion();

        $content = $this->generateContent($exclusions, $version);

        $dir = dirname($this->targetPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->targetPath, $content);
    }

    /** @param  ControllerDefinition[]  $controllers */
    private function buildExclusionList(HotwireRegistry $registry): array
    {
        $exclusions = [];

        foreach ($registry->controllers() as $controller) {
            if (! empty($controller->npm)) {
                $exclusions[] = '**/'.$controller->filename();
            }
        }

        sort($exclusions);

        return $exclusions;
    }

    /** @param  string[]  $exclusions */
    private function generateContent(array $exclusions, string $version): string
    {
        $globLines = '';

        foreach ($exclusions as $exclusion) {
            $globLines .= "    \"!{$exclusion}\",\n";
        }

        return <<<JS
// @hotwire-loader v{$version}
import { Stimulus } from "../libs/stimulus";
import { registerControllers } from "@emaia/stimulus-dynamic-loader";

const userControllers = import.meta.glob(
    "./**/*_controller.{js,ts}",
    { eager: false }
);

const packageControllers = import.meta.glob([
    "../../../vendor/emaia/laravel-hotwire/resources/js/controllers/**/*_controller.js",
{$globLines}], { eager: false });

registerControllers(Stimulus, packageControllers);
registerControllers(Stimulus, userControllers);
JS;
    }

    private function packageVersion(): string
    {
        $composerJson = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/composer.json'),
            true,
        );

        return $composerJson['version'] ?? '0.0.0';
    }

    private function parseVersion(string $content): ?string
    {
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), self::HEADER_PREFIX)) {
                return trim(substr($line, strlen(self::HEADER_PREFIX)));
            }
        }

        return null;
    }
}
