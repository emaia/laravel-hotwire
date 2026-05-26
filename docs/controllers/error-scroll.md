# Error Scroll

Scrolls to the first validation error inside a Turbo Frame after `turbo:frame-render`, so the user always sees what went wrong — even when `autoscroll` on the frame rolls to a different position.

**Identifier:** `error-scroll`
**Install:** `php artisan hotwire:controllers error-scroll`

## Requirements

- No external dependencies.
- Turbo 8+ (`turbo:frame-render` event).

## Usage

Place the controller on a `<turbo-frame>` or any ancestor that wraps the form:

```blade
<turbo-frame id="create_form" data-controller="error-scroll">
    <x-hwc::form action="/store" method="post">
        <x-hwc::field name="email" label="E-mail" required>
            <x-hwc::input type="email" />
        </x-hwc::field>
        <button type="submit">Save</button>
    </x-hwc::form>
</turbo-frame>
```

After a validation error, the controller finds the first `[aria-invalid]` element inside the frame and scrolls it into view with smooth animation and center alignment.

## Values

| Value | Type | Default | Description |
|---|---|---|---|
| `selector` | `string` | `"[aria-invalid]"` | CSS selector for the element to scroll to |
| `behavior` | `string` | `"smooth"` | `scrollIntoView` behavior: `"smooth"` or `"auto"` |
| `block` | `string` | `"center"` | `scrollIntoView` block: `"start"`, `"center"`, `"end"`, `"nearest"` |

## Customising

```blade
{{-- Instant scroll --}}
<turbo-frame data-controller="error-scroll" data-error-scroll-behavior-value="auto">

{{-- Scroll to top of error --}}
<turbo-frame data-controller="error-scroll" data-error-scroll-block-value="start">

{{-- Custom selector (e.g., when not using hwc components) --}}
<turbo-frame data-controller="error-scroll" data-error-scroll-selector-value=".text-red-500">

{{-- Combined --}}
<turbo-frame
    data-controller="error-scroll"
    data-error-scroll-selector-value=".field-error"
    data-error-scroll-behavior-value="auto"
    data-error-scroll-block-value="nearest"
>
```

## With `autoscroll`

The `<turbo-frame>` attribute `autoscroll` already scrolls to the frame itself after a frame navigation. Place `error-scroll` alongside it — the controller fires on `turbo:frame-render`, which happens after the `autoscroll` behavior, so the error scroll overrides the frame scroll:

```blade
<turbo-frame id="create_form" autoscroll data-controller="error-scroll">
    ...
</turbo-frame>
```

## Without a Turbo Frame

If you want error scrolling on full-page morphs (no frame), listen to `turbo:render` instead. A small variation:

```js
connect() {
    this.go = this.scrollToError.bind(this)
    document.addEventListener("turbo:render", this.go)
}
disconnect() {
    document.removeEventListener("turbo:render", this.go)
}
```
