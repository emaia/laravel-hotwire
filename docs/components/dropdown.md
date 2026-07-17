# Dropdown

Accessible disclosure dropdown with semantic trigger/content subcomponents, Floating UI positioning, outside-click
dismissal, `Escape` dismissal with focus return, optional close-on-select and animated open/close.

The component wraps the [`dropdown`](../controllers/dropdown.md) Stimulus controller and wires up trigger/menu
accessibility attributes for you.

## Usage

```blade
<hw:dropdown>
    <hw:dropdown.trigger>
        Options
        <hw:icon name="chevron-down" data-slot="dropdown-trigger-icon" />
    </hw:dropdown.trigger>

    <hw:dropdown.content>
        <hw:dropdown.group>
            <hw:dropdown.label>Account</hw:dropdown.label>
            <hw:dropdown.item href="/account">Profile</hw:dropdown.item>
            <hw:dropdown.item href="/support">Support</hw:dropdown.item>
            <hw:dropdown.separator />
            <hw:dropdown.item variant="destructive">Sign out</hw:dropdown.item>
        </hw:dropdown.group>
    </hw:dropdown.content>
</hw:dropdown>
```

`dropdown.trigger` renders a button, links it to `dropdown.content` with `aria-controls`, and keeps `aria-expanded` and
`data-state="open|closed"` in sync. Clicking an `<a>` or `<button>` inside the content closes the dropdown by default.

Add `data-slot="dropdown-trigger-icon"` to a chevron inside the trigger when you want it to rotate with the open state.
The Nova preset targets the trigger's `aria-expanded="true"` state, so no group class is required.

## Trigger As Child

Use `as-child` when another component should be the trigger. Dropdown merges its trigger attributes into the first child
element instead of rendering a nested button.

```blade
<hw:dropdown>
    <hw:dropdown.trigger as-child>
        <hw:sidebar.menu-button size="lg">
            <hw:avatar src="{{ $user->avatar_url }}" />
            <span class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-medium">{{ $user->name }}</span>
                <span class="truncate text-xs">{{ $user->email }}</span>
            </span>
            <hw:icon name="chevrons-up-down" class="ml-auto" />
        </hw:sidebar.menu-button>
    </hw:dropdown.trigger>

    <hw:dropdown.content side="top" collapsed-side="right" collapsed-align="end" mobile-side="bottom" align="start" width="w-(--anchor-width) min-w-56">
        <hw:dropdown.item href="/profile">Profile</hw:dropdown.item>
        <hw:dropdown.item href="/settings">Settings</hw:dropdown.item>
        <hw:dropdown.separator />
        <hw:dropdown.item variant="destructive">Sign out</hw:dropdown.item>
    </hw:dropdown.content>
</hw:dropdown>
```

This mirrors Radix/shadcn `DropdownMenuTrigger asChild`: useful for `sidebar.menu-button`, `navbar.item`, custom buttons
and other components that already own their visual styling.

## Sidebar Switcher

The same primitive works for team, project or workspace switchers in a sidebar header. Desktop can open to the side while
mobile opens below the trigger:

