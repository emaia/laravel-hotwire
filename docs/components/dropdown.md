# Dropdown

Accessible disclosure dropdown — a trigger button toggles a menu, with outside-click and `Escape` dismissal (returning
focus to the trigger), optional close-on-select, and animated open/close. Wraps the
[`dropdown`](../controllers/dropdown.md) Stimulus controller and wires up all the accessibility attributes for you.

## Basic usage

```html

<x-hwc::dropdown>
    <x-slot:trigger>
        Options
        <svg class="size-5"><!-- chevron --></svg>
    </x-slot:trigger>

    <a href="/account" class="block px-4 py-2 text-sm hover:bg-gray-100">Account</a>
    <a href="/support" class="block px-4 py-2 text-sm hover:bg-gray-100">Support</a>
    <form action="/logout" method="post">
        @csrf
        <button type="submit" class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100">Sign out</button>
    </form>
</x-hwc::dropdown>
```

The component renders the `<button>` trigger and the menu, links them via `id`/`aria-controls`, and keeps
`aria-expanded` in sync. Menu items are your own markup;
clicking an `<a>` or `<button>` closes the menu by default.

> **The `trigger` slot is the button's _content_, not the button.** The component already renders the `<button>` — don't
> put your own `<button>` inside the slot, or you'll get a (invalid) nested button that the browser unwraps, breaking
> the click wiring. Style the button by passing classes/attributes on the slot tag itself
> (`<x-slot:trigger class="btn-outline">…</x-slot:trigger>`) or with the `trigger-class` prop.

## Props

| Prop              | Type     | Default                          | Description                                                             |
|-------------------|----------|----------------------------------|-------------------------------------------------------------------------|
| `id`              | `string` | `uniqid('dropdown-')`            | The menu's `id` (and the trigger's `aria-controls`)                     |
| `align`           | `string` | `start`                          | Menu alignment: `start` or `end` (logical, so RTL-aware)                |
| `open`            | `bool`   | `false`                          | Start open (no animation)                                               |
| `close-on-select` | `bool`   | `true`                           | Close when an `<a>`/`<button>` inside the menu is clicked               |
| `transition`      | `bool`   | `true`                           | Include the default enter/leave transition classes                      |
| `trigger-class`   | `string` | `inline-flex items-center gap-1` | Trigger button layout classes (override freely)                         |
| `width`           | `string` | `w-56`                           | Menu width utility (override as needed)                                 |
| `menu-class`      | `string` | `''`                             | Extra classes appended to the menu                                      |

The `trigger` slot's own attributes are merged onto the rendered button — e.g.
`<x-slot:trigger class="btn">` adds `btn` to it. Use `trigger-class` to replace the default layout classes, or the
slot's `class` to append.

## Alignment

The menu aligns to the trigger's **start** edge by default (drops straight down, extends toward the end). Use
`align="end"` for triggers pinned to the end edge (e.g., an account menu in the top corner) so the menu doesn't
overflow. Both are logical, so they flip correctly under RTL.

```html

<x-hwc::dropdown align="end">
    <x-slot:trigger>Menu</x-slot:trigger>
    <a href="/x" class="block px-4 py-2 text-sm">Item</a>
</x-hwc::dropdown>
```

## Keeping the menu open on click

Disable `close-on-select` for menus with interactive content that shouldn't dismiss the dropdown, and close manually
where needed with `data-action="dropdown#close"`:

```html

<x-hwc::dropdown :close-on-select="false">
    <x-slot:trigger>Filters</x-slot:trigger>

    <label class="block px-4 py-2 text-sm"><input type="checkbox" name="active"/> Active</label>
    <button type="button" data-action="dropdown#close" class="block w-full px-4 py-2 text-left text-sm">Apply</button>
</x-hwc::dropdown>
```

## Custom styling and transitions

Override the menu box with `width`/`menu-class`, the trigger via the `trigger` slot's attributes, and disable the
built-in animation with `:transition="false"` (e.g., to drive it with CSS only). See the
[controller docs](../controllers/dropdown.md) for the transition attributes and the Tailwind v4 CSS-only approach.

## Accessibility

This is the **disclosure** pattern: `aria-expanded`/`aria-controls`/`aria-haspopup` are wired automatically, `Escape`
closes and restores focus to the trigger, and clicking outside dismisses. It does not impose `role="menu"` semantics —
for a strict ARIA menu with arrow-key roving, build it directly on the controller.
