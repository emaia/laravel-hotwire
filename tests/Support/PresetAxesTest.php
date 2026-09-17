<?php

use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\PresetAxes;

it('reads the values a slot varies by', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="badge"][data-variant="destructive"] { color: red; }
        [data-slot="badge"][data-variant="outline"] { color: blue; }
        [data-slot="badge"] { color: black; }
        CSS);

    expect($axes)->toBe(['badge' => ['data-variant' => ['destructive', 'outline']]]);
});

it('attributes a value to every slot of an :is group', function () {
    $axes = (new PresetAxes)->extract(
        ':is([data-slot="button"], [data-slot="modal-trigger"])[data-size="sm"] { height: 1rem; }'
    );

    expect($axes)->toBe([
        'button' => ['data-size' => ['sm']],
        'modal-trigger' => ['data-size' => ['sm']],
    ]);
});

it('reads attributes written inside an :is group member', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-theme="dark"] :is([data-slot="input"][type="date"], [data-slot="input"][type="week"]) { color-scheme: dark; }
        :is([data-slot="button"], [data-slot="chip"])[data-size="sm"] { @apply h-6; }
        CSS);

    expect($axes)->toBe([
        'input' => ['type' => ['date', 'week']],
        'button' => ['data-size' => ['sm']],
        'chip' => ['data-size' => ['sm']],
    ]);
});

it('reads a descendant chain wrapped in :where', function () {
    $axes = (new PresetAxes)->extract(
        ':where([data-slot="file-upload"][data-density="compact"] [data-slot="file-upload-dropzone"]) { @apply data-[view=image]:p-0; }'
    );

    expect($axes)->toBe([
        'file-upload' => ['data-density' => ['compact']],
        'file-upload-dropzone' => ['data-view' => ['image']],
    ]);
});

it('keeps a value on its own compound, not on a descendant', function () {
    $axes = (new PresetAxes)->extract(
        '[data-slot="sidebar"][data-variant="floating"] [data-slot="sidebar-container"] { padding: 0; }'
    );

    expect($axes)->toBe(['sidebar' => ['data-variant' => ['floating']]]);
});

it('keeps a value on its own compound across a column combinator', function () {
    $axes = (new PresetAxes)->extract(
        '[data-slot="table-column"]||[data-slot="table-cell"] { @apply data-[state=selected]:font-bold; }'
    );

    expect($axes)->toBe(['table-cell' => ['data-state' => ['selected']]]);
});

it('collects every axis, not only variant and size', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="attachment"][data-orientation="vertical"] { flex-direction: column; }
        [data-slot="attachment"][data-state="error"] { color: red; }
        CSS);

    expect($axes['attachment'])->toBe([
        'data-orientation' => ['vertical'],
        'data-state' => ['error'],
    ]);
});

it('reads axes written as Tailwind data variants inside the rule', function () {
    $axes = (new PresetAxes)->extract(
        '[data-slot="field-legend"] { @apply mb-1.5 data-[variant=label]:text-sm data-[variant=legend]:text-base; }'
    );

    expect($axes)->toBe(['field-legend' => ['data-variant' => ['label', 'legend']]]);
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

    expect($axes['field']['data-orientation'])->toBe(['horizontal', 'responsive']);
});

it('ignores selectors without a slot and never treats slot as an axis', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-variant="ghost"] { color: gray; }
        [data-slot="badge"] { color: black; }
        CSS);

    expect($axes)->toBe([]);
});

it('reads the shipped Nova preset', function () {
    $axes = (new PresetAxes)->extract(app(CssPresetFiles::class)->source('nova')->visualCss());

    expect($axes['badge']['data-variant'])->toContain('destructive')
        ->and($axes['attachment']['data-orientation'])->toContain('vertical')
        ->and($axes['modal-positioner']['data-size'])->toContain('full')
        ->and($axes)->not->toHaveKey('slot');
});

// --- Attributes outside the data namespace ---

