# Textarea

Auto-resizing the textarea with an optional character counter. Mirrors the same auto-derivation (`id`/`errorKey`),
`old()` merge, and ARIA wiring as `<hw:input>`.

## Quick example

```blade
<hw:textarea name="bio" auto-resize :counter="500" />
```

## Props

| Prop                | Type                | Default             | Description                                                  |
|---------------------|---------------------|---------------------|--------------------------------------------------------------|
| `name`              | `string\|null`      | —                   | Pass-through. Drives `id` and `errorKey` if those aren't set |
| `id`                | `string\|null`      | derived from `name` | Override the auto-derived id                                 |
| `value`             | `mixed`             | `null`              | Default content, merged with `old($errorKey, $value)`        |
| `errorKey`          | `string\|null`      | derived from `name` | Override for arrays where HTML `name` ≠ validation key       |
| `old`               | `bool`              | `true`              | Disable `old()` auto-merge                                   |
| `auto-resize`       | `bool`              | `false`             | Automatically grows the textarea to fit content              |
| `counter`           | `int\|null`         | `null`              | Enables char counter and sets `maxlength`                    |
| `countdown`         | `bool`              | `false`             | Counter shows remaining instead of used                      |
| `auto-submit`       | `bool\|string`      | `false`             | Add debounced auto-submit wiring                             |
| `auto-submit-delay` | `int\|string\|null` | `null`              | Per-field debounce override                                  |
| `class`             | `string`            | `""`                | Merged on the native editing area                            |
| `wrapper-class`     | `string`            | `""`                | Merged on the counted control's outer frame                  |

Any other HTML attribute (`placeholder`, `rows`, `disabled`, `data-*`, `aria-*`) passes through.

## Auto-derivation

Same convention as `<hw:input>`:

```blade
<hw:textarea name="variables[0][name]" />
{{-- id="variables-0-name", errorKey="variables.0.name" --}}
```

## Auto-resize

The textarea grows automatically as the user types and shrinks when text is deleted. Customize the resize debounce via
`data-auto-resize-resize-debounce-delay-value`:

```blade
<hw:textarea name="content" auto-resize
    data-auto-resize-resize-debounce-delay-value="200" />
```

## Char counter

Set `:counter` to display a character count below the editable area, inside the control's frame. Use `wrapper-class` to
style the frame and `class` to style the textarea. Add `countdown` to show remaining characters:

```blade
<hw:textarea name="tweet" :counter="280" countdown />
```

Customize the counter with `counter-slot`. Include `data-char-counter-target="counter"` where the live value belongs.
Slot attributes style the counter container; its content must be phrasing content (text and inline elements):

```blade
<hw:textarea name="bio" :counter="500">
    <x-slot:counter-slot class="text-end">
        <span data-char-counter-target="counter"></span> of 500 characters
    </x-slot:counter-slot>
</hw:textarea>
```

Omitting the counter target does not break input handling, but leaves custom content static and emits a console warning
when the controller connects.
The `counter-slot` named slot requires the `counter` prop; using the slot by itself raises an exception instead of
discarding its content.

## Auto-submit

Textareas submit with debounce by default when `auto-submit` is set:

```blade
<hw:form method="get" action="/notes" auto-submit auto-submit-delay="300">
    <hw:textarea name="q" auto-submit auto-submit-delay="800" />
</hw:form>
```

## Inheriting from `<hw:field>`

```blade
<hw:field name="bio" label="Bio" required>
    <hw:textarea auto-resize />
</hw:field>
```

## Controller integrations

`hotwire:check` detects the enabled `auto-resize`, `char-counter`, and `auto-submit` integrations. These controllers
auto-load from the package; publish one only when you want to customize its source.

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="textarea-wrapper"`
- `data-slot="textarea"`
- `data-slot="textarea-counter"`
