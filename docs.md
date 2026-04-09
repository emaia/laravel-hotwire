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

Os controllers são copiados para `resources/js/controllers/` e seguem a convenção Stimulus (`modal_controller.js` → `data-controller="modal"`), sendo descobertos automaticamente pelo `import.meta.glob`.

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

---

## Componentes

### Modal

Modal acessível com backdrop, animações, focus trap e integração com Turbo.

#### Uso básico

```html
<x-hwc-modal>
    <x-slot:trigger>
        <button data-action="modal#open" type="button">Abrir modal</button>
    </x-slot:trigger>

    <div class="p-6">
        <h2>Título</h2>
        <p>Conteúdo do modal.</p>
    </div>
</x-hwc-modal>
```

#### Com botão de fechar

```html
<x-hwc-modal close-button>
    <x-slot:trigger>
        <button data-action="modal#open" type="button">Abrir</button>
    </x-slot:trigger>

    <p class="p-6">Conteúdo com botão X para fechar.</p>
</x-hwc-modal>
```

#### Props

| Prop | Tipo | Default | Descrição |
|------|------|---------|-----------|
| `allow-small-width` | `bool` | `false` | Permite largura menor que 50% em telas `md+` |
| `allow-full-width` | `bool` | `true` | Permite largura total (sem `max-w-[50%]`) |
| `close-button` | `bool` | `false` | Exibe botão X para fechar |
| `padding` | `string` | `''` | Classes de padding no container do conteúdo |
| `fixed-top` | `bool` | `true` | Fixa o modal no topo com margem |
| `prevent-reopen-delay` | `int` | `1000` | Delay (ms) antes de permitir reabrir após fechar |

#### Slots

| Slot | Descrição |
|------|-----------|
| `trigger` | Elemento que dispara a abertura do modal |
| `slot` (default) | Conteúdo principal do modal |
| `loading_template` | Template exibido durante carregamento de conteúdo dinâmico |

#### Conteúdo dinâmico com Turbo Frames

O modal suporta conteúdo carregado via Turbo Frame. Use o target `dynamicContent` para que o controller observe mudanças e abra/feche automaticamente:

```html
<x-hwc-modal>
    <x-slot:trigger>
        <a
            href="/items/1/edit"
            data-action="modal#showLoading"
            data-turbo-frame="modal-content"
        >
            Editar
        </a>
    </x-slot:trigger>

    <turbo-frame id="modal-content" data-modal-target="dynamicContent">
    </turbo-frame>

    <x-slot:loading_template>
        <div class="flex items-center justify-center p-12">
            <span>Carregando...</span>
        </div>
    </x-slot:loading_template>
</x-hwc-modal>
```

Quando o Turbo Frame recebe conteúdo, o modal abre automaticamente. Quando o conteúdo é removido, o modal fecha.

#### Stimulus Values

Configuráveis via `data-modal-*-value` no elemento raiz:

| Value | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| `open-duration` | `Number` | `300` | Duração da animação de abertura (ms) |
| `close-duration` | `Number` | `300` | Duração da animação de fechamento (ms) |
| `lock-scroll` | `Boolean` | `true` | Trava scroll do body quando aberto |
| `close-on-escape` | `Boolean` | `true` | Fecha ao pressionar Escape |
| `close-on-click-outside` | `Boolean` | `true` | Fecha ao clicar fora do dialog |
| `prevent-reopen-delay` | `Number` | `300` | Delay anti-bounce no controller (ms) |

#### Actions

| Action | Descrição |
|--------|-----------|
| `modal#open` | Abre o modal |
| `modal#close` | Fecha o modal |
| `modal#showLoading` | Exibe o loading template enquanto aguarda resposta Turbo |

#### Eventos

| Evento | Descrição |
|--------|-----------|
| `modal:opened` | Disparado após a animação de abertura completar |
| `modal:closed` | Disparado após a animação de fechamento completar |

```javascript
element.addEventListener("modal:opened", (event) => {
    console.log("Modal aberto", event.detail.controller);
});
```

#### Acessibilidade

- `role="dialog"` e `aria-modal="true"` no overlay
- Focus trap: Tab/Shift+Tab ciclam entre elementos focáveis dentro do modal
- Foco retorna ao elemento que abriu o modal ao fechar
- Fecha com Escape (configurável)

#### Ignorar clique externo

Elementos fora do dialog que não devem fechar o modal podem usar `data-modal-ignore`:

```html
<div data-modal-ignore>
    Este dropdown não fecha o modal ao ser clicado.
</div>
```

#### Integração com Turbo

O modal fecha automaticamente em `turbo:before-cache`, evitando modais "fantasma" na navegação com Turbo Drive.