it('reads native and aria attributes from the selector', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="accordion-item"][aria-disabled="true"] > [data-slot="accordion-trigger"] { @apply opacity-50; }
        [data-slot="input"][type="date"] { @apply pr-2; }
        [data-slot="checkbox-indicator"][role="switch"] { @apply rounded-full; }
        CSS);

    expect($axes)->toBe([
        'accordion-item' => ['aria-disabled' => ['true']],
        'input' => ['type' => ['date']],
        'checkbox-indicator' => ['role' => ['switch']],
    ]);
});

it('records an attribute written without a value', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="accordion-item"][open]::details-content { @apply opacity-100; }
        [data-slot="popover-content"][popover] { @apply m-0; }
        CSS);

    expect($axes)->toBe([
        'accordion-item' => ['open' => []],
        'popover-content' => ['popover' => []],
    ]);
});

it('reads axes written as Tailwind aria variants inside the rule', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="button"] { @apply aria-disabled:opacity-50 aria-expanded:bg-muted; }
        [data-slot="slider"] { @apply aria-[invalid=true]:border-destructive; }
        CSS);

    expect($axes)->toBe([
        'button' => ['aria-disabled' => ['true'], 'aria-expanded' => ['true']],
        'slider' => ['aria-invalid' => ['true']],
    ]);
});

it('ignores aria variants that describe another element or negate a state', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="dropdown-trigger"] { @apply active:not-aria-[haspopup]:translate-y-px; }
        [data-slot="item"] { @apply group-aria-expanded:rotate-180 peer-aria-disabled:opacity-50; }
        CSS);

    expect($axes)->toBe([]);
});

it('leaves pseudo-class states out of the attribute vocabulary', function () {
    $axes = (new PresetAxes)->extract(
        '[data-slot="button"] { @apply hover:bg-muted disabled:opacity-50 focus-visible:ring-3 checked:bg-primary; }'
    );

    expect($axes)->toBe([]);
});

it('ignores attribute selectors that match on an operator rather than a value', function () {
    $axes = (new PresetAxes)->extract(
        '[data-slot="button"] { @apply [&_svg:not([class*=\'size-\'])]:size-4; }'
    );

    expect($axes)->toBe([]);
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

    expect($flat)->toBe(['badge' => ['data-variant' => ['outline'], 'data-size' => ['sm']]])
        ->and($expanded)->toBe($flat)
        ->and($nested)->toBe($flat);
});

it('reads a rule wrapped in @supports and @media', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        @supports selector(::details-content) {
            [data-slot="accordion-item"][open]::details-content {
                block-size: calc-size(auto, size);
            }

            @media (prefers-reduced-motion: reduce) {
                [data-slot="accordion-item"][aria-disabled="true"]::details-content { transition: none; }
            }
        }
        CSS);

    expect($axes)->toBe([
        'accordion-item' => ['open' => [], 'aria-disabled' => ['true']],
    ]);
});

it('reads axes and coverage from @scope preludes', function () {
    $extractor = new PresetAxes;
    $css = <<<'CSS'
        @scope ([data-slot="sidebar"][data-collapsible="icon"]) to ([data-slot="sidebar-wrapper"]) {
            [data-slot="sidebar-menu-button"] { @apply hidden; }
        }
        CSS;

    expect($extractor->extract($css))
        ->toBe(['sidebar' => ['data-collapsible' => ['icon']]])
        ->and($extractor->coverage($css))
        ->toBe(['visited' => 3, 'total' => 3]);
});

it('reads a selector split across lines', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="badge"],
        [data-slot="chip"] {
            @apply data-[variant=ghost]:opacity-50;
        }
        CSS);

    expect($axes)->toBe([
        'badge' => ['data-variant' => ['ghost']],
        'chip' => ['data-variant' => ['ghost']],
    ]);
});

it('never reads an axis out of a comment', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="badge"] { @apply border; } /* data-[variant=fake] */
        /* [data-slot="ghost"][data-variant="fake"] */
        CSS);

    expect($axes)->toBe([]);
});

