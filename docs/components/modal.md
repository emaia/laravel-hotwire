# Modal

Accessible modal with backdrop, animations, focus trap and Turbo integration.

## Basic usage

```html

<x-hwc::modal>
    <x-slot:trigger>
        <button data-action="modal#open" type="button">Open modal</button>
    </x-slot:trigger>

    <div class="p-6">
        <h2>Title</h2>
        <p>Modal content.</p>
    </div>
</x-hwc::modal>
```

## With a close button

The X button is shown by default (`close-button` is `true`). To hide it:

```html

<x-hwc::modal :close-button="false">
    <x-slot:trigger>
        <button data-action="modal#open" type="button">Open</button>
    </x-slot:trigger>

    <p class="p-6">Modal without the X button.</p>
</x-hwc::modal>
```

## Props

| Prop                   | Type      | Default            | Description                                     |
|------------------------|-----------|--------------------|-------------------------------------------------|
| `id`                   | `string`  | `uniqid('modal-')` | Root element ID                                 |
| `size`                 | `string`  | `'md'`             | Preset (`sm`/`md`/`lg`/`xl`/`full`/`auto`) or arbitrary width (`800px`, `60vw`) |
| `class`                | `string`  | `''`               | Additional CSS classes on the content container |
| `close-button`         | `bool`    | `true`             | Shows X button to close                         |
| `fixed-top`            | `bool`    | `false`            | Pins the modal to the top with a margin (ignored when `size="full"`) |
| `frame`                | `?string` | `null`             | Renders a Turbo Frame dynamic content target    |
| `prevent-reopen-delay` | `int`     | `1000`             | Delay (ms) before allowing reopen after closing |

### Size presets

All presets except `auto` apply `w-full` plus a fixed `max-w-*` cap, so the dialog fills the available
width up to the preset's cap. The caps follow a monotonically increasing scale — `sm < md < lg < xl` at
**any** viewport — so the choice is predictable regardless of screen size or browser zoom.

| `size`         | Width                                | px cap (md+) | Height                          | Notes                                   |
|----------------|--------------------------------------|--------------|---------------------------------|-----------------------------------------|
| `sm`           | `w-full md:max-w-md`                 | 448          | auto                            | Compact dialogs                         |
| `md` (default) | `w-full md:max-w-xl`                 | 576          | auto                            | Standard form/dialog size               |
| `lg`           | `w-full md:max-w-3xl`                | 768          | auto                            | Forms with multiple fields              |
| `xl`           | `w-full md:max-w-5xl`                | 1024         | auto                            | Wide content (tables, dashboards)       |
| `full`         | `w-full`                             | n/a          | `h-full` (viewport - padding)   | Fills the viewport; close button moves inside |
| `auto`         | No cap, no `w-full`                  | n/a          | auto                            | Sizes to content — use when you want the dialog to shrink-wrap |

Need "half the viewport" or another fluid value? Pass an arbitrary `size`:

```blade
<x-hwc::modal size="50vw">...</x-hwc::modal>
```

### Arbitrary size

Any non-preset value is forwarded as `style="max-width: <value>"` on the dialog, alongside the same
`w-full` that the presets use — so the dialog fills the available width up to that arbitrary cap (same
behavior as the presets, just with a custom number):

```blade
<x-hwc::modal size="800px">...</x-hwc::modal>
<x-hwc::modal size="60vw">...</x-hwc::modal>
<x-hwc::modal size="42rem">...</x-hwc::modal>
```

If you instead want the dialog to **shrink to content** with a custom cap, use `size="auto"` and add the
cap via the `class` prop on the inner panel:

```blade
<x-hwc::modal size="auto" class="md:max-w-[800px]">...</x-hwc::modal>
```

### Migrating from `allow-small-width` / `allow-full-width`

The previous boolean props have been replaced by `size`. Map your usage:

| Before                                                       | After                       |
|--------------------------------------------------------------|-----------------------------|
| `<x-hwc::modal>` (defaults)                                  | `<x-hwc::modal size="50vw">` to keep the old "half-viewport" behavior — or just `<x-hwc::modal>` for the new 576px cap |
| `<x-hwc::modal :allow-small-width="true">`                   | `<x-hwc::modal size="auto">` |
| `<x-hwc::modal :allow-full-width="false">`                   | `<x-hwc::modal size="50vw">` (or pick a preset) |
| `<x-hwc::modal :allow-small-width="true" :allow-full-width="false">` | `<x-hwc::modal size="md">` (576px cap, allows shrinking on `sm`) |

## Root attributes

Arbitrary attributes are forwarded to the root modal element. This is the escape hatch for custom
Stimulus values, ARIA attributes, ids, and data hooks:

```blade
<x-hwc::modal
    data-modal-close-on-escape-value="false"
    aria-labelledby="edit-post-title"
    data-test-id="edit-post-modal"
>
    ...
</x-hwc::modal>
```

## Slots

| Slot               | Description                                |
|--------------------|--------------------------------------------|
| `trigger`          | Element that triggers the modal opening    |
| `slot` (default)   | Main modal content                         |
| `loading_template` | Template shown while dynamic content loads |

## Dynamic content with Turbo Frames

The modal supports content loaded via Turbo Frame. Use the `frame` prop to render a dynamic content
target that the controller observes and opens/closes automatically:

