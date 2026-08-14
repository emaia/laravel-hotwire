# `<hw:drawer>`

Base drawer overlay with direction-aware slide transitions, backdrop, focus trap, Escape dismissal and click-outside
dismissal.

## Basic Usage

```blade
<hw:drawer direction="down">
    <hw:drawer.trigger>
        Open
    </hw:drawer.trigger>

    <hw:drawer.content>
        <hw:drawer.header>
            <hw:drawer.title>Notifications</hw:drawer.title>
            <hw:drawer.description>Recent activity for your account.</hw:drawer.description>
        </hw:drawer.header>

        <div class="flex-1 overflow-y-auto p-4">
            Content
        </div>

        <hw:drawer.footer>
            <hw:drawer.close>Close</hw:drawer.close>
        </hw:drawer.footer>
    </hw:drawer.content>
</hw:drawer>
```

## Automatic Behavior

The drawer traps focus while open, restores focus to the trigger on close, locks body scroll by default and closes
synchronously before Turbo caches the page. Its overlay uses `data-state="open|closed"`; Presence waits for actual
finite CSS motion and cancels stale teardown when the drawer rapidly reopens. Customize transition duration in CSS.

Use `<hw:sheet>` instead when you want a side panel with an always-visible close button.

## Frame Content

Use `frame` when a shared drawer host should load server-rendered content through Turbo Frames:

```blade
{{-- layout --}}
<hw:drawer frame="drawer-panel" direction="down" view-transition>
    <x-slot:loading_template>
        <div class="p-6">Loading...</div>
    </x-slot:loading_template>
</hw:drawer>
```

```blade
<a href="{{ route('notifications.index') }}" data-turbo-frame="drawer-panel">
    Notifications
</a>
```

In the destination view, use [`<hw:frame-or-page>`](./frame-or-page.md):

```blade
<hw:frame-or-page frame="drawer-panel" layout="layouts.app">
    {{-- drawer content or standalone page content --}}
</hw:frame-or-page>
```

The drawer guarantees exactly one frame content host. If the slot has no `<hw:drawer.content>`, it appends an empty host
even alongside a trigger or plain slot content. One content host wraps the frame and uses its slot as fallback content;
more than one content host with `frame` is invalid. Frame objects resolve with `dom_id()`, while null, false, empty, and
whitespace-only values disable the host.

The root owns the matching frame id. Use one `<hw:drawer.content>` for fallback content instead of adding a raw
`<turbo-frame>` with the same id.

`view-transition` mounts `turbo--view-transition` on the internal frame host for both automatic and explicit
`drawer.content` markup. It does nothing without `frame`; unsupported browsers keep the normal Turbo render. Enable it
on the drawer host rather than only on the response's `<hw:frame-or-page>` because Turbo preserves the existing host and
does not copy attributes from the response frame.

The drawer opens when the frame receives content. Per-link `data-loading-template="#template-id"` overrides the drawer's
`loading_template` slot. Return an empty `update` or `replace` stream for the drawer root or frame id, or a `refresh`
stream, to close it after a successful action. Stream rendering waits for the actual exit motion to finish.

## Requirements

- No external dependencies.
- Ships with `_composition.js`, `_focus_trap.js`, `_frame_overlay.js`, `_overlay.js`, `_overlay_stack.js`,
  `_presence.js`, and `_top_layer.js`; publishing the `drawer` controller publishes these helpers too.

## Props

| Prop                  | Default                                                      | Description                                                                |
|-----------------------|--------------------------------------------------------------|----------------------------------------------------------------------------|
| `id`                  | auto                                                         | Root element id.                                                           |
| `direction`           | `down`                                                       | `up`, `right`, `down`, or `left`.                                          |
| `side`                | `null`                                                       | Legacy alias for `direction`; `top` maps to `up`, `bottom` maps to `down`. |
| `size`                | `75vw`/`24rem` for side drawers, `auto` for vertical drawers | CSS length assigned to the drawer width or height variable.                |
| `frame`               | `null`                                                       | String/object Turbo Frame id for layout-shared, server-loaded content.      |
| `backdrop`            | `true`                                                       | Render the backdrop and click-outside target.                              |
| `motion`              | `default`                                                    | `default` follows CSS motion; `none` disables it.                          |
| `lockScroll`          | `true`                                                       | Lock body scroll while open.                                               |
| `closeOnEscape`       | `true`                                                       | Close when Escape is pressed.                                              |
| `closeOnClickOutside` | `true`                                                       | Close when the backdrop is clicked.                                        |
| `viewTransition`      | `false`                                                      | Animate successive renders inside the frame host.                          |

## Components

| Component            | Description                                  |
|----------------------|----------------------------------------------|
| `drawer.trigger`     | Button that toggles the drawer.              |
| `drawer.content`     | Overlay, backdrop and sliding popup wrapper. |
| `drawer.header`      | Header region.                               |
| `drawer.title`       | Drawer title.                                |
| `drawer.description` | Drawer description text.                     |
| `drawer.footer`      | Footer actions region.                       |
| `drawer.close`       | Button that closes the drawer.               |

## Future Enhancements

Swipe gestures, nested drawers and snap points are planned as separate enhancements after the base drawer behavior is
stable.

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="drawer-overlay"`
- `data-slot="drawer-trigger"`
- `data-slot="drawer-backdrop"`
- `data-slot="drawer-popup"`
- `data-slot="drawer-content"`
- `data-slot="drawer-header"`
- `data-slot="drawer-title"`
- `data-slot="drawer-description"`
- `data-slot="drawer-footer"`
- `data-slot="drawer-close"`
- `data-slot="drawer"`
