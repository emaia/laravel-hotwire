# Clipboard

Copies text to the clipboard when a button is clicked. Temporarily replaces the button label with a success message after copying.

**Identifier:** `utils--clipboard`

## Requirements

- No external dependencies. Uses the browser's `navigator.clipboard` API (requires HTTPS or localhost).

## Targets

| Target   | Description                                   |
|----------|-----------------------------------------------|
| `source` | Element whose text content or value is copied |
| `button` | The trigger button (optional — needed for success feedback) |

## Stimulus Values

| Value              | Type     | Default | Description                                                        |
|--------------------|----------|---------|--------------------------------------------------------------------|
| `success-content`  | `String` | —       | HTML to show inside the button after a successful copy             |
| `success-duration` | `Number` | `2000`  | Milliseconds before the button reverts to its original content     |

## Actions

| Action                    | Description           |
|---------------------------|-----------------------|
| `utils--clipboard#copy`   | Copies the source text to the clipboard |

## Basic usage — copy a code block

```html
<div data-controller="utils--clipboard">
    <pre data-utils--clipboard-target="source">npm install @hotwired/stimulus</pre>

    <button
        type="button"
        data-utils--clipboard-target="button"
        data-action="click->utils--clipboard#copy"
        data-utils--clipboard-success-content-value="Copied!"
    >
        Copy
    </button>
</div>
```

## Copy an input value

```html
<div data-controller="utils--clipboard">
    <input
        type="text"
        readonly
        value="https://example.com/invite/abc123"
        data-utils--clipboard-target="source"
    />

    <button
        type="button"
        data-utils--clipboard-target="button"
        data-action="click->utils--clipboard#copy"
        data-utils--clipboard-success-content-value="&#10003; Copied"
    >
        Copy link
    </button>
</div>
```

## Custom success duration

```html
<div
    data-controller="utils--clipboard"
    data-utils--clipboard-success-duration-value="3000"
>
    <code data-utils--clipboard-target="source">SECRET_KEY=abc123</code>

    <button
        type="button"
        data-utils--clipboard-target="button"
        data-action="click->utils--clipboard#copy"
        data-utils--clipboard-success-content-value='<svg ...></svg> Copied!'
    >
        <svg ...></svg> Copy
    </button>
</div>
```

## Without a button target

When no `button` target is present the copy still works — the success feedback is simply skipped:

```html
<div data-controller="utils--clipboard">
    <span data-utils--clipboard-target="source">some-api-key</span>
    <a href="#" data-action="click->utils--clipboard#copy">Copy</a>
</div>
```
