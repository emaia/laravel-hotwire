# Sheet

Controls `<hw:sheet>` open and close behavior. It is the sheet-flavoured variant of the Drawer controller, using
`data-sheet-*` names while keeping the same overlay lifecycle.

**Identifier:** `sheet`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers sheet`.

## Requirements

- No external dependencies.
- Turbo is optional and only needed when using dynamic frame content.

`sheet` extends the drawer controller, so it supports the same Presence lifecycle, dynamic frame targets and stream-close
behavior with the `data-sheet-*` target names. Empty `update`/`replace` streams and `refresh` streams wait for actual exit
motion before rendering. The overlay contract is `data-state="closed" data-motion="default" hidden inert`.

Frame-backed `<hw:sheet>` marks its root with `data-hotwire-overlay-host` and assigns a concrete Frame id to each new
opening. The canonical root remains the sole owner of new-opening interception; generated shells reuse this controller,
focus handling and stack. Hand-written markup can opt in with the marker and a matching `dynamicContent` Frame.
See [frame-backed overlay instances](../components/frame-or-page.md#frame-backed-overlay-instances).

For a frame-backed sheet, a link can set `data-overlay-family="sheet" data-overlay-side="left"`
`data-overlay-size="28rem"` as a loading hint. The response `<turbo-frame>` may override either value before rendering;
side accepts `left`, `right`, `top`, `bottom`. A size alone uses the current side's width/height variable. Closing or
abandoning the request restores the authored side and size. See the
[Blade contract](../components/frame-or-page.md#presentation-per-frame-opening).

Escape dismissal and focus trapping are suspended during IME composition.

## Basic Usage

```html
<div
    data-controller="sheet"
    data-sheet-lock-scroll-class="overflow-hidden"
    data-sheet-initial-focus-value="auto"
    data-action="turbo:before-cache@window->sheet#closeForCache"
>
    <button type="button" data-sheet-target="trigger" data-action="sheet#open">
        Open sheet
    </button>

    <div data-sheet-target="modal" data-state="closed" data-motion="default" hidden inert>
        <div
            data-sheet-target="backdrop"
            data-action="click->sheet#clickOutside"
        ></div>

        <aside data-sheet-target="dialog" role="dialog" aria-modal="true" aria-labelledby="sheet-title" tabindex="-1">
            <h2 id="sheet-title">Details</h2>
            <button type="button" data-action="sheet#close">Close</button>
        </aside>
    </div>
</div>
```

The controller opens and closes like Drawer, locks body scroll when configured, returns focus to the trigger, and closes
immediately before Turbo caches the page.

## Targets And Values

Sheet uses the Drawer targets and values with the `sheet` identifier: `trigger`, `modal`, `backdrop`, `dialog`,
`dynamicContent`, `loadingTemplate`, `lockScroll`, `closeOnEscape`, `closeOnClickOutside`, and `initialFocus`.

`initialFocus` defaults to `auto`, which focuses an eligible `[autofocus]` element and otherwise the semantic dialog
surface. `dialog` skips `[autofocus]`; `first-focusable` selects the first eligible control; `none` leaves focus where it
is until Tab enters the trap. Invalid values behave as `auto`. Keep the dialog surface programmatically focusable with
`tabindex="-1"`. Initial focus runs once per opening and is not repeated by frame updates, reconnects, or nested-overlay
resume.
If `autofocus` is also mounted inside the overlay, whichever controller focuses last wins: `initialFocus` runs when the
sheet opens, while `autofocus` runs on connect and affected `turbo:frame-load` events.

## Actions

| Action | Description |
|--------|-------------|
| `sheet#open` | Open the sheet. |
| `sheet#close` | Close the sheet. |
| `sheet#toggle` | Toggle the sheet. |
| `sheet#clickOutside` | Close when the backdrop is clicked. |
| `sheet#closeForCache` | Close immediately before Turbo caches the page. |

## Events

| Event | Description |
|-------|-------------|
| `sheet:opened` | Dispatched after the open transition. |
| `sheet:closed` | Dispatched after the close transition. |
