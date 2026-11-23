<?php

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $finder = new ExecutableFinder;

    foreach (['bash', 'git', 'node', 'npm', 'tar', 'cmp'] as $command) {
        if ($finder->find($command) === null) {
            $this->markTestSkipped("Stimulus artifact packaging requires {$command}.");
        }
    }

    $this->artifactDirectory = sys_get_temp_dir().'/hotwire-stimulus-build-'.uniqid();
    (new Filesystem)->ensureDirectoryExists($this->artifactDirectory);
});

afterEach(function () {
    if (isset($this->artifactDirectory)) {
        (new Filesystem)->deleteDirectory($this->artifactDirectory);
    }
});

it('packages a reproducible Stimulus revision without altering the upstream bundles', function () {
    $directory = $this->artifactDirectory;
    $files = new Filesystem;
    [$command, $env, $sourceSha, $buildSha] = stimulusArtifactFixture($directory);
    $short = substr($sourceSha, 0, 12);
    $tag = "stimulus-{$short}-r2";
    $name = "hotwired-{$tag}";
    $output = "{$directory}/output";
    $pack = new Process([...$command, 'output/', '2'], cwd: $directory, env: $env);
    $pack->mustRun();
    $otherOutput = "{$directory}/other-output";
    $files->ensureDirectoryExists("{$otherOutput}/.{$tag}.abandoned");
    $files->put("{$otherOutput}/.{$tag}.abandoned/{$name}.tgz", 'truncated tarball');
    (new Process([...$command, $otherOutput, '2'], env: $env))->mustRun();

    $assetDirectory = "{$output}/{$tag}";
    $tarball = "{$assetDirectory}/{$name}.tgz";
    $manifest = json_decode(stimulusArtifactEntry($tarball, 'package.json'), true, flags: JSON_THROW_ON_ERROR);
    $provenance = json_decode($files->get("{$assetDirectory}/{$name}.json"), true, flags: JSON_THROW_ON_ERROR);
    $contents = (new Process(['tar', '-tzf', $tarball]))->mustRun()->getOutput();
    $checksum = hash_file('sha256', $tarball);
    $bashAssetDirectory = trim((new Process(['bash', '-c', 'pwd -P'], cwd: $assetDirectory))->mustRun()->getOutput());

    expect($manifest)->toMatchArray([
        'name' => '@hotwired/stimulus',
        'version' => "3.2.2+hotwire.{$short}.r2",
        'gitHead' => $sourceSha,
    ])->not->toHaveKey('scripts')
        ->and($contents)->not->toContain('dist/types/tests/')
        ->and(stimulusArtifactEntry($tarball, 'dist/stimulus.js'))->toBe('official ESM')
        ->and(stimulusArtifactEntry($tarball, 'dist/stimulus.umd.js'))->toBe('official UMD')
        ->and(stimulusArtifactEntry($tarball, 'dist/types/index.d.ts'))->toBe('public types')
        ->and(stimulusArtifactEntry($tarball, 'LICENSE.md'))->toBe('MIT fixture')
        ->and($provenance['commit'])->toBe($sourceSha)
        ->and($provenance['upstream_build']['commit'])->toBe($buildSha)
        ->and($provenance['sha256'])->toBe($checksum)
        ->and($files->get("{$tarball}.sha256"))->toBe("{$checksum}  {$name}.tgz\n")
        ->and(hash_file('sha256', "{$otherOutput}/{$tag}/{$name}.tgz"))->toBe($checksum)
        ->and(is_readable($tarball))->toBeTrue()
        ->and($files->get("{$otherOutput}/.{$tag}.abandoned/{$name}.tgz"))->toBe('truncated tarball')
        ->and($pack->getOutput())->toContain("Built {$bashAssetDirectory}/{$name}.tgz (SHA-256:")
        ->and($pack->getOutput())->toContain('gh release create --prerelease --latest=false');

    if (PHP_OS_FAMILY !== 'Windows') {
        expect(fileperms($assetDirectory) & 0777)->toBe(0755)
            ->and(fileperms($tarball) & 0777)->toBe(0644);
    }

    $retry = new Process([...$command, $output, '2'], env: $env);
    expect($retry->run())->toBe(73)
        ->and($retry->getErrorOutput())->toContain('refusing to overwrite')
        ->not->toContain('Inspect ');
});

