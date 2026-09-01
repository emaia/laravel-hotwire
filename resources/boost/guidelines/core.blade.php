@php
    $registry = \Emaia\LaravelHotwire\Registry\HotwireRegistry::make();
    $prefix = config('hotwire.prefix', 'hw');
    $components = [];
    $controllers = [];

    foreach ($registry->components() as $component) {
        $components[$component->category->value][] = "`<{$prefix}:{$component->key}>`";
    }

    foreach ($registry->controllers() as $controller) {
        $controllers[$controller->category->value][] = "`{$controller->identifier}`";
    }
@endphp
# Laravel Hotwire

Laravel Hotwire is a server-driven UI toolkit combining Turbo Drive, Frames and Streams with Stimulus controllers and
Blade components. The configured component prefix in this application is `{{ $prefix }}`.

## Working rules

- Before writing unfamiliar markup, run `php artisan hotwire:docs <name>` and use the documented props exactly. Never
  invent a component prop or protected `data-{identifier}-*` configuration.
- Run `php artisan hotwire:components` to inspect component/controller relationships and `php artisan hotwire:check`
  after changing package usage.
- Package controllers auto-load from vendor after installation. `php artisan hotwire:controllers <name>` publishes a
  controller only when the application needs to customize or fork it.
- Package components provide semantic `data-slot`, variant, size and state hooks. Presets provide the package styling;
  do not expect package-authored utility classes in rendered markup.
- Configure component-owned controllers through documented component props. Additional application controllers may be
  composed on the same element without replacing the component's controller tokens.
- Load `laravel-hotwire-forms` for forms, validation, fields, controls, uploads and frame-hosted forms.
- Load `laravel-hotwire-turbo-workflows` for Frames, Streams, morphing, cache and optimistic updates.
- Load `laravel-hotwire-ui-development` for display, feedback, navigation, overlays, accessibility and styling.
- Load `laravel-hotwire-stimulus-controllers` when writing, extending or publishing Stimulus controllers.

## Available catalog

@foreach (\Emaia\LaravelHotwire\Registry\Category::cases() as $category)
@if (($components[$category->value] ?? []) !== [] || ($controllers[$category->value] ?? []) !== [])
### {{ ucfirst($category->value) }}

@if (($components[$category->value] ?? []) !== [])
Components: {!! implode(', ', $components[$category->value]) !!}
@endif

@if (($controllers[$category->value] ?? []) !== [])
Controllers: {!! implode(', ', $controllers[$category->value]) !!}
@endif

@endif
@endforeach

Use `php artisan hotwire:docs` as the exhaustive local reference. The bundled skills route decisions and common failure
modes; they intentionally do not duplicate every prop and controller contract from `docs/`.
