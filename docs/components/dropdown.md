# Dropdown

Accessible disclosure dropdown with a trigger button, a viewport-aware Floating UI menu, outside-click dismissal,
`Escape` dismissal with focus return, optional close-on-select and animated open/close.

The component wraps the [`dropdown`](../controllers/dropdown.md) Stimulus controller and wires up the trigger/menu
accessibility attributes for you.

## Usage

```blade
<hw:dropdown>
    <x-slot:trigger>
        Options
        <hw:icon name="chevron-down" data-slot="dropdown-trigger-icon" />
    </x-slot:trigger>

    <hw:dropdown.group>
        <hw:dropdown.label>Account</hw:dropdown.label>
        <hw:dropdown.item href="/account">Profile</hw:dropdown.item>
        <hw:dropdown.item href="/support">Support</hw:dropdown.item>
        <hw:dropdown.separator />
        <hw:dropdown.item variant="destructive">Sign out</hw:dropdown.item>
    </hw:dropdown.group>
</hw:dropdown>
```

The component renders the `<button>` trigger and the menu, links them via `id`/`aria-controls`, and keeps
`aria-expanded` in sync. Clicking an `<a>` or `<button>` inside the menu closes it by default.

Add `data-slot="dropdown-trigger-icon"` to a chevron inside the trigger when you want it to rotate with the open state.
The Nova preset targets the trigger's `aria-expanded="true"` state, so no `group-*` class is required.

The `trigger` slot is the button's content, not the button. The component already renders the `<button>`; style it by
passing attributes on the slot tag (`<x-slot:trigger class="...">`) or with `trigger-class`.

## Composition

```blade
<hw:dropdown align="end">
    <x-slot:trigger>Command menu</x-slot:trigger>

    <hw:dropdown.group>
        <hw:dropdown.label inset>Project</hw:dropdown.label>
        <hw:dropdown.item href="/projects/new" inset>
            New project
            <hw:dropdown.shortcut>N</hw:dropdown.shortcut>
        </hw:dropdown.item>
        <hw:dropdown.separator />
        <hw:dropdown.item disabled>Archive</hw:dropdown.item>
    </hw:dropdown.group>
</hw:dropdown>
```

`dropdown.item` renders an `<a>` when `href` is present, otherwise it renders a `<button type="button">`. Use
`variant="destructive"` for destructive actions. Disabled link items keep their `href` for semantics but receive
`aria-disabled="true"` and `tabindex="-1"`.

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `dropdown` | `id` | `uniqid('dropdown-')` | Menu `id` and trigger `aria-controls`. |
| `dropdown` | `side` | `bottom` | Preferred side: `top`, `right`, `bottom` or `left`. |
| `dropdown` | `align` | `start` | Menu alignment: `start`, `center` or `end`. |
| `dropdown` | `side-offset` | `4` | Main-axis gap between the trigger and menu. |
| `dropdown` | `align-offset` | `0` | Cross-axis offset along the trigger edge. |
| `dropdown` | `strategy` | `absolute` | Floating UI strategy: `absolute` or `fixed`. |
| `dropdown` | `flip` | `true` | Flip to the opposite side when the preferred side lacks room. |
| `dropdown` | `shift` | `true` | Shift within the viewport when the menu would overflow. |
| `dropdown` | `open` | `false` | Start open without an enter animation. |
| `dropdown` | `close-on-select` | `true` | Close when an `<a>` or `<button>` inside the menu is clicked. |
| `dropdown` | `transition` | `true` | Include the default enter/leave transition attributes. |
| `dropdown` | `trigger-class` | `''` | Trigger button classes; the Nova preset styles the trigger by default. |
| `dropdown` | `width` | `''` | Menu width classes; overrides the trigger-width default when set. |
| `dropdown` | `menu-class` | `''` | Extra classes appended to the menu. |
| `dropdown.label` | `inset` | `false` | Align the label with inset items. |
| `dropdown.item` | `href` | `null` | Render an anchor instead of a button. |
| `dropdown.item` | `variant` | `default` | `default` or `destructive`. |
| `dropdown.item` | `disabled` | `false` | Disable the item. |
| `dropdown.item` | `inset` | `false` | Add leading space for iconless items. |

## Positioning

The menu anchors to the trigger with Floating UI. It opens below the trigger by default, matches the trigger width,
flips when the preferred side lacks room, and shifts within the viewport.

```blade
<hw:dropdown side="bottom" align="end" :side-offset="4">
    <x-slot:trigger>Menu</x-slot:trigger>
    <hw:dropdown.item href="/settings">Settings</hw:dropdown.item>
</hw:dropdown>
```

Use `strategy="fixed"` when the trigger sits inside complex scrolling or transformed layouts. This is not a portal;
menus can still be clipped by an ancestor with `overflow: hidden`.

```blade
<hw:dropdown side="right" align="start" strategy="fixed">
    <x-slot:trigger>More</x-slot:trigger>
    <hw:dropdown.item href="/settings">Settings</hw:dropdown.item>
</hw:dropdown>
```

## Menu Width

By default, the Nova preset makes the menu match the trigger width with `w-(--anchor-width)` and hides horizontal
overflow. That works for command menus and short action lists, but custom form content often needs more room.

Set the width on `<hw:dropdown>` with the `width` prop. Do not put `min-w-*` only on an inner wrapper; the menu itself
will still be trigger-width and clip the wider child.

```blade
<hw:dropdown width="w-64 max-w-[calc(100vw-2rem)]">
    <x-slot:trigger>Filters</x-slot:trigger>

    <hw:field.group class="p-2">
        <!-- custom content -->
    </hw:field.group>
</hw:dropdown>
```

