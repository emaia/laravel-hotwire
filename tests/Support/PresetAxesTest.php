<?php

use Emaia\LaravelHotwire\Support\PresetAxes;

it('reads the values a slot varies by', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="badge"][data-variant="destructive"] { color: red; }
        [data-slot="badge"][data-variant="outline"] { color: blue; }
        [data-slot="badge"] { color: black; }
        CSS);

    expect($axes)->toBe(['badge' => ['variant' => ['destructive', 'outline']]]);
});

it('attributes a value to every slot of an :is group', function () {
    $axes = (new PresetAxes)->extract(
        ':is([data-slot="button"], [data-slot="modal-trigger"])[data-size="sm"] { height: 1rem; }'
    );

    expect($axes)->toBe([
        'button' => ['size' => ['sm']],
        'modal-trigger' => ['size' => ['sm']],
    ]);
});

it('keeps a value on its own compound, not on a descendant', function () {
    $axes = (new PresetAxes)->extract(
        '[data-slot="sidebar"][data-variant="floating"] [data-slot="sidebar-container"] { padding: 0; }'
    );

    expect($axes)->toBe(['sidebar' => ['variant' => ['floating']]]);
});

it('collects every axis, not only variant and size', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="attachment"][data-orientation="vertical"] { flex-direction: column; }
        [data-slot="attachment"][data-state="error"] { color: red; }
        CSS);

    expect($axes['attachment'])->toBe([
        'orientation' => ['vertical'],
        'state' => ['error'],
    ]);
});

it('ignores selectors without a slot and never treats slot as an axis', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-variant="ghost"] { color: gray; }
        [data-slot="badge"] { color: black; }
        CSS);

    expect($axes)->toBe([]);
});

it('reads the shipped Nova preset', function () {
    $axes = (new PresetAxes)->extract(File::get(__DIR__.'/../../resources/css/presets/nova.css'));

    expect($axes['badge']['variant'])->toContain('destructive')
        ->and($axes['attachment']['orientation'])->toContain('vertical')
        ->and($axes['modal-positioner']['size'])->toContain('full')
        ->and($axes)->not->toHaveKey('slot');
});
