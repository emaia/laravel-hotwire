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

it('reads axes written as Tailwind data variants inside the rule', function () {
    $axes = (new PresetAxes)->extract(
        '[data-slot="field-legend"] { @apply mb-1.5 data-[variant=label]:text-sm data-[variant=legend]:text-base; }'
    );

    expect($axes)->toBe(['field-legend' => ['variant' => ['label', 'legend']]]);
});

it('ignores data variants that describe another element', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="button"] { @apply has-data-[icon=inline-end]:pr-2 group-data-[state=open]:rotate-180; }
        [data-slot="item"] { @apply peer-data-[selected=true]:font-medium **:data-[slot=icon]:size-4; }
        CSS);

    expect($axes)->toBe([]);
});

it('merges axes from the selector and from the rule body', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="field"][data-orientation="horizontal"] { @apply flex; }
        [data-slot="field"] { @apply data-[orientation=responsive]:grid; }
        CSS);

    expect($axes['field']['orientation'])->toBe(['horizontal', 'responsive']);
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
