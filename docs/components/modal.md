# Modal

The modal component displays accessible overlay dialogs with backdrop handling, focus trapping, animations, and optional
Turbo Frame integration.

For most applications, you will render a trigger and content inside a single `<hw:modal>` component. If you need to load
modal content from the server, you may use the frame-backed modal pattern described later in this document.

## Contents

- [Basic Usage](#basic-usage)
- [Opening a Modal](#opening-a-modal)
- [Closing a Modal](#closing-a-modal)
- [Dynamic Content with Turbo Frames](#dynamic-content-with-turbo-frames)
- [Loading Templates](#loading-templates)
- [Behavior](#behavior)
- [Props](#props)
- [Subcomponents](#subcomponents)
- [Size Presets](#size-presets)
- [Controller Reference](#controller-reference)
- [Accessibility](#accessibility)
- [Turbo Integration](#turbo-integration)

## Basic Usage

To display a modal, place a trigger and content inside the root `<hw:modal>` component. The trigger opens the modal, while
`<hw:modal.content>` contains the dialog surface that will be shown to the user:

```blade
<hw:modal>
    <hw:modal.trigger>
        Edit profile
    </hw:modal.trigger>

    <hw:modal.content>
        <hw:modal.header>
            <hw:modal.title>Edit profile</hw:modal.title>
            <hw:modal.description>
                Update your account details.
            </hw:modal.description>
        </hw:modal.header>

        <form class="grid gap-4">
            <hw:input name="name" label="Name" />
            <hw:input name="email" type="email" label="Email" />
        </form>

        <hw:modal.footer>
            <hw:modal.close>Cancel</hw:modal.close>
            <hw:button>Save changes</hw:button>
        </hw:modal.footer>
    </hw:modal.content>
</hw:modal>
```

This example is self-contained: the root component mounts the `modal` Stimulus controller, the trigger opens it, and the
content component renders the overlay, backdrop, dialog surface, and close button.

`modal.content` must render inside the same `modal` root. The root supplies the frame target, motion, size and
close-button context used by the dependent content. For Turbo Stream updates, replace the modal frame or render the
owning Modal root rather than rendering `modal.content` by itself. `modal.trigger` may render standalone; pass `frame` or
`data-turbo-frame` explicitly when it should target a layout-shared Modal frame.

## Opening a Modal

`<hw:modal.trigger>` renders a button-like control that opens the modal. It supports the same visual variants and sizes
as `<hw:button>`:

```blade
<hw:modal.trigger variant="outline" size="sm">
    Edit
</hw:modal.trigger>
```

You may also render the trigger as a link. A local link trigger opens the existing modal content immediately and cancels
its `href` navigation:

```blade
<hw:modal.trigger as="a" href="/users/1/edit">
    Edit user
</hw:modal.trigger>
```

When the modal is backed by a Turbo Frame, anchor triggers inherit the modal's frame target unless you provide a local
`frame` value or an explicit `data-turbo-frame` attribute.

| Prop      | Type                     | Default     | Description                                                                                  |
|-----------|--------------------------|-------------|----------------------------------------------------------------------------------------------|
| `variant` | `string`                 | `'default'` | Button variant                                                                               |
| `size`    | `string`                 | `'default'` | Button size                                                                                  |
| `as`      | `button\|a`             | `'button'`  | Rendered element                                                                             |
| `type`    | `button\|submit\|reset` | `'button'`  | Native type when `as="button"`                                                             |
| `frame`   | `string\|object\|false\|null` | inherited | Turbo Frame target for an anchor trigger; overrides the modal target when set |

The `as` prop is trimmed, lowercased, and restricted to `button` or `a`; unsupported values are rejected. Disabled
anchors omit `href` and receive `aria-disabled="true"` and `tabindex="-1"`.

## Closing a Modal

The X close icon is shown by default. You may hide it with `:close-button="false"`:

```blade
<hw:modal :close-button="false">
    <hw:modal.trigger>Open</hw:modal.trigger>

    <hw:modal.content>
        This modal does not render the X close icon.
    </hw:modal.content>
</hw:modal>
```

Use `<hw:modal.close>` for semantic footer or inline close actions:

```blade
<hw:modal.footer>
    <hw:modal.close variant="outline">Cancel</hw:modal.close>
    <hw:button>Save changes</hw:button>
</hw:modal.footer>
```

`modal.close` supports `variant`, `size`, `as`, and `type` with the same defaults as `modal.trigger`, except `variant`
defaults to `outline`. It uses the same `button|a` allowlist, native button type validation, and disabled-anchor behavior.

## Dynamic Content with Turbo Frames

For an application layout that should reuse one modal shell for many links, provide a `frame` value. Laravel Hotwire will
render a single Turbo Frame host inside the modal content:

```blade
<a href="/posts/1/edit" data-turbo-frame="modal">
    Edit post
</a>

<hw:modal id="modal-shell" frame="modal" view-transition>
    <x-slot:loading_template>
        <div class="flex items-center justify-center p-8">
            Loading...
        </div>
    </x-slot:loading_template>
</hw:modal>
```

When the frame receives content, the modal opens automatically. A successful action may close the modal by returning an
empty `update` or `replace` stream for the frame id, or by returning a `refresh` stream:

```php
return turbo_stream()->update('modal');
```

If the slot does not contain `<hw:modal.content>`, an empty content host is appended automatically. If the slot contains
one content host, that host wraps the frame and its slot becomes fallback content. More than one content host with
`frame` is invalid.

The root component owns the matching frame id. Do not place a raw `<turbo-frame>` with that id in the slot; use one
`<hw:modal.content>` when fallback content is needed.

The frame host rendered by the component looks like this:

```html
<turbo-frame
    id="modal"
    data-controller="turbo--view-transition"
    data-turbo--view-transition-skip-initial-value="true"
    data-modal-target="dynamicContent"
></turbo-frame>
```

`view-transition` mounts `turbo--view-transition` directly on the persistent frame host. It animates navigations after
the modal is open; the initial overlay opening and closing continue to use the modal's own motion. The prop has no effect
without `frame`, and browsers without the View Transitions API keep the standard Turbo render.

Enable the option on the modal host, not only on a `<hw:frame-or-page view-transition>` response. Turbo preserves the
existing host during a frame render and replaces its children, so attributes from the response frame are not copied to the
host.

`frame` accepts strings or objects resolved with `dom_id()`; null, false, empty, and whitespace-only values disable the
frame host.

An anchor `<hw:modal.trigger>` inside a frame-backed modal inherits the host frame as `data-turbo-frame`. Turbo navigates
into the host; the modal opens when a loading template or the response content reaches the frame. A local anchor trigger
without a frame opens the existing modal content immediately and cancels its `href` navigation. A local `frame` value
overrides the inherited target. An explicit native `data-turbo-frame` wins; binding it to `false` suppresses the inherited
target and makes the trigger local.

## Loading Templates

The `loading_template` slot lives on the root modal and is used while dynamic frame content is loading.

The resolution order is: per-link `data-loading-template`, the modal's `loading_template` slot, then no loading template.

```blade
<a
    href="/posts/1/edit"
    data-turbo-frame="modal"
    data-loading-template="#form-skeleton"
>
    Edit post
</a>

<template id="form-skeleton">
    <div class="grid gap-3 p-4">
        <hw:skeleton class="h-6 w-1/3" />
        <hw:skeleton class="h-32 w-full" />
    </div>
</template>
```

## Behavior

Arbitrary attributes are forwarded to the root modal element. Regular `data-controller` / `data-action` attributes and
the `stimulus` prop are merged and deduplicated with the internal `modal` controller. Component-owned `data-modal-*`
attributes are protected; configure supported behavior with props instead.

```blade
<hw:modal
    aria-labelledby="edit-post-title"
    data-test-id="edit-post-modal"
    data-controller="analytics"
    data-action="modal:opened->analytics#track"
>
    ...
</hw:modal>
```

The overlay uses `data-state="open|closed"`, `data-motion="default|none"`, `hidden`, and `inert`. Presence observes the
actual finite CSS motion on the backdrop and dialog, so custom timing belongs in CSS rather than a duration prop. During
exit the overlay becomes closed and inert immediately, remains rendered until motion settles, and can be reopened without
stale teardown. Reduced-motion preference skips the wait.

## Props

| Prop              | Type                         | Default            | Description                                                                         |
|-------------------|------------------------------|--------------------|-------------------------------------------------------------------------------------|
| `id`              | `string\|object`             | generated          | Root id. Pass a model for a [stable cross-request id](../recipes/stable-component-ids.md). |
| `size`            | `string`                     | `'md'`             | Preset (`sm`/`md`/`lg`/`xl`/`full`/`auto`) or arbitrary width (`800px`, `60vw`)     |
| `class`           | `string`                     | `''`               | Additional CSS classes on the panel                                                 |
| `close-button`    | `bool`                       | `true`             | Shows the X close icon                                                              |
| `fixed-top`       | `bool`                       | `false`            | Pins the modal to the top with a margin (ignored when `size="full"`)              |
| `frame`           | `string\|object\|false\|null` | `null`             | Enables dynamic modal content using a Turbo Frame. Objects are resolved with `dom_id()` |
| `motion`          | `string`                     | `'default'`        | `default` follows CSS motion; `none` disables it                                    |
| `stimulus`        | `Htmlable\|null`            | `null`             | Additional Stimulus attributes to merge onto the root element                       |
| `view-transition` | `bool`                       | `false`            | Animates successive renders inside the frame host with the View Transitions API     |

## Subcomponents

| Component           | Description                               |
|---------------------|-------------------------------------------|
| `modal.trigger`     | Button-like control that opens the modal  |
| `modal.content`     | Overlay, backdrop and dialog content      |
| `modal.header`      | Header layout inside content              |
| `modal.title`       | Modal title                               |
| `modal.description` | Supporting text                           |
| `modal.footer`      | Footer action layout                      |
| `modal.close`       | Button-like control that closes the modal |

## Size Presets

All presets except `auto` apply `w-full` plus a fixed `max-w-*` cap, so the dialog fills the available width up to the
preset's cap.

| `size`         | Width                 | px cap (md+) | Height                       |
|----------------|-----------------------|--------------|------------------------------|
| `sm`           | `w-full md:max-w-md`  | 448          | auto                         |
| `md` (default) | `w-full md:max-w-xl`  | 576          | auto                         |
| `lg`           | `w-full md:max-w-3xl` | 768          | auto                         |
| `xl`           | `w-full md:max-w-5xl` | 1024         | auto                         |
| `full`         | `w-full`              | n/a          | `h-full` within viewport pad |
| `auto`         | No cap, no `w-full`   | n/a          | auto                         |

Pass an arbitrary size to set an inline max width on the dialog positioner:

```blade
<hw:modal size="800px">...</hw:modal>
<hw:modal size="60vw">...</hw:modal>
```

## Controller Reference

### Values

| Value                    | Type      | Default | Description                            |
|--------------------------|-----------|---------|----------------------------------------|
| `lock-scroll`            | `Boolean` | `true`  | Locks body scroll when open            |
| `close-on-escape`        | `Boolean` | `true`  | Closes on Escape key                   |
| `close-on-click-outside` | `Boolean` | `true`  | Closes when clicking outside the modal |

### Actions

| Action                | Description                                       |
|-----------------------|---------------------------------------------------|
| `modal#open`          | Opens the modal                                   |
| `modal#close`         | Closes the modal                                  |
| `modal#closeForCache` | Closes synchronously before Turbo caches the page |

### Events

| Event          | Description                                 |
|----------------|---------------------------------------------|
| `modal:opened` | Fired after the opening animation completes |
| `modal:closed` | Fired after the closing animation completes |

## Accessibility

- `role="dialog"` and `aria-modal="true"` on the overlay.
- Focus is trapped inside the modal while it is open.
- Focus returns to the element that triggered the modal on close.
- Escape closes the modal by default and may be configured through the controller value.

## Turbo Integration

The modal closes automatically on `turbo:before-cache`, preventing ghost modals when navigating with Turbo Drive.

For modals driven by a Turbo Frame, clearing the frame closes them via the content observer:

```php
return turbo_stream()->update('modal');
```

## Requirements

- No external dependencies.
- Ships with `_composition.js`, `_focus_trap.js`, `_frame_overlay.js`, `_overlay.js`, `_overlay_stack.js`,
  `_presence.js`, and `_top_layer.js`; publishing the `modal` controller publishes these helpers too.

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="modal-overlay"`
- `data-slot="modal-trigger"`
- `data-slot="modal-backdrop"`
- `data-slot="modal-positioner"`
- `data-slot="modal-panel"`
- `data-slot="modal-content"`
- `data-slot="modal-header"`
- `data-slot="modal-title"`
- `data-slot="modal-description"`
- `data-slot="modal-footer"`
- `data-slot="modal-close"`
- `data-slot="modal-close-icon"`
- `data-slot="modal"`
