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

| Componente | Blade | Stimulus Identifier | Docs |
|------------|-------|---------------------|------|
| [Modal](src/Components/Modal/readme.md) | `<x-hwc-modal>` | `dialog--modal` | [readme](src/Components/Modal/readme.md) |