it('does not create an output directory when the upstream commits do not match', function () {
    $directory = $this->artifactDirectory;
    [$command, $env] = stimulusArtifactFixture($directory);
    $command[2] = stimulusFixtureCommit("{$directory}/source", 'Wrong source fixture');
    $output = "{$directory}/mismatch";
    $mismatch = new Process([...$command, $output], env: $env);

    expect($mismatch->run())->not->toBe(0)
        ->and($mismatch->getErrorOutput())->toContain('does not identify Stimulus source commit')
        ->not->toContain('Inspect ')
        ->and(is_dir($output))->toBeFalse();
});

it('explains a missing upstream entry without a Node stack trace', function () {
    $directory = $this->artifactDirectory;
    [$command, $env] = stimulusArtifactFixture($directory, includePublicTypes: false);
    $output = "{$directory}/output";
    $pack = new Process([...$command, $output], env: $env);

    expect($pack->run())->not->toBe(0)
        ->and($pack->getErrorOutput())->toContain('Missing Stimulus types file: dist/types/index.d.ts. Review the upstream build layout.')
        ->not->toContain('ENOENT')
        ->and($pack->getErrorOutput())->not->toContain('    at ')
        ->and(is_dir($output))->toBeFalse();
});

it('rejects an output path blocked by a regular file before fetching', function (string $suffix) {
    $directory = $this->artifactDirectory;
    [$command, $env] = stimulusArtifactFixture($directory);
    $blocked = "{$directory}/not-a-directory";
    (new Filesystem)->put($blocked, 'leave me alone');
    $pack = new Process([...$command, $blocked.$suffix], env: $env);

    expect($pack->run())->not->toBe(0)
        ->and($pack->getErrorOutput())->toContain('output path is not a directory')
        ->and($pack->getOutput())->not->toContain('Fetching')
        ->and(file_get_contents($blocked))->toBe('leave me alone');
})->with(['the output itself' => '', 'a trailing slash' => '/', 'an ancestor' => '/child']);

it('rejects an unwritable output directory before fetching', function () {
    $directory = $this->artifactDirectory;
    [$command, $env] = stimulusArtifactFixture($directory);
    $output = "{$directory}/output";
    mkdir($output, 0500);
    $pack = new Process([...$command, $output], env: $env);

    try {
        if (is_writable($output)) {
            $this->markTestSkipped('The current user can write to a read-only directory.');
        }
        expect($pack->run())->not->toBe(0)
            ->and($pack->getErrorOutput())->toContain('output directory cannot be written or traversed')
            ->and($pack->getOutput())->not->toContain('Fetching');
    } finally {
        chmod($output, 0700);
    }
});

it('rejects invalid upstream package metadata without a Node stack trace', function (array $manifestOverrides) {
    $directory = $this->artifactDirectory;
    [$command, $env] = stimulusArtifactFixture($directory, manifestOverrides: $manifestOverrides);
    $output = "{$directory}/output";
    $pack = new Process([...$command, $output], env: $env);

    expect($pack->run())->not->toBe(0)
        ->and($pack->getErrorOutput())->toContain('Expected the official @hotwired/stimulus manifest and a stable upstream version.')
        ->and($pack->getErrorOutput())->not->toContain('    at ')
        ->and(is_dir($output))->toBeFalse();
})->with([
    'wrong package' => [['name' => '@hotwired/other']],
    'unstable version' => [['version' => '3.2.2-beta.1']],
]);

it('explains an upstream entry blocked by a non-directory without a Node stack trace', function () {
    $directory = $this->artifactDirectory;
    [$command, $env] = stimulusArtifactFixture($directory, typesDirectory: false);
    $output = "{$directory}/output";
    $pack = new Process([...$command, $output], env: $env);

    // Node may report either ENOTDIR or ENOENT for a path with a non-directory ancestor.
    expect($pack->run())->not->toBe(0)
        ->and($pack->getErrorOutput())->toContain('Stimulus types file: dist/types/index.d.ts', 'Review the upstream build layout.')
        ->and($pack->getErrorOutput())->not->toContain('    at ')
        ->and(is_dir($output))->toBeFalse();
});

