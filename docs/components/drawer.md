# `<x-hwc::drawer>`

Off-canvas drawer with backdrop, focus trap, Escape and click-outside dismissal. Slides in from any of the four
edges of the viewport (left by default, right, top or bottom). Pairs the `drawer` Stimulus controller with a
Blade-rendered panel.

Common uses: mobile navigation menu, shopping cart, notifications drawer, contextual settings sidebar, bottom-sheet
on small viewports.

## Quick start

```blade
<x-hwc::drawer>
    <x-slot:trigger>
        <button type="button" data-action="drawer#open" class="lg:hidden">
            Open menu
        </button>
    </x-slot:trigger>

    <nav class="p-6">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('settings') }}">Settings</a>
    </nav>
</x-hwc::drawer>
```

The trigger lives next to the panel; click it and the drawer slides in. Escape, the backdrop click, and the built-in
close button dismiss it. Focus is trapped inside the panel while open and returns to the trigger on close.

## Props

| Prop          | Type                                  | Default     | Description                                                            |
|---------------|---------------------------------------|-------------|------------------------------------------------------------------------|
| `id`          | `string`                              | auto        | Root element id. Auto-generated as `drawer-{uniqid}` when empty        |
| `position`    | `'left' \| 'right' \| 'top' \| 'bottom'` | `'left'` | Which edge the panel slides in from. Throws on any other value         |
| `size`        | `string`                              | `'320px'`   | Any CSS length — applied as `width` for left/right, `height` for top/bottom |
| `class`       | `string`                              | `''`        | Extra Tailwind/CSS classes appended to the panel                       |
| `backdrop`    | `bool`                                | `true`      | Renders the dimmed backdrop (and the click-to-close target it carries) |
| `closeButton` | `bool`                                | `true`      | Renders the built-in close button (×) at the top-right of the panel    |

## Slots

| Slot      | Required | Purpose                                                                                            |
|-----------|----------|----------------------------------------------------------------------------------------------------|
| default   | yes      | The panel's content. Anything inside renders inside `.hwc-drawer-panel`                            |
| `trigger` | no       | Sits outside the dialog. Use this for the open button so it shares the same component scope        |

## Direction examples

```blade
{{-- Left drawer (default) — sidebar / nav pattern --}}
<x-hwc::drawer>...</x-hwc::drawer>

{{-- Right drawer — cart / notifications pattern --}}
<x-hwc::drawer position="right">...</x-hwc::drawer>

{{-- Top drawer — notification banner / command palette --}}
<x-hwc::drawer position="top" size="40vh">...</x-hwc::drawer>

{{-- Bottom drawer — mobile bottom-sheet pattern --}}
<x-hwc::drawer position="bottom" size="50vh">...</x-hwc::drawer>

{{-- Custom width with CSS expressions --}}
<x-hwc::drawer size="min(480px, 100vw)">...</x-hwc::drawer>
```

The `size` prop's axis follows the direction: `width` for left/right, `height` for top/bottom. Same input, the
component infers which dimension to apply.

## Styling — `hwc-*` hooks

The component emits stable class hooks alongside its Tailwind defaults so you can override visuals app-side without
fighting utility classes:

- `.hwc-drawer` — root element (carries `data-controller="drawer"`)
- `.hwc-drawer-container` — full-viewport overlay (positioning, z-index)
- `.hwc-drawer-backdrop` — dimmed backdrop
- `.hwc-drawer-panel` — the sliding panel itself
- `.hwc-drawer-close` — the built-in close button

Example: swap the panel surface to your sidebar theme without redefining transitions or layout:

```css
.hwc-drawer-panel    { @apply bg-sidebar text-sidebar-foreground; }
.hwc-drawer-backdrop { @apply bg-black/60; }
```

## Triggering open/close

The component exposes three Stimulus actions on the `drawer` controller. Wire them via `data-action`:

| Action          | Effect                                                                |
|-----------------|-----------------------------------------------------------------------|
| `drawer#open`   | Opens the drawer. Tracks the clicked element for focus return on close|
| `drawer#close`  | Closes with the configured close transition                           |
| `drawer#toggle` | Opens if closed, closes if open                                       |

The built-in close button and backdrop already wire themselves. External triggers (whether in the `trigger` slot or
elsewhere on the page that scopes the same controller) need the `data-action`:

```blade
<x-hwc::drawer>
    <x-slot:trigger>
        <button data-action="drawer#toggle">≡</button>
    </x-slot:trigger>
    ...
</x-hwc::drawer>
```

## Events

The controller dispatches Stimulus-style custom events on the root element. Use them for analytics, focus management,
or to coordinate with other controllers:

| Event            | Detail            | When                                       |
|------------------|-------------------|--------------------------------------------|
| `drawer:opened`  | `{ controller }`  | After the open transition completes        |
| `drawer:closed`  | `{ controller }`  | After the close transition completes       |

## Accessibility

- `role="dialog"` and `aria-modal="true"` on the container
- Focus is trapped inside the panel while open (Tab/Shift+Tab cycle within)
- Escape closes the drawer (in capture phase, so peer document-level Escape listeners — e.g. an enclosing dropdown — do
  not also fire)
- Click on the backdrop closes the drawer; click anywhere inside the panel does not
- On close, focus returns to the element that opened the drawer
- The built-in close button carries `aria-label="Close"` and is reachable via the focus trap

## Coordination with Turbo

- `data-action="turbo:before-cache@window->drawer#closeForCache"` is wired automatically, so the drawer is in a
  fully closed state when Turbo snapshots the page. Navigating back never restores a half-open frame
- Document-level Escape and the backdrop click do not propagate to the rest of the page while the drawer is open

## Controller stacking

Apps can add their own controllers on the same root via `data-controller="…"`; they are unioned with `drawer`. The
component filters `data-drawer-*` attributes the app might pass in, since those control internal animation classes
and would conflict with the controller.

```blade
<x-hwc::drawer data-controller="analytics">
    ...
</x-hwc::drawer>
{{-- renders: data-controller="drawer analytics" --}}
```

## See also

- [`drawer` controller](../controllers/drawer.md) — full controller API: values, classes, events, methods
- [`<x-hwc::modal>`](modal.md) — when the overlay is centered rather than off-canvas
- [`<x-hwc::dropdown>`](dropdown.md) — for lightweight menus that don't need a backdrop or focus trap
