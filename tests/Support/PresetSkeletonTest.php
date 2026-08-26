<?php

use Emaia\LaravelHotwire\Support\PresetSkeleton;

it('preserves semantic layers and identical selectors in distinct layers', function () {
    $lines = (new PresetSkeleton)->render([<<<'CSS'
        @layer components.nova.base, components.nova.variant, components.nova.state;
        @layer components.nova.base {
            [data-slot="button"] { color: black; }
        }
        @layer components.nova.state {
            [data-slot="button"] { transition: color 150ms; }
        }
        CSS], ['Button' => ['button']]);
    $css = implode("\n", $lines);

    expect($css)
        ->toContain('@layer nova.base, nova.variant, nova.state;')
        ->toContain('@layer nova.base {')
        ->toContain('@layer nova.state {')
        ->and(substr_count($css, '[data-slot="button"] {}'))->toBe(2)
        ->and(strpos($css, '@layer nova.base, nova.variant, nova.state;'))
        ->toBeLessThan(strpos($css, '/* Button */'));
});

it('keeps conditional wrappers nested inside semantic layers', function () {
    $css = implode("\n", (new PresetSkeleton)->render([<<<'CSS'
        @layer components.nova.state {
            @media (hover: hover) {
                [data-slot="button"]:hover { color: black; }
            }
        }
        CSS], ['Button' => ['button']]));

    expect($css)
        ->toContain('@layer nova.state {')
        ->toContain('@media (hover: hover) {')
        ->toContain('[data-slot="button"]:hover {}');
});
