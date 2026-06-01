# Dropdown

Accessible disclosure dropdown: a trigger button toggles a menu. It dismisses on outside click, on `Escape` (returning
focus to the trigger) and — by default — when an actionable item inside the menu is clicked. The show/hide animation is
optional and driven entirely by CSS classes; positioning is left to you.

**Identifier:** `dropdown`  
**Install:** `php artisan hotwire:controllers dropdown`

## Requirements

- No external dependencies. Ships with a small `_transition.js` helper, which `hotwire:controllers` publishes alongside
  the controller automatically.

## Targets

| Target    | Required | Description                                                                                            |
|-----------|:--------:|--------------------------------------------------------------------------------------------------------|
| `trigger` |    ✅     | The button that toggles the menu; its `aria-expanded` is synced and it receives focus back on `Escape` |
| `menu`    |    ✅     | The element shown/hidden                                                                               |

## Stimulus Values

| Value             | Type      | Default | Description                                                              |
|-------------------|-----------|---------|--------------------------------------------------------------------------|
| `open`            | `Boolean` | `false` | Initial/reflected open state. Set to `true` to start open (no animation) |
| `close-on-select` | `Boolean` | `true`  | Close when an `<a>` or `<button>` inside the menu is clicked             |

## Stimulus Classes

| Class    | Default    | Description                    |
|----------|------------|--------------------------------|
| `hidden` | `"hidden"` | Class toggled to hide the menu |

## Actions

| Action   | Description                              |
|----------|------------------------------------------|
| `toggle` | Toggle open/closed (bind to the trigger) |
| `open`   | Open the menu                            |
| `close`  | Close the menu                           |

## Basic usage

Position the menu yourself with CSS (e.g. a `relative` wrapper and an `absolute` menu):

```html

<div data-controller="dropdown" class="relative inline-block">
    <button
        data-dropdown-target="trigger"
        data-action="dropdown#toggle"
        aria-haspopup="true"
        aria-expanded="false"
        class="group inline-flex items-center gap-1"
    >
        Options
        <svg class="size-5 transition-transform group-aria-expanded:rotate-180"><!-- chevron --></svg>
    </button>

    <div data-dropdown-target="menu" class="absolute right-0 mt-2 hidden w-56 rounded-md bg-white shadow-lg">
        <a href="/account" class="block px-4 py-2 text-sm">Account</a>
        <a href="/support" class="block px-4 py-2 text-sm">Support</a>
        <form action="/logout" method="post">
            <button type="submit" class="block w-full px-4 py-2 text-left text-sm">Sign out</button>
        </form>
    </div>
</div>
```

The chevron rotates for free as the menu opens: the controller keeps `aria-expanded` in sync on the button, the button
is a `group`, and the icon reacts with `group-aria-expanded:rotate-180`. (Use the `group-aria-expanded:` variant, not
`aria-expanded:` — the latter would check the icon's own attribute, which is never set.)

## Transitions

Declare enter/leave transitions with `data-transition-*` on the menu (Vue/`stimulus-use` style). They are optional —
without them the menu just toggles the hidden class.

```html

<div
    data-dropdown-target="menu"
    class="absolute origin-top-right right-0 mt-2 hidden w-56 rounded-md bg-white shadow-lg"
    data-transition-enter="transition ease-out duration-100"
    data-transition-enter-from="opacity-0 scale-95"
    data-transition-enter-to="opacity-100 scale-100"
    data-transition-leave="transition ease-in duration-75"
    data-transition-leave-from="opacity-100 scale-100"
    data-transition-leave-to="opacity-0 scale-95"
>
    …
</div>
```

| Attribute                    | Applied                                  |
|------------------------------|------------------------------------------|
| `data-transition-enter`      | Throughout the enter transition (timing) |
| `data-transition-enter-from` | Enter start state                        |
| `data-transition-enter-to`   | Enter end state                          |
| `data-transition-leave`      | Throughout the leave transition (timing) |
| `data-transition-leave-from` | Leave start state                        |
| `data-transition-leave-to`   | Leave end state                          |

### CSS-only transitions (Tailwind v4)

You can skip the `data-transition-*` attributes entirely and animate with CSS, keyed off the hidden class — the
controller then just toggles `hidden` instantly and CSS does the rest. This needs three pieces, because `hidden` is
`display: none`:

1. **`@starting-style`** — the state the menu animates _from_ when it leaves `display: none` (the enter start).
2. **`transition-behavior: allow-discrete`** (Tailwind `transition-discrete`) — lets `display` take part in the
   transition, deferring `display: none` until the leave animation finishes.
3. **`display` in the transitioned properties** (hence `transition-all`).

In plain CSS:

```css
.menu {
    transition: opacity 150ms ease,
    transform 150ms ease,
    display 150ms allow-discrete;
    opacity: 1;
    transform: scale(1);
}

/* hidden state — the class the controller toggles */
.menu.hidden {
    display: none;
    opacity: 0;
    transform: scale(0.95);
}

/* where the enter animation starts (coming out of display: none) */
@starting-style {
    .menu:not(.hidden) {
        opacity: 0;
        transform: scale(0.95);
    }
}
```

The same thing with Tailwind v4 utilities — no `data-transition-*` needed:

```html

<div
    data-dropdown-target="menu"
    class="absolute right-0 mt-2 hidden w-56 scale-100 rounded-md bg-white opacity-100 shadow-lg transition-all transition-discrete duration-150 ease-out starting:scale-95 starting:opacity-0 [&.hidden]:scale-95 [&.hidden]:opacity-0"
>
    …
</div>
```

| Utility                                    | Role                                                          |
|--------------------------------------------|---------------------------------------------------------------|
| `transition-all transition-discrete`       | Transition everything (incl. `display`) with `allow-discrete` |
| `opacity-100 scale-100`                    | Resting (visible) state                                       |
| `starting:opacity-0 starting:scale-95`     | `@starting-style` — where the enter starts                    |
| `[&.hidden]:opacity-0 [&.hidden]:scale-95` | Leave state, matched to the hidden class                      |

> The two approaches are mutually exclusive: provide `data-transition-*` and the JS engine drives the animation (any
> browser/Tailwind); omit them and the CSS path above takes over (needs `@starting-style`/`allow-discrete`, i.e. recent
> browsers + Tailwind v4).

## Closing on select

By default, clicking an `<a>` or `<button>` inside the menu closes it. To opt out and close manually:

```html

<div data-controller="dropdown" data-dropdown-close-on-select-value="false">
    <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Filters</button>
    <div data-dropdown-target="menu" class="hidden">
        <!-- interactive content that should not dismiss the menu -->
        <button type="button" data-action="dropdown#close">Apply</button>
    </div>
</div>
```

## Accessibility

- `aria-expanded` is kept in sync on the trigger(s).
- `Escape` closes the menu and returns focus to the trigger.
- Set `aria-haspopup` and, if you give the menu an `id`, `aria-controls` on the trigger.
- This is the **disclosure** pattern (a button revealing a panel), which is correct for menus of links/actions. It does
  not impose `role="menu"`/`menuitem` semantics or arrow-key roving — that strict ARIA menu pattern is out of scope.

## Turbo

The dropdown closes on `turbo:before-cache`, so a cached page snapshot is never restored with the menu open.
