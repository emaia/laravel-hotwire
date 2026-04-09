[![Latest Version on Packagist](https://img.shields.io/packagist/v/emaia/laravel-hotwire-components.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire-components)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire-components/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/emaia/laravel-hotwire-components/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire-components/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/emaia/laravel-hotwire-components/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/emaia/laravel-hotwire-components.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire-components)

# Laravel Hotwire Components

Componentes Blade reutilizáveis com Stimulus controllers para projetos Laravel + Hotwire.

## Requisitos

- PHP 8.4+
- Laravel 11 ou 12
- [Stimulus](https://stimulus.hotwired.dev/) com loader compatível com `import.meta.glob` (ex: [@emaia/stimulus-dynamic-loader](https://www.npmjs.com/package/@emaia/stimulus-dynamic-loader))
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

Os componentes dependem de Stimulus controllers que precisam ser publicados no seu projeto para serem descobertos pelo bundler (Vite).

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

Os controllers são copiados para `resources/js/controllers/` preservando a estrutura de pastas. A convenção de namespace Stimulus é usada: `dialog/modal_controller.js` → `data-controller="dialog--modal"`. O [@emaia/stimulus-dynamic-loader](https://www.npmjs.com/package/@emaia/stimulus-dynamic-loader) descobre e carrega automaticamente via `import.meta.glob`.

> Se o controller já existir e for idêntico ao do pacote, o comando informa que está atualizado. Se diferir, pede confirmação antes de sobrescrever.

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

| Componente                               | Blade | Stimulus Identifier | Docs                                      |
|------------------------------------------|-------|---------------------|-------------------------------------------|
| [Modal](docs/components/modal/readme.md) | `<x-hwc-modal>` | `dialog--modal` | [readme](docs/components/modal/readme.md) |

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
