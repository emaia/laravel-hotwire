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

### TailwindCSS (v4)

Adicione essas configurações no seu entrypoint CSS `/resources/css/app.css`

```css
@source '../../vendor/emaia/laravel-hotwire-components/resources/views/**/*.blade.php';
@custom-variant turbo-frame (turbo-frame[src] &);
@custom-variant modal ([data-dialog--modal-target="dialog"] &);
@custom-variant aria-busy (form[aria-busy="true"] &);
@custom-variant self-aria-busy (html[aria-busy="true"] &);
@custom-variant turbo-frame-aria-busy (turbo-frame[aria-busy="true"] &);
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
php artisan hwc:controllers autoselect autosubmit progress
```

### Dialog

| Controller                                          | Identifier      | Dependências | Docs                                           |
|-----------------------------------------------------|-----------------|--------------|------------------------------------------------|
| [Modal](docs/controllers/dialog/modal.md)           | `dialog--modal` | —            | [readme](docs/controllers/dialog/modal.md)     |

### Form

| Controller                                                          | Identifier                | Dependências | Docs                                                    |
|---------------------------------------------------------------------|---------------------------|--------------|---------------------------------------------------------|
| [Autoselect](docs/controllers/form/autoselect.md)                   | `form--autoselect`        | —            | [readme](docs/controllers/form/autoselect.md)           |
| [Autosubmit](docs/controllers/form/autosubmit.md)                   | `form--autosubmit`        | —            | [readme](docs/controllers/form/autosubmit.md)           |
| [Clean Querystring](docs/controllers/form/clean-querystring.md)     | `form--clean-querystring` | —            | [readme](docs/controllers/form/clean-querystring.md)    |
| [Clear Input](docs/controllers/form/clear-input.md)                 | `form--clear-input`       | —            | [readme](docs/controllers/form/clear-input.md)          |
| [Remote](docs/controllers/form/remote.md)                           | `form--remote`            | —            | [readme](docs/controllers/form/remote.md)               |
| [Reset Files](docs/controllers/form/reset-files.md)                 | `form--reset-files`       | —            | [readme](docs/controllers/form/reset-files.md)          |
| [Textarea Autogrow](docs/controllers/form/textarea-autogrow.md)     | `form--textarea-autogrow` | —            | [readme](docs/controllers/form/textarea-autogrow.md)    |
| [Unsaved Changes](docs/controllers/form/unsaved-changes.md)         | `form--unsaved-changes`   | —            | [readme](docs/controllers/form/unsaved-changes.md)      |

### Frame

| Controller                                                        | Identifier              | Dependências      | Docs                                                 |
|-------------------------------------------------------------------|-------------------------|-------------------|------------------------------------------------------|
| [Polling](docs/controllers/frame/polling.md)                      | `frame--polling`        | `@hotwired/turbo` | [readme](docs/controllers/frame/polling.md)          |
| [Progress](docs/controllers/frame/progress.md)                    | `frame--progress`       | `@hotwired/turbo` | [readme](docs/controllers/frame/progress.md)         |
| [View Transition](docs/controllers/frame/view-transition.md)      | `frame--view-transition`| —                 | [readme](docs/controllers/frame/view-transition.md)  |

### Dev

| Controller                                    | Identifier | Dependências | Docs                                       |
|-----------------------------------------------|------------|--------------|--------------------------------------------|
| [Log](docs/controllers/dev/log.md)            | `dev--log` | —            | [readme](docs/controllers/dev/log.md)      |

### Lib

| Controller                                    | Identifier   | Dependências | Docs                                         |
|-----------------------------------------------|--------------|--------------|----------------------------------------------|
| [GTM](docs/controllers/lib/gtm.md)            | `lib--gtm`   | —            | [readme](docs/controllers/lib/gtm.md)        |
| [Maska](docs/controllers/lib/maska.md)        | `lib--maska` | `maska`      | [readme](docs/controllers/lib/maska.md)      |
| [Tippy](docs/controllers/lib/tippy.md)        | `lib--tippy` | `tippy.js`   | [readme](docs/controllers/lib/tippy.md)      |

### Media

| Controller                                          | Identifier      | Dependências | Docs                                           |
|-----------------------------------------------------|-----------------|--------------|------------------------------------------------|
| [OEmbed](docs/controllers/media/oembed.md)          | `media--oembed` | —            | [readme](docs/controllers/media/oembed.md)     |
| [Pending](docs/controllers/media/pending.md)        | `media--pending`| —            | [readme](docs/controllers/media/pending.md)    |

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
