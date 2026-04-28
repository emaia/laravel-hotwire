<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\DocSearchIndex;

// --- Lookup by name ---

it('displays docs for a top-level controller', function () {
    $this->artisan('hotwire:docs auto-submit')
        ->expectsOutputToContain('Auto Submit')
        ->assertSuccessful();
});

it('displays docs for a substrate controller using slash notation', function () {
    $this->artisan('hotwire:docs turbo/progress')
        ->expectsOutputToContain('Progress')
        ->assertSuccessful();
});

it('displays docs for a component', function () {
    $this->artisan('hotwire:docs flash-message --component')
        ->expectsOutputToContain('Flash Message')
        ->assertSuccessful();
});

it('fails with an error for an unknown name', function () {
    $this->artisan('hotwire:docs nonexistent')
        ->expectsOutputToContain('not found')
        ->assertFailed();
});

// --- Flag filtering in name lookup ---

it('does not find component-only names when --controller is given', function () {
    $this->artisan('hotwire:docs flash-container --controller')
        ->expectsOutputToContain('not found')
        ->assertFailed();
});

it('does not find controller-only names when --component is given', function () {
    $this->artisan('hotwire:docs auto-submit --component')
        ->expectsOutputToContain('not found')
        ->assertFailed();
});

// --- Ambiguity ---

it('prompts when name exists in both controllers and components', function () {
    $this->artisan('hotwire:docs modal')
        ->expectsChoice(
            'Found in both controllers and components. Which would you like to view?',
            'controller',
            ['controller', 'component'],
        )
        ->assertSuccessful();
});

it('shows controller docs directly with --controller when name is ambiguous', function () {
    $this->artisan('hotwire:docs modal --controller')
        ->expectsOutputToContain('Modal')
        ->assertSuccessful();
});

it('shows component docs directly with --component when name is ambiguous', function () {
    $this->artisan('hotwire:docs modal --component')
        ->expectsOutputToContain('Modal')
        ->assertSuccessful();
});

// --- Mutually exclusive flags ---

it('fails with a clear error when --controller and --component are both given', function () {
    $this->artisan('hotwire:docs --controller --component')
        ->expectsOutputToContain('mutually exclusive')
        ->assertFailed();
});

// --- No argument ---

it('fails with an error when no argument is given in non-interactive mode', function () {
    $this->artisan('hotwire:docs --no-interaction')
        ->expectsOutputToContain('interactive mode')
        ->assertFailed();
});

// --- DocSearchIndex unit tests ---

it('includes both controllers and components when no filter is applied', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, true, 'hwc');

    $labels = array_column($entries, 'label');
    $allLabels = implode("\n", $labels);

    expect($allLabels)->toContain('<x-hwc::')   // at least one component
        ->and($allLabels)->not->toContain('<x-hwc::auto-submit'); // auto-submit is a controller, not a component
});

it('excludes components when includeComponents is false', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, false, 'hwc');

    $labels = implode("\n", array_column($entries, 'label'));

    expect($labels)->not->toContain('<x-hwc::');
});

it('excludes controllers when includeControllers is false', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), false, true, 'hwc');

    $labels = implode("\n", array_column($entries, 'label'));

    // Component labels start with <x-hwc:: ; controller labels do not
    expect($labels)->toContain('<x-hwc::')
        ->and($labels)->not->toMatch('/^auto-submit/m')
        ->and($labels)->not->toMatch('/^modal\s/m');
});

it('uses the given prefix in component labels', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), false, true, 'hw');

    $labels = implode("\n", array_column($entries, 'label'));

    expect($labels)->toContain('<x-hw::modal>');
    expect($labels)->not->toContain('<x-hwc::');
});

it('includes category and description in the search index', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, false, 'hwc');

    $autoSubmit = collect($entries)->first(fn ($e) => str_contains($e['search'], 'auto-submit'));

    expect($autoSubmit['search'])->toContain('forms')
        ->and($autoSubmit['search'])->toContain('debounce');
});
