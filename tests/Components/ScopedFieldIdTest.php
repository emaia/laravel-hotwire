<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ViewErrorBag;

class ScopedFormRecord extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->headers->remove('Turbo-Frame');
    app()->forgetScopedInstances();
});

afterEach(function () {
    request()->headers->remove('Turbo-Frame');
});

// --- Form identity ---

it('assigns deterministic ids to forms in the page render scope', function () {
    $view = $this->blade('<x-hw::form method="get"><span>x</span></x-hw::form><x-hw::form method="get"><span>y</span></x-hw::form>');

    $view->assertSee('id="hw-form-page-1"', false);
    $view->assertSee('id="hw-form-page-2"', false);
});

it('uses the Turbo Frame id as the form render scope', function () {
    request()->headers->set('Turbo-Frame', 'results');

    $view = $this->blade('<x-hw::form method="get"><span>x</span></x-hw::form>');

    $view->assertSee('id="hw-form-frame-results-1"', false);
});

it('preserves explicit ids and resolves model ids for forms', function () {
    $this->blade('<x-hw::form method="get" id="search"><span>x</span></x-hw::form>')
        ->assertSee('id="search"', false);

    $record = new ScopedFormRecord;
    $record->id = 42;

    $this->blade('<x-hw::form method="get" :id="$record"><span>x</span></x-hw::form>', ['record' => $record])
        ->assertSee('id="form_scoped_form_record_42"', false);
});

// --- Field scope ---

it('scopes field-derived ids to the enclosing form', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get">
            <x-hw::field name="title" label="Title">
                <x-hw::input type="text" />
            </x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('id="hw-form-page-1-title"', false);
    $view->assertSee('for="hw-form-page-1-title"', false);
    $view->assertSee('aria-describedby="hw-form-page-1-title-error"', false);
    $view->assertSee('id="hw-form-page-1-title-error"', false);
});

it('keeps same-named fields distinct across forms', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get" id="create">
            <x-hw::field name="title"><x-hw::input /></x-hw::field>
        </x-hw::form>
        <x-hw::form method="get" id="edit">
            <x-hw::field name="title"><x-hw::input /></x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('id="create-title"', false);
    $view->assertSee('id="edit-title"', false);
});

it('disambiguates duplicate field names inside one form', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get">
            <x-hw::field name="title"><x-hw::input /></x-hw::field>
            <x-hw::field name="title"><x-hw::input /></x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('id="hw-form-page-1-title"', false);
    $view->assertSee('id="hw-form-page-1-title-2"', false);
    $view->assertSee('id="hw-form-page-1-title-error"', false);
    $view->assertSee('id="hw-form-page-1-title-2-error"', false);
});

it('keeps an explicit field id inside a scoped form', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get">
            <x-hw::field name="title" id="custom-title"><x-hw::input /></x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('id="custom-title"', false);
    $view->assertSee('id="custom-title-error"', false);
    $view->assertDontSee('id="hw-form-page-1-title"', false);
});

// --- Bare composition inside a form ---

it('scopes bare controls and pairs their error ids in both render orders', function (string $template) {
    $view = $this->blade($template);

    $view->assertSee('id="hw-form-page-1-email"', false);
    $view->assertSee('id="hw-form-page-1-email-error"', false);
})->with([
    'control before error' => '<x-hw::form method="get"><x-hw::input type="text" name="email" /><x-hw::field.error name="email" /></x-hw::form>',
    'error before control' => '<x-hw::form method="get"><x-hw::field.error name="email" /><x-hw::input type="text" name="email" /></x-hw::form>',
]);

it('keeps a bare label paired with its control inside a form', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get">
            <x-hw::field.label name="email">E-mail</x-hw::field.label>
            <x-hw::input type="text" name="email" />
        </x-hw::form>
    BLADE);

    $view->assertSee('for="hw-form-page-1-email"', false);
    $view->assertSee('id="hw-form-page-1-email"', false);
});

it('scopes bare selection groups and their items inside a form', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get">
            <x-hw::radio-group name="plan" :options="['free' => 'Free']" />
        </x-hw::form>
    BLADE);

    $view->assertSee('id="hw-form-page-1-plan-free"', false);
});