it('exposes no partial artifacts and preserves a destination found before rename', function (string $failure) {
    if (PHP_OS_FAMILY === 'Windows' && in_array($failure, ['interrupt', 'terminate-finalization'], true)) {
        $this->markTestSkipped('These cases require POSIX signal delivery from Node to Bash.');
    }

    $directory = $this->artifactDirectory;
    $output = realpath($directory).'/output';
    [$command, $env, $sourceSha] = stimulusArtifactFixture($directory);
    $tag = 'stimulus-'.substr($sourceSha, 0, 12);
    $name = "hotwired-{$tag}";
    $shim = "{$directory}/file-io.cjs";
    (new Filesystem)->put($shim, <<<'JS'
        const fs = require("node:fs");
        const { syncBuiltinESMExports } = require("node:module");
        const { dirname, join, resolve } = require("node:path");
        const { copyFileSync, mkdirSync, renameSync } = fs;
        const finalPath = resolve(process.env.TEST_FINAL);
        const pathKey = (path) => process.platform === "win32" ? resolve(path).toLowerCase() : resolve(path);
        const outputKey = pathKey(dirname(finalPath));
        const finalKey = pathKey(finalPath);
        const isConflict = process.env.TEST_FAILURE.startsWith("conflict");
        function createCompetingOutput() {
            mkdirSync(finalPath);
            if (process.env.TEST_FAILURE === "conflict") {
                fs.writeFileSync(join(finalPath, "sentinel"), "another run's file");
            }
        }
        fs.copyFileSync = function (source, destination, ...args) {
            const staging = dirname(dirname(pathKey(destination))) === outputKey;
            if (staging) {
                if (fs.existsSync(finalPath)) throw new Error("final directory appeared before rename");
                if (destination.endsWith(".tgz") && ["full disk", "interrupt"].includes(process.env.TEST_FAILURE)) {
                    fs.writeFileSync(destination, "truncated tarball");
                    if (process.env.TEST_FAILURE === "interrupt") {
                        process.kill(process.ppid, "SIGINT");
                        process.exit(130);
                    }
                    throw Object.assign(new Error("simulated full disk"), { code: "ENOSPC" });
                }
            }
            copyFileSync(source, destination, ...args);
            if (staging && destination.endsWith(".json") && isConflict) createCompetingOutput();
        };
        fs.renameSync = function (source, destination) {
            if (pathKey(destination) === finalKey && process.env.TEST_FAILURE === "rename-error") {
                throw Object.assign(new Error("simulated rename failure"), { code: "EIO" });
            }
            if (pathKey(destination) === finalKey && process.env.TEST_FAILURE === "terminate-finalization") {
                process.kill(process.ppid, "SIGTERM");
                Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, 150);
            }
            return renameSync(source, destination);
        };
        syncBuiltinESMExports();
        JS);
    $failureEnv = $env + [
        'NODE_OPTIONS' => '--require "'.str_replace('\\', '/', $shim).'"',
        'TEST_FINAL' => "{$output}/{$tag}",
        'TEST_FAILURE' => $failure,
    ];
    $pack = new Process([...$command, 'output/'], cwd: $directory, env: $failureEnv);
    $status = $pack->run();
    if ($failure === 'terminate-finalization') {
        $tarball = "{$output}/{$tag}/{$name}.tgz";
        expect($status)->toBe(143)
            ->and($pack->getErrorOutput())->not->toContain('Inspect ')
            ->and(scandir("{$output}/{$tag}"))->toBe(['.', '..', "{$name}.json", "{$name}.tgz", "{$name}.tgz.sha256"])
            ->and(file_get_contents("{$tarball}.sha256"))->toBe(hash_file('sha256', $tarball)."  {$name}.tgz\n");

        return;
    }

    expect($status)->not->toBe(0);
    if (str_starts_with($failure, 'conflict')) {
        expect($status)->toBe(73)
            ->and($pack->getErrorOutput())->toContain('refusing to overwrite')
            ->not->toContain('Inspect ')
            ->and(scandir($output))->toBe(['.', '..', $tag]);
        if ($failure === 'conflict') {
            expect(scandir("{$output}/{$tag}"))->toBe(['.', '..', 'sentinel'])
                ->and(file_get_contents("{$output}/{$tag}/sentinel"))->toBe("another run's file");
        } else {
            expect(scandir("{$output}/{$tag}"))->toBe(['.', '..']);
        }

        return;
    }

    if ($failure === 'interrupt') {
        expect($status)->toBe(130);
    } elseif ($failure === 'rename-error') {
        expect($pack->getErrorOutput())->toContain('could not finalize', '(EIO)');
    } else {
        expect($pack->getErrorOutput())->toContain("could not stage {$name}.tgz (ENOSPC)");
    }

    expect($pack->getErrorOutput())->not->toContain('Inspect ')
        ->and(scandir($output))->toBe(['.', '..']);
    (new Process([...$command, $output], env: $env))->mustRun();
    expect(scandir($output))->toBe(['.', '..', $tag])
        ->and(scandir("{$output}/{$tag}"))->toBe(['.', '..', "{$name}.json", "{$name}.tgz", "{$name}.tgz.sha256"]);
})->with([
    'partial write' => 'full disk',
    'Ctrl-C' => 'interrupt',
    'SIGTERM during final rename' => 'terminate-finalization',
    'failed final rename' => 'rename-error',
    'non-empty competing directory' => 'conflict',
    'empty competing directory' => 'conflict-empty',
]);

