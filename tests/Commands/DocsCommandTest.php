<?php

use Emaia\LaravelHotwire\Registry\Category;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\DocSearchIndex;

function docsListRows(bool $includeControllers, bool $includeComponents): array
{
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), $includeControllers, $includeComponents, 'hw');
    $categoryOrder = array_flip(array_map(fn (Category $category) => $category->value, Category::cases()));

    usort($entries, function (array $a, array $b) use ($categoryOrder): int {
        return [$categoryOrder[$a['category']], $a['type'], $a['type'] === 'component' ? $a['tags'][0] : $a['key']]
            <=> [$categoryOrder[$b['category']], $b['type'], $b['type'] === 'component' ? $b['tags'][0] : $b['key']];
    });

    return array_map(fn (array $entry) => [
        ucfirst($entry['type']),
        $entry['type'] === 'component' ? implode(', ', $entry['tags']) : $entry['key'],
        $entry['category'],
        $entry['description'],
    ], $entries);
}

// --- Lookup by name ---

it('displays docs for a top-level controller', function () {
    $this->artisan('hotwire:docs auto-submit')
        ->expectsOutputToContain('Type: controller')
        ->expectsOutputToContain('Category: forms')
        ->expectsOutputToContain('Identifier: auto-submit')
        ->expectsOutputToContain('Auto Submit')
        ->assertSuccessful();
});

it('displays docs for a substrate controller using slash notation', function () {
    $this->artisan('hotwire:docs turbo/progress')
        ->expectsOutputToContain('Progress')
        ->assertSuccessful();
});

it('displays docs for a component', function () {
    $this->artisan('hotwire:docs toast --component')
        ->expectsOutputToContain('Type: component')
        ->expectsOutputToContain('Blade: <x-hw::toast>')
        ->doesntExpectOutputToContain('Blade: <x-hw::toast>, <x-hw::toast>')
        ->expectsOutputToContain('Controllers: toast')
        ->expectsOutputToContain('Toast')
        ->assertSuccessful();
});

it('displays the permanent hw alias with a configured component prefix', function () {
    config()->set('hotwire.prefix', 'ui');

    $this->artisan('hotwire:docs toast --component')
        ->expectsOutputToContain('Blade: <x-ui::toast>, <x-hw::toast>')
        ->assertSuccessful();
});

it('fails with an error for an unknown name', function () {
    $this->artisan('hotwire:docs nonexistent')
        ->expectsOutputToContain('not found')
        ->assertFailed();
});

// --- Flag filtering in name lookup ---

it('does not find component-only names when --controller is given', function () {
    $this->artisan('hotwire:docs badge --controller')
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

it('fails with a clear error when --pager and --no-pager are both given', function () {
    $this->artisan('hotwire:docs modal --pager --no-pager')
        ->expectsOutputToContain('mutually exclusive')
        ->assertFailed();
});

// --- No argument ---

it('fails with an error when no argument is given in non-interactive mode', function () {
    $this->artisan('hotwire:docs --no-interaction')
        ->expectsOutputToContain('interactive mode')
        ->assertFailed();
});

// --- List mode ---

it('lists both controllers and components with --list', function () {
    $this->artisan('hotwire:docs --list')
        ->expectsTable(['Type', 'Name', 'Category', 'Description'], docsListRows(true, true))
        ->assertSuccessful();
});

it('lists only controllers with --list --controller', function () {
    $this->artisan('hotwire:docs --list --controller')
        ->expectsTable(['Type', 'Name', 'Category', 'Description'], docsListRows(true, false))
        ->assertSuccessful();
});

it('lists only components with --list --component', function () {
    $this->artisan('hotwire:docs --list --component')
        ->expectsTable(['Type', 'Name', 'Category', 'Description'], docsListRows(false, true))
        ->assertSuccessful();
});

it('lists configured and permanent component aliases', function () {
    config()->set('hotwire.prefix', 'ui');

    $this->artisan('hotwire:docs --list --component')
        ->expectsOutputToContain('<x-ui::modal>, <x-hw::modal>')
        ->assertSuccessful();
});

it('fails when name is combined with --list', function () {
    $this->artisan('hotwire:docs modal --list')
        ->expectsOutputToContain('cannot be used together with --list')
        ->assertFailed();
});

// --- DocSearchIndex unit tests ---

it('includes both controllers and components when no filter is applied', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, true, 'hw');

    $labels = array_column($entries, 'label');
    $allLabels = implode("\n", $labels);

    expect($allLabels)->toContain('<x-hw::')   // at least one component
        ->and($allLabels)->not->toContain('<x-hw::auto-submit'); // auto-submit is a controller, not a component
});

it('excludes components when includeComponents is false', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, false, 'hw');

    $labels = implode("\n", array_column($entries, 'label'));

    expect($labels)->not->toContain('<x-hw::');
});

it('excludes controllers when includeControllers is false', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), false, true, 'hw');

    $labels = implode("\n", array_column($entries, 'label'));

    // Component labels start with <x-hw:: ; controller labels do not
    expect($labels)->toContain('<x-hw::')
        ->and($labels)->not->toMatch('/^auto-submit/m')
        ->and($labels)->not->toMatch('/^modal\s/m');
});

it('keeps picker labels aligned to the configured component prefix', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, true, 'ui');
    $alertDialog = collect($entries)->first(
        fn (array $entry): bool => $entry['type'] === 'component' && $entry['key'] === 'alert-dialog'
    );
    $categoryOffsets = collect($entries)
        ->map(fn (array $entry): int|false => strpos($entry['label'], "[{$entry['category']}]"))
        ->unique()
        ->values()
        ->all();

    expect($alertDialog['label'])->toContain('<x-ui::alert-dialog>')
        ->not->toContain('<x-hw::alert-dialog>')
        ->and($alertDialog['tags'])->toBe(['<x-ui::alert-dialog>', '<x-hw::alert-dialog>'])
        ->and($categoryOffsets)->toHaveCount(1);
});

it('keeps component aliases out of search metadata', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), false, true, 'ui');
    $modal = collect($entries)->firstWhere('key', 'modal');
    $searchTerms = explode(' ', $modal['search']);

    expect($searchTerms)->not->toContain('ui')
        ->not->toContain('hw')
        ->and($modal['search'])->not->toContain('<x-')
        ->and($modal['search'])->not->toContain('::');
});

it('includes category and description in the search index', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, false, 'hw');

    $autoSubmit = collect($entries)->first(fn ($e) => str_contains($e['search'], 'auto-submit'));

    expect($autoSubmit['search'])->toContain('forms')
        ->and($autoSubmit['search'])->toContain('debounce');
});
