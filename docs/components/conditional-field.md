# Conditional Field

Renders a dependent block for the [`conditional-fields`](../controllers/conditional-fields.md)
controller — the show/hide rule lives in **exactly one place**, the `when` prop, and the
component takes care of both the server-side initial visibility and the runtime data attributes
the controller reads.

The component eliminates the classic "rule duplication" pitfall: without it, the dependent
markup has to encode the rule twice (once in `data-when-*` attributes for the client, once in
`@if (...) hidden disabled @endif` for the server), and any drift between the two flashes the
wrong fields on first paint.

## Props

| Prop    | Type     | Default      | Description                                                                                                |
|---------|----------|--------------|------------------------------------------------------------------------------------------------------------|
| `when`  | `array`  | (required)   | The rule: `['field' => 'value']`, `['field' => ['v1', 'v2']]` (OR), or `['field' => ':checked']`. Multiple entries AND-match. |
| `state` | `array`  | `[]`         | Override current values per field. Used for edit forms where values come from a model in addition to `old()`. |
| `tag`   | `string` | `'fieldset'` | Wrapper element. `<fieldset>` is recommended — the `disabled` cascade reaches every descendant control for free. |

## Wrapping with the controller

Put `data-controller="conditional-fields"` on the form (or any parent of the dependents and their
triggers). The component handles the rest:

```blade
<form data-controller="conditional-fields" action="/feedback" method="POST">
    @csrf

    <x-hwc::select name="reason">
        <option value="">Pick one…</option>
        <option value="bug">Bug</option>
        <option value="feature">Feature</option>
        <option value="other">Other</option>
    </x-hwc::select>

    <x-hwc::conditional-field :when="['reason' => ['bug', 'feature']]">
        <x-hwc::field name="details" label="What happened?">
            <x-hwc::textarea name="details">{{ old('details') }}</x-hwc::textarea>
        </x-hwc::field>
    </x-hwc::conditional-field>

    <x-hwc::conditional-field :when="['reason' => 'other']">
        <x-hwc::field name="other_reason" label="Tell us">
            <x-hwc::input name="other_reason" value="{{ old('other_reason') }}" />
        </x-hwc::field>
    </x-hwc::conditional-field>

    <button type="submit">Send</button>
</form>
```

## Edit forms — the `state` prop

On a fresh request, the component reads each trigger's value from `request()->input()`, which
covers `old()` after a failed validation. For edit forms the initial value comes from a model
attribute, not from the request — pass it via `state`:

```blade
@php $state = [
    'reason' => old('reason', $message->reason),
]; @endphp

<form data-controller="conditional-fields" action="/messages/{{ $message->id }}" method="POST">
    @csrf @method('PATCH')

    <x-hwc::select name="reason">
        @foreach ($reasons as $value => $label)
            <option value="{{ $value }}" @selected($state['reason'] === $value)>{{ $label }}</option>
        @endforeach
    </x-hwc::select>

    <x-hwc::conditional-field :when="['reason' => 'other']" :state="$state">
        <x-hwc::input name="other_reason" value="{{ old('other_reason', $message->other_reason) }}" />
    </x-hwc::conditional-field>
</form>
```

The same `$state` array can drive multiple dependents — compute it once at the top of the form
and reuse.

## Token shortcuts

| Rule                                          | Meaning                                                                |
|-----------------------------------------------|------------------------------------------------------------------------|
| `['ship_different' => ':checked']`            | Match when the named field is "checked" (truthy in `state`/`request`). |
| `['agree' => ':unchecked']`                   | Match when the named field is empty / unchecked.                       |
| `['plan' => 'enterprise']`                    | Equality.                                                              |
| `['plan' => ['pro', 'enterprise']]`           | OR.                                                                    |
| `['authorized' => 'no', 'needs_visa' => 'yes']` | AND across two fields.                                                |

## When to use `<div>` instead of `<fieldset>`

For a single-field dependent that does not need a `<legend>`, `<div>` is acceptable:

```blade
<x-hwc::conditional-field tag="div" :when="['mode' => 'advanced']" class="mt-4">
    <x-hwc::input name="threshold" value="{{ old('threshold') }}" />
</x-hwc::conditional-field>
```

The controller walks descendant inputs and toggles their `disabled` state instead of using the
`<fieldset>` cascade. Slightly more work at runtime but the markup stays flatter.

## Multiple dependents reuse the same controller

A single `data-controller="conditional-fields"` on the form is enough — every `<x-hwc::conditional-field>`
under it registers as a dependent target automatically. No per-dependent wiring.

## See also

- [Conditional fields controller](../controllers/conditional-fields.md) — the underlying
  Stimulus controller, with the full rule grammar reference.
- [Conditional fields recipe](../recipes/conditional-fields.md) — real-world form patterns
  (checkout shipping, subscription tiers, NPS survey, newsletter preferences).
