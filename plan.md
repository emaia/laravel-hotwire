# Plano — Controller `carousel` + componente `<x-hwc::carousel>`

## Context

O pacote `laravel-hotwire` ainda não tem carousel. O draft enviado pelo usuário é
funcional mas tem limitações:

- Identifier `slider--embla` força uma "substrate folder" (`slider/`) que viola a
  convenção do projeto ("no UI-role folders" — `CLAUDE.md`).
- HTML dos dots hardcoded com classes Tailwind dentro do JS — quebra o
  princípio de o controller ser presentational-free e impede customização.
- Sem suporte a plugins oficiais do Embla (Autoplay, Fade, Class Names, Wheel/
  AutoScroll/AutoHeight) que cobrem ~90% dos casos reais de uso.
- Sem integração com Turbo (cache/morph), sem disable de prev/next, sem dispatch
  de eventos Stimulus para integrar com outros controllers, sem reInit quando
  `optionsValue` muda.

Objetivo: entregar um par **controller + Blade component** que siga os mesmos
padrões dos outros (`tooltip`, `modal`, `tabs`), seja extensível via plugins
opcionais, totalmente compatível com Turbo Drive/Frames/morphs, e bem testado.

## Decisões alinhadas

- Linguagem: **JavaScript** (`.js`).
- Identifier: **`carousel`** — segue o padrão do pacote de nomear pelo *que a
  UI faz*, não pela lib (`tooltip` não `tippy`, `toast` não `sonner`). Simétrico
  com o componente `<x-hwc::carousel>` e portável caso troquemos Embla por
  outra lib.
- Plugins suportados nativamente: **Autoplay, Class Names, Fade, Wheel Gestures,
  Auto Scroll, Auto Height** (carregados sob demanda via dynamic `import()`).
- Controles: **targets explícitos** — sem `innerHTML` hardcoded.
- Entrega: **controller + Blade component completo** `<x-hwc::carousel>`.
- **CSS co-localizado** `resources/js/controllers/carousel.css` importado pelo
  controller. Embla não ships CSS próprio, e o mínimo estrutural (overflow,
  flex) precisa estar sempre presente. Mesmo padrão do `tooltip` (que importa
  `tippy.js/dist/tippy.css`), aplicado a um arquivo nosso. Selectors baseados em
  `[data-carousel-target=...]` para não acoplar a um sistema de naming.

## Arquitetura

### 1. Controller `resources/js/controllers/carousel_controller.js`

```js
static targets = [
    "viewport",      // required — limite visual com overflow:hidden
    "container",     // required — flex container que Embla anima
    "prevButton",
    "nextButton",
    "dotList",       // container dos dots gerados
    "dotTemplate",   // <template> com markup customizável de cada dot
    "progress",      // elemento cuja width/transform reflete progresso (0..1)
    "indexLabel",    // text node "1"
    "totalLabel",    // text node "10"
];

static values = {
    options: { type: Object, default: {} },         // todas as opts do Embla
    plugins: { type: Object, default: {} },         // {autoplay: {...}|true, fade: true, ...}
};

static classes = ["activeDot", "disabledNav"];
// data-carousel-active-dot-class="bg-white"
// data-carousel-disabled-nav-class="opacity-40 pointer-events-none"
```

**Carregamento de plugins** — dynamic import condicional (code-splitting via
Vite, só baixa o módulo se o usuário pediu):

```js
async #loadPlugins() {
    const flags = this.pluginsValue ?? {};
    const out = [];
    const optsFor = (v) => (typeof v === "object" && v !== null ? v : {});

    if (flags.autoplay) {
        const { default: Autoplay } = await import("embla-carousel-autoplay");
        out.push(Autoplay(optsFor(flags.autoplay)));
    }
    if (flags.classNames) { /* embla-carousel-class-names */ }
    if (flags.fade) { /* embla-carousel-fade */ }
    if (flags.autoScroll) { /* embla-carousel-auto-scroll */ }
    if (flags.autoHeight) { /* embla-carousel-auto-height */ }
    if (flags.wheelGestures) { /* embla-carousel-wheel-gestures */ }
    return out;
}
```

Cada `import()` em try/catch — se a dep não estiver instalada, log claro:
*"install embla-carousel-autoplay to use the autoplay plugin"*.

