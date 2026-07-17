# Multi Select

Multi-value select control for forms. It renders a native hidden `<select multiple>` for submission and uses the
`multi-select` Stimulus controller for the custom trigger, searchable listbox, selection state and Floating UI
positioning.

The list search uses `<hw:input clearable>` so the clear button is an actual tabbable control instead of the
browser-native `type="search"` clear affordance, which is not consistently reachable by keyboard tab order.

The popup keeps action/status controls outside the listbox semantics: `select-all` is a button action, while the empty
message is a status message shown next to the list rather than a listbox option.

## Usage

```blade
<hw:multi-select
    name="status[]"
    :options="['active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived']"
    :selected="request('status', [])"
/>
```

## With Select All And Max

```blade
<hw:multi-select
    name="tags[]"
    :options="$tags->pluck('name', 'id')->all()"
    select-all
    sort-selected
    :max="3"
/>
```

## Positioning

The listbox uses the shared Floating UI helper and supports the same positioning props as Dropdown. Multi Select uses
`strategy="fixed"` by default so the panel can cross clipped Drawer, Modal and scroll-container boundaries:

```blade
<hw:multi-select side="bottom" align="end" width="w-72" />
```

Use `strategy="absolute"` only when you explicitly want the panel positioned within the nearest positioned ancestor.

## Props

| Prop | Default | Description |
| --- | --- | --- |
| `name` | `null` | Submitted field name. Appends `[]` automatically when missing. |
| `options` | `[]` | Value/label options. |
| `selected` | `[]` | Initially selected values, merged with `old()` by default. |
| `placeholder` | `Select options` | Trigger text when nothing is selected. |
| `search` | `true` | Render the search input. |
| `empty-text` | `No options found.` | Message shown when the option list is empty or the search has no matches. |
| `select-all` | `false` | Render a select-all action button before the listbox. |
| `max` | `null` | Maximum selected options. |
| `list-all` | `false` | Show selected labels instead of a count. |
| `list-all-limit` | `3` | Maximum labels shown when `list-all` is enabled before appending the hidden count text; use `0` to show every label. |
| `list-all-more-text` | `+:count more` | Template appended after the visible labels when `list-all-limit` is exceeded; use `:count` for the hidden count. |
| `sort-selected` | `false` | Move selected options to the top of the list while preserving their original relative order. |
| `close-list-on-item-select` | `false` | Close after selecting an option. |
| `side` | `bottom` | Preferred side for the floating listbox: `top`, `right`, `bottom` or `left`. |
| `align` | `start` | Alignment on the selected side: `start`, `center` or `end`. |
| `side-offset` | `4` | Distance between the trigger and listbox on the main axis. |
| `align-offset` | `0` | Offset along the cross axis. |
| `strategy` | `fixed` | Floating UI positioning strategy. Use `absolute` only when the listbox should stay within the nearest positioned ancestor. |
| `flip` | `true` | Allow Floating UI to flip the listbox when there is not enough room. |
| `shift` | `true` | Allow Floating UI to shift the listbox to stay in view. |
| `width` | `''` | Content width classes. |

## Styling Hooks

- `data-slot="multi-select"`
- `data-slot="multi-select-native"`
- `data-slot="multi-select-trigger"`
- `data-slot="multi-select-trigger-icon"`
- `data-slot="multi-select-value"`
- `data-slot="multi-select-content"`
- `data-slot="multi-select-search"`
- `data-slot="multi-select-list"`
- `data-slot="multi-select-select-all"`
- `data-slot="multi-select-option"`
- `data-slot="multi-select-indicator"`
- `data-slot="multi-select-option-text"`
- `data-slot="multi-select-empty"`
- `data-slot="multi-select-validation"`

## Required Controllers

Uses the `multi-select` controller, which depends on `@floating-ui/dom`. The component search also uses `clear-input`
for its accessible clear button.