```blade
<hw:sidebar.menu>
    <hw:sidebar.menu-item>
        <hw:dropdown>
            <hw:dropdown.trigger as-child>
                <hw:sidebar.menu-button size="lg">
                    <span class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                        <hw:icon name="gallery-vertical-end" />
                    </span>
                    <span class="grid flex-1 text-left text-sm leading-tight">
                        <span class="truncate font-medium">{{ $activeTeam->name }}</span>
                        <span class="truncate text-xs">{{ $activeTeam->plan }}</span>
                    </span>
                    <hw:icon name="chevrons-up-down" class="ml-auto" />
                </hw:sidebar.menu-button>
            </hw:dropdown.trigger>

            <hw:dropdown.content side="right" collapsed-side="right" mobile-side="bottom" align="start" width="w-(--anchor-width) min-w-56">
                <hw:dropdown.label>Teams</hw:dropdown.label>

                @foreach ($teams as $team)
                    <hw:dropdown.item>
                        {{ $team->name }}
                        <hw:dropdown.shortcut>⌘{{ $loop->iteration }}</hw:dropdown.shortcut>
                    </hw:dropdown.item>
                @endforeach

                <hw:dropdown.separator />
                <hw:dropdown.item>
                    <hw:icon name="plus" />
                    Add team
                </hw:dropdown.item>
            </hw:dropdown.content>
        </hw:dropdown>
    </hw:sidebar.menu-item>
</hw:sidebar.menu>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `dropdown` | `id` | `uniqid('dropdown-')` | Content `id` and trigger `aria-controls`. |
| `dropdown` | `open` | `false` | Start open without an enter animation. |
| `dropdown` | `close-on-select` | `true` | Close when an `<a>` or `<button>` inside the content is clicked. |
| `dropdown.trigger` | `as-child` | `false` | Merge trigger behavior into the first child element instead of rendering a button. |
| `dropdown.content` | `side` | `bottom` | Preferred side: `top`, `right`, `bottom` or `left`. |
| `dropdown.content` | `align` | `start` | Content alignment: `start`, `center` or `end`. |
| `dropdown.content` | `mobile-side` | `null` | Side override while the mobile media query matches. |
| `dropdown.content` | `mobile-align` | `null` | Align override while the mobile media query matches. |
| `dropdown.content` | `mobile-media` | `(max-width: 767px)` | Media query used by mobile side/align overrides. |
| `dropdown.content` | `collapsed-side` | `null` | Side override while the dropdown is inside a collapsed container. |
| `dropdown.content` | `collapsed-align` | `null` | Align override while the dropdown is inside a collapsed container. |
| `dropdown.content` | `collapsed-when` | Sidebar icon/collapsed selector | Selector used to detect collapsed context. |
| `dropdown.content` | `side-offset` | `4` | Main-axis gap between the trigger and content. |
| `dropdown.content` | `align-offset` | `0` | Cross-axis offset along the trigger edge. |
| `dropdown.content` | `strategy` | `absolute` | Floating UI strategy: `absolute` or `fixed`. |
| `dropdown.content` | `flip` | `true` | Flip to the opposite side when the preferred side lacks room. |
| `dropdown.content` | `shift` | `true` | Shift within the viewport when the content would overflow. |
| `dropdown.content` | `transition` | `true` | Include the default enter/leave transition attributes. |
| `dropdown.content` | `width` | `''` | Content width classes; overrides the trigger-width default when set. |
| `dropdown.label` | `inset` | `false` | Align the label with inset items. |
| `dropdown.item` | `href` | `null` | Render an anchor instead of a button. |
| `dropdown.item` | `variant` | `default` | `default` or `destructive`. |
| `dropdown.item` | `disabled` | `false` | Disable the item. |
| `dropdown.item` | `inset` | `false` | Add leading space for iconless items. |

## Positioning

The content anchors to the active trigger with Floating UI. It opens below the trigger by default, matches the trigger
width, flips when the preferred side lacks room, and shifts within the viewport.

```blade
<hw:dropdown>
    <hw:dropdown.trigger>Menu</hw:dropdown.trigger>
    <hw:dropdown.content side="bottom" align="end" :side-offset="4">
        <hw:dropdown.item href="/settings">Settings</hw:dropdown.item>
    </hw:dropdown.content>
</hw:dropdown>
```

Use responsive overrides when the same dropdown should open differently on small screens:

```blade
<hw:dropdown.content side="right" align="start" mobile-side="bottom" mobile-align="end">
    ...
</hw:dropdown.content>
```

When the media query changes while the dropdown is open, the controller recalculates Floating UI positioning.

Use collapsed overrides for icon-only sidebars or other collapsed containers. This is separate from mobile sizing: a
desktop sidebar rail is still a desktop viewport. The default collapsed selector matches the package Sidebar while it is
rendering its icon rail (`data-collapsible="icon"`), while the wrapper is collapsed, or while the Sidebar reports
`data-sidebar-collapsible="icon"` with collapsed state. The default selector avoids quoted attribute values so it can be
rendered safely as an HTML attribute.

```blade
<hw:dropdown.content side="top" align="start" collapsed-side="right" collapsed-align="end">
    ...
