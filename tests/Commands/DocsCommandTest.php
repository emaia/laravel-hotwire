<?php

use Emaia\LaravelHotwire\Registry\Category;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\DocSearchIndex;

function docsListRows(bool $includeControllers, bool $includeComponents): array
{
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), $includeControllers, $includeComponents, 'hw');
    $categoryOrder = array_flip(array_map(fn (Category $category) => $category->value, Category::cases()));

    usort($entries, function (array $a, array $b) use ($categoryOrder): int {
        return [$categoryOrder[$a['category']], $a['type'], $a['type'] === 'component' ? $a['title'] : $a['key']]
            <=> [$categoryOrder[$b['category']], $b['type'], $b['type'] === 'component' ? $b['title'] : $b['key']];
    });

    return array_map(fn (array $entry) => [
        ucfirst($entry['type']),
        $entry['type'] === 'component' ? $entry['title'] : $entry['key'],
        $entry['type'] === 'component' ? "<hw:{$entry['key']}>" : '—',
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
        ->expectsOutputToContain('Blade: <hw:toast>')
        ->doesntExpectOutputToContain('Blade: <hw:toast>, <hw:toast>')
        ->expectsOutputToContain('Controllers: toast')
        ->expectsOutputToContain('Toast')
        ->assertSuccessful();
});

it('documents the Textarea counter slot without colliding with its counter prop', function () {
    $this->artisan('hotwire:docs textarea --component')
        ->expectsOutputToContain('<x-slot:counter-slot')
        ->doesntExpectOutputToContain('<x-slot:counter>')
        ->assertSuccessful();
});

it('displays the permanent hw alias with a configured component prefix', function () {
    config()->set('hotwire.prefix', 'ui');

    $this->artisan('hotwire:docs toast --component')
        ->expectsOutputToContain('Blade: <ui:toast>, <hw:toast>')
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
        ->expectsTable(['Type', 'Name', 'Blade Tag', 'Category', 'Description'], docsListRows(true, true))
        ->assertSuccessful();
});

it('lists only controllers with --list --controller', function () {
    $this->artisan('hotwire:docs --list --controller')
        ->expectsTable(['Type', 'Name', 'Blade Tag', 'Category', 'Description'], docsListRows(true, false))
        ->assertSuccessful();
});

it('lists only components with --list --component', function () {
    $this->artisan('hotwire:docs --list --component')
        ->expectsTable(['Type', 'Name', 'Blade Tag', 'Category', 'Description'], docsListRows(false, true))
        ->assertSuccessful();
});

it('orders component rows by their displayed name', function () {
    Artisan::call('hotwire:docs --list --component');
    $output = Artisan::output();

    preg_match('/^\|\s*Component\s*\|\s*Button\s*\|/m', $output, $button, PREG_OFFSET_CAPTURE);
    preg_match('/^\|\s*Component\s*\|\s*Button Group\s*\|/m', $output, $buttonGroup, PREG_OFFSET_CAPTURE);
    preg_match('/^\|\s*Component\s*\|\s*Reveal\s*\|/m', $output, $reveal, PREG_OFFSET_CAPTURE);
    preg_match('/^\|\s*Component\s*\|\s*Reveal Item\s*\|/m', $output, $revealItem, PREG_OFFSET_CAPTURE);

    expect($button)->not->toBeEmpty()
        ->and($buttonGroup)->not->toBeEmpty()
        ->and($reveal)->not->toBeEmpty()
        ->and($revealItem)->not->toBeEmpty();

    expect($button[0][1])->toBeLessThan($buttonGroup[0][1])
        ->and($reveal[0][1])->toBeLessThan($revealItem[0][1]);
});

it('lists configured and permanent component aliases', function () {
    config()->set('hotwire.prefix', 'ui');

    $this->artisan('hotwire:docs --list --component')
        ->expectsOutputToContain('<ui:modal>, <hw:modal>')
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

    expect($allLabels)->toContain('<hw:')   // at least one component
        ->and($allLabels)->not->toContain('<hw:auto-submit>'); // auto-submit is a controller, not a component
});

it('excludes components when includeComponents is false', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, false, 'hw');

    $labels = implode("\n", array_column($entries, 'label'));

    expect($labels)->not->toContain('<hw:');
});

it('excludes controllers when includeControllers is false', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), false, true, 'hw');

    $labels = implode("\n", array_column($entries, 'label'));

    // Component labels start with <hw: ; controller labels do not
    expect($labels)->toContain('<hw:')
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

    expect($alertDialog['label'])->toContain('<ui:alert-dialog>')
        ->not->toContain('<hw:alert-dialog>')
        ->and($alertDialog['tags'])->toBe(['<ui:alert-dialog>', '<hw:alert-dialog>'])
        ->and($categoryOffsets)->toHaveCount(1);
});

it('keeps component aliases out of search metadata', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), false, true, 'ui');
    $modal = collect($entries)->firstWhere('key', 'modal');
    $searchTerms = explode(' ', $modal['search']);

    expect($searchTerms)->not->toContain('ui')
        ->not->toContain('hw')
        ->and($modal['search'])->not->toContain('<ui:')
        ->and($modal['search'])->not->toContain('<hw:');
});

it('includes category and description in the search index', function () {
    $entries = (new DocSearchIndex)->build(HotwireRegistry::make(), true, false, 'hw');

    $autoSubmit = collect($entries)->first(fn ($e) => str_contains($e['search'], 'auto-submit'));

    expect($autoSubmit['search'])->toContain('forms')
        ->and($autoSubmit['search'])->toContain('debounce');
});
