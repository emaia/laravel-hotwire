<?php

use Emaia\LaravelHotwire\Support\PresetAxes;
use Illuminate\Support\Facades\File;

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

// --- Format independence ---

it('reads rules regardless of how the CSS is formatted', function () {
    $extractor = new PresetAxes;
    $flat = $extractor->extract(
        '[data-slot="badge"][data-variant="outline"] { @apply border data-[size=sm]:text-xs; }'
    );

    $expanded = $extractor->extract(<<<'CSS'
        [data-slot="badge"][data-variant="outline"] {
            @apply border data-[size=sm]:text-xs;
        }
        CSS);

    $nested = $extractor->extract(<<<'CSS'
        .style-brand {
            /* a comment mentioning data-[variant=fake] */
            [data-slot='badge'][data-variant='outline'] {
                @apply border data-[size=sm]:text-xs;
            }
        }
        CSS);

    expect($flat)->toBe(['badge' => ['variant' => ['outline'], 'size' => ['sm']]])
        ->and($expanded)->toBe($flat)
        ->and($nested)->toBe($flat);
});

it('reads a selector split across lines', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="badge"],
        [data-slot="chip"] {
            @apply data-[variant=ghost]:opacity-50;
        }
        CSS);

    expect($axes)->toBe([
        'badge' => ['variant' => ['ghost']],
        'chip' => ['variant' => ['ghost']],
    ]);
});

it('never reads an axis out of a comment', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="badge"] { @apply border; } /* data-[variant=fake] */
        /* [data-slot="ghost"][data-variant="fake"] */
        CSS);

    expect($axes)->toBe([]);
});

it('keeps arbitrary variants that describe another element out', function () {
    // Unquoted attributes only ever appear inside arbitrary variants, which target descendants.
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="card"] { @apply [&>[data-slot=card-footer]]:pt-0 has-[[data-active=true]]:ring-2; }
        CSS);

    expect($axes)->toBe([]);
});

it('survives strings that contain the other quote character', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="checkbox"] { mask: url("data:image/svg+xml,%3csvg fill='none' %3e%3c/svg%3e"); }
        [data-slot="checkbox"][data-checkable="true"] { @apply border; }
        CSS);

    expect($axes)->toBe(['checkbox' => ['checkable' => ['true']]]);
});

it('inherits the subject when a nested rule has no slot of its own', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="badge"] {
            &[data-variant="ghost"] { @apply opacity-50; }
        }
        CSS);

    expect($axes)->toBe(['badge' => ['variant' => ['ghost']]]);
});

it('reports how much of the stylesheet it managed to read', function () {
    $extractor = new PresetAxes;

    expect($extractor->coverage('[data-slot="badge"] { @apply border; }'))
        ->toBe(['visited' => 1, 'total' => 1])
        // An unterminated block is never emitted as a rule, so its slot goes unaccounted for.
        ->and($extractor->coverage('[data-slot="a"] { @apply border; } [data-slot="b"] { @apply border;'))
        ->toBe(['visited' => 1, 'total' => 2]);
});

it('reads every slot occurrence of every shipped preset', function () {
    $extractor = new PresetAxes;

    foreach (glob(__DIR__.'/../../resources/css/presets/*.css') ?: [] as $path) {
        $coverage = $extractor->coverage(File::get($path));

        expect($coverage['total'])->toBeGreaterThan(0)
            ->and($coverage['visited'])->toBe($coverage['total'], basename($path).' has rules the scanner cannot read.');
    }
});

it('ignores statements that sit outside any rule', function () {
    $extractor = new PresetAxes;
    $css = <<<'CSS'
        @import "tokens.css";
        @source inline("hidden");
        [data-slot="badge"][data-variant="outline"] { @apply border; }
        CSS;

    expect($extractor->extract($css))->toBe(['badge' => ['variant' => ['outline']]])
        ->and($extractor->coverage($css))->toBe(['visited' => 1, 'total' => 1]);
});

it('reads a quoted value without the quotes', function () {
    $extractor = new PresetAxes;

    expect($extractor->extract('[data-slot="badge"] { @apply data-[variant=\'ghost\']:opacity-50; }'))
        ->toBe(['badge' => ['variant' => ['ghost']]])
        ->and($extractor->extract('[data-slot="badge"] { @apply data-[variant=ghost]:opacity-50; }'))
        ->toBe(['badge' => ['variant' => ['ghost']]]);
});
