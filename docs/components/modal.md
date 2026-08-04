# Modal

Accessible modal with backdrop, animations, focus trap and Turbo integration.

## Basic usage

```blade
<hw:modal>
    <hw:modal.trigger>
        Open modal
    </hw:modal.trigger>

    <hw:modal.content>
        <hw:modal.header>
            <hw:modal.title>Edit profile</hw:modal.title>
            <hw:modal.description>
                Update your account details.
            </hw:modal.description>
        </hw:modal.header>

        <form class="grid gap-4">
            ...
        </form>

        <hw:modal.footer>
            <hw:modal.close>Cancel</hw:modal.close>
            <hw:button>Save</hw:button>
        </hw:modal.footer>
    </hw:modal.content>
</hw:modal>
```

`<hw:modal>` owns the Stimulus controller and configuration. `<hw:modal.content>` renders the overlay, backdrop, dialog
surface and optional close icon exactly where it is placed.

## Trigger

`<hw:modal.trigger>` renders a button that opens the modal. It supports the same visual variants and sizes as
`<hw:button>`.

```blade
<hw:modal.trigger variant="outline" size="sm">
    Edit
</hw:modal.trigger>

<hw:modal.trigger as="a" href="/users/1/edit">
    Edit user
</hw:modal.trigger>
```

| Prop      | Type                     | Default     | Description                         |
|-----------|--------------------------|-------------|-------------------------------------|
| `variant` | `string`                 | `'default'` | Button variant                      |
| `size`    | `string`                 | `'default'` | Button size                         |
| `as`      | `button\|a`             | `'button'`  | Rendered element                    |
| `type`    | `button\|submit\|reset` | `'button'`  | Native type when `as="button"`     |
| `frame`   | `string\|object\|false\|null` | inherited | Turbo Frame target for an anchor trigger; overrides the modal target when set |

`as` is trimmed, lowercased, and restricted to `button` or `a`; unsupported values are rejected. Disabled anchors omit
`href` and receive `aria-disabled="true"` and `tabindex="-1"`.

## Close Actions

The X close icon is shown by default. Hide it with `:close-button="false"`.

```blade
<hw:modal :close-button="false">
    <hw:modal.trigger>Open</hw:modal.trigger>
    <hw:modal.content>Modal without the X close icon.</hw:modal.content>
</hw:modal>
```

Use `<hw:modal.close>` for semantic footer or inline close actions.

```blade
<hw:modal.footer>
    <hw:modal.close variant="outline">Cancel</hw:modal.close>
    <hw:button>Save</hw:button>
</hw:modal.footer>
```

`modal.close` supports `variant`, `size`, `as` and `type` with the same defaults as `modal.trigger`, except `variant`
defaults to `outline`. It uses the same `button|a` allowlist, native button type validation, and disabled-anchor behavior.

## Props

| Prop           | Type             | Default            | Description                                                                     |
|----------------|------------------|--------------------|---------------------------------------------------------------------------------|
| `id`           | `string`         | `uniqid('modal-')` | Root element ID                                                                 |
| `size`         | `string`         | `'md'`             | Preset (`sm`/`md`/`lg`/`xl`/`full`/`auto`) or arbitrary width (`800px`, `60vw`) |
| `class`        | `string`         | `''`               | Additional CSS classes on the panel                                             |
| `close-button` | `bool`           | `true`             | Shows the X close icon                                                          |
| `fixed-top`    | `bool`           | `false`            | Pins the modal to the top with a margin (ignored when `size="full"`)            |
| `frame`        | `string\|object\|false\|null` | `null` | Renders one Turbo Frame dynamic content target; objects use `dom_id()`          |
| `motion`       | `string`         | `'default'`        | `default` follows CSS motion; `none` disables it                                |
| `stimulus`     | `Htmlable\|null` | `null`             | Optional extra Stimulus binding merged into the root element                    |
| `view-transition` | `bool`       | `false`            | Animate successive renders inside the frame host with the View Transitions API  |

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

## Size presets

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

## Dynamic content with Turbo Frames

