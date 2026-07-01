# Button

Displays a button or a component that looks like a button.

## Basic usage

```blade
<x-hwc::button>Save</x-hwc::button>
```

Renders a native `<button type="button">` with semantic styling hooks. The selected CSS preset applies the visual style.

## Variants

```blade
<x-hwc::button variant="default">Default</x-hwc::button>
<x-hwc::button variant="destructive">Delete</x-hwc::button>
<x-hwc::button variant="outline">Cancel</x-hwc::button>
<x-hwc::button variant="secondary">Secondary</x-hwc::button>
<x-hwc::button variant="ghost">Ghost</x-hwc::button>
<x-hwc::button variant="link">Link</x-hwc::button>
```

| Variant       | When to use                                                                          |
|---------------|--------------------------------------------------------------------------------------|
| `default`     | Primary action on a surface (e.g. "Save", "Submit")                                  |
| `destructive` | Irreversible action (e.g. alert-dialog destructive action)                           |
| `outline`     | Secondary action that should still be visible (e.g. alert-dialog cancel)             |
| `secondary`   | Tertiary action (e.g. "More options")                                                |
| `ghost`       | Action without a surface — pairs with `size="icon"` for icon-only buttons            |
| `link`        | Inline action that should look like a hyperlink                                      |

## Sizes

```blade
<x-hwc::button size="xs">Extra small</x-hwc::button>
<x-hwc::button size="sm">Small</x-hwc::button>
<x-hwc::button size="default">Default</x-hwc::button>
<x-hwc::button size="lg">Large</x-hwc::button>

<x-hwc::button size="icon-xs"><x-hwc::icon name="x" /></x-hwc::button>
<x-hwc::button size="icon-sm"><x-hwc::icon name="x" /></x-hwc::button>
<x-hwc::button size="icon"><x-hwc::icon name="x" /></x-hwc::button>
<x-hwc::button size="icon-lg"><x-hwc::icon name="x" /></x-hwc::button>
```

| Size       | Use case                                            |
|------------|-----------------------------------------------------|
| `xs`       | Inline actions, badge-like buttons                  |
| `sm`       | Compact forms, dropdown menu items                  |
| `default`  | Most actions; toolbars; form footers                |
| `lg`       | Hero CTAs                                           |
| `icon-xs`  | Tight icon button (badge close, avatar overlay)     |
| `icon-sm`  | Compact icon button                                 |
| `icon`     | Default icon button (e.g. Modal close)              |
| `icon-lg`  | Large icon button (toolbar primary)                 |

Exact dimensions are preset-owned. Use `data-size` selectors in app CSS if you need to customise them.

## Attaching a Stimulus controller / action

Blade's component parser doesn't accept `{{ $bag }}` as a bare attribute spread inside a component tag — so the usual `{{ stimulus()->controller(…)->action(…) }}` idiom can't go directly on `<x-hwc::button>`. Pass it via the named `:stimulus` prop instead:

```blade
<x-hwc::button
    as="a"
    href="{{ route('tasks.create') }}"
    :stimulus="stimulus()->controller('hotkey')->action('hotkey', 'click', 'keydown.n@window')"
    data-turbo-frame="modal"
>
    New Task
</x-hwc::button>
```

The `:stimulus` prop accepts any `Htmlable` (the `stimulus()`, `stimulus_controller()`, `stimulus_action()` and `stimulus_target()` helpers all return one). Its `toHtml()` is rendered inline alongside the regular attribute bag, so existing `data-controller` / `data-action` you pass via plain HTML attributes still merge correctly.

If you don't have a Stimulus binding, omit the prop — the rendered button has no extra `data-*` overhead.

## Rendering as a different tag

By default the component renders `<button>`. Pass `as="a"` to render an anchor with the same look — useful when the action navigates to a different page:

```blade
<x-hwc::button as="a" href="/dashboard">Dashboard</x-hwc::button>

<x-hwc::button as="a" variant="outline" href="{{ route('profile') }}">Profile</x-hwc::button>
```

When `as="a"` is used, the `type` attribute is omitted (it has no meaning on `<a>`). Any HTML attribute the anchor needs — `href`, `target`, `rel`, `download` — passes through as usual.

`as` accepts any tag name; in practice only `button` (default) and `a` are common.

## Props

| Prop      | Type     | Default     | Description                                                                                   |
|-----------|----------|-------------|-----------------------------------------------------------------------------------------------|
| `variant` | `string` | `'default'` | One of `default`, `destructive`, `outline`, `secondary`, `ghost`, `link`                       |
| `size`    | `string` | `'default'` | One of `xs`, `sm`, `default`, `lg`, `icon-xs`, `icon-sm`, `icon`, `icon-lg`                    |
| `type`    | `string` | `'button'`  | Rendered as `<button type="...">`. Use `submit` inside forms. Ignored when `as` is not `button`. |
| `as`      | `string` | `'button'`  | HTML tag to render. Use `a` for links.                                                         |
| `slotName`| `string` | `'button'` | Internal escape hatch for shipped components that need a button element with a more specific `data-slot`. Most apps should not set this. |
| `stimulus`| `Htmlable\|null` | `null` | Optional Stimulus binding (from `stimulus()`, `stimulus_controller()`, `stimulus_action()` or `stimulus_target()`). Pass via `:stimulus="..."`; rendered inline alongside the regular attributes. |

All other HTML attributes (`id`, `name`, `disabled`, `aria-*`, `data-*`, `href`, `target`, `class`) pass through to the rendered element. Package styling is not emitted as inline classes; presets target the data attributes below.

## Data attributes for CSS and tests

Every rendered button carries:

- `data-slot="button"`
- `data-variant="<variant>"`
- `data-size="<size>"`

Useful for app CSS hooks (`[data-variant="destructive"]:focus { ... }`) and automated tests that target a button without depending on Tailwind class strings.

## Already integrated in shipped components

`<x-hwc::button>` is wired into the components that ship with the package — you don't need to use it directly to benefit from the consistency:

- **Modal close button** — `variant="ghost" size="icon"` wrapping `<x-hwc::icon name="x" />`.
- **Alert-dialog cancel/action** — `outline` and `default` respectively. Pass `confirm-variant="destructive"` for destructive actions; `$cancelClass` and `$confirmClass` are forwarded as `class` attributes on the inner buttons.
