# Button

Displays a button or a component that looks like a button.

## Basic usage

```blade
<hw:button>Save</hw:button>
```

Renders a native `<button type="button">` with semantic styling hooks. The selected CSS preset applies the visual style.

## Variants

```blade
<hw:button variant="default">Default</hw:button>
<hw:button variant="destructive">Delete</hw:button>
<hw:button variant="outline">Cancel</hw:button>
<hw:button variant="secondary">Secondary</hw:button>
<hw:button variant="ghost">Ghost</hw:button>
<hw:button variant="link">Link</hw:button>
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
<hw:button size="xs">Extra small</hw:button>
<hw:button size="sm">Small</hw:button>
<hw:button size="default">Default</hw:button>
<hw:button size="lg">Large</hw:button>

<hw:button size="icon-xs"><hw:icon name="x" /></hw:button>
<hw:button size="icon-sm"><hw:icon name="x" /></hw:button>
<hw:button size="icon"><hw:icon name="x" /></hw:button>
<hw:button size="icon-lg"><hw:icon name="x" /></hw:button>
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

Blade's component parser doesn't accept `{{ $bag }}` as a bare attribute spread inside a component tag — so the usual `{{ stimulus()->controller(…)->action(…) }}` idiom can't go directly on `<hw:button>`. Pass it via the named `:stimulus` prop instead:

```blade
<hw:button
    as="a"
    href="{{ route('tasks.create') }}"
    :stimulus="stimulus()->controller('hotkey')->action('hotkey', 'click', 'keydown.n@window')"
    data-turbo-frame="modal"
>
    New Task
</hw:button>
```

The `:stimulus` prop accepts the output of `stimulus()`, `stimulus_controller()`, `stimulus_action()` or `stimulus_target()` (they are both `Htmlable` and `Arrayable`). It is merged with the regular attribute bag, so existing `data-controller` / `data-action` you pass via plain HTML attributes compose with the prop and repeated tokens are deduplicated.

If you don't have a Stimulus binding, omit the prop — the rendered button has no extra `data-*` overhead.

## Targeting a Turbo Frame

Use `frame` to render `data-turbo-frame` without writing the raw attribute manually:

```blade
<hw:button as="a" href="{{ route('tasks.create') }}" frame="modal">
    New task
</hw:button>
```

This renders `data-turbo-frame="modal"`. If you pass an explicit `data-turbo-frame` attribute, it wins over `frame`.

## Keyboard shortcuts

Use `hotkey` to mount the `hotkey` controller on the button and click it from a global keydown action:

```blade
<hw:button type="submit" hotkey="ctrl+s">
    Save
</hw:button>
```

Multiple shortcuts are separated by spaces. `cmd` is normalized to Stimulus' `meta` modifier; no platform detection is
performed.

```blade
<hw:button hotkey="cmd+s ctrl+s">
    Save
</hw:button>
```

This renders actions for `keydown.meta+s@window->hotkey#click` and `keydown.ctrl+s@window->hotkey#click`.

Shortcut behavior and shortcut display are separate. If the key should be visible, compose `<hw:kbd>` in the button
slot; `hotkey` only wires the action:

```blade
<hw:button type="submit" hotkey="cmd+s">
    Save <hw:kbd>⌘S</hw:kbd>
</hw:button>
```

## Tooltip

Use `tooltip` to mount the `tooltip` controller and set concise string content:

```blade
<hw:button tooltip="Save changes" tooltip-side="bottom" tooltip-align="end">
    Save
</hw:button>
```

The `tooltip` prop is intentionally a lightweight Button convenience. Do not put Blade component markup in the
attribute. For rich content such as `Save Changes <hw:kbd>S</hw:kbd>`, use an explicit tooltip composition instead of a
Button prop.

For conditional display, pass `tooltip-enabled-when` with the selector expected by the tooltip controller:

```blade
<hw:button
    tooltip="Only shown while collapsed"
    tooltip-side="right"
    tooltip-enabled-when="[data-slot=sidebar][data-collapsible=icon]"
>
    <x-lucide-settings class="size-4" />
</hw:button>
```

