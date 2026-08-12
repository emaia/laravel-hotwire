<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Filesystem\Filesystem;

final readonly class ControllerLoadPlan
{
    /** @var string[] */
    public array $includedComDepControllers;

    /** @var string[] */
    public array $preloadControllers;

    /** @var string[] */
    public array $eagerControllers;

    /**
     * @param  array<string, ResolvedController>  $applicationControllers
     * @param  array<string, ResolvedController>  $packageControllers
     * @param  array<string, ResolvedController>  $eagerApplicationControllers
     * @param  array<string, ResolvedController>  $eagerPackageControllers
     * @param  string[]  $excludedPackagePaths
     */
    public function __construct(
        public ControllerLoadPolicy $policy,
        public array $applicationControllers,
        public array $packageControllers,
        public array $eagerApplicationControllers,
        public array $eagerPackageControllers,
        public array $excludedPackagePaths,
    ) {
        $this->includedComDepControllers = $policy->includedComDepControllers;
        $this->preloadControllers = $policy->preloadControllers;
        $this->eagerControllers = $policy->eagerControllers;
    }

    /**
     * Build an inspectable load plan with application controllers taking precedence.
     *
     * @param  string[]|null  $includedComDepControllers
     * @param  string[]  $preloadControllers
     * @param  string[]  $eagerControllers
     */
    public static function make(
        Filesystem $files,
        HotwireRegistry $registry,
        string $appControllersPath,
        ?array $includedComDepControllers = null,
        array $preloadControllers = [],
        array $eagerControllers = [],
    ): self {
        $resolver = new ControllerResolver($files, $registry, $appControllersPath);
        $application = $resolver->applicationControllers();
        $preloadControllers = array_values(array_unique($preloadControllers));
        $eagerControllers = array_values(array_unique($eagerControllers));
        $preloadControllers = array_values(array_diff($preloadControllers, $eagerControllers));
        sort($preloadControllers);
        sort($eagerControllers);
        $selected = [];
        $selectedPackageControllers = [];

        foreach (array_values(array_unique(array_merge($preloadControllers, $eagerControllers))) as $identifier) {
            $selected[$identifier] = $resolver->resolve($identifier);

            if ($selected[$identifier]->origin === ControllerOrigin::Package) {
                $selectedPackageControllers[] = $identifier;
            }
        }

        $package = [];
        $excludedPackagePaths = [];
        $effectiveComDep = [];

        foreach ($registry->controllers() as $identifier => $definition) {
            $source = $resolver->packageController($definition);
            $included = empty($definition->npm)
                || $includedComDepControllers === null
                || in_array($identifier, $includedComDepControllers, true)
                || in_array($identifier, $selectedPackageControllers, true);

            if (! $included) {
                $excludedPackagePaths[] = $source->loaderPath;

                continue;
            }

            if (! empty($definition->npm)) {
                $effectiveComDep[] = $identifier;
            }

            $package[$identifier] = $source;
        }

        sort($effectiveComDep);
        sort($excludedPackagePaths);

        $eagerApplication = [];
        $eagerPackage = [];
        $eagerPaths = [];

        foreach ($selected as $identifier => $source) {

            if (! in_array($identifier, $eagerControllers, true)) {
                continue;
            }

            $eagerPaths[$identifier] = $source->loaderPath;

            if ($source->origin === ControllerOrigin::Application) {
                $eagerApplication[$identifier] = $source;

                continue;
            }

            $eagerPackage[$identifier] = $source;
        }

        return new self(
            new ControllerLoadPolicy(
                $effectiveComDep,
                $preloadControllers,
                $eagerControllers,
                includeAllComDepControllers: $includedComDepControllers === null,
                eagerControllerPaths: $eagerPaths,
            ),
            $application,
            $package,
            $eagerApplication,
            $eagerPackage,
            $excludedPackagePaths,
        );
    }
}