Use `width="min-w-64 w-max max-w-[calc(100vw-2rem)]"` when the menu should be at least a certain width but still grow
with its content.

## Keeping The Menu Open

Disable `close-on-select` for menus with interactive content that should not dismiss the dropdown, and close manually
where needed with `data-action="dropdown#close"`.

```blade
<hw:form :action="route('projects.index')" method="get" clean-query-params>
    <hw:dropdown width="w-64 max-w-[calc(100vw-2rem)]" :close-on-select="false">
        <x-slot:trigger>
            Filters
            <hw:icon name="chevron-down" data-slot="dropdown-trigger-icon" />
        </x-slot:trigger>

        <hw:field.group class="p-2">
            <hw:field
                name="status[]"
                orientation="horizontal"
                :error="false"
                class="rounded-md px-1.5 py-1 hover:bg-accent"
            >
                <hw:input
                    id="status-active"
                    type="checkbox"
                    value="active"
                    :checked="in_array('active', (array) request('status', []), true)"
                />
                <hw:field.label for="status-active">Active</hw:field.label>
            </hw:field>

            <hw:field
                name="status[]"
                orientation="horizontal"
                :error="false"
                class="rounded-md px-1.5 py-1 hover:bg-accent"
            >
                <hw:input
                    id="status-paused"
                    type="checkbox"
                    value="paused"
                    :checked="in_array('paused', (array) request('status', []), true)"
                />
                <hw:field.label for="status-paused">Paused</hw:field.label>
            </hw:field>

            <hw:field
                name="status[]"
                orientation="horizontal"
                :error="false"
                class="rounded-md px-1.5 py-1 hover:bg-accent"
            >
                <hw:input
                    id="status-archived"
                    type="checkbox"
                    value="archived"
                    :checked="in_array('archived', (array) request('status', []), true)"
                />
                <hw:field.label for="status-archived">Archived</hw:field.label>
            </hw:field>

            <hw:dropdown.separator />

            <hw:button-group class="justify-end">
                <hw:button as="a" href="{{ route('projects.index') }}" variant="ghost" size="sm">
                    Clear
                </hw:button>

                <hw:button type="submit" size="sm" data-action="dropdown#close">
                    Apply
                </hw:button>
            </hw:button-group>
        </hw:field.group>
    </hw:dropdown>
</hw:form>
```

Use form components for interactive form content. `dropdown.item` is best for link/button actions, not checkbox rows.

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `dropdown` | `div` | `dropdown` |
| `dropdown` trigger slot | `button` | `dropdown-trigger` |
| `dropdown` menu | `div` | `dropdown-menu` |
| `dropdown.group` | `div` with `role="group"` | `dropdown-group` |
| `dropdown.label` | `div` | `dropdown-label` |
| `dropdown.item` | `a` or `button` | `dropdown-item` |
| `dropdown.separator` | `div` with `role="separator"` | `dropdown-separator` |
| `dropdown.shortcut` | `span` | `dropdown-shortcut` |

## Styling Hooks

- `data-slot="dropdown"`
- `data-dropdown-side-value="top|right|bottom|left"`
- `data-dropdown-align-value="start|center|end"`
- `data-dropdown-side-offset-value`
- `data-dropdown-align-offset-value`
- `data-dropdown-strategy-value="absolute|fixed"`
- `data-dropdown-flip-value="true|false"`
- `data-dropdown-shift-value="true|false"`
- `data-slot="dropdown-trigger"`
- `aria-expanded="true|false"`
- `data-slot="dropdown-trigger-icon"`
- `data-slot="dropdown-menu"`
- `data-open="true|false"`
- `data-side="top|right|bottom|left"`
- `data-align="start|center|end"`
- `--anchor-width`
- `--anchor-height`
- `--available-width`
- `--available-height`
- `--transform-origin`
- `data-slot="dropdown-group"`
- `data-slot="dropdown-label"`
- `data-inset="true"`
- `data-slot="dropdown-item"`
- `data-variant="default|destructive"`
- `data-disabled="true"`
- `data-slot="dropdown-separator"`
- `data-slot="dropdown-shortcut"`

`width` and `menu-class` are explicit escape hatches on the menu element itself. By default, the Nova preset sizes the
menu with `w-(--anchor-width)`, constrains it with `max-h-(--available-height)`, hides horizontal overflow, and animates
from `--transform-origin`.

## Token Reference

The Nova preset styles the dropdown from semantic tokens defined in [theming](../theming.md). Override those tokens in
your app theme instead of targeting raw colors.

| Token | Used by |
| --- | --- |
| `--background` | Trigger surface. |
| `--foreground` | Trigger text and subtle menu ring opacity. |
| `--border` | Trigger border and separators. |
| `--ring` | Trigger focus-visible ring. |
| `--muted` | Trigger hover/open background. |
| `--muted-foreground` | Labels and shortcuts. |
| `--input` | Dark-mode trigger border/background contrast. |
| `--popover` | Menu surface. |
| `--popover-foreground` | Menu and item text. |
| `--accent` | Item hover/focus background. |
| `--accent-foreground` | Item hover/focus text and shortcut text. |
| `--destructive` | Destructive item text and hover/focus treatment. |
| `--radius` | Derived radius tokens used by trigger, menu and items. |

Floating UI also writes runtime positioning variables (`--anchor-width`, `--available-height`, `--transform-origin`,
etc.). Those are layout hooks, not theme tokens.

## Accessibility

This is the disclosure pattern: `aria-expanded`/`aria-controls`/`aria-haspopup` are wired automatically, `Escape` closes
and restores focus to the trigger, and clicking outside dismisses. It does not impose `role="menu"` semantics; for a
strict ARIA menu with arrow-key roving, build it directly on the controller.
