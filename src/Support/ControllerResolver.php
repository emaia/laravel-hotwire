<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class ControllerResolver
{
    /** @var array<string, ResolvedController>|null */
    private ?array $applicationControllers = null;

    /** @var array<string, string[]> */
    private array $ambiguousApplicationControllers = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly HotwireRegistry $registry,
        private readonly string $appControllersPath,
    ) {}

    /** Resolve an application override or package controller by identifier. */
    public function resolve(string $identifier): ResolvedController
    {
        $application = $this->applicationControllers();

        if (isset($this->ambiguousApplicationControllers[$identifier])) {
            throw new RuntimeException(
                "Controller [{$identifier}] is ambiguous; found conventional candidates: "
                .implode(', ', $this->ambiguousApplicationControllers[$identifier]).'.',
            );
        }

        if (isset($application[$identifier])) {
            return $application[$identifier];
        }

        $controller = $this->registry->controller($identifier);

        if ($controller !== null) {
            return $this->packageController($controller);
        }

        throw new RuntimeException("Controller [{$identifier}] was not found in the application or Hotwire registry.");
    }

    /** @return array<string, ResolvedController> */
    public function applicationControllers(): array
    {
        if ($this->applicationControllers !== null) {
            return $this->applicationControllers;
        }

        if (! $this->files->isDirectory($this->appControllersPath)) {
            return $this->applicationControllers = [];
        }

        $candidates = [];

        foreach ($this->files->allFiles($this->appControllersPath) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (! preg_match('/_controller\.(js|ts)$/', $relative)) {
                continue;
            }

            $logical = self::logicalPath($relative);
            $identifier = str_replace(
                ['/', '_'],
                ['--', '-'],
                (string) preg_replace('/_controller\.(js|ts)$/', '', $logical),
            );
            $candidates[$identifier][] = './'.$relative;
        }

        $controllers = [];

        foreach ($candidates as $identifier => $paths) {
            sort($paths);

            if (count($paths) > 1) {
                $this->ambiguousApplicationControllers[$identifier] = $paths;

                continue;
            }

            $controllers[$identifier] = new ResolvedController(
                $identifier,
                ControllerOrigin::Application,
                $paths[0],
            );
        }

        ksort($controllers);

        return $this->applicationControllers = $controllers;
    }

    public static function logicalPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if (preg_match('#(?:^|/)(?:controllers|components)/#', $path, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return $path;
        }

        $matched = $match[0][0];
        $offset = $match[0][1];

        return substr($path, $offset + strlen($matched));
    }

    /** Resolve a registry definition to its Vite loader path. */
    public function packageController(ControllerDefinition $controller): ResolvedController
    {
        $relative = (string) preg_replace('#^resources/js/controllers/#', '', $controller->source);

        return new ResolvedController(
            $controller->identifier,
            ControllerOrigin::Package,
            '../../../vendor/emaia/laravel-hotwire/resources/js/controllers/'.$relative,
        );
    }
}
