# `drawer` controller

Drives the [`<x-hwc::drawer>`](../components/drawer.md) component — an off-canvas drawer with backdrop, focus trap,
Escape and click-outside dismissal. Direction-agnostic: the controller only toggles class lists, so the same code
animates left/right/top/bottom drawers (the component picks the right transform classes per direction). Standalone-
friendly — any markup that exposes the right targets and classes can mount it directly without the component.

## Targets

| Target      | Required | Purpose                                                              |
|-------------|----------|----------------------------------------------------------------------|
| `container` | yes      | Full-viewport overlay. Toggled via the `hidden` attribute + classes  |
| `panel`     | yes      | The sliding panel. FocusTrap activates on it while open              |
| `backdrop`  | no       | Dimmed backdrop. Wire its `click` to `drawer#clickOutside`           |

## Values

| Value                  | Type    | Default | Description                                                       |
|------------------------|---------|---------|-------------------------------------------------------------------|
| `openDuration`         | Number  | `300`   | ms — matches the panel/backdrop CSS transition duration on open   |
| `closeDuration`        | Number  | `300`   | ms — matches the close transition; controls when the events fire  |
| `lockScroll`           | Boolean | `true`  | Adds the `lockScroll` class to `<body>` while open                |
| `closeOnEscape`        | Boolean | `true`  | Escape key closes the drawer                                      |
| `closeOnClickOutside`  | Boolean | `true`  | Clicking the backdrop closes the drawer                           |

## Classes

The controller applies class lists on open/close — no inline styles. Animate by setting these to whatever Tailwind /
CSS transitions you want; the duration values above just tell the controller when to fire the `:opened`/`:closed`
events.

| Class set          | Applied to     | When                                                |
|--------------------|----------------|-----------------------------------------------------|
| `hidden`           | `container`    | While closed (e.g. `opacity-0 pointer-events-none`) |
| `visible`          | `container`    | While open (e.g. `opacity-100 pointer-events-auto`) |
| `backdropHidden`   | `backdrop`     | While closed (e.g. `opacity-0`)                     |
| `backdropVisible`  | `backdrop`     | While open (e.g. `opacity-100`)                     |
| `panelHidden`      | `panel`        | While closed — direction-specific transform: `-translate-x-full` (left), `translate-x-full` (right), `-translate-y-full` (top), `translate-y-full` (bottom) |
| `panelVisible`     | `panel`        | While open — `translate-x-0` (left/right) or `translate-y-0` (top/bottom) |
| `lockScroll`       | `<body>`       | While open, when `lockScroll` value is true         |

## Actions

| Action          | Signature           | Effect                                                                            |
|-----------------|---------------------|-----------------------------------------------------------------------------------|
| `open`          | `(event?)`          | Opens the drawer. Captures `event.currentTarget` (or active element) as the trigger to focus on close. Ignores ctrl/meta/shift + non-primary mouse buttons |
| `close`         | `()`                | Closes with the close transition. Restores focus to the captured trigger          |
| `toggle`        | `(event?)`          | Opens if closed, closes if open                                                   |
| `clickOutside`  | `(event)`           | Wired on `backdrop` — closes when `closeOnClickOutside` is true                   |
| `closeForCache` | `()`                | Synchronous, no-transition close. Wired to `turbo:before-cache@window` so Turbo's snapshot captures a fully closed state |

## Events

Dispatched on the controller's root element via `this.dispatch(…)`. Both bubble.

| Event            | Detail            | When                                |
|------------------|-------------------|-------------------------------------|
| `drawer:opened`  | `{ controller }`  | After the open transition completes |
| `drawer:closed`  | `{ controller }`  | After the close transition completes |

## Lifecycle

- `connect()` — registers the Escape keydown in capture phase (so it runs before bubble-phase peers like the dropdown
  controller and can stop them with `stopImmediatePropagation` while open) and creates the FocusTrap on `panelTarget`
- `disconnect()` — removes the Escape listener, clears pending transition timers, hard-closes if open (no animation,
  no focus restore — the element is leaving the DOM)
- `closeForCache()` — used for `turbo:before-cache`. Hard-closes without animation or focus restore so Turbo's
  snapshot is clean

## Standalone markup

If you don't want the Blade component, mount the controller directly. Pick the transform classes for the direction:

```html
<!-- Bottom-sheet pattern: vertical translate -->
<div data-controller="drawer"
     data-drawer-hidden-class="opacity-0 pointer-events-none"
     data-drawer-visible-class="opacity-100 pointer-events-auto"
     data-drawer-backdrop-hidden-class="opacity-0"
     data-drawer-backdrop-visible-class="opacity-100"
     data-drawer-panel-hidden-class="translate-y-full"
     data-drawer-panel-visible-class="translate-y-0"
     data-drawer-lock-scroll-class="overflow-hidden"
     data-action="turbo:before-cache@window->drawer#closeForCache">

    <button data-action="drawer#open">Open</button>

    <div data-drawer-target="container"
         class="fixed inset-0 z-50 opacity-0 pointer-events-none transition-opacity duration-300"
         role="dialog" aria-modal="true" hidden>

        <div data-drawer-target="backdrop"
             data-action="click->drawer#clickOutside"
             class="absolute inset-0 bg-slate-600/80 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>

        <div data-drawer-target="panel"
             style="height: 50vh"
             class="fixed inset-x-0 bottom-0 translate-y-full bg-white shadow-xl transition-transform duration-300">
            <button data-action="drawer#close">Close</button>
            <!-- panel content -->
        </div>
    </div>
</div>
```

For left/right drawers, swap to `-translate-x-full` / `translate-x-full` (hidden) ↔ `translate-x-0` (visible), and
use `inset-y-0 left-0` / `inset-y-0 right-0` with `style="width: …"`.

## Co-existence with other controllers

The controller is designed to stack on the same element as other Stimulus controllers (analytics, custom hooks):

- Only reads/writes its own targets and `this.element`
- Cleans up every listener and timer in `disconnect()` so re-renders/morphs don't leave duplicates behind
- Escape uses `stopImmediatePropagation` only when the drawer is open, so it doesn't interfere with other Escape
  handlers (e.g. a peer dropdown) when closed

## See also

- [`<x-hwc::drawer>`](../components/drawer.md) — Blade component that ships this controller pre-wired
- [`modal`](modal.md) — the centered-overlay sibling with similar semantics
