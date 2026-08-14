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

        <aside data-drawer-target="dialog" role="dialog" aria-modal="true">
            <h2>Navigation</h2>
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
