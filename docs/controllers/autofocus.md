# Autofocus

Focuses the first matching field on `connect()` and again on `turbo:frame-load`. The native HTML
`autofocus` attribute does not fire on Turbo Drive visits or frame swaps — this controller fills that
gap so modals, frame-loaded forms and wizard steps land with the right field focused.

**Identifier:** `autofocus`  
**Install:** `php artisan hotwire:controllers autofocus`

## Requirements

- No external dependencies.

## Targets

| Target  | Description                                                                     |
|---------|---------------------------------------------------------------------------------|
| `field` | The element to focus when `strategy="target"`. Ignored by the other strategies. |

## Values

| Value              | Type    | Default               | Description                                                                                      |
|--------------------|---------|-----------------------|--------------------------------------------------------------------------------------------------|
| `strategy`         | String  | `autofocus-attribute` | How to pick the element to focus. See [Strategies](#strategies).                                 |
| `scroll-into-view` | Boolean | `false`               | When `false` (default), `focus({ preventScroll: true })`. When `true`, allow the page to scroll. |

## Strategies

| Strategy              | Picks                                                                                |
|-----------------------|--------------------------------------------------------------------------------------|
| `autofocus-attribute` | First `[autofocus]` element inside the controller scope. Default.                    |
| `first-focusable`     | First `<input>`, `<select>`, `<textarea>` or `<button>` inside the controller scope. |
| `target`              | The `data-autofocus-target="field"` element.                                         |

All strategies skip elements that are `[disabled]`, `[type="hidden"]`, `[tabindex="-1"]`, or inside a
`[hidden]` / `[aria-hidden="true"]` ancestor.

## Behavior

- Runs on `connect()` — covers the initial page load and any later mount of the controller.
- Runs again on `turbo:frame-load` — covers frame swaps. **Does not** run on `turbo:render`, so Drive's
  native focus restoration is left alone.
- Never steals focus: if anything inside the controller scope is already the active element when the
  controller would run, the focus call is skipped.

## Basic usage

The HTML `autofocus` attribute already says what to focus — drop the controller on the form so the
attribute keeps working across Turbo navigations.

```html

<form data-controller="autofocus" action="/messages" method="POST">
    <input type="text" name="title" autofocus/>
    <textarea name="body"></textarea>
    <button type="submit">Save</button>
</form>
```

## Inside a modal

`<hw:modal>` already wraps frame-loaded content. Add the controller on the form *inside* the
frame so the right field is focused after the modal opens.

```blade
<hw:modal frame="user-edit">
    {{-- server response into the frame --}}
    <form data-controller="autofocus" action="..." method="POST">
        <input type="text" name="name" autofocus />
        ...
    </form>
</hw:modal>
```

## Picking the first focusable field

Use `first-focusable` when the markup does not carry `[autofocus]` and you just want "whatever comes
first that the user can type into."

```html

<form data-controller="autofocus" data-autofocus-strategy-value="first-focusable">
    <input type="hidden" name="csrf" value="..."/>
    <button type="button" tabindex="-1">Help</button>
    <input type="text" name="title"/> {{-- focused --}}
    <input type="text" name="slug"/>
</form>
```

## Picking an explicit target

When the field to focus is buried below a header or sidebar, mark it with the `field` target.

```html

<section data-controller="autofocus" data-autofocus-strategy-value="target">
    <header>
        <button>Back</button>
    </header>
    <form>
        <input type="text" name="search" data-autofocus-target="field"/>
    </form>
</section>
```

## Letting the page scroll

By default the controller focuses with `{ preventScroll: true }` — useful inside modals and other
overlays. Set `scroll-into-view-value="true"` when you do want the page to scroll the field into
view, for example on a long settings page after a frame swap.

```html

<form data-controller="autofocus"
      data-autofocus-strategy-value="first-focusable"
      data-autofocus-scroll-into-view-value="true">
    ...
</form>
```
