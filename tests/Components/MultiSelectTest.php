<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareMultiSelectErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
    session()->forget('_old_input');
});

it('renders the multi-select controller, trigger, content and native select', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\', \'paused\' => \'Paused\']" />');

    $view->assertSee('data-slot="multi-select"', false);
    $view->assertSee('data-controller="multi-select"', false);
    $view->assertSee('data-slot="multi-select-native"', false);
    $view->assertSee('name="status[]"', false);
    $view->assertSee('multiple', false);
    $view->assertSee('hidden', false);
    $view->assertSee('data-slot="multi-select-trigger"', false);
    $view->assertSee('data-multi-select-target="trigger"', false);
    $view->assertSee('data-slot="multi-select-content"', false);
    $view->assertSee('data-multi-select-target="content"', false);
    $view->assertSee('role="listbox"', false);
    $view->assertSee('aria-multiselectable="true"', false);
    $view->assertSee('data-slot="multi-select-empty"', false);
    $view->assertSee('No options found.', false);
});

it('allows custom empty text', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" empty-text="Nothing matches." :options="[\'active\' => \'Active\']" />');

    $view->assertSee('Nothing matches.', false);
    $view->assertDontSee('No options found.', false);
});

it('normalizes names for array submission', function () {
    $view = $this->blade('<x-hw::multi-select name="status" :options="[\'active\' => \'Active\']" />');

    $view->assertSee('name="status[]"', false);
    $view->assertSee('id="status"', false);
    $view->assertSee('aria-controls="status-content"', false);
    $view->assertSee('aria-describedby="status-error"', false);
});

it('renders options and selected state from the selected prop', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\', \'paused\' => \'Paused\']" :selected="[\'paused\']" />');

    $html = (string) $view;

    expect($html)->toContain('value="paused" selected');
    $view->assertSee('data-value="paused"', false);
    $view->assertSee('aria-selected="true"', false);
    $view->assertSee('data-selected="true"', false);
    $view->assertSee('1 selected');
});

it('merges selected values with old input', function () {
    session()->put('_old_input', ['status' => ['active']]);

    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\', \'paused\' => \'Paused\']" :selected="[\'paused\']" />');
    $html = (string) $view;

    expect($html)->toContain('value="active" selected')
        ->and($html)->not->toContain('value="paused" selected');
});

it('emits search, select-all, max and positioning values', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\']" :max="2" select-all side="right" align="end" :side-offset="8" :align-offset="-2" strategy="fixed" :flip="false" :shift="false" />');

    $view->assertSee('data-multi-select-search-value="true"', false);
    $view->assertSee('type="text"', false);
    $view->assertSee('data-slot="multi-select-search"', false);
    $view->assertSee('data-controller="clear-input"', false);
    $view->assertSee('data-clear-input-target="input"', false);
    $view->assertSee('data-clear-input-target="clearButton"', false);
    $view->assertSee('data-multi-select-select-all-value="true"', false);
    $view->assertSee('data-multi-select-max-value="2"', false);
    $view->assertSee('data-multi-select-list-all-limit-value="3"', false);
    $view->assertSee('data-multi-select-list-all-more-text-value="+:count more"', false);
    $view->assertSee('data-multi-select-sort-selected-value="false"', false);
    $view->assertSee('data-multi-select-side-value="right"', false);
    $view->assertSee('data-multi-select-align-value="end"', false);
    $view->assertSee('data-multi-select-side-offset-value="8"', false);
    $view->assertSee('data-multi-select-align-offset-value="-2"', false);
    $view->assertSee('data-multi-select-strategy-value="fixed"', false);
    $view->assertSee('data-multi-select-flip-value="false"', false);
    $view->assertSee('data-multi-select-shift-value="false"', false);
    $view->assertSee('data-slot="multi-select-select-all"', false);
    $view->assertSee('aria-pressed="false"', false);
});

