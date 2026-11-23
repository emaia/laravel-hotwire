<?php

use Emaia\LaravelHotwire\Components\ConditionalField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;

class ConditionalFieldMessage extends Model
{
    protected $guarded = [];
}

function seedOldInput(array $values): void
{
    request()->setLaravelSession(session()->driver());
    session()->put('_old_input', $values);
}

it('renders a fieldset with the dependent target attribute', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => \'other\']">inside</x-hw::conditional-field>');

    $view->assertSee('<fieldset', false);
    $view->assertSee('data-conditional-fields-target="dependent"', false);
    $view->assertSee('inside');
});

it('emits a data-when-* attribute for a single string value', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => \'other\']">inside</x-hw::conditional-field>');

    $view->assertSee('data-when-reason="other"', false);
});

it('accepts field equals value string grammar', function () {
    $view = $this->blade('<x-hw::conditional-field when="type=link">inside</x-hw::conditional-field>');

    $view->assertSee('data-when-type="link"', false);
});

it('accepts pipe-separated OR values in string grammar', function () {
    $view = $this->blade('<x-hw::conditional-field when="type=link|text">inside</x-hw::conditional-field>');

    $view->assertSee('data-when-type="link|text"', false);
});

it('accepts space-separated AND conditions in string grammar', function () {
    $view = $this->blade('<x-hw::conditional-field when="type=link location=sidebar">inside</x-hw::conditional-field>');

    $view->assertSee('data-when-type="link"', false);
    $view->assertSee('data-when-location="sidebar"', false);
});

it('accepts checked tokens in string grammar', function () {
    $view = $this->blade('<x-hw::conditional-field when="active=:checked">inside</x-hw::conditional-field>');

    $view->assertSee('data-when-active=":checked"', false);
});

it('emits a pipe-separated data-when-* attribute for an array value', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => [\'bug\', \'feature\']]">inside</x-hw::conditional-field>');

    $view->assertSee('data-when-reason="bug|feature"', false);
});

it('preserves spaces inside trigger values when emitting data-when-*', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'user\' => [\'Kris Jhonson\', \'John Doe\']]">inside</x-hw::conditional-field>');

    $view->assertSee('data-when-user="Kris Jhonson|John Doe"', false);
});

it('passes through tokens like :checked unchanged', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'ship_different\' => \':checked\']">inside</x-hw::conditional-field>');

    $view->assertSee('data-when-ship_different=":checked"', false);
});

it('renders one attribute per field for multi-field AND rules', function () {
    $view = $this->blade(
        '<x-hw::conditional-field :when="[\'authorized\' => \'no\', \'needs_visa\' => \'yes\']">inside</x-hw::conditional-field>'
    );

    $view->assertSee('data-when-authorized="no"', false);
    $view->assertSee('data-when-needs_visa="yes"', false);
});

it('renders hidden and disabled by default when neither old() nor model is set', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => \'other\']">inside</x-hw::conditional-field>');

    $view->assertSee('hidden', false);
    $view->assertSee('disabled', false);
});

it('renders visible after validation retry — old() value drives the rule', function () {
    seedOldInput(['reason' => 'other']);

    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => \'other\']">inside</x-hw::conditional-field>');

    $view->assertDontSee(' hidden', false);
    $view->assertDontSee(' disabled', false);
});

it('renders hidden after validation retry when old() value does not match', function () {
    seedOldInput(['reason' => 'bug']);

    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => \'other\']">inside</x-hw::conditional-field>');

    $view->assertSee('hidden', false);
    $view->assertSee('disabled', false);
});

it('falls back to object state when old() is empty', function () {
    $message = new ConditionalFieldMessage(['reason' => 'other']);

    $component = new ConditionalField(
        when: ['reason' => 'other'],
        state: $message,
    );

    expect($component->matches)->toBeTrue();
});

it('falls back to state when old() is empty', function () {
    $component = new ConditionalField(
        when: 'type=link',
        state: ['type' => 'link'],
    );

    expect($component->matches)->toBeTrue();
});

