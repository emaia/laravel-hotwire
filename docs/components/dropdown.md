# Dropdown

Accessible disclosure dropdown — a trigger button toggles a menu, with outside-click and `Escape` dismissal (returning
focus to the trigger), optional close-on-select, and animated open/close. Wraps the
[`dropdown`](../controllers/dropdown.md) Stimulus controller and wires up all the accessibility attributes for you.

## Basic usage

```html

<hw:dropdown>
    <x-slot:trigger>
        Options
        <hw:icon name="chevron-down" data-slot="dropdown-trigger-icon"/>
    </x-slot:trigger>

    <hw:dropdown.group>
        <hw:dropdown.label>Account</hw:dropdown.label>
        <hw:dropdown.item href="/account">Profile</hw:dropdown.item>
        <hw:dropdown.item href="/support">Support</hw:dropdown.item>
        <hw:dropdown.separator/>
        <hw:dropdown.item variant="destructive">Sign out</hw:dropdown.item>
    </hw:dropdown.group>
</hw:dropdown>
```

The component renders the `<button>` trigger and the menu, links them via `id`/`aria-controls`, and keeps
`aria-expanded` in sync. Use the menu subcomponents for common item markup, or bring your own markup when you need
custom interactive content. Clicking an `<a>` or `<button>` inside the menu closes it by default.

Add `data-slot="dropdown-trigger-icon"` to a chevron inside the trigger when you want it to rotate with the open
state. The Nova preset targets the trigger's `aria-expanded="true"` state, so no `group-*` class is required.

> **The `trigger` slot is the button's _content_, not the button.** The component already renders the `<button>` — don't
> put your own `<button>` inside the slot, or you'll get a (invalid) nested button that the browser unwraps, breaking
> the click wiring. Style the button by passing classes/attributes on the slot tag itself
> (`<x-slot:trigger class="btn-outline">…</x-slot:trigger>`) or with the `trigger-class` prop.

## Menu Subcomponents

```html

<hw:dropdown align="end">
    <x-slot:trigger>Command menu</x-slot:trigger>

    <hw:dropdown.group>
        <hw:dropdown.label inset>Project</hw:dropdown.label>
        <hw:dropdown.item href="/projects/new" inset>
            New project
            <hw:dropdown.shortcut>N</hw:dropdown.shortcut>
        </hw:dropdown.item>
        <hw:dropdown.separator/>
        <hw:dropdown.item disabled>Archive</hw:dropdown.item>
    </hw:dropdown.group>
</hw:dropdown>
```

| Component            | Description                                                         |
|----------------------|---------------------------------------------------------------------|
| `dropdown.group`     | Semantic wrapper for related menu content                           |
| `dropdown.label`     | Section label; accepts `inset`                                      |
| `dropdown.item`      | Link or button item; accepts `href`, `variant`, `disabled`, `inset` |
| `dropdown.separator` | Horizontal visual separator                                         |
| `dropdown.shortcut`  | Trailing shortcut/helper text, usually inside an item               |

`dropdown.item` renders an `<a>` when `href` is present, otherwise it renders a `<button type="button">`. Use
`variant="destructive"` for destructive actions. Disabled link items keep their `href` for semantics but receive
`aria-disabled="true"` and `tabindex="-1"`.

## Props

| Prop              | Type     | Default               | Description                                                           |
|-------------------|----------|-----------------------|-----------------------------------------------------------------------|
| `id`              | `string` | `uniqid('dropdown-')` | The menu's `id` (and the trigger's `aria-controls`)                   |
| `align`           | `string` | `start`               | Menu alignment: `start` or `end` (logical, so RTL-aware)              |
| `open`            | `bool`   | `false`               | Start open (no animation)                                             |
| `close-on-select` | `bool`   | `true`                | Close when an `<a>`/`<button>` inside the menu is clicked             |
| `transition`      | `bool`   | `true`                | Include the default enter/leave transition classes                    |
| `trigger-class`   | `string` | `''`                  | Trigger button classes; the Nova preset styles the trigger by default |
| `width`           | `string` | `''`                  | Menu width classes; the Nova preset applies the default width         |
| `menu-class`      | `string` | `''`                  | Extra classes appended to the menu                                    |

The `trigger` slot's own attributes are merged onto the rendered button — e.g.
`<x-slot:trigger class="btn">` adds `btn` to it. Use `trigger-class` to replace the default layout classes, or the
slot's `class` to append.

## Alignment

The menu aligns to the trigger's **start** edge by default (drops straight down, extends toward the end). Use
`align="end"` for triggers pinned to the end edge (e.g., an account menu in the top corner) so the menu doesn't
overflow. Both are logical, so they flip correctly under RTL.

```html

<hw:dropdown align="end">
    <x-slot:trigger>Menu</x-slot:trigger>
    <hw:dropdown.item href="/x">Item</hw:dropdown.item>
</hw:dropdown>
```

## Keeping the menu open on click

Disable `close-on-select` for menus with interactive content that shouldn't dismiss the dropdown, and close manually
where needed with `data-action="dropdown#close"`:

```html

<hw:dropdown :close-on-select="false">
    <x-slot:trigger>Filters</x-slot:trigger>

    <label>
        <input type="checkbox" name="active"/>
        Active
    </label>

    <hw:dropdown.item data-action="dropdown#close">Apply</hw:dropdown.item>
</hw:dropdown>
```

## Custom styling and transitions

Override the menu box with `width`/`menu-class`, the trigger via the `trigger` slot's attributes, and disable the
built-in animation with `:transition="false"` (e.g., to drive it with CSS only). See the
[controller docs](../controllers/dropdown.md) for the transition attributes and the Tailwind v4 CSS-only approach.

## Accessibility

This is the **disclosure** pattern: `aria-expanded`/`aria-controls`/`aria-haspopup` are wired automatically, `Escape`
closes and restores focus to the trigger, and clicking outside dismisses. It does not impose `role="menu"` semantics —
for a strict ARIA menu with arrow-key roving, build it directly on the controller.
