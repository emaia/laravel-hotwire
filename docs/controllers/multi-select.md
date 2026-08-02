# Multi Select

Enhances server-rendered multi-select markup with open/close behavior, search, multi-value selection, select-all,
maximum selection limits, validation proxy updates and Floating UI positioning.

When the search input is rendered with the package `clear-input` controller, `inputCleared` resets the option filter.
The optional select-all target is treated as a button action, and the empty target is a status message outside the
listbox option semantics.

**Identifier:** `multi-select`
**Install:** `php artisan hotwire:controllers multi-select`

## Requirements

- `@floating-ui/dom` for anchored positioning.
- Ships with `_floating.js`, `_form_errors.js`, `_frame_events.js`, `_presence.js`, and `_top_layer.js`; publishing the
  controller publishes these helpers too.
- Without the Nova preset, reset native Popover positioning with
  `[data-hotwire-top-layer][popover] { inset: auto; margin: 0; }` and define the floating element's border and padding.

## Targets

| Target | Description |
| --- | --- |
| `trigger` | Button that opens/closes the listbox. |
| `content` | Floating listbox panel. |
| `select` | Hidden native `<select multiple>` used for form submission. |
| `option` | Selectable options. |
| `value` | Trigger summary text. |
| `search` | Optional search input. |
| `selectAll` | Optional select-all action button before the listbox. |
| `empty` | Empty-state message shown when no options are visible; keep it outside the listbox. |
| `validation` | Optional required validation proxy. |

## Values

| Value | Default | Description |
| --- | --- | --- |
| `placeholder` | `Select options` | Summary text when empty. |
| `search` | `true` | Enables search behavior. |
| `select-all` | `false` | Enables select-all behavior. |
| `max` | unset | Maximum selected options. |
| `list-all` | `false` | Show selected labels instead of a count. |
| `list-all-limit` | `3` | Maximum labels shown when `list-all` is enabled before appending the hidden count text; use `0` to show every label. |
| `list-all-more-text` | `+:count more` | Template appended after the visible labels when `list-all-limit` is exceeded; use `:count` for the hidden count. |
| `sort-selected` | `false` | Move selected options to the top of the list while preserving their original relative order. |
| `close-list-on-item-select` | `false` | Close after option selection. |
| `open` | `false` | Initial/reflected open state. |
| `side` | `bottom` | Preferred side for the floating listbox: `top`, `right`, `bottom` or `left`. |
| `align` | `start` | Alignment on the selected side: `start`, `center` or `end`. |
| `side-offset` | `4` | Distance between the trigger and listbox on the main axis. |
| `align-offset` | `0` | Offset along the cross axis. |
| `strategy` | `fixed` | Floating UI positioning strategy: viewport-relative `fixed` or page-relative `absolute` while in the top layer. |
| `flip` | `true` | Allow Floating UI to flip the listbox when there is not enough room. |
| `shift` | `true` | Allow Floating UI to shift the listbox to stay in view. |

Motion is configured on the content target with `data-motion="default|none"`, not as a Stimulus value. The
`<hw:multi-select>` component renders it from its root `motion` prop.

## Markup

Use the Blade component unless you need fully custom HTML. A custom closed panel must start with
`data-state="closed" hidden inert`:

```html
<div data-controller="multi-select">
    <select data-multi-select-target="select" name="status[]" multiple hidden>
        <option value="active">Active</option>
        <option value="paused">Paused</option>
    </select>

    <button
        type="button"
        data-multi-select-target="trigger"
        data-action="multi-select#toggle keydown->multi-select#onTriggerKeydown"
        aria-expanded="false"
        data-multi-select-state="closed"
    >
        <span data-multi-select-target="value">Select options</span>
    </button>

    <div
        data-multi-select-target="content"
        data-state="closed"
        data-motion="default"
        data-side="bottom"
        data-align="start"
        hidden
        inert
    >
        <input data-multi-select-target="search" type="text">
        <div data-multi-select-target="list" role="listbox" aria-multiselectable="true">
            <div
                data-multi-select-target="option"
                data-value="active"
                data-selected="false"
                role="option"
                aria-selected="false"
                tabindex="-1"
            >Active</div>
        </div>
    </div>
</div>
```

## Positioning And Top Layer

The controller uses Floating UI `offset`, `flip`, `shift`, and `size` middleware and promotes content to the native top
layer when the browser supports the Popover API. The normal DOM fallback remains available in older browsers and can be
clipped by ancestors.

While native top layer is active, `fixed` uses viewport-relative coordinates and `absolute` uses page/document
coordinates; `absolute` does not use the nearest positioned ancestor in that mode. Without native Popover support,
`absolute` falls back to normal offset-parent behavior.

Presence waits for the first placement before entering. The content receives `data-side`, `data-align`,
`--anchor-width`, `--anchor-height`, `--available-width`, `--available-height`, and `--transform-origin`.
`data-side` and `data-align` represent the resolved placement after any flip. Results from superseded positioning runs
are ignored.

## Presence And Motion

Opening removes `hidden` but leaves content at `data-state="closed"` and inert until the first placement resolves. It then
sets content to `data-state="open"`, trigger state to `data-multi-select-state="open"`, and makes the panel interactive.
Closing immediately restores both closed states and `inert`, waits for the content's CSS transition or finite animation,
then adds `hidden`.

The Nova preset transitions only `opacity`, `scale`, and `translate`. Custom closed-state CSS must not set
`display: none` or otherwise hide the panel; Presence owns `hidden`. Set `data-motion="none"` for immediate changes.
Reduced-motion preference skips motion, and reopening during exit cancels stale hiding and top-layer teardown.

Replacing the content target tears down Presence, Floating UI, top-layer state, and content listeners for the old node;
replacing the trigger re-anchors open content. `disconnect()` and `turbo:before-cache` synchronously apply `hidden inert`,
cancel pending positioning, and leave the top layer so Turbo does not cache an open listbox.

## Form Baselines

Selection changes update the native options' live `selected` state without rewriting their `selected` attributes, so a
native form reset restores the server-rendered defaults. When the same controller instance survives a submission, it
commits the current selection as the new reset and unsaved-changes baseline after the corresponding page, frame, or
Turbo Stream response finishes. A response that reports HTTP success but renders `aria-invalid="true"` in the submitted
form restores the previous defaults without changing the attempted selection. If the component is replaced, its new
server-rendered `selected` attributes define the baseline just as they do for native controls. An immediate follow-up
submission carries the pending response's baseline, so a failed follow-up restores the last accepted selection rather
than intermediate live DOM state. Turbo Stream validation is scoped to targets that affect the form, and baseline
settlement waits for relevant custom or deferred stream renderers.

## Events

- `multi-select:select`
- `multi-select:unselect`
- `multi-select:change`
- `multi-select:select-all`
- `multi-select:deselect-all`