**Ciclo de vida**:

- `connect()`:
  1. Resolve viewport (`hasViewportTarget ? viewportTarget : element`).
  2. `plugins = await this.#loadPlugins()`.
  3. `this.embla = EmblaCarousel(viewport, this.optionsValue, plugins)`.
  4. Renderiza dots (se `hasDotListTarget`) clonando `dotTemplateTarget`
     (`<template>`) ou gerando `<button type=button>` minimalista de fallback.
     Cada dot recebe `data-carousel-index-param="i"` e `data-action="carousel#scrollTo"`.
  5. Inscreve `select`, `reInit`, `slidesInView`, `slidesChanged`, `scroll`,
     `settle` em handlers idempotentes (bound em `initialize`).
  6. Sync inicial: prev/next disabled, dot ativo, progress, labels.
  7. Listener `turbo:before-cache@window` (action no element) → destrói a
     instância antes do snapshot (Embla escreve `transform` inline).

- `disconnect()`: remove listeners, `embla.destroy()`, zera `this.embla`,
  zera `dotNodes`.

- `optionsValueChanged()` / `pluginsValueChanged()`: se já conectou, chama
  `embla.reInit(this.optionsValue, await this.#loadPlugins())`.

**Actions**:

- `next()` → `embla.scrollNext()`
- `prev()` → `embla.scrollPrev()`
- `scrollTo({ params: { index } })` → `embla.scrollTo(index ?? 0)`
- `play()` / `stop()` → delegam para `embla.plugins().autoplay?.play()/stop()`

**Eventos dispatch** (via `this.dispatch(...)` → `carousel:nome` na element):

- `carousel:init` `{ detail: { embla } }`
- `carousel:select` `{ detail: { index, previousIndex, slidesInView } }`
- `carousel:settle` (idem)

Permite integração externa: `data-action="carousel:select->analytics#track"`.

**Coexistência (CLAUDE.md)**: scope DOM reads ao próprio `this.element`/targets,
nunca toca em `data-controller` de irmãos, cleanup completo no `disconnect`,
handlers idempotentes em events Turbo compartilhados.

### 2. CSS `resources/js/controllers/carousel.css`

Mínimo estrutural — usa atributos de target para não criar dependência de
sistema de classes:

```css
[data-controller~="carousel"] [data-carousel-target="viewport"] {
    overflow: hidden;
}
[data-controller~="carousel"] [data-carousel-target="container"] {
    display: flex;
    will-change: transform;
    backface-visibility: hidden;
}
```

Importado no topo do controller: `import "./carousel.css";`.

Width por slide (`flex: 0 0 X%`) fica a cargo do usuário/componente (Tailwind).
Isso permite layouts multi-slide-per-view, vertical, etc.

### 3. Blade component

`src/Components/Carousel.php`:

```php
public function __construct(
    public ?string $id = null,
    public bool $loop = false,
    public string $align = 'center',          // start|center|end
    public string $axis = 'x',                // x|y
    public int|string $slidesToScroll = 1,    // int | 'auto'
    public bool $dragFree = false,
    public string $containScroll = 'trimSnaps', // ''|'trimSnaps'|'keepSnaps'
    public int $duration = 25,
    public bool $skipSnaps = false,
    public int $startIndex = 0,
    public ?array $breakpoints = null,
    public ?array $plugins = null,            // ['autoplay' => ['delay'=>4000], 'fade' => true]
    public bool $navigation = true,
    public bool $dots = true,
    public bool $progress = false,
    public bool $counter = false,
    public string $class = '',
    public string $viewportClass = '',
    public string $containerClass = '',
    public string $slideClass = 'min-w-0 flex-[0_0_100%]',
    public string $activeDotClass = 'bg-white',
    public string $dotClass = 'size-2.5 rounded-full bg-white/50 transition-colors',
    public string $disabledNavClass = 'opacity-40 pointer-events-none',
) { /* derive id, normalize plugins map */ }

public function optionsJson(): string  // omite defaults via StripsNullProps
public function pluginsJson(): string
```

Usa o trait `StripsNullProps` (`src/Components/Concerns/StripsNullProps.php`)
para omitir props nulas/default do JSON. O `slideClass` é exposto como prop
mas o slot default já recebe slides do usuário (usuário aplica classe nos
slides, ou usa Tailwind `[&>*]:flex-[0_0_100%]` no `containerClass`).

