---
name: laravel-hotwire-ui-development
description: >-
  Compose Laravel Hotwire display, feedback, navigation, overlay and utility components with accessible structure,
  nested overlay behavior, semantic data slots, themes, presets, and application styling.
---
@php
    $prefix = config('hotwire.prefix', 'hw');
    $component = static fn (string $name): string => "<{$prefix}:{$name}>";
    $open = static fn (string $name, string $attributes = ''): string => "<{$prefix}:{$name}".($attributes === '' ? '' : " {$attributes}").'>';
    $close = static fn (string $name): string => "</{$prefix}:{$name}>";
@endphp
# Laravel Hotwire UI Development

## When to use

Load this skill for components in the display, feedback, navigation, overlay and utility catalog categories; for
component composition and accessibility; or for preset/theme work. Run `php artisan hotwire:docs <name> --component`
before using an unfamiliar component or prop.

## Composition contract

- Package components render semantic structure and hooks such as `data-slot`, `data-variant`, `data-size`, `data-state`
  and ARIA/native state. Presets turn those hooks into appearance.
- Components may merge an application `data-controller` with internal controllers. Never replace the whole attribute or
  write protected `data-{internal-controller}-*` configuration when a documented prop exists.
- Prefer component events and an additional application controller over forking a closed component controller.
- Use stable unique ids for controls, overlays, frame hosts and morph targets.
- Preserve semantic labels, descriptions, roles and keyboard behavior when composing subcomponents manually.

Use `php artisan hotwire:components` to see dependencies. The catalog is broad; do not substitute an invented component
name for a documented one.

## Overlay selection

| Interaction | Prefer |
| --- | --- |
| Centered task/dialog | `{!! $component('modal') !!}` |
| Destructive confirmation with captured action | `{!! $component('alert-dialog') !!}` |
| Edge panel with drawer gesture/direction semantics | `{!! $component('drawer') !!}` |
| Edge-aligned dialog panel | `{!! $component('sheet') !!}` |
| Anchored action menu | `{!! $component('dropdown') !!}` |
| Anchored non-modal content | `{!! $component('popover') !!}` |
| Hover/focus preview | `{!! $component('hover-card') !!}` |

Set an explicit accessible title/description or ARIA label. Do not remove focus management, Escape handling, inert state
or focus return to obtain a visual effect.

## Nested overlays

Render independent overlay roots as siblings in the layout, with unique ids and Frame ids. Opening a child suspends the
parent focus trap; only the top overlay handles Escape. Scroll locks remain reference-counted until the final locking
overlay closes.

```blade
{!! $open('modal', 'id="user-modal-shell" frame="user-modal" size="lg"') !!}{!! $close('modal') !!}
{!! $open('modal', 'id="roles-modal-shell" frame="roles-modal" size="md"') !!}{!! $close('modal') !!}

{!! $open('button', 'as="a" href="/users/1/roles" data-turbo-frame="roles-modal"') !!}
    Manage roles
{!! $close('button') !!}
```

Do not nest a destructive form inside an update form. Do not duplicate a raw frame already owned by an overlay root.
Render overlay subcomponents under their owning root so they receive the required context. For a complete server-driven
workflow, load `laravel-hotwire-turbo-workflows`.

## Component-owned behavior

When a component prop activates a controller, use that prop. Examples include Button `hotkey`, Form `auto-submit`, and
overlay motion/position props. Unsupported internal `data-*` may be filtered and fail silently. Use the `stimulus` prop
where documented to add application controllers/actions without clobbering the component wiring.

Only components with an intentionally open integration surface expose a `controller` prop. Do not assume every
controller-backed component can swap identifiers; composition is the default extension path.

## Styling

Read [styling and preset rules](references/styling.md) before changing appearance. Core rules:

- Use semantic tokens, never package-authored raw color utilities.
- Dark mode is `<html data-theme="dark">`, not a `dark` class.
- Put visual appearance in preset selectors targeting `data-slot`.
- Put mechanics whose absence breaks behavior in structural CSS.
- Do not add Tailwind `@source` for imported package presets.
- Closed Presence states must remain measurable during exit motion; do not force `display: none` before Presence applies
  `hidden`.

For a custom preset:

```bash
php artisan hotwire:make-preset brand --from=nova
```

For a selective bundle:

```bash
php artisan hotwire:styles --preset=nova --components=button,field,input,modal --include=tooltip --output=resources/css/hotwire.css
```

Use `--include` for components/controllers emitted dynamically by Streams or JavaScript because static scanning cannot
discover them. Do not import a full preset and a selective bundle together, and never hand-edit a generated bundle.

## Verify

Run `php artisan hotwire:check`. Manually verify keyboard navigation, focus return, Escape, nested overlay behavior,
light/dark themes, mobile layout and the component both inside and outside Turbo Frames.

## See also

- `laravel-hotwire-forms` for accessible field and validation contracts.
- `laravel-hotwire-turbo-workflows` for frame-backed overlays and server reconciliation.
- `laravel-hotwire-stimulus-controllers` for application behavior composed onto components.
