# Multi Select

Multi-value select control for forms. It renders a native hidden `<select multiple>` for submission and uses the
`multi-select` Stimulus controller for the custom trigger, searchable listbox, selection state and Floating UI
positioning.

The list search uses `<hw:input-group>` with a clearable `<hw:input>`, so the search icon is composed through the same
addon primitive available to applications and the clear button is an actual tabbable control instead of the
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

The listbox uses the shared Floating UI helper and supports Dropdown's core side, alignment, offset, strategy, flip, and
shift props. Multi Select uses `strategy="fixed"` by default and promotes the panel to the browser's native top layer
when supported, so it can cross clipped Drawer, Modal and scroll-container boundaries:

```blade
<hw:multi-select side="bottom" align="end" width="w-72" />
```

Both strategies retain top-layer promotion. `fixed` uses viewport-relative coordinates; `absolute` uses page/document
coordinates while in the top layer, not the nearest positioned ancestor. In browsers without native Popover support,
`absolute` uses its normal offset parent and can still be clipped by ancestors.

The enter motion starts only after Floating UI resolves the first placement. If `flip` changes the preferred placement,
`data-side` and `data-align` expose the resolved placement used on screen. Superseded asynchronous positioning results
are ignored.

## Motion And Presence

The floating content is server-rendered with `data-state="closed" hidden inert`. During exit, `data-state="closed"` and
`inert` apply immediately, while `hidden` is deferred until the CSS motion finishes. The trigger also keeps
`data-multi-select-state="open|closed"` synchronized.

Use the root `motion` prop to disable motion:

```blade
<hw:multi-select
    name="status[]"
    :options="$statuses"
    motion="none"
/>
```

The selected preset transitions only `opacity`, `scale`, and `translate`. Custom CSS may use transitions or finite animations
keyed by the content's `data-state`, but the closed-state selector must never apply `display: none` or `hidden`; Presence
owns the `hidden` attribute. Rapid reopen cancels stale exit cleanup, and `prefers-reduced-motion: reduce` skips motion.

## Search Icon

Multi Select renders a small inline search SVG by default. Override the `searchIcon` slot when your application uses a
specific icon set:

```blade
<hw:multi-select name="status[]" :options="$statuses">
    <x-slot:searchIcon>
        <x-lucide-search class="size-4" />
    </x-slot:searchIcon>
</hw:multi-select>
```

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
| `strategy` | `fixed` | Floating UI positioning strategy: viewport-relative `fixed` or page-relative `absolute` while in the top layer. |
| `flip` | `true` | Allow Floating UI to flip the listbox when there is not enough room. |
| `shift` | `true` | Allow Floating UI to shift the listbox to stay in view. |
| `motion` | `default` | Presence motion for the floating content: `default` or `none`. |
| `width` | `''` | Content width classes. |
| `trigger-class` | `''` | Additional classes on the trigger button. |
| `content-class` | `''` | Additional classes on the floating content panel. |

## Styling hooks

- `data-slot="multi-select"`
- `data-slot="multi-select-native"`
- `data-slot="multi-select-trigger"`
- `aria-expanded="true|false"`
- `data-multi-select-state="open|closed"` on the trigger
- `data-state="open|closed"` on the floating content
- `data-slot="multi-select-trigger-icon"`
- `data-slot="multi-select-value"`
- `data-slot="multi-select-content"`
- `data-motion="default|none"`
- `data-side="top|right|bottom|left"`
- `data-align="start|center|end"`
- `--anchor-width`
- `--anchor-height`
- `--available-width`
- `--available-height`
- `--transform-origin`
- `data-slot="multi-select-search"`
- `data-slot="multi-select-search-icon"`
- `data-slot="multi-select-list"`
- `data-slot="multi-select-select-all"`
- `data-slot="multi-select-option"`
- `data-slot="multi-select-indicator"`
- `data-slot="multi-select-option-text"`
- `data-slot="multi-select-empty"`
- `data-slot="multi-select-validation"`

`data-side` and `data-align` report the resolved Floating UI placement after any flip. `width` and `content-class` are
applied directly to `data-slot="multi-select-content"`; `trigger-class` is applied to the trigger.

## Controller integrations

Uses the `multi-select` controller, which depends on `@floating-ui/dom` and ships with `_composition.js`, `_floating.js`,
`_form_errors.js`, `_frame_events.js`, `_presence.js`, and `_top_layer.js`. The component search also uses `clear-input`
for its accessible clear button.