`resources/views/component-views/carousel.blade.php` (estrutura):

```blade
<div
    data-controller="carousel"
    data-carousel-options-value="{{ $optionsJson() }}"
    data-carousel-plugins-value="{{ $pluginsJson() }}"
    data-carousel-active-dot-class="{{ $activeDotClass }}"
    data-carousel-disabled-nav-class="{{ $disabledNavClass }}"
    data-action="turbo:before-cache@window->carousel#teardownForCache"
    {{ $attributes->except(['data-controller','data-action'])->whereDoesntStartWith('data-carousel-')->merge(['id' => $id, 'class' => $class]) }}
>
    <div data-carousel-target="viewport" class="{{ $viewportClass }}">
        <div data-carousel-target="container" class="{{ $containerClass }}">
            {{ $slot }}
        </div>
    </div>

    @if ($navigation)
        <button type="button" data-carousel-target="prevButton" data-action="carousel#prev" aria-label="Previous">
            {{ $prev_button ?? '‹' }}
        </button>
        <button type="button" data-carousel-target="nextButton" data-action="carousel#next" aria-label="Next">
            {{ $next_button ?? '›' }}
        </button>
    @endif

    @if ($dots)
        <div data-carousel-target="dotList" role="tablist" aria-label="Slide selection"></div>
        @if (isset($dot_template))
            <template data-carousel-target="dotTemplate">{{ $dot_template }}</template>
        @else
            <template data-carousel-target="dotTemplate">
                <button type="button" class="{{ $dotClass }}" data-action="carousel#scrollTo"></button>
            </template>
        @endif
    @endif

    @if ($progress)
        <div class="h-1 bg-black/10"><div data-carousel-target="progress" class="h-full bg-black"></div></div>
    @endif

    @if ($counter)
        <span><span data-carousel-target="indexLabel">1</span>/<span data-carousel-target="totalLabel">0</span></span>
    @endif
</div>
```

### 4. Registry — `src/Registry/catalog.php`

```php
'carousel' => [
    'class' => Carousel::class,
    'view' => 'hotwire::component-views.carousel',
    'docs' => 'docs/components/carousel.md',
    'category' => 'utility',
    'description' => 'Carousel/slider powered by Embla with optional navigation, dots, progress and plugins',
    'controllers' => ['carousel'],
],
// ...
'carousel' => [
    'source' => 'resources/js/controllers/carousel_controller.js',
    'docs' => 'docs/controllers/carousel.md',
    'category' => 'utility',
    'description' => 'Carousel/slider — wraps Embla Carousel with optional Autoplay/Fade/ClassNames/Wheel plugins',
    'npm' => ['embla-carousel' => '^8.5.0'],
],
```

Plugins opcionais (`embla-carousel-autoplay` etc.) **não** entram no `npm` do
registry — a doc lista para instalar conforme o uso. Mantém `hotwire:check --fix`
enxuto: só adiciona o core.

### 5. Documentação

- `docs/controllers/carousel.md` — modelo `docs/controllers/tooltip.md`:
  requirements (Embla core + opcionais), targets, values, classes, actions,
  eventos dispatched, exemplos (básico, autoplay, fade, vertical, breakpoints,
  multi-slide-per-view, slides carregados via Turbo Frame), "Markup contract"
  com viewport / container / slides.
- `docs/components/carousel.md` — modelo `docs/components/modal.md`: props
  table, slots (`default`, `prev_button`, `next_button`, `dot_template`),
  exemplos blade completos, observações Turbo (carousel dentro de frame,
  lazy-image em slides, modal+carousel).

### 6. Testes

**JS unit (`tests/Controllers/carousel_controller.test.js`)** — segue o padrão
de `tests/Controllers/tabs_controller.test.js` usando `mountController` de
`resources/js/helpers/test_stimulus.js`. Mock do módulo `embla-carousel` via
`mock.module()` do Bun:

- mounta com defaults → mock recebe o `viewportTarget` correto
- `next()`/`prev()` chamam `scrollNext`/`scrollPrev` do mock
- `scrollTo` com `params.index` chama `scrollTo(index)`
- renderiza N dots a partir de `scrollSnapList`
- aplica `activeDotClass` no dot do `selectedScrollSnap`
- atualiza `prevButton.disabled` + classe `disabledNavClass` conforme
  `canScrollPrev/Next` do mock
