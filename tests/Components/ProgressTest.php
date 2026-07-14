<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

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