For a global modal shell in a layout, provide `frame`. The modal guarantees exactly one frame content host. If the slot
does not contain `<hw:modal.content>`, it appends an empty content host even when the slot contains a trigger or plain
markup. If the slot contains one content host, that host wraps the frame and its slot becomes fallback content. More than
one content host with `frame` is invalid.

The root component owns the matching frame id. Do not place a raw `<turbo-frame>` with that id in the slot; use one
`<hw:modal.content>` when fallback content is needed.

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

This renders a frame inside the modal content:

```html

<turbo-frame
    id="modal"
    data-controller="turbo--view-transition"
    data-turbo--view-transition-skip-initial-value="true"
    data-modal-target="dynamicContent"
></turbo-frame>
```

When the Turbo Frame receives content, the modal opens automatically. Return an empty `update` or `replace` stream for
the frame id, or a `refresh` stream, to close it after a successful action.

`view-transition` mounts `turbo--view-transition` directly on the persistent frame host. It animates navigations after
the modal is open; the initial overlay opening and closing continue to use the modal's own motion. The prop has no effect
without `frame`, and browsers without the View Transitions API keep the standard Turbo render.

Enable the option on the modal host, not only on a `<hw:frame-or-page view-transition>` response. Turbo preserves the
existing host during a frame render and replaces its children, so attributes from the response frame are not copied to
the host.

`frame` accepts strings or objects resolved with `dom_id()`; null, false, empty, and whitespace-only values disable the
frame host.

An anchor `<hw:modal.trigger>` inside a frame-backed modal inherits the host frame as `data-turbo-frame`. Turbo navigates
into the host; the modal opens when a loading template or the response content reaches the frame. A local anchor trigger
without a frame opens the existing modal content immediately and cancels its `href` navigation. A local `frame` value
overrides the inherited target. An explicit native `data-turbo-frame` wins; binding it to `false` suppresses the inherited
target and makes the trigger local.

## Loading template

The `loading_template` slot lives on the root modal and is used while dynamic frame content is loading.

Resolution order: per-link `data-loading-template` -> modal's `loading_template` slot -> no loading template.

```blade
<a href="/posts/1/edit"
   data-turbo-frame="modal"
   data-loading-template="#form-skeleton">
    Edit post
</a>

<template id="form-skeleton">
    <div class="grid gap-3 p-4">
        <hw:skeleton class="h-6 w-1/3" />
        <hw:skeleton class="h-32 w-full" />
    </div>
</template>
```

## Root attributes

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

## Stimulus values

| Value                    | Type      | Default | Description                            |
|--------------------------|-----------|---------|----------------------------------------|
| `lock-scroll`            | `Boolean` | `true`  | Locks body scroll when open            |
| `close-on-escape`        | `Boolean` | `true`  | Closes on Escape key                   |
| `close-on-click-outside` | `Boolean` | `true`  | Closes when clicking outside the modal |

## Actions

| Action                | Description                                       |
|-----------------------|---------------------------------------------------|
| `modal#open`          | Opens the modal                                   |
| `modal#close`         | Closes the modal                                  |
| `modal#closeForCache` | Closes synchronously before Turbo caches the page |

## Events

| Event          | Description                                 |
|----------------|---------------------------------------------|
| `modal:opened` | Fired after the opening animation completes |
| `modal:closed` | Fired after the closing animation completes |

The overlay uses `data-state="open|closed"`, `data-motion="default|none"`, `hidden`, and `inert`. Presence observes the
actual finite CSS motion on the backdrop and dialog, so custom timing belongs in CSS rather than a duration prop. During
exit the overlay becomes closed and inert immediately, remains rendered until motion settles, and can be reopened
without stale teardown. Reduced-motion preference skips the wait.

## Accessibility

- `role="dialog"` and `aria-modal="true"` on the overlay
- Focus trap: Tab/Shift+Tab cycle through focusable elements inside the modal
- Focus returns to the element that triggered the modal on close
- Closes on Escape, configurable through the controller value

## Turbo integration

The modal closes automatically on `turbo:before-cache`, preventing ghost modals when navigating with Turbo Drive.

For modals driven by a Turbo Frame, clearing the frame closes them via the content observer:

```php
return turbo_stream()->update('modal');
```