- `optionsValueChanged` chama `embla.reInit(...)`
- `disconnect()` chama `embla.destroy()` e remove listeners
- dispatch de `carousel:select` no handler `select`
- `turbo:before-cache@window` action destrói a instância

**JS browser (`tests/Browser/carousel_controller.pw.js`)** — opcional, 1-3
specs com Embla real para validar drag/autoplay/focus.

**PHP (`tests/Components/CarouselTest.php`)** — segue padrão `tests/Components/*Test.php`:

- defaults → contém `data-controller="carousel"`, slot, dotList, prev/next
- `navigation=false` → não renderiza prev/next
- `dots=false` → não renderiza dotList nem template
- `plugins=['autoplay' => ['delay' => 5000]]` → JSON correto em
  `data-carousel-plugins-value`
- merge de `$attributes` extras na raiz
- `id` auto-gerado quando ausente
- `optionsJson` omite defaults via `StripsNullProps`

## Faseamento

A entrega é dividida em **4 fases incrementais**. Cada fase é independentemente
shippable (controller funcional, testes verdes, doc atualizada), o que permite
commit/PR separado por fase, revisão isolada e desbloquear melhorias futuras
sem segurar o core.

### Fase 1 — Core controller (sem plugins)

Entrega o carousel funcionando standalone, sem plugins externos. É o
**bloco maior** porque inclui a lógica de mounting, CSS, integração com
`hotwire:controllers` e fundação dos testes.

Inclui:
- `resources/js/controllers/carousel_controller.js` — targets viewport,
  container, prevButton, nextButton, dotList, dotTemplate. Values:
  `options` apenas (sem `plugins`). Classes: `activeDot`, `disabledNav`.
- `resources/js/controllers/carousel.css` — mínimo estrutural.
- Actions: `next`, `prev`, `scrollTo`.
- Dot rendering via `dotTemplate` + ativação do dot selecionado.
- Prev/Next disabled state via `canScrollPrev/Next`.
- Listener `turbo:before-cache@window` → destroy.
- Dispatch `carousel:init`, `carousel:select`, `carousel:settle`.
- `optionsValueChanged` → `reInit`.
- `disconnect` → destroy + cleanup.
- Registry: entry do controller `carousel` em `src/Registry/catalog.php`
  com `npm => ['embla-carousel' => '^8.5.0']`.
- Doc: `docs/controllers/carousel.md` (sem seção de plugins).
- Testes JS: `tests/Controllers/carousel_controller.test.js` cobrindo mount,
  actions, dots, disabled state, dispatch, cache teardown, reInit, disconnect.
- **Pré-requisito a investigar antes do commit**: confirmar que
  `hotwire:controllers` copia o `.css` co-localizado adjacente ao controller.
  Se não, ajustar `src/Commands/ControllersCommand.php` / `ControllerImports.php`
  para incluir o sibling `.css` no publish. Se o ajuste for grande, sai como
  Fase 1.5 isolada.

Critério de aceite Fase 1:
- `bun test tests/Controllers/carousel_controller.test.js` passa
- `php artisan hotwire:controllers carousel` publica `.js` **e** `.css`
- `php artisan hotwire:check` valida o controller
- `composer analyse` e `composer format` OK

### Fase 2 — Plugins

Adiciona suporte declarativo aos plugins oficiais do Embla, sem mexer no
contrato do core.

Inclui:
- Novo value `plugins: { type: Object, default: {} }`.
- Método privado `#loadPlugins()` com dynamic import condicional para:
  Autoplay, Class Names, Fade, Wheel Gestures, Auto Scroll, Auto Height.
  Cada `import()` em try/catch com erro amigável quando o pacote não está
  instalado.
- Refactor de `connect`/`optionsValueChanged` para passar `plugins` ao Embla.
- Actions opcionais `play()` / `stop()` delegando para
  `embla.plugins().autoplay`.
- `pluginsValueChanged` → `reInit` com novos plugins.
- Doc: nova seção "Plugins" em `docs/controllers/carousel.md` listando cada
  plugin, package npm e snippet de uso.
