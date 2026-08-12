# Controller Preloads

`<hw:controller-preloads>` emits production Vite `modulepreload` links for selected Stimulus controllers. It renders
nothing while the Vite development server is running.

```blade
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <hw:controller-preloads />
</head>
```

Keep `@vite` first so its existing preloads are deduplicated. When it uses an invocation-specific build directory, pass
the same directory to the component with `build-directory="frontend"`.

The default selection comes from `config/hotwire.php`:

```php
'controllers' => [
    'preload' => ['reveal', 'turbo--progress'],
    'eager' => [],
],
```

Pass `controllers` to replace the configured selection for one page:

```blade
<hw:controller-preloads :controllers="['catalog-search']" />
```

Application controllers under `resources/js/controllers` take precedence over package controllers with the same
Stimulus identifier. Nested identifiers use Stimulus' `--` separator.

Preload keeps each controller in a separate chunk and only moves its network request earlier. Use the `eager` config
only when a controller must be evaluated with the application entrypoint.
