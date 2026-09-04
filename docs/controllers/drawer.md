# Drawer

Controls an off-canvas drawer overlay with Presence motion, focus trapping, scroll locking, Escape dismissal, and optional
Turbo Frame-driven dynamic content.

**Identifier:** `drawer`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers drawer`.

## Requirements

- No external dependencies.
- Turbo is optional and only needed when using `dynamicContent` with a Turbo Frame.

Escape dismissal and focus trapping are suspended during IME composition.

## Basic Usage

```html
<div
    data-controller="drawer"
    data-drawer-lock-scroll-class="overflow-hidden"
    data-drawer-initial-focus-value="auto"
    data-action="turbo:before-cache@window->drawer#closeForCache"
>
    <button type="button" data-drawer-target="trigger" data-action="drawer#open">
        Open drawer
    </button>

    <div data-drawer-target="modal" data-state="closed" data-motion="default" hidden inert>
        <div
            data-drawer-target="backdrop"
            data-action="click->drawer#clickOutside"
        ></div>

        <aside data-drawer-target="dialog" role="dialog" aria-modal="true" aria-labelledby="drawer-title" tabindex="-1">
            <h2 id="drawer-title">Navigation</h2>
            <button type="button" data-action="drawer#close">Close</button>
        </aside>
    </div>
</div>
```

The `modal` target must start as `data-state="closed" data-motion="default" hidden inert`. Presence derives lifecycle
completion from CSS motion on `backdrop` and `dialog`; `data-motion="none"` and reduced motion skip it. Use direct-child
state selectors when drawers can be nested.

## Dynamic Frame Behavior

When `dynamicContent` is present, the controller opens the drawer after the frame receives content and clears the frame
after close. It injects `loadingTemplate` during `turbo:before-fetch-request`, supports per-link `data-loading-template`,
and delays empty `update`/`replace` streams for the drawer root or frame, plus `refresh` streams, until the close animation
finishes.

## Targets

| Target | Description |
|--------|-------------|
| `trigger` | Optional trigger used for focus return. |
| `modal` | Full-screen overlay container. |
| `backdrop` | Backdrop click target. |
| `dialog` | Sliding drawer panel. |
| `dynamicContent` | Optional Turbo Frame that opens the drawer when content loads. |
| `loadingTemplate` | Optional loading template used while the dynamic frame fetches. |

## Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `lockScroll` | `boolean` | `true` | Lock body scroll while open. |
| `closeOnEscape` | `boolean` | `true` | Close on Escape. |
| `closeOnClickOutside` | `boolean` | `true` | Close on backdrop click. |
| `initialFocus` | `string` | `auto` | `auto`, `dialog`, `first-focusable`, or `none`. |

`auto` focuses an eligible `[autofocus]` element and otherwise the semantic dialog surface. `dialog` skips
`[autofocus]`; `first-focusable` selects the first eligible control; `none` leaves focus where it is until Tab enters the
trap. Invalid values behave as `auto`. Keep the dialog surface programmatically focusable with `tabindex="-1"`. Initial
focus runs once per opening and is not repeated by frame updates, reconnects, or nested-overlay resume.
If `autofocus` is also mounted inside the overlay, whichever controller focuses last wins: `initialFocus` runs when the
drawer opens, while `autofocus` runs on connect and affected `turbo:frame-load` events.

## Actions

| Action | Description |
|--------|-------------|
| `open` | Open the drawer. |
| `close` | Close the drawer. |
| `toggle` | Toggle the drawer. |
| `clickOutside` | Close from the backdrop when enabled. |
| `closeForCache` | Close immediately before Turbo caches the page. |

## Events

| Event | When |
|-------|------|
| `drawer:opened` | After the open transition completes. |
| `drawer:closed` | After the close transition completes. |
