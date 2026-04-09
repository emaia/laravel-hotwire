[![Latest Version on Packagist](https://img.shields.io/packagist/v/emaia/laravel-hotwire-components.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire-components)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire-components/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/emaia/laravel-hotwire-components/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire-components/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/emaia/laravel-hotwire-components/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/emaia/laravel-hotwire-components.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire-components)

# Laravel Hotwire Components

Componentes Blade reutilizáveis com Stimulus controllers para projetos Laravel + Hotwire.

## Requisitos

- PHP 8.4+
- Laravel 11 ou 12
- [Stimulus](https://stimulus.hotwired.dev/) com loader compatível com `import.meta.glob` (
  ex: [@emaia/stimulus-dynamic-loader](https://www.npmjs.com/package/@emaia/stimulus-dynamic-loader))
- Tailwind CSS

## Instalação

```bash
composer require emaia/laravel-hotwire-components
```

Publique o arquivo de configuração (opcional):

```bash
php artisan vendor:publish --tag=hotwire-components-config
```

### Stimulus Controllers

Os componentes dependem de Stimulus controllers que precisam ser publicados no seu projeto para serem descobertos pelo
bundler (Vite).

**Interativo** — selecione quais controllers publicar:

```bash
php artisan hwc:controllers
```

**Por nome:**

```bash
php artisan hwc:controllers modal
```

**Todos de uma vez:**

```bash
php artisan hwc:controllers --all
```

**Listar disponíveis (CI/scripts):**

```bash
php artisan hwc:controllers --list
```

**Sobrescrever existentes:**

```bash
php artisan hwc:controllers modal --force
```

Os controllers são copiados para `resources/js/controllers/` preservando a estrutura de pastas. A convenção de namespace
Stimulus é usada: `dialog/modal_controller.js` → `data-controller="dialog--modal"`.
O [@emaia/stimulus-dynamic-loader](https://www.npmjs.com/package/@emaia/stimulus-dynamic-loader) descobre e carrega
automaticamente via `import.meta.glob`.

> Se o controller já existir e for idêntico ao do pacote, o comando informa que está atualizado. Se diferir, pede
> confirmação antes de sobrescrever.

#### Configuração no projeto (usando vite)

```js
// resources/js/app.js
import "./libs";

// resources/js/libs/index.js
import "./turbo";
import "./stimulus";
import "../controllers";

// resources/js/controllers/index.js
import {Stimulus} from "../libs/stimulus";
import {registerControllers} from "@emaia/stimulus-dynamic-loader";

const controllers = import.meta.glob("./**/*_controller.{js,ts}", {
    eager: false,
});

registerControllers(Stimulus, controllers);

```

### Customização de Views

Para customizar o HTML/Tailwind dos componentes:

```bash
php artisan vendor:publish --tag=hotwire-components-views
```

As views publicadas em `resources/views/vendor/hotwire-components/` terão prioridade sobre as do pacote.

## Configuração

```php
// config/hotwire-components.php

return [
    'prefix' => 'hwc', // <x-hwc-modal>
];
```

Altere o `prefix` para usar outro prefixo nos componentes Blade. Ex: `'prefix' => 'hotwire'` → `<x-hotwire-modal>`.

## Componentes

| Componente                                               | Blade                   | Stimulus Identifier                            | Docs                                              |
|----------------------------------------------------------|-------------------------|------------------------------------------------|---------------------------------------------------|
| [Modal](docs/components/modal/readme.md)                 | `<x-hwc-modal>`         | `dialog--modal`                                | [readme](docs/components/modal/readme.md)         |
| [Flash Message](docs/components/flash-message/readme.md) | `<x-hwc-flash-message>` | `notification--toaster`, `notification--toast` | [readme](docs/components/flash-message/readme.md) |
| [Loader](docs/components/loader/readme.md)               | `<x-hwc-loader>`        | —                                              | [readme](docs/components/loader/readme.md)        |

## Stimulus Controllers (standalone)

Controllers Stimulus sem componente Blade associado. Usados diretamente via `data-controller` e `data-action`.

```bash
php artisan hwc:controllers autoselect autosubmit progressbar
```

### Form

| Controller                                                                | Identifier                     | Dependências | Docs                                                      |
|---------------------------------------------------------------------------|--------------------------------|--------------|-----------------------------------------------------------|
| [Autoselect](docs/controllers/form/autoselect.md)                         | `form--autoselect`             | —            | [readme](docs/controllers/form/autoselect.md)             |
| [Autosubmit](docs/controllers/form/autosubmit.md)                         | `form--autosubmit`             | —            | [readme](docs/controllers/form/autosubmit.md)             |
| [Clean Querystring](docs/controllers/form/clean-querystring.md)           | `form--clean-querystring`      | —            | [readme](docs/controllers/form/clean-querystring.md)      |
| [Clear Input](docs/controllers/form/clearinput.md)                        | `form--clearinput`             | —            | [readme](docs/controllers/form/clearinput.md)             |
| [Form](docs/controllers/form/form.md)                                     | `form--form`                   | —            | [readme](docs/controllers/form/form.md)                   |
| [Form Reset Input Files](docs/controllers/form/form-reset-input-files.md) | `form--form-reset-input-files` | —            | [readme](docs/controllers/form/form-reset-input-files.md) |
| [Textarea Autogrow](docs/controllers/form/textarea-autogrow.md)           | `form--textarea-autogrow`      | —            | [readme](docs/controllers/form/textarea-autogrow.md)      |
| [Unsaved Changes](docs/controllers/form/unsaved-changes.md)               | `form--unsaved-changes`        | —            | [readme](docs/controllers/form/unsaved-changes.md)        |

### Frame

| Controller                                                               | Identifier                     | Dependências      | Docs                                                      |
|--------------------------------------------------------------------------|--------------------------------|-------------------|-----------------------------------------------------------|
| [Progressbar](docs/controllers/frame/progressbar.md)                     | `frame--progressbar`           | `@hotwired/turbo` | [readme](docs/controllers/frame/progressbar.md)           |
| [Frame View Transition](docs/controllers/frame/frame-view-transition.md) | `frame--frame-view-transition` | —                 | [readme](docs/controllers/frame/frame-view-transition.md) |
| [Refresh Turbo Frame](docs/controllers/frame/refresh-turbo-frame.md)     | `frame--refresh-turbo-frame`   | `@hotwired/turbo` | [readme](docs/controllers/frame/refresh-turbo-frame.md)   |

### Hwc

| Controller                                                        | Identifier           | Dependências | Docs                                               |
|-------------------------------------------------------------------|----------------------|--------------|-----------------------------------------------------|
| [GTM](docs/controllers/hwc/gtm.md)                               | `hwc--gtm`           | —            | [readme](docs/controllers/hwc/gtm.md)              |
| [Log](docs/controllers/hwc/log.md)                                | `hwc--log`           | —            | [readme](docs/controllers/hwc/log.md)              |
| [Maska](docs/controllers/hwc/maska.md)                            | `hwc--maska`         | `maska`      | [readme](docs/controllers/hwc/maska.md)            |
| [OEmbed](docs/controllers/hwc/oembed.md)                          | `hwc--oembed`        | —            | [readme](docs/controllers/hwc/oembed.md)           |
| [Pending Image](docs/controllers/hwc/pending-image.md)            | `hwc--pending-image` | —            | [readme](docs/controllers/hwc/pending-image.md)    |
| [Tippy](docs/controllers/hwc/tippy.md)                            | `hwc--tippy`         | `tippy.js`   | [readme](docs/controllers/hwc/tippy.md)            |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ednilson Maia](https://github.com/emaia)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