it('keeps the select-all action and empty state outside the listbox semantics', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" select-all :options="[\'active\' => \'Active\']" />');
    $html = (string) $view;

    preg_match('/<button[^>]*data-slot="multi-select-select-all"[^>]*>/i', $html, $selectAllMatches);

    expect($selectAllMatches[0] ?? '')
        ->toContain('aria-pressed="false"')
        ->not->toContain('role="option"')
        ->not->toContain('aria-selected');

    $dom = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $xpath = new DOMXPath($dom);
    $listbox = $xpath->query('//*[@role="listbox"]')->item(0);

    expect($listbox)->not->toBeNull()
        ->and($xpath->query('.//*[@data-slot="multi-select-select-all"]', $listbox))->toHaveCount(0)
        ->and($xpath->query('.//*[@data-slot="multi-select-empty"]', $listbox))->toHaveCount(0);
});

it('caps long list-all summaries while keeping the full labels in the title', function () {
    $view = $this->blade('<x-hw::multi-select name="countries[]" list-all :options="[\'AR\' => \'Argentina\', \'AU\' => \'Australia\', \'AT\' => \'Austria\', \'BR\' => \'Brazil\']" :selected="[\'AR\', \'AU\', \'AT\', \'BR\']" />');

    $view->assertSee('Argentina, Australia, Austria, +1 more', false);
    $view->assertSee('title="Argentina, Australia, Austria, Brazil"', false);
});

it('allows customizing the list-all hidden count text', function () {
    $view = $this->blade('<x-hw::multi-select name="countries[]" list-all list-all-more-text="+:count itens" :options="[\'AR\' => \'Argentina\', \'AU\' => \'Australia\', \'AT\' => \'Austria\', \'BR\' => \'Brazil\']" :selected="[\'AR\', \'AU\', \'AT\', \'BR\']" />');

    $view->assertSee('Argentina, Australia, Austria, +1 itens', false);
    $view->assertSee('data-multi-select-list-all-more-text-value="+:count itens"', false);
});

it('can list every selected label when the list-all limit is disabled', function () {
    $view = $this->blade('<x-hw::multi-select name="countries[]" list-all :list-all-limit="0" :options="[\'AR\' => \'Argentina\', \'AU\' => \'Australia\', \'AT\' => \'Austria\', \'BR\' => \'Brazil\']" :selected="[\'AR\', \'AU\', \'AT\', \'BR\']" />');

    $view->assertSee('Argentina, Australia, Austria, Brazil', false);
    $view->assertDontSee('+1 more', false);
});

it('emits sort-selected configuration', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\']" sort-selected />');

    $view->assertSee('data-multi-select-sort-selected-value="true"', false);
});

it('uses fixed positioning by default so panels can escape clipped containers', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\']" />');

    $view->assertSee('data-multi-select-strategy-value="fixed"', false);
});

it('keeps the internal clearable search input out of form submission and validation', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\']" required />');

    $html = (string) $view;
    preg_match('/<input[^>]*data-slot="multi-select-search"[^>]*>/i', $html, $matches);

    expect($matches[0] ?? '')
        ->toContain('type="text"')
        ->toContain('data-multi-select-target="search"')
        ->not->toContain('name=')
        ->not->toContain('required');
});

it('omits search and select all controls when disabled', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\']" :search="false" :select-all="false" />');

    $view->assertSee('data-multi-select-search-value="false"', false);
    $view->assertSee('data-multi-select-select-all-value="false"', false);
    $view->assertDontSee('data-slot="multi-select-search"', false);
    $view->assertDontSee('data-slot="multi-select-select-all"', false);
});

it('sets invalid and required state from Laravel errors and required attribute', function () {
    shareMultiSelectErrors(['status' => ['Required']]);

    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\']" required />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
    $view->assertSee('data-multi-select-required-value="true"', false);
    $view->assertSee('data-slot="multi-select-validation"', false);
    $view->assertSee('required', false);
});

it('overrides content width and accepts extra content classes', function () {
    $view = $this->blade('<x-hw::multi-select name="status[]" :options="[\'active\' => \'Active\']" width="w-72" content-class="text-sm" />');

    $view->assertSee('w-72', false);
    $view->assertSee('text-sm', false);
});

it('registers multi-select in the catalog with Floating UI dependency', function () {
    $multiSelect = HotwireRegistry::make()->component('multi-select');

    expect($multiSelect->docs)->toBe('docs/components/multi-select.md')
        ->and($multiSelect->controllers)->toBe(['multi-select', 'clear-input'])
        ->and(HotwireRegistry::make()->controller('multi-select')->npm)
        ->toHaveKey('@floating-ui/dom', '^1.8.0');
});