```blade
<a href="/items/1/edit" data-turbo-frame="modal">
    Edit
</a>

<x-hwc::modal frame="modal">
    <x-slot:loading_template>
        <div class="flex items-center justify-center p-12">
            <span>Loading...</span>
        </div>
    </x-slot:loading_template>
</x-hwc::modal>
```

`frame="modal"` renders this frame inside the modal body:

```html
<turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame>
```

Use a different root `id` if you set one manually:

```blade
<x-hwc::modal id="modal-shell" frame="modal" />
```

When the Turbo Frame receives content, the modal opens automatically. When the content is removed,
it closes. The modal also listens globally for clicks on `a[data-turbo-frame="<its frame id>"]`, so
the loading template fires even when the trigger lives outside the modal element (typical when the
modal sits in a shared layout).

## Loading template

The `loading_template` slot defines what fills the dynamic content while the Turbo Frame request is
in flight. The lifecycle:

1. User clicks `<a data-turbo-frame="<frame id>">` — anywhere on the page.
2. The modal injects the loading template into its `dynamicContent` target.
3. The content observer sees the inserted markup and opens the modal.
4. The frame response arrives → its content replaces the loading template.

The injection only happens if the response hasn't already arrived. For very fast responses, the
loading template never flashes — the modal opens straight to the final content. If no per-link or
slot template exists, there is no loading state; the modal waits for the real frame content and
opens when that content arrives.

### Default template

Provided once via the slot — used for every trigger:

```blade
<x-hwc::modal frame="modal">
    <x-slot:loading_template>
        <div class="flex items-center justify-center p-12">
            <span class="animate-spin">⏳</span>
            <span>Loading...</span>
        </div>
    </x-slot:loading_template>
</x-hwc::modal>
```

### Per-link template override

A trigger can point to its own template via `data-loading-template="<selector>"`. Useful when
different actions need different loading skeletons (a form skeleton vs. a list skeleton, for
example):

```blade
<a href="/posts/1/edit"
   data-turbo-frame="modal"
   data-loading-template="#form-skeleton">
    Edit post
</a>

<a href="/posts/1/comments"
   data-turbo-frame="modal"
   data-loading-template="#list-skeleton">
    View comments
</a>

<template id="form-skeleton">
    <div class="space-y-3 p-6">
        <div class="h-6 w-1/3 animate-pulse rounded bg-gray-200"></div>
        <div class="h-32 w-full animate-pulse rounded bg-gray-200"></div>
    </div>
</template>

<template id="list-skeleton">
    <ul class="divide-y p-6">
        @for ($i = 0; $i < 5; $i++)
            <li class="h-12 animate-pulse bg-gray-100"></li>
        @endfor
    </ul>
</template>
```

Resolution order: per-link `data-loading-template` → modal's `loading_template` slot → no loading
template. Without a loading template, the modal stays closed until the real frame content arrives.

## Stimulus Values

Configurable via `data-modal-*-value` on the root element:

| Value                    | Type      | Default | Description                              |
|--------------------------|-----------|---------|------------------------------------------|
| `open-duration`          | `Number`  | `300`   | Opening animation duration (ms)          |
| `close-duration`         | `Number`  | `300`   | Closing animation duration (ms)          |
| `lock-scroll`            | `Boolean` | `true`  | Locks body scroll when open              |
| `close-on-escape`        | `Boolean` | `true`  | Closes on Escape key                     |
| `close-on-click-outside` | `Boolean` | `true`  | Closes when clicking outside the modal   |
| `prevent-reopen-delay`   | `Number`  | `300`   | Anti-bounce delay in the controller (ms) |

## Actions

| Action              | Description                                                |
|---------------------|------------------------------------------------------------|
| `modal#open`        | Opens the modal                                            |
| `modal#close`       | Closes the modal                                           |
| `modal#showLoading` | Shows the loading template while awaiting a Turbo response |

## Events

| Event          | Description                                 |
|----------------|---------------------------------------------|
| `modal:opened` | Fired after the opening animation completes |
| `modal:closed` | Fired after the closing animation completes |

```javascript
element.addEventListener("modal:opened", (event) => {
    console.log("Modal opened", event.detail.controller);
});
```

## Accessibility

- `role="dialog"` and `aria-modal="true"` on the overlay
- Focus trap: Tab/Shift+Tab cycle through focusable elements inside the modal
- Focus returns to the element that triggered the modal on close
- Closes on Escape (configurable)

## Ignore outside click

Elements outside the modal that should not close it can use `data-modal-ignore`:

```html

<div data-modal-ignore>
    This dropdown will not close the modal when clicked.
</div>
```

## Turbo integration

The modal closes automatically on `turbo:before-cache`, preventing ghost modals when navigating with Turbo Drive.

### Closing a modal from the server

For modals driven by a Turbo Frame, clearing the frame closes them via the content observer:

```php
return turbo_stream()->update('modal');
```

### Convenience macro

`TurboStreamBuilder` is `Macroable` — register a `closeModal()` shortcut once in a service provider:

```php
// app/Providers/AppServiceProvider.php
use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;

public function boot(): void
{
    TurboStreamBuilder::macro('closeModal', function (string $id = 'modal') {
        return $this->update($id);
    });
}
```

Then any controller becomes a one-liner:

```php
return turbo_stream()->closeModal();
return turbo_stream()->closeModal('edit-users');

// or chained with a flash and a refresh
return turbo_stream()
    ->refresh(method: 'morph')
    ->closeModal()
    ->flash('success', 'Post updated');
```
