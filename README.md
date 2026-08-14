[![Latest Version on Packagist](https://img.shields.io/packagist/v/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3A"Fix+PHP+Code+Style+Issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)

# Laravel Hotwire

Laravel Hotwire is a server-driven UI toolkit for Laravel applications. It combines Turbo Drive, Turbo Frames, Turbo
Streams, Stimulus controllers and Blade components so you can build interactive interfaces without turning every screen
into a client-side app.

## Requirements

- PHP 8.3+
- Laravel 12+
- Vite
- Tailwind CSS v4
- PHP extensions: DOM, libxml and mbstring

## Installation

Install the package with Composer:

```bash
composer require emaia/laravel-hotwire
```

Then scaffold the JavaScript, CSS and package dependencies:

```bash
php artisan hotwire:install
```

The installer adds Stimulus, Turbo, `@emaia/stimulus-lazy-loader`, controller-specific npm dependencies, the `@hotwire`
Vite alias, the controller loader, CSS preset imports and Laravel Idea metadata. Components work immediately after
installation. You only publish controllers when you want to customize their source.

Publish configuration only when you need to change the component prefix or controller loading policy:

```bash
php artisan vendor:publish --tag=hotwire-config
```

For lean installs, CI flags and loader details, see [Advanced installation](docs/installation.md).

## Basic Usage

Render components with the configured prefix, `hw` by default:

```blade
<hw:form action="{{ route('posts.store') }}" method="post" auto-submit>
    <hw:field name="title" label="Title">
        <hw:input name="title" placeholder="Write a title" />
    </hw:field>

    <hw:field name="status" label="Status">
        <hw:select
            name="status"
            :options="['draft' => 'Draft', 'published' => 'Published']"
        />
    </hw:field>

    <hw:button type="submit">Save post</hw:button>
</hw:form>
```

Components merge their own Stimulus controllers with any `data-controller` attributes you provide, and they expose stable
`data-slot`, `data-variant`, `data-size` and `data-state` hooks for preset and application CSS.

## Turbo Streams

The package includes [`emaia/laravel-hotwire-turbo`](https://github.com/emaia/laravel-hotwire-turbo), including request
detection, DOM helpers and a fluent stream builder:

```php
public function store(Request $request)
{
    $message = Message::create($request->validate([
        'body' => ['required', 'string'],
    ]));

    return turbo_stream()
        ->append('messages', view('messages.item', compact('message')))
        ->update('message-form', view('messages.form'))
        ->toast('success', 'Message posted');
}
```

Useful helpers from the Turbo package include `dom_id()`, `dom_class()`, `request()->wantsTurboStream()`,
`request()->wasFromTurboFrame()` and `request()->turboFrameId()`.

## Frame-Backed Modals

Use a shared modal host in your layout, then point links at its frame. The server returns normal Blade views and the modal
opens when the frame receives content.

```blade
{{-- resources/views/layouts/app.blade.php --}}
<main>{{ $slot }}</main>

<hw:modal frame="modal">
    <x-slot:loading_template>
        <div class="p-6 text-sm text-muted-foreground">Loading...</div>
    </x-slot:loading_template>
</hw:modal>
```

```blade
<hw:button as="a" href="{{ route('posts.edit', $post) }}" data-turbo-frame="modal">
    Edit post
</hw:button>
```

```php
public function update(Request $request, Post $post)
{
    $post->update($request->validate([
        'title' => ['required', 'string', 'max:255'],
    ]));

    return turbo_stream()
        ->refresh(method: 'morph')
        ->update('modal')
        ->toast('success', 'Post updated');
}
```

See [Modal](docs/components/modal.md), [Frame-or-page views](docs/recipes/frame-or-page.md) and
[Server-driven modals](docs/recipes/server-driven-modals.md) for the full pattern.

## Stimulus Helpers

Use the helper API when raw `data-*` attributes get too noisy:

```blade
<div {{ stimulus()
    ->controller('chart', ['name' => 'Revenue', 'data' => [12, 18, 31]])
    ->target('chart', 'canvas')
    ->action('chart', 'refresh', 'turbo:frame-load')
}}>
    <canvas data-chart-target="canvas"></canvas>
</div>
```

Available helpers:

```php
stimulus();
stimulus_controller($name, $values = [], $classes = [], $outlets = []);
stimulus_action($controller, $method, $event = null, $params = []);
stimulus_target($controller, $target);
```

See [Stimulus attribute helpers](docs/stimulus-helpers.md) for values, classes, outlets, action params and escaping.

## Components

Laravel Hotwire ships composable Blade components for common server-rendered UI patterns:

| Category | Component | Docs |
|----------|-----------|------|
| display | `<hw:accordion>` | [Docs](docs/components/accordion.md) |
| feedback | `<hw:alert>` | [Docs](docs/components/alert.md) |
| overlay | `<hw:alert-dialog>` | [Docs](docs/components/alert-dialog.md) |
| display | `<hw:aspect-ratio>` | [Docs](docs/components/aspect-ratio.md) |
| display | `<hw:attachment>` | [Docs](docs/components/attachment.md) |
| display | `<hw:avatar>` | [Docs](docs/components/avatar.md) |
| utility | `<hw:back-to-top>` | [Docs](docs/components/back-to-top.md) |
| display | `<hw:badge>` | [Docs](docs/components/badge.md) |
| navigation | `<hw:breadcrumb>` | [Docs](docs/components/breadcrumb.md) |
| display | `<hw:button>` | [Docs](docs/components/button.md) |
| display | `<hw:button-group>` | [Docs](docs/components/button-group.md) |
| display | `<hw:card>` | [Docs](docs/components/card.md) |
| display | `<hw:carousel>` | [Docs](docs/components/carousel.md) |
| display | `<hw:chart>` | [Docs](docs/components/chart.md) |
| forms | `<hw:checkbox>` | [Docs](docs/components/checkbox.md) |
| forms | `<hw:checkbox-group>` | [Docs](docs/components/checkbox-group.md) |
| forms | `<hw:checkbox-group.item>` | [Docs](docs/components/checkbox-group.md) |
| utility | `<hw:color-scheme.script>` | [Docs](docs/components/color-scheme.md) |
| utility | `<hw:color-scheme.toggle>` | [Docs](docs/components/color-scheme.md) |
| forms | `<hw:conditional-field>` | [Docs](docs/components/conditional-field.md) |
| utility | `<hw:controller-preloads>` | [Docs](docs/components/controller-preloads.md) |
| overlay | `<hw:drawer>` | [Docs](docs/components/drawer.md) |
| overlay | `<hw:dropdown>` | [Docs](docs/components/dropdown.md) |
| display | `<hw:empty-state>` | [Docs](docs/components/empty-state.md) |
| forms | `<hw:field>` | [Docs](docs/components/field.md) |
| forms | `<hw:field.error>` | [Docs](docs/components/field.md) |
| forms | `<hw:field.group>` | [Docs](docs/components/field.md) |
| forms | `<hw:field.label>` | [Docs](docs/components/field.md) |
| forms | `<hw:file>` | [Docs](docs/components/file.md) |
| forms | `<hw:file-upload>` | [Docs](docs/components/file-upload.md) |
| forms | `<hw:form>` | [Docs](docs/components/form.md) |
| turbo | `<hw:frame>` | [Docs](docs/components/frame.md) |
| turbo | `<hw:frame-or-page>` | [Docs](docs/components/frame-or-page.md) |
| turbo | `<hw:frame-or-page.frame>` | [Docs](docs/components/frame-or-page.md) |
| turbo | `<hw:frame-or-page.page>` | [Docs](docs/components/frame-or-page.md) |
| overlay | `<hw:hover-card>` | [Docs](docs/components/hover-card.md) |
| display | `<hw:icon>` | [Docs](docs/components/icon.md) |
| forms | `<hw:input>` | [Docs](docs/components/input.md) |
| forms | `<hw:input-group>` | [Docs](docs/components/input-group.md) |
| display | `<hw:item>` | [Docs](docs/components/item.md) |
| display | `<hw:kbd>` | [Docs](docs/components/kbd.md) |
| display | `<hw:map>` | [Docs](docs/components/map.md) |
| display | `<hw:marker>` | [Docs](docs/components/marker.md) |
| turbo | `<hw:meta>` | [Docs](docs/components/meta.md) |
| turbo | `<hw:meta.cache>` | [Docs](docs/components/meta.md) |
| turbo | `<hw:meta.color-scheme>` | [Docs](docs/components/meta.md) |
| turbo | `<hw:meta.csrf>` | [Docs](docs/components/meta.md) |
| turbo | `<hw:meta.prefetch>` | [Docs](docs/components/meta.md) |
| turbo | `<hw:meta.refresh>` | [Docs](docs/components/meta.md) |
| turbo | `<hw:meta.root>` | [Docs](docs/components/meta.md) |
| turbo | `<hw:meta.view-transition>` | [Docs](docs/components/meta.md) |
| turbo | `<hw:meta.visit-control>` | [Docs](docs/components/meta.md) |
| overlay | `<hw:modal>` | [Docs](docs/components/modal.md) |
| forms | `<hw:multi-select>` | [Docs](docs/components/multi-select.md) |
| navigation | `<hw:navbar>` | [Docs](docs/components/navbar.md) |
| navigation | `<hw:navbar.item>` | [Docs](docs/components/navbar.md) |
| turbo | `<hw:optimistic>` | [Docs](docs/components/optimistic.md) |
| navigation | `<hw:pagination>` | [Docs](docs/components/pagination.md) |
| overlay | `<hw:popover>` | [Docs](docs/components/popover.md) |
| feedback | `<hw:progress>` | [Docs](docs/components/progress.md) |
| forms | `<hw:radio-group>` | [Docs](docs/components/radio-group.md) |
| forms | `<hw:radio-group.item>` | [Docs](docs/components/radio-group.md) |
| display | `<hw:read-more>` | [Docs](docs/components/read-more.md) |
| display | `<hw:reveal>` | [Docs](docs/components/reveal.md) |
| display | `<hw:reveal.item>` | [Docs](docs/components/reveal.md) |
| forms | `<hw:rich-text>` | [Docs](docs/components/rich-text.md) |
| utility | `<hw:scroll-progress>` | [Docs](docs/components/scroll-progress.md) |
| forms | `<hw:select>` | [Docs](docs/components/select.md) |
| display | `<hw:separator>` | [Docs](docs/components/separator.md) |
| overlay | `<hw:sheet>` | [Docs](docs/components/sheet.md) |
| navigation | `<hw:side-panel>` | [Docs](docs/components/side-panel.md) |
| navigation | `<hw:sidebar>` | [Docs](docs/components/sidebar.md) |
| feedback | `<hw:skeleton>` | [Docs](docs/components/skeleton.md) |
| forms | `<hw:slider>` | [Docs](docs/components/slider.md) |
| feedback | `<hw:spinner>` | [Docs](docs/components/spinner.md) |
| navigation | `<hw:sticky>` | [Docs](docs/components/sticky.md) |
| forms | `<hw:switch>` | [Docs](docs/components/switch.md) |
| display | `<hw:table>` | [Docs](docs/components/table.md) |
| display | `<hw:tabs>` | [Docs](docs/components/tabs.md) |
| forms | `<hw:textarea>` | [Docs](docs/components/textarea.md) |
| utility | `<hw:timeago>` | [Docs](docs/components/timeago.md) |
| feedback | `<hw:toast>` | [Docs](docs/components/toast.md) |
| feedback | `<hw:toaster>` | [Docs](docs/components/toaster.md) |
| forms | `<hw:toggle>` | [Docs](docs/components/toggle.md) |
| forms | `<hw:toggle-group>` | [Docs](docs/components/toggle-group.md) |
| forms | `<hw:toggle-group.item>` | [Docs](docs/components/toggle-group.md) |

List everything available in your installed version:

```bash
php artisan hotwire:components
php artisan hotwire:docs --list --component
```

## Controllers

Package controllers auto-load from the vendor directory after `hotwire:install`. Use them directly with
`data-controller`, or through the Blade components that mount them for you.

Standalone controllers include:

| Category | Controller | Docs |
|----------|------------|------|
| display | `accordion` | [Docs](docs/controllers/accordion.md) |
| overlay | `alert-dialog` | [Docs](docs/controllers/alert-dialog.md) |
| display | `animated-number` | [Docs](docs/controllers/animated-number.md) |
| forms | `auto-resize` | [Docs](docs/controllers/auto-resize.md) |
| forms | `auto-save` | [Docs](docs/controllers/auto-save.md) |
| forms | `auto-select` | [Docs](docs/controllers/auto-select.md) |
| forms | `auto-submit` | [Docs](docs/controllers/auto-submit.md) |
| forms | `autofocus` | [Docs](docs/controllers/autofocus.md) |
| utility | `back-to-top` | [Docs](docs/controllers/back-to-top.md) |
| display | `carousel` | [Docs](docs/controllers/carousel.md) |
| forms | `char-counter` | [Docs](docs/controllers/char-counter.md) |
| display | `chart` | [Docs](docs/controllers/chart.md) |
| forms | `checkbox` | [Docs](docs/controllers/checkbox.md) |
| forms | `checkbox-select-all` | [Docs](docs/controllers/checkbox-select-all.md) |
| forms | `clean-query-params` | [Docs](docs/controllers/clean-query-params.md) |
| forms | `clear-input` | [Docs](docs/controllers/clear-input.md) |
| utility | `color-scheme` | [Docs](docs/controllers/color-scheme.md) |
| forms | `conditional-fields` | [Docs](docs/controllers/conditional-fields.md) |
| utility | `copy-to-clipboard` | [Docs](docs/controllers/copy-to-clipboard.md) |
| dev | `dev--log` | [Docs](docs/controllers/dev/log.md) |
| display | `disclosure` | [Docs](docs/controllers/disclosure.md) |
| overlay | `drawer` | [Docs](docs/controllers/drawer.md) |
| overlay | `dropdown` | [Docs](docs/controllers/dropdown.md) |
| forms | `error-scroll` | [Docs](docs/controllers/error-scroll.md) |
| forms | `file-preserve` | [Docs](docs/controllers/file-preserve.md) |
| forms | `file-upload` | [Docs](docs/controllers/file-upload.md) |
| utility | `gtm` | [Docs](docs/controllers/gtm.md) |
| utility | `hotkey` | [Docs](docs/controllers/hotkey.md) |
| overlay | `hover-card` | [Docs](docs/controllers/hover-card.md) |
| forms | `input-mask` | [Docs](docs/controllers/input-mask.md) |
| display | `lazy-image` | [Docs](docs/controllers/lazy-image.md) |
| display | `map` | [Docs](docs/controllers/map.md) |
| overlay | `modal` | [Docs](docs/controllers/modal.md) |
| overlay | `modal-auto-close` | [Docs](docs/controllers/modal-auto-close.md) |
| forms | `money-input` | [Docs](docs/controllers/money-input.md) |
| forms | `multi-select` | [Docs](docs/controllers/multi-select.md) |
| display | `oembed` | [Docs](docs/controllers/oembed.md) |
| turbo | `optimistic--dispatch` | [Docs](docs/controllers/optimistic/dispatch.md) |
| turbo | `optimistic--form` | [Docs](docs/controllers/optimistic/form.md) |
| turbo | `optimistic--link` | [Docs](docs/controllers/optimistic/link.md) |
| navigation | `pagination` | [Docs](docs/controllers/pagination.md) |
| forms | `password-visibility` | [Docs](docs/controllers/password-visibility.md) |
| overlay | `popover` | [Docs](docs/controllers/popover.md) |
| display | `read-more` | [Docs](docs/controllers/read-more.md) |
| forms | `remote-form` | [Docs](docs/controllers/remote-form.md) |
| forms | `reset-files` | [Docs](docs/controllers/reset-files.md) |
| display | `reveal` | [Docs](docs/controllers/reveal.md) |
| forms | `rich-text` | [Docs](docs/controllers/rich-text.md) |
| forms | `rich-text-toolbar` | [Docs](docs/controllers/rich-text-toolbar.md) |
| utility | `scroll-progress` | [Docs](docs/controllers/scroll-progress.md) |
| overlay | `sheet` | [Docs](docs/controllers/sheet.md) |
| navigation | `side-panel` | [Docs](docs/controllers/side-panel.md) |
| navigation | `sidebar` | [Docs](docs/controllers/sidebar.md) |
| forms | `slider` | [Docs](docs/controllers/slider.md) |
| forms | `slug` | [Docs](docs/controllers/slug.md) |
| display | `tabs` | [Docs](docs/controllers/tabs.md) |
| utility | `timeago` | [Docs](docs/controllers/timeago.md) |
| feedback | `toast` | [Docs](docs/controllers/toast.md) |
| feedback | `toaster` | [Docs](docs/controllers/toaster.md) |
| forms | `toggle` | [Docs](docs/controllers/toggle.md) |
| forms | `toggle-group` | [Docs](docs/controllers/toggle-group.md) |
| overlay | `tooltip` | [Docs](docs/controllers/tooltip.md) |
| turbo | `turbo--frame-src` | [Docs](docs/controllers/turbo/frame-src.md) |
| turbo | `turbo--morph-guard` | [Docs](docs/controllers/turbo/morph-guard.md) |
| turbo | `turbo--polling` | [Docs](docs/controllers/turbo/polling.md) |
| turbo | `turbo--preserve-scroll` | [Docs](docs/controllers/turbo/preserve-scroll.md) |
| turbo | `turbo--progress` | [Docs](docs/controllers/turbo/progress.md) |
| turbo | `turbo--view-transition` | [Docs](docs/controllers/turbo/view-transition.md) |
| forms | `unsaved-changes` | [Docs](docs/controllers/unsaved-changes.md) |

Publish a package controller only when you want to fork and customize it:

```bash
php artisan hotwire:controllers carousel
php artisan hotwire:controllers --list
php artisan hotwire:controllers --outdated --force
```

Prefer extension through the `@hotwire` alias when you want a new controller based on a package controller:

```js
// resources/js/controllers/gallery_controller.js
import CarouselController from "@hotwire/carousel_controller.js";

export default class extends CarouselController {
    static targets = [...CarouselController.targets, "caption"];

    onSelect(index) {
        super.onSelect(index);
        this.captionTarget.textContent = `Slide ${index + 1}`;
    }
}
```

See [Extending controllers](docs/extending-controllers.md) for the extension and fork paths.

## Styling

Components emit semantic attributes. Presets turn those attributes into Tailwind styles:

```css
@import "tailwindcss";
@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';
@source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css';
```

Override styles after the preset import:

```css
[data-slot="button"][data-variant="default"] {
    @apply bg-indigo-600 text-white hover:bg-indigo-700;
}
```

Generate an application-owned preset when you want to own the whole visual language:

```bash
php artisan hotwire:make-preset brand
php artisan hotwire:make-preset brand --from=nova
```

See [Presets](docs/presets.md) and [Theming](docs/theming.md).

## Verification

Check the loader stub, npm dependencies and published controller customizations:

```bash
php artisan hotwire:check
php artisan hotwire:check --fix
php artisan hotwire:check --fix --skip-install
```

`hotwire:check --fix` regenerates the controller loader and adds missing npm dependencies. By default it also runs the
detected package manager install command; use `--skip-install` when CI handles that separately.

## Documentation

Browse docs from the terminal:

```bash
php artisan hotwire:docs
php artisan hotwire:docs modal --component
php artisan hotwire:docs auto-submit
```

Useful starting points:

- [Advanced installation](docs/installation.md)
- [Cookbook](docs/recipes/readme.md)
- [Registry](docs/registry.md)
- [Upgrade guide](docs/upgrade.md)
- [Stimulus helpers](docs/stimulus-helpers.md)

## Development

```bash
composer test
composer analyse
bun run test
bun run test:browser
composer format
```

The registry in [`src/Registry/catalog.php`](src/Registry/catalog.php) is the source of truth for package components,
controllers, npm dependencies, docs paths and styling hooks. Update it whenever you add or rename a package component or
controller.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome via pull requests.

## Security Vulnerabilities

Please review [our security policy](https://github.com/emaia/laravel-hotwire/security/policy) on how to report security
vulnerabilities.

## Credits

- [Ednilson Maia](https://github.com/emaia)
- [All Contributors](https://github.com/emaia/laravel-hotwire/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
