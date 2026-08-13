<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ControllerLoadPlan;
use Emaia\LaravelHotwire\Support\ControllerOrigin;
use Emaia\LaravelHotwire\Support\ControllerResolver;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    HotwireRegistry::reset();
    $this->registry = HotwireRegistry::make();
    $this->files = new Filesystem;
    $this->dir = sys_get_temp_dir().'/hwc-load-plan-'.uniqid();
    $this->files->makeDirectory($this->dir, 0755, true);
});

afterEach(function () {
    $this->files->deleteDirectory($this->dir);
});

function writeController(string $dir, string $relative): string
{
    $path = $dir.'/'.$relative;
    (new Filesystem)->ensureDirectoryExists(dirname($path));
    file_put_contents($path, 'export default class {}');

    return $path;
}

// --- ControllerResolver ---

it('resolves conventional application JavaScript and TypeScript controllers', function () {
    writeController($this->dir, 'search_controller.js');
    writeController($this->dir, 'admin/navigation_controller.ts');

    $resolver = new ControllerResolver($this->files, $this->registry, $this->dir);

    expect($resolver->resolve('search'))
        ->origin->toBe(ControllerOrigin::Application)
        ->loaderPath->toBe('./search_controller.js')
        ->and($resolver->resolve('admin--navigation'))
        ->origin->toBe(ControllerOrigin::Application)
        ->loaderPath->toBe('./admin/navigation_controller.ts');
});

it('matches lazy-loader v2 path prefixes for nested controllers directories', function () {
    writeController($this->dir, 'admin/controllers/report_controller.js');

    $resolver = new ControllerResolver($this->files, $this->registry, $this->dir);

    expect($resolver->resolve('report')->loaderPath)->toBe('./admin/controllers/report_controller.js');
});

it('removes only the first reserved controller path prefix', function () {
    writeController($this->dir, 'controllers/admin/controllers/report_controller.js');

    $resolver = new ControllerResolver($this->files, $this->registry, $this->dir);

    expect($resolver->resolve('admin--controllers--report')->loaderPath)
        ->toBe('./controllers/admin/controllers/report_controller.js');
});

it('resolves package controllers from the registry', function () {
    $source = (new ControllerResolver($this->files, $this->registry, $this->dir))->resolve('carousel');

    expect($source)
        ->origin->toBe(ControllerOrigin::Package)
        ->identifier->toBe('carousel')
        ->loaderPath->toBe('../../../vendor/emaia/laravel-hotwire/resources/js/controllers/carousel_controller.js');
});

it('prefers a conventional application controller over its package counterpart', function () {
    writeController($this->dir, 'carousel_controller.ts');

    $source = (new ControllerResolver($this->files, $this->registry, $this->dir))->resolve('carousel');

    expect($source)
        ->origin->toBe(ControllerOrigin::Application)
        ->loaderPath->toBe('./carousel_controller.ts');
});

it('rejects unknown controller identifiers', function () {
    (new ControllerResolver($this->files, $this->registry, $this->dir))->resolve('missing');
})->throws(RuntimeException::class, 'Controller [missing] was not found');

it('rejects ambiguous conventional application candidates', function () {
    writeController($this->dir, 'admin/navigation_controller.js');
    writeController($this->dir, 'admin/navigation_controller.ts');

    (new ControllerResolver($this->files, $this->registry, $this->dir))->resolve('admin--navigation');
})->throws(RuntimeException::class, 'Controller [admin--navigation] is ambiguous');

it('allows unrelated ambiguous application candidates in a load plan', function () {
    writeController($this->dir, 'admin/navigation_controller.js');
    writeController($this->dir, 'admin/navigation_controller.ts');

    $plan = ControllerLoadPlan::make($this->files, $this->registry, $this->dir);

    expect($plan->applicationControllers)->not->toHaveKey('admin--navigation');
});

it('rejects ambiguous application candidates selected by the load policy', function () {
    writeController($this->dir, 'admin/navigation_controller.js');
    writeController($this->dir, 'admin/navigation_controller.ts');

    ControllerLoadPlan::make(
        $this->files,
        $this->registry,
        $this->dir,
        eagerControllers: ['admin--navigation'],
    );
})->throws(RuntimeException::class, 'Controller [admin--navigation] is ambiguous');

// --- ControllerLoadPlan ---

it('records explicit dependency opt-ins and resolves preload selections', function () {
    writeController($this->dir, 'navigation_controller.ts');

    $plan = ControllerLoadPlan::make(
        $this->files,
        $this->registry,
        $this->dir,
        includedComDepControllers: ['carousel'],
        preloadControllers: ['navigation'],
        eagerControllers: ['carousel'],
    );

    expect($plan->includedComDepControllers)->toBe(['carousel'])
        ->and($plan->preloadControllers)->toBe(['navigation'])
        ->and($plan->eagerControllers)->toBe(['carousel'])
        ->and(array_keys($plan->eagerApplicationControllers))->toBe([])
        ->and(array_keys($plan->eagerPackageControllers))->toBe(['carousel'])
        ->and($plan->packageControllers)->toHaveKey('modal')
        ->and($plan->packageControllers)->not->toHaveKey('chart');
});

it('keeps package fallbacks when an application controller shadows them', function () {
    writeController($this->dir, 'carousel_controller.ts');

    $plan = ControllerLoadPlan::make($this->files, $this->registry, $this->dir);

    expect($plan->applicationControllers)->toHaveKey('carousel')
        ->and($plan->packageControllers)->toHaveKey('carousel');
});

it('rejects unknown preload identifiers', function () {
    ControllerLoadPlan::make(
        $this->files,
        $this->registry,
        $this->dir,
        preloadControllers: ['missing'],
    );
})->throws(RuntimeException::class, 'Controller [missing] was not found');

it('rejects unknown eager identifiers', function () {
    ControllerLoadPlan::make(
        $this->files,
        $this->registry,
        $this->dir,
        eagerControllers: ['missing'],
    );
})->throws(RuntimeException::class, 'Controller [missing] was not found');

it('includes an explicitly preloaded package controller in a filtered plan', function () {
    $plan = ControllerLoadPlan::make(
        $this->files,
        $this->registry,
        $this->dir,
        includedComDepControllers: [],
        preloadControllers: ['carousel'],
    );

    expect($plan->packageControllers)->toHaveKey('carousel')
        ->and($plan->includedComDepControllers)->toBe(['carousel']);
});

it('allows an eager application override when its package controller is excluded', function () {
    writeController($this->dir, 'carousel_controller.ts');

    $plan = ControllerLoadPlan::make(
        $this->files,
        $this->registry,
        $this->dir,
        includedComDepControllers: [],
        eagerControllers: ['carousel'],
    );

    expect($plan->eagerApplicationControllers)
        ->toHaveKey('carousel')
        ->and($plan->eagerPackageControllers)->toBe([]);
});

it('removes eager controllers from the effective preload selection', function () {
    writeController($this->dir, 'reveal_controller.js');

    $plan = ControllerLoadPlan::make(
        $this->files,
        $this->registry,
        $this->dir,
        preloadControllers: ['reveal', 'modal'],
        eagerControllers: ['reveal'],
    );

    expect($plan->preloadControllers)->toBe(['modal'])
        ->and($plan->eagerControllers)->toBe(['reveal']);
});