it('scopes selection groups nested in a field inside a form', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get">
            <x-hw::field name="plan">
                <x-hw::radio-group :options="['free' => 'Free']" />
            </x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('id="hw-form-page-1-plan-free"', false);
});

it('scopes named selection groups inside a nameless field across forms', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get" id="create">
            <x-hw::field label="Plan">
                <x-hw::radio-group name="plan" :options="['free' => 'Free']" />
            </x-hw::field>
        </x-hw::form>
        <x-hw::form method="get" id="edit">
            <x-hw::field label="Plan">
                <x-hw::radio-group name="plan" :options="['free' => 'Free']" />
            </x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('id="create-plan-free"', false);
    $view->assertSee('id="edit-plan-free"', false);
    $view->assertSee('aria-describedby="create-plan-error"', false);
    $view->assertSee('aria-describedby="edit-plan-error"', false);
});

it('scopes named checkbox and toggle groups inside a nameless field', function (string $template, string $expectedItemId, string $expectedErrorId) {
    $view = $this->blade($template);

    $view->assertSee('id="create-'.$expectedItemId.'"', false);
    $view->assertSee('aria-describedby="create-'.$expectedErrorId.'"', false);
    $view->assertSee('id="create-'.$expectedErrorId.'"', false);
})->with([
    'checkbox group' => [
        '<x-hw::form method="get" id="create"><x-hw::field label="Tags"><x-hw::checkbox-group name="tags" :options="[\'a\' => \'A\']" /></x-hw::field></x-hw::form>',
        'tags-a',
        'tags-error',
    ],
    'toggle group' => [
        '<x-hw::form method="get" id="create"><x-hw::field label="View"><x-hw::toggle-group name="view" :options="[\'a\' => \'A\']" /></x-hw::field></x-hw::form>',
        'view-a-input',
        'view-error',
    ],
]);

it('scopes selection groups whose name diverges from a named field', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get" id="create">
            <x-hw::field name="title">
                <x-hw::radio-group name="plan" :options="['free' => 'Free']" />
            </x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('id="create-plan-free"', false);
});

// --- Divergent names inside a field ---

it('scopes controls whose name diverges from the field across forms', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get" id="create">
            <x-hw::field name="title"><x-hw::input name="other" /></x-hw::field>
        </x-hw::form>
        <x-hw::form method="get" id="edit">
            <x-hw::field name="title"><x-hw::input name="other" /></x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('id="create-other"', false);
    $view->assertSee('id="edit-other"', false);
});

it('scopes labels and errors whose name diverges from the field', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get" id="create">
            <x-hw::field name="title" :error="false">
                <x-hw::field.label name="other">Other</x-hw::field.label>
                <x-hw::input name="other" />
                <x-hw::field.error name="other" />
            </x-hw::field>
        </x-hw::form>
    BLADE);

    $view->assertSee('for="create-other"', false);
    $view->assertSee('id="create-other"', false);
    $view->assertSee('aria-describedby="create-other-error"', false);
    $view->assertSee('id="create-other-error"', false);
});

it('keeps field error ids distinct from a bare error with the same name', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::form method="get">
            <x-hw::field name="title"><x-hw::input /></x-hw::field>
            <x-hw::field name="title"><x-hw::input /></x-hw::field>
            <x-hw::field.error name="title" />
        </x-hw::form>
    BLADE);

    $view->assertSee('id="hw-form-page-1-title-error"', false);
    $view->assertSee('id="hw-form-page-1-title-2-error"', false);
    $view->assertSee('id="hw-form-page-1-title-error-2"', false);
});

// --- Outside a form nothing changes ---

it('leaves fields outside forms on their name-derived ids', function () {
    $view = $this->blade('<x-hw::field name="title"><x-hw::input /></x-hw::field>');

    $view->assertSee('id="title"', false);
    $view->assertSee('id="title-error"', false);
});

// --- Determinism ---

it('renders identical scoped ids across render scopes', function () {
    $template = '<x-hw::form method="get"><x-hw::field name="title"><x-hw::input /></x-hw::field></x-hw::form>';

    $first = (string) $this->blade($template);

    app()->forgetScopedInstances();

    $second = (string) $this->blade($template);

    expect($second)->toBe($first);
});