</hw:dropdown.content>
```

Use `strategy="fixed"` when the trigger sits inside complex scrolling or transformed layouts. Menus are promoted to the
browser top layer when supported, so they avoid common clipping issues inside overlays.

## Menu Width

By default, the Nova preset makes content match the trigger width with `w-(--anchor-width)` and hides horizontal overflow.
Set the width on `<hw:dropdown.content>` when custom content needs more room.

```blade
<hw:dropdown.content width="w-64 max-w-[calc(100vw-2rem)]">
    <hw:field.group class="p-2">
        <!-- custom content -->
    </hw:field.group>
</hw:dropdown.content>
```

Use `width="min-w-64 w-max max-w-[calc(100vw-2rem)]"` when the content should be at least a certain width but still grow
with its contents.

## Keeping The Menu Open

Disable `close-on-select` for menus with interactive content that should not dismiss the dropdown, and close manually
where needed with `data-action="dropdown#close"`.

```blade
<hw:dropdown :close-on-select="false">
    <hw:dropdown.trigger>
        Filters
        <hw:icon name="chevron-down" data-slot="dropdown-trigger-icon" />
    </hw:dropdown.trigger>

    <hw:dropdown.content width="w-64 max-w-[calc(100vw-2rem)]">
        <hw:field.group class="p-2">
            <!-- form fields -->

            <hw:button type="submit" size="sm" data-action="dropdown#close">
                Apply
            </hw:button>
        </hw:field.group>
    </hw:dropdown.content>
</hw:dropdown>
```

Use form components for interactive form content. `dropdown.item` is best for link/button actions, not checkbox rows.

## Keyboard Navigation

Dropdown stays a disclosure component, not a strict ARIA menu. It does not add `role="menu"` automatically, does not use
roving tabindex and does not capture arrow keys, `Home` or `End`. Users navigate the open panel with the browser's native
`Tab`/`Shift+Tab` focus order, which keeps both simple action lists and custom form content predictable.

`Escape` closes the dropdown and restores focus to the trigger. When nested inside an overlay such as a Drawer, Modal or
Sidebar, the dropdown consumes the first `Escape`; the parent overlay handles a later `Escape` after the dropdown has
closed.

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `dropdown` | `div` | `dropdown` |
| `dropdown.trigger` | `button` or first child with `as-child` | `dropdown-trigger` unless `as-child` |
| `dropdown.content` | `div` | `dropdown-menu` |
| `dropdown.group` | `div` with `role="group"` | `dropdown-group` |
| `dropdown.label` | `div` | `dropdown-label` |
| `dropdown.item` | `a` or `button` | `dropdown-item` |
| `dropdown.separator` | `div` with `role="separator"` | `dropdown-separator` |
| `dropdown.shortcut` | `span` | `dropdown-shortcut` |

## Styling Hooks

- `data-slot="dropdown"`
- `data-slot="dropdown-trigger"`
- `aria-expanded="true|false"`
- `data-state="open|closed"`
- `data-slot="dropdown-trigger-icon"`
- `data-slot="dropdown-menu"`
- `data-dropdown-side-value="top|right|bottom|left"`
- `data-dropdown-align-value="start|center|end"`
- `data-dropdown-mobile-side-value="top|right|bottom|left"`
- `data-dropdown-mobile-align-value="start|center|end"`
- `data-dropdown-collapsed-side-value="top|right|bottom|left"`
- `data-dropdown-collapsed-align-value="start|center|end"`
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

`width` is an explicit escape hatch on the content element itself. By default, the Nova preset sizes the menu with
`w-(--anchor-width)`, constrains it with `max-h-(--available-height)`, hides horizontal overflow, and animates from
`--transform-origin`.

## Required Controllers

- `dropdown`
