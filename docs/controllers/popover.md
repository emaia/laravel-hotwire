# popover

Toggles an anchored dialog from a trigger button, with click-outside, Escape and cross-popover dismissal.

Identifier: `popover` → `data-controller="popover"`.

## Targets

| Target    | Required | Description                            |
|-----------|----------|----------------------------------------|
| `trigger` | yes      | Button that toggles the popover        |
| `content` | yes      | Element shown when the popover is open |

## Public API

| Method                          | Description                                                                 |
|---------------------------------|-----------------------------------------------------------------------------|
| `open()`                        | Sets `aria-expanded="true"` and `aria-hidden="false"`. Dispatches `basecoat:popover` so other open popovers close. |
| `close(focusOnTrigger = true)`  | Reverses the attributes; optionally restores focus to the trigger.          |
| `toggle()`                      | Flips between `open` and `close`.                                           |
| `isOpen` (getter)               | `true` when `aria-expanded="true"` on the trigger.                          |

## Behavior

- Click on the trigger → `toggle()`.
- Click outside the controller's root element → `close()`.
- `Escape` keydown anywhere inside the root element → `close()`.
- Receiving `basecoat:popover` from a different source → `close(false)` (no focus theft).
- If the content contains `[autofocus]`, that element is focused after the open transition completes (or immediately when no transition is detected).

## Markup

The minimum markup the controller expects:

```html
<div data-controller="popover">
    <button type="button" data-popover-target="trigger" aria-expanded="false">Open</button>
    <div data-popover-target="content" data-popover aria-hidden="true">
        <input type="text" autofocus />
    </div>
</div>
```

The trigger and content must be descendants of the controller root (they don't need to be direct children, but typically are). For full ARIA wiring (ids, `aria-controls`, `aria-labelledby`, `role="dialog"`) use the `<x-hwc::popover>` Blade component.

## Events

| Event                 | When                                       | Detail                              |
|-----------------------|--------------------------------------------|-------------------------------------|
| `basecoat:popover`    | Dispatched on `document` from `open()`     | `{ source: rootElement }`           |
| `basecoat:initialized`| Dispatched on the root after `connect()`   | —                                   |
