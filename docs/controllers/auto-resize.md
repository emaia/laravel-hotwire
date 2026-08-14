# Auto Resize

Automatically resizes a `<textarea>` to fit its content, eliminating scroll bars while keeping the field comfortable during typing and editing.

**Identifier:** `auto-resize`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with `php artisan hotwire:controllers auto-resize`.

## Requirements

- No external dependencies.

## Basic Usage

```html
<textarea
    data-controller="auto-resize"
    name="description"
    rows="3"
    placeholder="Describe..."
></textarea>
```

The textarea grows automatically as the user types and shrinks when text is deleted.

## With Initial Content

The textarea automatically adjusts its height on connect, even with pre-existing content:

```html
<textarea
    data-controller="auto-resize"
    name="bio"
>{{ $user->bio }}</textarea>
```

## With Custom Resize Delay

```html
<textarea
    data-controller="auto-resize"
    data-auto-resize-resize-debounce-delay-value="200"
    name="content"
></textarea>
```

## Without Resize Debounce

```html
<textarea
    data-controller="auto-resize"
    data-auto-resize-resize-debounce-delay-value="0"
    name="notes"
></textarea>
```

## Behavior

The controller recomputes the height on connect, on input, and after window resize events. Set `resize-debounce-delay` to `0` to disable resize debouncing.

The controller re-syncs `overflow: hidden` and recomputes the height on every `turbo:render`. With `<hw:meta refresh />` (or `data-turbo-action="morph"`), idiomorph preserves the textarea element but wipes the inline `style` attribute set at runtime. The listener restores both pieces after the morph completes.

## Values

| Value                   | Type     | Default | Description                                                          |
|-------------------------|----------|---------|----------------------------------------------------------------------|
| `resize-debounce-delay` | `Number` | `100`   | Debounce delay (ms) on the window `resize` event. Use `0` to disable |