it('lets local state override inherited state', function () {
    $view = $this->blade(
        <<<'BLADE'
        <x-hw::form conditional-fields :state="$formState">
            <x-hw::conditional-field when="type=link" :state="$fieldState">URL</x-hw::conditional-field>
        </x-hw::form>
        BLADE,
        [
            'formState' => ['type' => 'text'],
            'fieldState' => ['type' => 'link'],
        ],
    );

    $view->assertDontSee(' hidden', false)
        ->assertDontSee(' disabled', false);
});

it('accepts scoped state from an application context provider', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-residual-context-wrapper :conditional-field-state="['type' => 'link']">
            <x-hw::conditional-field when="type=link">URL</x-hw::conditional-field>
        </x-residual-context-wrapper>
    BLADE);

    $view->assertDontSee(' hidden', false)
        ->assertDontSee(' disabled', false);
});

it('old() value wins over state on validation retry', function () {
    seedOldInput(['reason' => 'bug']);

    $message = new ConditionalFieldMessage(['reason' => 'other']);

    $component = new ConditionalField(
        when: ['reason' => 'other'],
        state: $message,
    );

    expect($component->matches)->toBeFalse();
});

it('matches :checked token when state is truthy', function () {
    $message = new ConditionalFieldMessage(['ship_different' => '1']);

    $component = new ConditionalField(
        when: ['ship_different' => ':checked'],
        state: $message,
    );

    expect($component->matches)->toBeTrue();
});

it('matches :unchecked token by default when no value is present', function () {
    $component = new ConditionalField(when: ['agree' => ':unchecked']);

    expect($component->matches)->toBeTrue();
});

it('matches an array trigger value when old() carries the wanted item', function () {
    seedOldInput(['interests' => ['news', 'events']]);

    $component = new ConditionalField(when: ['interests' => 'events']);

    expect($component->matches)->toBeTrue();
});

it('accepts a custom wrapper tag', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => \'other\']" tag="div">x</x-hw::conditional-field>');

    $view->assertSee('<div', false);
    $view->assertSee('data-conditional-fields-target="dependent"', false);
});

it('renders a validated as prop without raw tag output', function () {
    $view = $this->blade('<x-hw::conditional-field when="plan=pro" as="DIV"><input name="team"></x-hw::conditional-field>');

    $view->assertSee('<div', false)
        ->assertSee('</div>', false)
        ->assertDontSee(' as=', false);
});

it('forwards extra attributes to the wrapper element', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => \'other\']" class="my-class">x</x-hw::conditional-field>');

    $view->assertSee('class="my-class"', false);
});

it('protects the internal conditional-fields target while merging extra stimulus attributes', function () {
    $view = $this->blade('<x-hw::conditional-field :when="[\'reason\' => \'other\']" data-conditional-fields-target="override" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'change\')">x</x-hw::conditional-field>');

    $view->assertSee('data-conditional-fields-target="dependent"', false);
    $view->assertDontSee('data-conditional-fields-target="override"', false);
    $view->assertSee('data-controller="analytics"', false);
    $view->assertSee('data-action="change->analytics#track"', false);
});

// --- Negated rules ---

it('accepts an unless-only string rule without leaking the prop into HTML', function () {
    $view = $this->blade(
        '<x-hw::conditional-field unless="status=completed" :state="[\'status\' => \'accepted\']">Save</x-hw::conditional-field>'
    );

    $view->assertSee('data-unless-status="completed"', false)
        ->assertDontSee(' unless=', false)
        ->assertDontSee(' hidden', false)
        ->assertDontSee(' disabled', false);
});

it('hides and disables a block when its unless rule matches', function () {
    $view = $this->blade(
        '<x-hw::conditional-field unless="status=completed" :state="[\'status\' => \'completed\']">Save</x-hw::conditional-field>'
    );

    $view->assertSee(' hidden', false)
        ->assertSee(' disabled', false);
});

