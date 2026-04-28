# Copy To Clipboard

Copies text to the clipboard when a button is clicked. Temporarily replaces the button label with a success message after copying.

**Identifier:** `copy-to-clipboard`  
**Install:** `php artisan hotwire:controllers copy-to-clipboard`

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
| `copy-to-clipboard#copy`   | Copies the source text to the clipboard |

## Basic usage — copy a code block

```html
<div data-controller="copy-to-clipboard">
    <pre data-copy-to-clipboard-target="source">npm install @hotwired/stimulus</pre>

    <button
        type="button"
        data-copy-to-clipboard-target="button"
        data-action="click->copy-to-clipboard#copy"
        data-copy-to-clipboard-success-content-value="Copied!"
    >
        Copy
    </button>
</div>
```

## Copy an input value

```html
<div data-controller="copy-to-clipboard">
    <input
        type="text"
        readonly
        value="https://example.com/invite/abc123"
        data-copy-to-clipboard-target="source"
    />

    <button
        type="button"
        data-copy-to-clipboard-target="button"
        data-action="click->copy-to-clipboard#copy"
        data-copy-to-clipboard-success-content-value="&#10003; Copied"
    >
        Copy link
    </button>
</div>
```

## Custom success duration

```html
<div
    data-controller="copy-to-clipboard"
    data-copy-to-clipboard-success-duration-value="3000"
>
    <code data-copy-to-clipboard-target="source">SECRET_KEY=abc123</code>

    <button
        type="button"
        data-copy-to-clipboard-target="button"
        data-action="click->copy-to-clipboard#copy"
        data-copy-to-clipboard-success-content-value='<svg ...></svg> Copied!'
    >
        <svg ...></svg> Copy
    </button>
</div>
```

## Without a button target

When no `button` target is present the copy still works — the success feedback is simply skipped:

```html
<div data-controller="copy-to-clipboard">
    <span data-copy-to-clipboard-target="source">some-api-key</span>
    <a href="#" data-action="click->copy-to-clipboard#copy">Copy</a>
</div>
```