When `tooltip` is active, configure tooltip values through Button props. Raw `data-tooltip-*` attributes are reserved for
the internal controller wiring and are ignored in favour of the explicit props.

## Rendering as a different tag

By default the component renders `<button>`. Pass `as="a"` to render an anchor with the same look — useful when the action navigates to a different page:

```blade
<hw:button as="a" href="/dashboard">Dashboard</hw:button>

<hw:button as="a" variant="outline" href="{{ route('profile') }}">Profile</hw:button>
```

When `as="a"` is used, the `type` attribute is omitted (it has no meaning on `<a>`). Any HTML attribute the anchor needs — `href`, `target`, `rel`, `download` — passes through as usual.

`as` accepts any tag name; in practice only `button` (default) and `a` are common.

## Props

| Prop                   | Type             | Default     | Description                                                                                                                                                                   |
|------------------------|------------------|-------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `variant`              | `string`         | `'default'` | One of `default`, `destructive`, `outline`, `secondary`, `ghost`, `link`                                                                                                      |
| `size`                 | `string`         | `'default'` | One of `xs`, `sm`, `default`, `lg`, `icon-xs`, `icon-sm`, `icon`, `icon-lg`                                                                                                   |
| `type`                 | `string`         | `'button'`  | Rendered as `<button type="...">`. Use `submit` inside forms. Ignored when `as` is not `button`.                                                                              |
| `as`                   | `string`         | `'button'`  | HTML tag to render. Use `a` for links.                                                                                                                                        |
| `slotName`             | `string`         | `'button'`  | Internal escape hatch for shipped components that need a button element with a more specific `data-slot`. Most apps should not set this.                                      |
| `frame`                | `string\|null`   | `null`      | Render `data-turbo-frame` for links/actions targeting a Turbo Frame. Explicit `data-turbo-frame` wins.                                                                        |
| `hotkey`               | `string\|null`   | `null`      | Mount `hotkey` and click the button from one or more global keyboard shortcuts. `cmd` maps to `meta`.                                                                         |
| `tooltip`              | `string\|null`   | `null`      | Mount `tooltip` and set `data-tooltip-content-value`.                                                                                                                         |
| `tooltip-side`         | `string\|null`   | `null`      | Set `data-tooltip-side-value` when `tooltip` is active.                                                                                                                       |
| `tooltip-align`        | `string\|null`   | `null`      | Set `data-tooltip-align-value` when `tooltip` is active.                                                                                                                      |
| `tooltip-enabled-when` | `string\|null`   | `null`      | Set `data-tooltip-enabled-when-value` when `tooltip` is active.                                                                                                               |
| `stimulus`             | `Htmlable\|null` | `null`      | Optional Stimulus binding from `stimulus()`, `stimulus_controller()`, `stimulus_action()` or `stimulus_target()`. Pass via `:stimulus="..."`; merged with regular attributes. |

All other HTML attributes (`id`, `name`, `disabled`, `aria-*`, `data-*`, `href`, `target`, `class`) pass through to the rendered element. Package styling is not emitted as inline classes; presets target the data attributes below.

## Data attributes for CSS and tests

Every rendered button carries:

- `data-slot="button"`
- `data-variant="<variant>"`
- `data-size="<size>"`

Useful for app CSS hooks (`[data-variant="destructive"]:focus { ... }`) and automated tests that target a button without depending on Tailwind class strings.

## Already integrated in shipped components

`<hw:button>` is wired into the components that ship with the package — you don't need to use it directly to benefit from the consistency:

- **Modal close button** — `variant="ghost" size="icon"` wrapping `<hw:icon name="x" />`.
- **Alert-dialog cancel/action** — `outline` and `default` respectively. Pass `confirm-variant="destructive"` for destructive actions; `$cancelClass` and `$confirmClass` are forwarded as `class` attributes on the inner buttons.