it('ignores the invalid tail of an open comment', function () {
    $extractor = new PresetAxes;
    $css = <<<'CSS'
        [data-slot="badge"][data-variant="outline"] { @apply border; }
        /* [data-slot="ghost"][data-variant="fake"] { @apply block; }
        CSS;

    expect($extractor->extract($css))->toBe(['badge' => ['data-variant' => ['outline']]])
        ->and($extractor->coverage($css))->toBe(['visited' => 1, 'total' => 1])
        ->and($extractor->unvisitedSlots($css))->toBe([]);
});

it('keeps arbitrary variants that describe another element out', function () {
    // In shipped presets, unquoted attributes occur only inside arbitrary variants that target descendants.
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

    expect($axes)->toBe(['checkbox' => ['data-checkable' => ['true']]]);
});

it('inherits the subject when a nested rule has no slot of its own', function () {
    $axes = (new PresetAxes)->extract(<<<'CSS'
        [data-slot="badge"] {
            &[data-variant="ghost"] { @apply opacity-50; }
        }
        CSS);

    expect($axes)->toBe(['badge' => ['data-variant' => ['ghost']]]);
});

it('reports how much of the stylesheet it managed to read', function () {
    $extractor = new PresetAxes;

    expect($extractor->coverage('[data-slot="badge"] { @apply border; }'))
        ->toBe(['visited' => 1, 'total' => 1])
        ->and($extractor->unvisitedSlots('[data-slot="badge"] { @apply border; }'))
        ->toBe([])
        // An unterminated block is never emitted as a rule, so its slot goes unaccounted for.
        ->and($extractor->coverage('[data-slot="a"] { @apply border; } [data-slot="b"] { @apply border;'))
        ->toBe(['visited' => 1, 'total' => 2])
        ->and($extractor->unvisitedSlots('[data-slot="a"] { @apply border; } [data-slot="b"] { @apply border;'))
        ->toBe(['b']);
});

it('leaves slots in malformed rules unvisited while retaining later valid coverage', function () {
    $extractor = new PresetAxes;
    $css = <<<'CSS'
        [data-slot="invalid"] { ); }
        [data-slot="valid"] { color: red; }
        CSS;

    expect($extractor->coverage($css))->toBe(['visited' => 1, 'total' => 2])
        ->and($extractor->unvisitedSlots($css))->toBe(['invalid']);
});

it('tracks Tailwind slot variants in parser coverage', function () {
    $extractor = new PresetAxes;
    $malformed = <<<'CSS'
        .card { @apply data-[slot=ghost]:hidden; ); }
        [data-slot="wrapper"] { color: red; }
        CSS;
    $control = <<<'CSS'
        .card { @apply data-[slot=ghost]:hidden; }
        [data-slot="wrapper"] { color: red; }
        CSS;

    expect($extractor->inspectCoverage($malformed))->toBe([
        'visited' => 1,
        'total' => 2,
        'unvisitedSlots' => ['ghost'],
        'unvisitedReferences' => ['ghost'],
        'invalidScopeRoots' => [],
        'unprovableScopeSlots' => [],
        'complete' => false,
    ])->and($extractor->inspectCoverage($control))->toBe([
        'visited' => 2,
        'total' => 2,
        'unvisitedSlots' => [],
        'unvisitedReferences' => [],
        'invalidScopeRoots' => [],
        'unprovableScopeSlots' => [],
        'complete' => true,
    ]);
});

it('reports incomplete analysis even when invalid syntax loses no slot mentions', function () {
    expect((new PresetAxes)->inspectCoverage(<<<'CSS'
        .invalid { color: red; ); }
        [data-slot="wrapper"] { color: green; }
        CSS))->toBe([
        'visited' => 1,
        'total' => 1,
        'unvisitedSlots' => [],
        'unvisitedReferences' => [],
        'invalidScopeRoots' => [],
        'unprovableScopeSlots' => [],
        'complete' => false,
    ]);
});

it('distinguishes incomplete slot syntax from complete unvisited references', function () {
    expect((new PresetAxes)->inspectCoverage('[data-slot="ghost" { color: red; }'))->toBe([
        'visited' => 0,
        'total' => 1,
        'unvisitedSlots' => ['ghost'],
        'unvisitedReferences' => [],
        'invalidScopeRoots' => [],
        'unprovableScopeSlots' => [],
        'complete' => false,
    ]);
});

it('ignores slot-like strings in discarded rules', function () {
    expect((new PresetAxes)->inspectCoverage(<<<'CSS'
        .invalid { content: '[data-slot="static"] data-[slot=variant]'; ); }
        [data-slot="wrapper"] { color: red; }
        CSS))->toBe([
        'visited' => 1,
        'total' => 1,
        'unvisitedSlots' => [],
        'unvisitedReferences' => [],
        'invalidScopeRoots' => [],
        'unprovableScopeSlots' => [],
        'complete' => false,
    ]);
});

it('normalizes comments consistently on both sides of coverage', function () {
    expect((new PresetAxes)->inspectCoverage(<<<'CSS'
        .card { @apply data-/**/[slot=ghost]:hidden; }
        CSS))->toBe([
        'visited' => 0,
        'total' => 0,
        'unvisitedSlots' => [],
        'unvisitedReferences' => [],
        'invalidScopeRoots' => [],
        'unprovableScopeSlots' => [],
        'complete' => true,
    ]);
});