/**
 * @param  array<string, string>  $manifestOverrides
 * @return array{list<string>, array<string, string>, string, string}
 */
function stimulusArtifactFixture(string $directory, bool $includePublicTypes = true, bool $typesDirectory = true, array $manifestOverrides = []): array
{
    $files = new Filesystem;
    $source = "{$directory}/source";
    $build = "{$directory}/upstream-build";
    $files->ensureDirectoryExists($source);
    $files->put("{$source}/LICENSE.md", 'MIT fixture');
    $files->put("{$source}/README.md", 'Stimulus fixture');
    $sourceSha = stimulusFixtureCommit($source, 'Stimulus source fixture');

    $files->ensureDirectoryExists("{$build}/dist");
    $files->put("{$build}/package.json", json_encode([
        'name' => '@hotwired/stimulus',
        'version' => '3.2.2',
        'main' => 'dist/stimulus.umd.js',
        'module' => 'dist/stimulus.js',
        'types' => 'dist/types/index.d.ts',
        'scripts' => ['prepack' => 'exit 42'],
        ...$manifestOverrides,
    ], JSON_THROW_ON_ERROR));
    $files->put("{$build}/dist/stimulus.js", 'official ESM');
    $files->put("{$build}/dist/stimulus.umd.js", 'official UMD');
    if ($typesDirectory) {
        $files->ensureDirectoryExists("{$build}/dist/types/tests");
        if ($includePublicTypes) {
            $files->put("{$build}/dist/types/index.d.ts", 'public types');
        }
        $files->put("{$build}/dist/types/tests/example.d.ts", 'upstream test declaration');
    } else {
        $files->put("{$build}/dist/types", 'not a directory');
    }
    $buildSha = stimulusFixtureCommit($build, "Stimulus build fixture\n\nhttps://github.com/hotwired/stimulus/tree/{$sourceSha}");

    return [
        ['bash', dirname(__DIR__, 2).'/scripts/build_stimulus_artifact.sh', $sourceSha, $buildSha],
        [
            'GIT_CONFIG_COUNT' => '2',
            'GIT_CONFIG_KEY_0' => "url.file://{$source}.insteadOf",
            'GIT_CONFIG_VALUE_0' => 'https://github.com/hotwired/stimulus.git',
            'GIT_CONFIG_KEY_1' => "url.file://{$build}.insteadOf",
            'GIT_CONFIG_VALUE_1' => 'https://github.com/hotwired/dev-builds.git',
        ],
        $sourceSha,
        $buildSha,
    ];
}

function stimulusFixtureCommit(string $directory, string $message): string
{
    (new Process(['git', 'init', '-q', $directory]))->mustRun();
    (new Process(['git', 'add', '.'], cwd: $directory))->mustRun();
    $tree = trim((new Process(['git', 'write-tree'], cwd: $directory))->mustRun()->getOutput());

    $sha = trim((new Process([
        'git', '-c', 'user.name=Stimulus fixture', '-c', 'user.email=fixture@example.test',
        '-c', 'commit.gpgSign=false', 'commit-tree', $tree, '-m', $message,
    ], cwd: $directory))->mustRun()->getOutput());
    (new Process(['git', 'update-ref', 'refs/heads/main', $sha], cwd: $directory))->mustRun();

    return $sha;
}

function stimulusArtifactEntry(string $tarball, string $entry): string
{
    return (new Process(['tar', '-xzOf', $tarball, "package/{$entry}"]))->mustRun()->getOutput();
}
