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
- Ships with `_floating.js` and `_transition.js` helpers.

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
| `side` | `bottom` | Preferred side for the floating listbox: `top`, `right`, `bottom` or `left`. |
| `align` | `start` | Alignment on the selected side: `start`, `center` or `end`. |
| `side-offset` | `4` | Distance between the trigger and listbox on the main axis. |
| `align-offset` | `0` | Offset along the cross axis. |
| `strategy` | `fixed` | Floating UI positioning strategy. Use `absolute` only when the listbox should stay within the nearest positioned ancestor. |
| `flip` | `true` | Allow Floating UI to flip the listbox when there is not enough room. |
| `shift` | `true` | Allow Floating UI to shift the listbox to stay in view. |

## Events

- `multi-select:select`
- `multi-select:unselect`
- `multi-select:change`
- `multi-select:select-all`
- `multi-select:deselect-all`
