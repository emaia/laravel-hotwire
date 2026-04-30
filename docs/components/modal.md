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

## With close button

The X button is shown by default (`close-button` is `true`). To hide it:

```html

<x-hwc::modal :close-button="false">
    <x-slot:trigger>
        <button data-action="modal#open" type="button">Open</button>
    </x-slot:trigger>

    <p class="p-6">Modal without X button.</p>
</x-hwc::modal>
```

## Props

| Prop                   | Type      | Default            | Description                                     |
|------------------------|-----------|--------------------|-------------------------------------------------|
| `id`                   | `string`  | `uniqid('modal-')` | Root element ID                                 |
| `allow-small-width`    | `bool`    | `false`            | Allows width smaller than 50% on `md+` screens  |
| `allow-full-width`     | `bool`    | `true`             | Allows full width (no `max-w-[50%]`)            |
| `class`                | `string`  | `''`               | Additional CSS classes on the content container |
| `close-button`         | `bool`    | `true`             | Shows X button to close                         |
| `fixed-top`            | `bool`    | `false`            | Pins the modal to the top with a margin         |
| `frame`                | `?string` | `null`             | Renders a Turbo Frame dynamic content target    |
| `prevent-reopen-delay` | `int`     | `1000`             | Delay (ms) before allowing reopen after closing |

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

| Action               | Description                                                |
|----------------------|------------------------------------------------------------|
| `modal#open`         | Opens the modal                                            |
| `modal#close`        | Closes the modal                                           |
| `modal#showLoading`  | Shows the loading template while awaiting a Turbo response |

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
