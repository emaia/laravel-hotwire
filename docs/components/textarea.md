# Textarea

Auto-resizing textarea with optional character counter. Mirrors the same auto-derivation (`id`/`errorKey`), `old()` merge, and ARIA wiring as `<x-hwc::input>`.

## Quick example

```blade
<x-hwc::textarea name="bio" auto-resize :counter="500" />
```

## Props

| Prop            | Type           | Default                        | Description                                                       |
|-----------------|----------------|--------------------------------|-------------------------------------------------------------------|
| `name`          | `string\|null` | —                              | Pass-through. Drives `id` and `errorKey` if those aren't set       |
| `id`            | `string\|null` | derived from `name`            | Override the auto-derived id                                      |
| `value`         | `mixed`        | `null`                         | Default content, merged with `old($errorKey, $value)`              |
| `errorKey`      | `string\|null` | derived from `name`            | Override for arrays where HTML `name` ≠ validation key            |
| `old`           | `bool`         | `true`                         | Disable `old()` auto-merge                                        |
| `auto-resize`   | `bool`         | `false`                        | Automatically grows the textarea to fit content                   |
| `counter`       | `int\|null`    | `null`                         | Enables char counter and sets `maxlength`                         |
| `countdown`     | `bool`         | `false`                        | Counter shows remaining instead of used                           |
| `class`         | `string`       | `""`                           | Merged on `<textarea>`                                            |
| `wrapper-class` | `string`       | `""`                           | Merged on the wrapper when counter is active                      |

Any other HTML attribute (`placeholder`, `rows`, `disabled`, `data-*`, `aria-*`) passes through.

## Auto-derivation

Same convention as `<x-hwc::input>`:

```blade
<x-hwc::textarea name="variables[0][name]" />
{{-- id="variables-0-name", aria-describedby="variables-0-name-error", errorKey="variables.0.name" --}}
```

## Auto-resize

The textarea grows automatically as the user types and shrinks when text is deleted. Customize the resize debounce via `data-auto-resize-resize-debounce-delay-value`:

```blade
<x-hwc::textarea name="content" auto-resize
    data-auto-resize-resize-debounce-delay-value="200" />
```

## Char counter

When `:counter` is set, a wrapper `<span>` with `data-controller="char-counter"` is rendered around the textarea, and a `<small data-char-counter-target="counter">` shows the live count. Add `countdown` to show remaining characters:

```blade
<x-hwc::textarea name="tweet" :counter="280" countdown />
```

Customize the counter markup with the `counter` slot:

```blade
<x-hwc::textarea name="bio" :counter="500">
    <x-slot:counter>
        <span class="text-xs text-gray-500" data-char-counter-target="counter"></span>
    </x-slot:counter>
</x-hwc::textarea>
```

## Inheriting from `<x-hwc::field>`

```blade
<x-hwc::field name="bio" required>
    <x-hwc::textarea auto-resize />
</x-hwc::field>
```

## Required controllers

`hotwire:check` looks for `auto-resize` and `char-counter`. Only the ones you use need to be published.
