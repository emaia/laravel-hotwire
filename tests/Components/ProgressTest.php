<?php

use Emaia\LaravelHotwire\Components\Progress;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders a server-side progress bar with track and indicator', function () {
    $view = $this->blade('<x-hw::progress value="56" id="upload-progress" />');

    $view->assertSee('<div', false)
        ->assertSee('id="upload-progress"', false)
        ->assertSee('data-slot="progress"', false)
        ->assertSee('role="progressbar"', false)
        ->assertSee('aria-valuemin="0"', false)
        ->assertSee('aria-valuemax="100"', false)
        ->assertSee('aria-valuenow="56"', false)
        ->assertSee('data-value="56"', false)
        ->assertSee('data-max="100"', false)
        ->assertSee('style="--progress-value: 56%;"', false)
        ->assertSee('data-slot="progress-track"', false)
        ->assertSee('data-slot="progress-indicator"', false)
        ->assertDontSee('bg-primary', false)
        ->assertDontSee('h-1', false);
});

it('derives progress percentage from custom max and clamps the filled width', function () {
    $customMax = $this->blade('<x-hw::progress value="3" max="4" />');
    $overflow = $this->blade('<x-hw::progress value="150" max="100" />');

    expect((string) $customMax)
        ->toContain('aria-valuemax="4"')
        ->toContain('aria-valuenow="3"')
        ->toContain('style="--progress-value: 75%;"')
        ->and((string) $overflow)
        ->toContain('aria-valuenow="100"')
        ->toContain('style="--progress-value: 100%;"');
});

it('renders label and value subcomponents with parent progress state', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::progress value="42">
            <x-hw::progress.label>Upload progress</x-hw::progress.label>
            <x-hw::progress.value />
        </x-hw::progress>
    BLADE);

    $view->assertSee('data-slot="progress-label"', false)
        ->assertSeeText('Upload progress')
        ->assertSee('data-slot="progress-value"', false)
        ->assertSeeText('42%')
        ->assertSee('style="--progress-value: 42%;"', false);
});

it('keeps progress value through an intermediate component with generic props', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::progress value="42">
            <x-residual-context-wrapper formatted-percentage="99">
                <x-hw::progress.value />
            </x-residual-context-wrapper>
        </x-hw::progress>
    BLADE);

    $view->assertSeeText('42%')
        ->assertDontSeeText('99%');
});

it('uses the nearest progress across nested owners', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::progress value="25">
            <x-hw::progress.value />
            <x-hw::progress value="75">
                <x-hw::progress.value />
            </x-hw::progress>
            <x-hw::progress.value />
        </x-hw::progress>
    BLADE);

    expect(preg_replace('/\s+/', ' ', $html))->toMatch('/>25%<.*>75%<.*>25%</s');
});

it('requires an owning progress for the value subcomponent', function () {
    expect(fn () => $this->blade('<x-hw::progress.value />'))
        ->toThrow(ViewException::class, 'Progress value without explicit content must be rendered inside a Progress root.');
});

it('renders explicit value content without a Progress owner', function () {
    $view = $this->blade('<x-hw::progress.value>3 of 5</x-hw::progress.value>');

    $view->assertSee('data-slot="progress-value"', false)
        ->assertSeeText('3 of 5');
});

it('explains the Blade slot boundary when a wrapper creates the progress owner', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    expect(fn () => $this->blade(<<<'BLADE'
        <x-progress-owner-wrapper>
            <x-hw::progress.value />
        </x-progress-owner-wrapper>
    BLADE))->toThrow(ViewException::class, 'slot content renders before the view of the wrapper');
});

it('allows composed track and indicator without rendering a duplicate track', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::progress value="25">
            <x-hw::progress.track>
                <x-hw::progress.indicator />
            </x-hw::progress.track>
        </x-hw::progress>
    BLADE);

    expect(substr_count($html, 'data-slot="progress-track"'))->toBe(1)
        ->and(substr_count($html, 'data-slot="progress-indicator"'))->toBe(1)
        ->and($html)->toContain('style="--progress-value: 25%;"');
});

