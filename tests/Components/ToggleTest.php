<?php

use Emaia\LaravelHotwire\Components\Toggle;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

// --- Basic render ---

it('renders an accessible pressed button', function () {
    $view = $this->blade('<x-hw::toggle :pressed="true" variant="outline" size="sm">Bold</x-hw::toggle>');

    $view->assertSee('<button', false)
        ->assertSee('type="button"', false)
        ->assertSee('data-slot="toggle"', false)
        ->assertSee('data-controller="toggle"', false)
        ->assertSee('data-action="click->toggle#toggle"', false)
        ->assertSee('aria-pressed="true"', false)
        ->assertSee('data-state="on"', false)
        ->assertSee('data-variant="outline"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSeeText('Bold');
});

it('renders the off state by default', function () {
    $view = $this->blade('<x-hw::toggle>Italic</x-hw::toggle>');

    $view->assertSee('aria-pressed="false"', false)
        ->assertSee('data-state="off"', false)
        ->assertSee('data-toggle-pressed-value="false"', false)
        ->assertSee('data-variant="default"', false)
        ->assertSee('data-size="default"', false);
});

it('supports string pressed values', function () {
    $view = $this->blade('<x-hw::toggle pressed="true">Pinned</x-hw::toggle>');

    $view->assertSee('aria-pressed="true"', false)
        ->assertSee('data-state="on"', false);
});

it('honours the type prop', function () {
    $view = $this->blade('<x-hw::toggle type="submit">Apply</x-hw::toggle>');

    $view->assertSee('type="submit"', false);
});

it('emits disabled state for styling and behavior', function () {
    $view = $this->blade('<x-hw::toggle disabled>Locked</x-hw::toggle>');

    $view->assertSee('disabled', false)
        ->assertSee('data-disabled="true"', false);
});

it('adds a named group marker for pressed-state icon styling', function () {
    $view = $this->blade('<x-hw::toggle>Favorite</x-hw::toggle>');

    $view->assertSee('class="group/toggle"', false);
});

it('does not duplicate the group marker when the user provides it', function () {
    $html = (string) $this->blade('<x-hw::toggle class="group/toggle w-full">Favorite</x-hw::toggle>');

    expect(substr_count($html, 'group/toggle'))->toBe(1)
        ->and($html)->toContain('class="group/toggle w-full"');
});

// --- Semantic styling contract ---

it('does not emit package Tailwind classes inline', function () {
    $view = $this->blade('<x-hw::toggle>Bold</x-hw::toggle>');

    $view->assertDontSee('bg-primary', false)
        ->assertDontSee('inline-flex', false)
        ->assertDontSee('focus-visible:ring', false);
});

// --- Hidden input integration ---

it('renders a hidden input when name is provided', function () {
    $html = (string) $this->blade('<x-hw::toggle name="filters[]" value="featured" pressed>Featured</x-hw::toggle>');

    expect($html)->toContain('type="hidden"')
        ->and($html)->toContain('name="filters[]"')
        ->and($html)->toContain('value="featured"')
        ->and($html)->toContain('data-toggle-input')
        ->and($html)->toContain('data-toggle-input-id-value="hw-toggle-input-')
        ->and($html)->not->toContain('name="filters[]" disabled')
        ->and(substr_count($html, 'name="filters[]"'))->toBe(1);
});

it('disables the hidden input while unpressed', function () {
    $html = (string) $this->blade('<x-hw::toggle name="featured" value="1">Featured</x-hw::toggle>');

    expect($html)->toContain('type="hidden"')
        ->and($html)->toContain('name="featured"')
        ->and($html)->toContain('disabled')
        ->and($html)->toContain('aria-pressed="false"');
});

it('disables the hidden input when the toggle is disabled', function () {
    $html = (string) $this->blade('<x-hw::toggle name="featured" value="1" pressed disabled>Featured</x-hw::toggle>');

    expect($html)->toContain('type="hidden"')
        ->and($html)->toContain('disabled')
        ->and($html)->toContain('data-disabled="true"');
});

it('uses on as the hidden input value by default', function () {
    $view = $this->blade('<x-hw::toggle name="featured" pressed>Featured</x-hw::toggle>');

    $view->assertSee('value="on"', false);
});

// --- Auto-submit ---

it('can opt into auto-submit change action', function () {
    $view = $this->blade('<x-hw::toggle name="featured" auto-submit>Featured</x-hw::toggle>');

    $view->assertSee('data-action="click->toggle#toggle change->auto-submit#submit"', false);
});

// --- Pass-through and Stimulus merge ---

it('merges user stimulus attributes with the toggle controller', function () {
    $view = $this->blade('<x-hw::toggle data-controller="analytics" data-action="click->analytics#track">Bold</x-hw::toggle>');

    $view->assertSee('data-controller="toggle analytics"', false)
        ->assertSee('data-action="click->toggle#toggle click->analytics#track"', false);
});

it('filters protected toggle controller attributes', function () {
    $view = $this->blade('<x-hw::toggle pressed data-toggle-pressed-value="false" data-toggle-value-value="x">Bold</x-hw::toggle>');

    $view->assertSee('data-toggle-pressed-value="true"', false)
        ->assertDontSee('data-toggle-pressed-value="false"', false)
        ->assertDontSee('data-toggle-value-value="x"', false);
});

it('passes through arbitrary HTML attributes', function () {
    $view = $this->blade('<x-hw::toggle id="bold" class="w-full" aria-label="Toggle bold" data-test="x">Bold</x-hw::toggle>');

    $view->assertSee('id="bold"', false)
        ->assertSee('class="group/toggle w-full"', false)
        ->assertSee('aria-label="Toggle bold"', false)
        ->assertSee('data-test="x"', false);
});

// --- Catalog ---

it('registers toggle in the component and controller catalogs', function () {
    $registry = HotwireRegistry::make();
    $toggle = $registry->component('toggle');
    $controller = $registry->controller('toggle');

    expect($toggle->class)->toBe(Toggle::class)
        ->and($toggle->view)->toBe('hotwire::component-views.toggle')
        ->and($toggle->controllers)->toBe(['toggle', 'auto-submit'])
        ->and($controller->source)->toBe('resources/js/controllers/toggle_controller.js')
        ->and($controller->docs)->toBe('docs/controllers/toggle.md');
});
