# Error Scroll

Scrolls to the first validation error after `turbo:frame-render` (inside a Turbo Frame) or `turbo:render` (full-page
morphs). The user always sees what went wrong — even when `autoscroll` on a frame rolls to a different position.

**Identifier:** `error-scroll`
**Install:** `php artisan hotwire:controllers error-scroll`

## Requirements

- No external dependencies.
- Turbo 8+ (`turbo:frame-render` and `turbo:render` events).

## Usage

Place the controller on a `<turbo-frame>`, a `<form>`, or any ancestor that wraps the form:

```blade
{{-- With a Turbo Frame --}}
<turbo-frame id="create_form" data-controller="error-scroll">
    <hw:form action="/store" method="post">
        <hw:field name="email" label="E-mail" required>
            <hw:input type="email" />
        </hw:field>
        <button type="submit">Save</button>
    </hw:form>
</turbo-frame>

{{-- Without a Turbo Frame (full-page morphs) --}}
<form data-controller="error-scroll" action="/store" method="post">
    <hw:field name="email" label="E-mail" required>
        <hw:input type="email" />
    </hw:field>
    <button type="submit">Save</button>
</form>
```

After a validation error, the controller finds the first `[aria-invalid]` element inside the container and scrolls it
into view with smooth animation and center alignment.

## Values

| Value      | Type     | Default            | Description                                                         |
|------------|----------|--------------------|---------------------------------------------------------------------|
| `selector` | `string` | `"[aria-invalid]"` | CSS selector for the element to scroll to                           |
| `behavior` | `string` | `"smooth"`         | `scrollIntoView` behavior: `"smooth"` or `"auto"`                   |
| `block`    | `string` | `"center"`         | `scrollIntoView` block: `"start"`, `"center"`, `"end"`, `"nearest"` |

## Customising

```blade
{{-- Instant scroll --}}
<turbo-frame data-controller="error-scroll" data-error-scroll-behavior-value="auto">

{{-- Scroll to top of error --}}
<turbo-frame data-controller="error-scroll" data-error-scroll-block-value="start">

{{-- Custom selector (e.g., when not using Hotwire components) --}}
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

The `<turbo-frame>` attribute `autoscroll` already scrolls to the frame itself after a frame navigation. Place
`error-scroll` alongside it — the controller fires on `turbo:frame-render`, which happens after the `autoscroll`
behavior, so the error scroll overrides the frame scroll:

```blade
<turbo-frame id="create_form" autoscroll data-controller="error-scroll">
    ...
</turbo-frame>
```

## Without a Turbo Frame

The controller listens to both `turbo:frame-render` and `turbo:render` events, so it works natively on full-page morphs
with no modifications needed. Just place `data-controller="error-scroll"` on the `<form>` or a wrapper element.