it('protects structural Progress slots from user overrides', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::progress value="25" data-slot="custom-root">
            <x-hw::progress.track data-slot="custom-track">
                <x-hw::progress.indicator />
            </x-hw::progress.track>
        </x-hw::progress>
    BLADE);

    expect(substr_count($html, 'data-slot="progress"'))->toBe(1)
        ->and(substr_count($html, 'data-slot="progress-track"'))->toBe(1)
        ->and($html)->not->toContain('custom-root')
        ->not->toContain('custom-track');
});

it('allows a raw composed track without rendering a duplicate track', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::progress value="25">
            <div data-slot="progress-track"><div data-slot="progress-indicator"></div></div>
        </x-hw::progress>
    BLADE);

    expect(substr_count($html, 'data-slot="progress-track"'))->toBe(1);
});

it('recognizes a raw track when an earlier attribute contains a greater-than sign', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::progress value="25">
            <div x-show="count > 0" data-slot="progress-track"><div data-slot="progress-indicator"></div></div>
        </x-hw::progress>
    BLADE);

    expect(substr_count($html, 'data-slot="progress-track"'))->toBe(1);
});

it('ignores track signatures inside inert text containers', function (string $container) {
    $html = (string) $this->blade(<<<BLADE
        <x-hw::progress value="25">
            <{$container}><div data-slot="progress-track"></div></{$container}>
        </x-hw::progress>
    BLADE);

    expect(substr_count($html, 'data-slot="progress-track"'))->toBe(2)
        ->and(substr_count($html, 'data-slot="progress-indicator"'))->toBe(1);
})->with([
    'iframe',
    'noembed',
    'noframes',
    'noscript',
    'plaintext',
    'script',
    'style',
    'template',
    'textarea',
    'title',
    'xmp',
]);

it('does not let a nested progress track suppress the outer default track', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::progress value="25">
            <x-hw::progress value="75" />
        </x-hw::progress>
    BLADE);

    expect(substr_count($html, 'data-slot="progress-track"'))->toBe(2);
});

it('adopts a package track passed through a wrapper into a nested Progress', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::progress value="25">
            <x-progress-owner-wrapper>
                <x-hw::progress.track><x-hw::progress.indicator /></x-hw::progress.track>
            </x-progress-owner-wrapper>
        </x-hw::progress>
    BLADE);

    expect(substr_count($html, 'data-slot="progress-track"'))->toBe(2)
        ->and(substr_count($html, 'data-slot="progress-indicator"'))->toBe(2)
        ->and($html)->not->toContain('data-progress-owner');
});

it('does not let a nested raw progress track suppress the outer default track', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::progress value="25">
            <x-hw::progress value="75">
                <div data-slot="progress-track"><div data-slot="progress-indicator"></div></div>
            </x-hw::progress>
        </x-hw::progress>
    BLADE);

    expect(substr_count($html, 'data-slot="progress-track"'))->toBe(2);
});

it('does not expose progress root props as generic component data', function () {
    $progress = new Progress(value: 3, max: 4);
    $data = $progress->data();

    expect($data['progressRoot'])->toBe($progress)
        ->and($data['progressPercentage'])->toBe('75')
        ->and($data)->not->toHaveKeys([
            'value',
            'max',
            'formattedValue',
            'formattedMax',
            'formattedPercentage',
        ]);
});

it('registers progress in the component catalog and subcomponent aliases', function () {
    $progress = HotwireRegistry::make()->component('progress');

    expect($progress->key)->toBe('progress')
        ->and($progress->controllers)->toBe([])
        ->and($progress->docs)->toBe('docs/components/progress.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('progress.track')
        ->toHaveKey('progress.indicator')
        ->toHaveKey('progress.label')
        ->toHaveKey('progress.value');
});