it('accepts unless arrays with literal values and OR alternatives', function () {
    $component = new ConditionalField(
        unless: ['status' => ['Completed successfully', '!cancelled']],
        state: ['status' => '!cancelled'],
    );

    expect($component->matches)->toBeFalse()
        ->and($component->dataUnlessAttributes())->toBe([
            [
                'name' => 'status',
                'attribute' => 'data-unless-status',
                'value' => 'Completed successfully|!cancelled',
            ],
        ]);
});

it('negates the entire AND expression in unless', function (string $status, string $approved, bool $visible) {
    $component = new ConditionalField(
        unless: 'status=completed|cancelled approved=yes',
        state: ['status' => $status, 'approved' => $approved],
    );

    expect($component->matches)->toBe($visible);
})->with([
    'all match' => ['completed', 'yes', false],
    'OR alternative matches' => ['cancelled', 'yes', false],
    'first condition differs' => ['accepted', 'yes', true],
    'second condition differs' => ['completed', 'no', true],
]);

it('requires when to match and unless not to match', function (string $plan, string $status, bool $visible) {
    $component = new ConditionalField(
        when: 'plan=pro',
        unless: 'status=completed',
        state: ['plan' => $plan, 'status' => $status],
    );

    expect($component->matches)->toBe($visible);
})->with([
    'when matches and unless differs' => ['pro', 'accepted', true],
    'both match' => ['pro', 'completed', false],
    'neither matches' => ['starter', 'accepted', false],
]);

it('emits both positive and negative rules for the same field', function () {
    $view = $this->blade(
        '<x-hw::conditional-field when="status=accepted|completed" unless="status=completed">Save</x-hw::conditional-field>'
    );

    $view->assertSee('data-when-status="accepted|completed"', false)
        ->assertSee('data-unless-status="completed"', false);
});

it('does not hide matching when rules for an empty unless expression', function (array|string $unless) {
    $component = new ConditionalField(
        when: 'plan=pro',
        unless: $unless,
        state: ['plan' => 'pro'],
    );

    expect($component->matches)->toBeTrue()
        ->and($component->dataUnlessAttributes())->toBe([]);
})->with([
    'empty array' => [[]],
    'empty string' => [''],
]);

it('shows an unless block when a trigger has no selected value', function (mixed $value) {
    $component = new ConditionalField(
        unless: ['status' => 'completed'],
        state: ['status' => $value],
    );

    expect($component->matches)->toBeTrue();
})->with([null, '', [[]]]);

it('negates checkbox state tokens', function (string $token, mixed $value, bool $visible) {
    $component = new ConditionalField(
        unless: ['active' => $token],
        state: ['active' => $value],
    );

    expect($component->matches)->toBe($visible);
})->with([
    'checked' => [':checked', '1', false],
    'unchecked' => [':checked', null, true],
    'not unchecked' => [':unchecked', '1', true],
    'not checked' => [':unchecked', null, false],
]);

it('negates membership across all checkbox group values', function (array $values, bool $visible) {
    $component = new ConditionalField(
        unless: ['interests' => ['news', 'events']],
        state: ['interests' => $values],
    );

    expect($component->matches)->toBe($visible);
})->with([
    'one forbidden choice' => [['tips', 'events'], false],
    'no forbidden choices' => [['tips'], true],
    'nothing selected' => [[], true],
]);

it('uses old input ahead of inherited state for unless rules', function () {
    seedOldInput(['status' => 'completed']);

    $view = $this->blade(<<<'BLADE'
        <x-hw::form conditional-fields :state="['status' => 'accepted']">
            <x-hw::conditional-field unless="status=completed">Save</x-hw::conditional-field>
        </x-hw::form>
    BLADE);

    $view->assertSee('data-controller="conditional-fields"', false)
        ->assertSee('data-unless-status="completed"', false)
        ->assertSee(' hidden', false)
        ->assertSee(' disabled', false);
});