- Testes JS: mockando dynamic imports — verifica que (a) flags ligadas
  carregam o módulo correto, (b) flags desligadas não carregam,
  (c) `reInit` é chamado quando `pluginsValue` muda, (d) erro amigável quando
  módulo ausente.

Critério de aceite Fase 2:
- `bun test tests/Controllers/carousel_controller.test.js` (suite estendida) passa
- Doc atualizada com seção de plugins
- Sem mudança no `npm` do registry (plugins são opt-in instalação manual)

### Fase 3 — Blade component `<x-hwc::carousel>`

Casca ergonômica sobre o controller. Independente de Fase 2 — funciona com
ou sem plugins.

Inclui:
- `src/Components/Carousel.php` usando `StripsNullProps`.
- `resources/views/component-views/carousel.blade.php`.
- Registry: entry `carousel` em `components` referenciando `controllers => ['carousel']`.
- Doc: `docs/components/carousel.md` com props table, slots (`default`,
  `prev_button`, `next_button`, `dot_template`) e exemplos.
- Testes PHP: `tests/Components/CarouselTest.php` cobrindo defaults,
  flags `navigation/dots`, JSON de `plugins`, merge de `$attributes`, id
  auto-gerado, `StripsNullProps`.

Critério de aceite Fase 3:
- `composer test --filter=Carousel` passa
- `php artisan hotwire:components` lista `carousel`
- `php artisan hotwire:check` em uma view com `<x-hwc::carousel>` exige
  `carousel` controller publicado + `embla-carousel` em `package.json`
- `php artisan hotwire:docs carousel` exibe a doc

### Fase 4 — Extras (progress, counter, browser tests)

Polimento opcional, pode entrar depois de feedback de uso real.

Inclui:
- Targets adicionais no controller: `progress`, `indexLabel`, `totalLabel`.
  Handler `scroll` atualiza `progress.style.width` (ou `transform`);
  handler `select`/`init` atualiza labels.
- Props `progress` e `counter` no componente.
- `tests/Browser/carousel_controller.pw.js` — 1-3 specs validando drag real,
  autoplay e focus.

Critério de aceite Fase 4:
- `bun run test:browser` passa
- Demo manual via Testbench confirma drag, autoplay, prev/next disable,
  dispatch de eventos no console.

## Arquivos por fase

| Fase | Criar                                                                                                                                                                                                                                                                                          | Modificar                                                                                       |
|------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| 1    | `resources/js/controllers/carousel_controller.js`, `resources/js/controllers/carousel.css`, `docs/controllers/carousel.md`, `tests/Controllers/carousel_controller.test.js`                                                                                                                   | `src/Registry/catalog.php` (entry controller), possivelmente `ControllersCommand`/`ControllerImports` |
| 2    | (incrementa) doc + testes existentes                                                                                                                                                                                                                                                          | `carousel_controller.js`, `docs/controllers/carousel.md`, `tests/Controllers/carousel_controller.test.js` |
| 3    | `src/Components/Carousel.php`, `resources/views/component-views/carousel.blade.php`, `docs/components/carousel.md`, `tests/Components/CarouselTest.php`                                                                                                                                       | `src/Registry/catalog.php` (entry component)                                                    |
| 4    | `tests/Browser/carousel_controller.pw.js`                                                                                                                                                                                                                                                     | `carousel_controller.js`, `Carousel.php`, view, doc do componente                               |

## Verificação end-to-end

```bash
# 1. Testes unitários
bun test tests/Controllers/carousel_controller.test.js
composer test --filter=Carousel

# 2. Suite completa (regressão)
bun test
composer test

# 3. Validação manual via demo (Testbench)
#    cria uma view com <x-hwc::carousel loop :plugins="['autoplay' => true]">
#    e verifica no browser: drag funciona, autoplay roda, prev/next disable
#    no início/fim quando loop=false, dots clicáveis, eventos dispatched
#    aparecem no console com data-controller="dev--log".

# 4. Static analysis + format
composer analyse
composer format
```

## Fora de escopo (futuro)

- Sub-componente `<x-hwc::carousel.slide>` com `$slideClass` injetado.
- Helper `carousel:reInit` via Turbo Stream action — útil quando slides chegam
  por stream e precisam recalcular tamanhos imediatamente.
- Plugin "Accessibility" oficial do Embla — avaliar depois.
