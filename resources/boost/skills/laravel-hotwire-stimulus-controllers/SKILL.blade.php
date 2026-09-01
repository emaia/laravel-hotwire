---
name: laravel-hotwire-stimulus-controllers
description: >-
  Write, compose, extend and publish Laravel Hotwire Stimulus controllers with package naming, shared-element safety,
  lifecycle cleanup, fluent stimulus helpers, local overrides, and Laravel Idea metadata.
---
@php
    $prefix = config('hotwire.prefix', 'hw');
    $component = static fn (string $name): string => "<{$prefix}:{$name}>";
@endphp
# Laravel Hotwire Stimulus Controllers

## When to use

Load this skill when writing client-side behavior, stacking a controller on `{!! $component('form') !!}` or another
component, extending a package controller, publishing a fork, wiring targets/actions/values, or handling Turbo lifecycle
events. Use component props first when a shipped component already exposes the behavior.

## Loading and naming

After `php artisan hotwire:install`, package controllers load lazily from vendor and application controllers load from
`resources/js/controllers`; an application identifier shadows the package identifier. Publishing is customization, not
activation.

Package files are flat by intent (`auto_submit_controller.js` -> `auto-submit`) except technical substrate folders
(`turbo/progress_controller.js` -> `turbo--progress`). Files beginning with `_` are helpers, not controllers.

The application generator currently requires a namespace/name:

```bash
php artisan hotwire:make-controller form/auto-save
php artisan hotwire:make-controller form/auto-save --ts
```

This creates `form/auto_save_controller.js` or `.ts` with identifier `form--auto-save`.

## Shared-element safety

Package components commonly mount several controllers on one element. A custom controller must therefore:

- Scope DOM reads/writes to `this.element` and declared targets.
- Never overwrite `data-controller`, `data-action` or attributes owned by another controller.
- Make shared Turbo event handlers idempotent and order-independent.
- Use `event.detail.success` consistently for `turbo:submit-end`.
- Dispatch an event for cross-controller communication instead of reaching into another controller's internals.
- Remove every listener, timer, observer and third-party instance in `disconnect()`.

```js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.onRender = this.render.bind(this);
        document.addEventListener("turbo:render", this.onRender);
        this.interval = window.setInterval(() => this.render(), 30_000);
    }

    disconnect() {
        document.removeEventListener("turbo:render", this.onRender);
        window.clearInterval(this.interval);
    }

    render() {
        // Keep work scoped to this.element.
    }
}
```

Use Playwright rather than happy-dom when behavior depends on MutationObserver delivery, focus, layout,
requestAnimationFrame, Turbo-like DOM replacement or real browser timing.

## Fluent Blade helpers

Available helpers:

```php
stimulus();
stimulus_controller($name, $values = [], $classes = [], $outlets = []);
stimulus_action($controller, $method, $event = null, $params = []);
stimulus_target($controller, $target);
```

The builder exposes `controllers()`, `controller()`, `action()`, `target()`, `toArray()` and `toHtml()`. Keys become
kebab-case; null configuration is omitted; booleans/arrays/objects are JSON encoded; repeated tokens are deduplicated.
Names are not sanitized, so user-controlled data belongs in values or params, never attribute names.

```blade
<div
    {!! '{' !!}{!! '{' !!}
        stimulus()
            ->controller('copy-to-clipboard', ['successContent' => 'Copied'])
    {!! '}' !!}{!! '}' !!}
>
    <textarea
        {!! '{' !!}{!! '{' !!}
            stimulus()
                ->controller('auto-resize', ['resizeDebounceDelay' => 0])
                ->target('copy-to-clipboard', 'source')
        {!! '}' !!}{!! '}' !!}
    ></textarea>

    <button
        type="button"
        {!! '{' !!}{!! '{' !!}
            stimulus()
                ->target('copy-to-clipboard', 'button')
                ->action('copy-to-clipboard', 'copy', 'click')
        {!! '}' !!}{!! '}' !!}
    >
        Copy
    </button>
</div>
```

Pass the builder through a documented component `stimulus` prop when available; otherwise use normal attributes. Do not
try to override protected `data-{internal-controller}-*` configuration.

## Extend or fork

Prefer a new application identifier that subclasses the vendor controller through the `@hotwire` Vite alias:

```js
import CarouselController from "@hotwire/carousel_controller.js";

export default class extends CarouselController {
    static targets = [...CarouselController.targets, "caption"];
}
```

Preserve inherited targets, values and classes. If lifecycle methods are overridden, call `super.connect()` and
`super.disconnect()` when the parent defines them.

Fork only when the application must change behavior under the same identifier:

```bash
php artisan hotwire:controllers carousel
```

The local source then shadows vendor. Package-owned JS starts with `// @hotwire-package`; generated application
controllers intentionally do not. Removing the marker from a published fork protects it from `hotwire:check --fix` and
future forced updates. Do not add the marker to hand-written code unless opting back into package replacement.

`controller` is not a universal component prop. It exists only for intentionally open integrations; otherwise compose
another controller and react to public events.

## Commands

```bash
php artisan hotwire:controllers --list
php artisan hotwire:controllers turbo/progress
php artisan hotwire:controllers --outdated --force
php artisan hotwire:check
php artisan hotwire:check --fix
php artisan hotwire:ide-json
```

Run `php artisan hotwire:ide-json` after adding or renaming application controllers when IDE completion is needed.

## Verify

Unit-test deterministic controller behavior with the package Stimulus test helper. Use browser tests for focus,
MutationObserver, animation and Turbo DOM timing. Then run `php artisan hotwire:check`.

## See also

- `laravel-hotwire-forms` for component-owned form controllers and props.
- `laravel-hotwire-turbo-workflows` for Turbo event and cache semantics.
- `laravel-hotwire-ui-development` for overlay/accessibility composition.