it('leaves slots in malformed scope preludes unvisited', function () {
    $extractor = new PresetAxes;
    $css = '@scope ([data-slot="ghost"]]) { color: red; }';

    expect($extractor->coverage($css))->toBe(['visited' => 0, 'total' => 1])
        ->and($extractor->unvisitedSlots($css))->toBe(['ghost']);
});

it('retains a valid scope prelude when a later nested rule is malformed', function () {
    $extractor = new PresetAxes;
    $css = <<<'CSS'
        @scope ([data-slot="root"]) {
            :scope { color: red; }
            .invalid { ); }
        }
        CSS;

    $analysis = $extractor->inspectCoverage($css);

    expect($analysis['visited'])->toBe(1)
        ->and($analysis['total'])->toBe(1)
        ->and($analysis['unvisitedSlots'])->toBe([])
        ->and($analysis['invalidScopeRoots'])->toBe(['[data-slot="root"]'])
        ->and($analysis['unprovableScopeSlots'])->toBe(['root']);
});

it('retains a valid scope prelude after a malformed declaration in its body', function () {
    $extractor = new PresetAxes;
    $css = <<<'CSS'
        @scope ([data-slot="root"]) {
            invalid: );
            :scope { color: red; }
        }
        CSS;

    $analysis = $extractor->inspectCoverage($css);

    expect($analysis['visited'])->toBe(1)
        ->and($analysis['total'])->toBe(1)
        ->and($analysis['unvisitedSlots'])->toBe([])
        ->and($analysis['invalidScopeRoots'])->toBe(['[data-slot="root"]'])
        ->and($analysis['unprovableScopeSlots'])->toBe(['root']);
});

it('reads every slot occurrence of every shipped preset', function () {
    $extractor = new PresetAxes;

    foreach (app(CssPresetFiles::class)->names() as $preset) {
        $coverage = $extractor->coverage(app(CssPresetFiles::class)->source($preset)->visualCss());

        expect($coverage['total'])->toBeGreaterThan(0)
            ->and($coverage['visited'])->toBe($coverage['total'], "Preset [{$preset}] has rules the scanner cannot read.");
    }
});

it('ignores statements that sit outside any rule', function () {
    $extractor = new PresetAxes;
    $css = <<<'CSS'
        @import "tokens.css";
        @source inline("hidden");
        [data-slot="badge"][data-variant="outline"] { @apply border; }
        CSS;

    expect($extractor->extract($css))->toBe(['badge' => ['data-variant' => ['outline']]])
        ->and($extractor->coverage($css))->toBe(['visited' => 1, 'total' => 1]);
});

it('reads a quoted value without the quotes', function () {
    $extractor = new PresetAxes;

    expect($extractor->extract('[data-slot="badge"] { @apply data-[variant=\'ghost\']:opacity-50; }'))
        ->toBe(['badge' => ['data-variant' => ['ghost']]])
        ->and($extractor->extract('[data-slot="badge"] { @apply data-[variant=ghost]:opacity-50; }'))
        ->toBe(['badge' => ['data-variant' => ['ghost']]]);
});
