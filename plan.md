## Plano De Produto

### Fase 1: Fundação E Baixo Risco

- Criar wrappers Blade para:
  - `tooltip`
  - `hotkey`
  - `copy-to-clipboard`
  - `auto-submit`
  - `char-counter`
  - `autoresize`
  - `input-mask`
  - `unsaved-changes`
- Padronizar eventos, props e escape hatch via Stimulus direto

### Fase 2: Fluxos Laravel/Turbo

- Criar:
  - `button`
  - `link`
  - `form`
  - `submit`
- Consolidar loading, confirm, remote form e optimistic UI
- Melhorar `flash-container` e `flash-message`
- Adicionar helpers/documentação para Turbo Streams e Turbo morph
- Tornar `hotwire:check` e `hotwire:components` mais orientados a correção e categoria

### Fase 3: Primitives Visuais Opcionais

- Criar:
  - `field`
  - `label`
  - `description`
  - `error`
  - `input`
  - `textarea`
  - `select`
  - `checkbox`
  - `switch`
- Priorizar HTML nativo, acessibilidade e composição
- Não introduzir dropdown/select custom nem tag compiler nessa fase
- Adicionar wrappers convenientes só se padrões reais se repetirem

## Form Essentials — Componentes Blade Element-First

### Princípio

Componentes nomeados pelo **elemento HTML que renderizam** (`input`, `textarea`, `form`, `file-input`, `checkbox-group`, `label`, `error`), com **props que descrevem comportamento** (`mask`, `clearable`, `autoresize`, `:counter`, `remote`, `auto-submit`, `unsaved-changes`, etc.). A lógica interna do componente decide:

- quais controllers Stimulus aplicar;
- onde aplicar (no elemento ou em wrapper);
- onde injetar targets (`data-*-target`);
- a estrutura HTML necessária (wrapper extra, botões auxiliares, classes).

O usuário **nunca escreve `data-controller` nem `data-*-target` manualmente** — basta props + atributos HTML normais.

### Princípios de DX (norte do design)

1. **Zero CSS framework opinion** — o pacote não envia classes utilitárias. Default = HTML cru + estado ARIA. Hooks vazios (`hwc-input`, `hwc-label`, `hwc-error`) servem como anchors para o usuário sobrepor com o CSS dele.
2. **Convenções fazem o trabalho** — `name="email"` infere `id="email"` e `errorKey="email"`. Para arrays HTML (`name="variables[0][name]"`), brackets são convertidos automaticamente para dot notation (`variables.0.name`) na chave de erro, e para hífens (`variables-0-name`) no id.
3. **Boolean props absorvem Stimulus** — usuário nunca escreve `data-controller`. `clearable`, `auto-select`, `:mask`, `:counter` viram boolean/scalar props que o componente compõe.
4. **Pass-through é a regra** — qualquer atributo HTML não declarado como prop cai no elemento principal via `$attributes->merge()`. `class`, `placeholder`, `pattern`, `data-foo`, `aria-*` passam direto.
5. **Acessibilidade automática** — `aria-invalid`, `aria-describedby` apontando para o erro, `aria-required`, `role="alert"`+`aria-live="polite"` no erro, todos setados sem o usuário pedir.
6. **`<x-hwc::field>` propaga via `@aware`** — `name`, `id`, `errorKey`, `required` digitados uma vez no wrapper são vistos por `<x-hwc::label>`, `<x-hwc::input>` e `<x-hwc::error>` aninhados.

### Por que não wrappers 1:1 (descartado)

A intenção inicial era um wrapper Blade por controller form-related (`<x-hwc::autoresize>`, `<x-hwc::input-mask>`, etc.). Foi descartada porque:

- nome ambíguo: `<x-hwc::autoresize>` renderizando `<textarea>` não é intuitivo;
- usuário ainda precisava marcar `data-*-target` nos filhos manualmente;
- composição via aninhamento de wrappers fica ruim (`<auto-submit><unsaved-changes><clean-query-params><form>...`).

Os controllers form-related listados na Fase 1 de "Plano De Produto" (`auto-submit`, `char-counter`, `autoresize`, `input-mask`, `unsaved-changes`) passam a ser **absorvidos** pelos componentes element-first desta seção, em vez de virarem componentes próprios. Os não-form daquela lista (`tooltip`, `hotkey`, `copy-to-clipboard`) seguem como wrappers 1:1.

### Inventário

| Componente | Renderiza | Comportamentos absorvidos | Controllers Stimulus |
|---|---|---|---|
| `<x-hwc::field>` | `<div>` wrapper de label+input+error | propagação de `name`/`id`/`errorKey`/`required` via `@aware` | — |
| `<x-hwc::label>` | `<label>` | required marker, optional marker, info tooltip | tooltip (opcional) |
| `<x-hwc::error>` | `<div>` ou `<ul>` (sempre presente, `hidden` se vazio) | role/aria-live automático | — |
| `<x-hwc::input>` | `<input>` (com wrapper se `clearable` ou `:counter`) | mask, clearable, auto-select, counter | input-mask, clear-input, auto-select, char-counter |
| `<x-hwc::textarea>` | `<textarea>` (com wrapper se `:counter`) | auto-resize, counter | auto-resize, char-counter |
| `<x-hwc::form>` | `<form>` | remote, auto-submit, unsaved-changes, clean-query-params | remote-form, auto-submit, unsaved-changes, clean-query-params |
| `<x-hwc::file-input>` | `<input type=file>` (+ botão reset opcional) | resetable | reset-files |
| `<x-hwc::checkbox-group>` | wrapper com master + items | select-all | checkbox-select-all |

### Especificação por componente

#### `<x-hwc::input>`

**Props:**

| Prop | Tipo | Default | Efeito |
|---|---|---|---|
| `name` | string | — | Pass-through; alimenta `id` e `errorKey` se ausentes |
| `id` | string | `FieldKey::toId($name)` | Casa com `for` do label |
| `type` | string | `"text"` | Pass-through |
| `value` | mixed | — | Mergeado com `old($errorKey, $value)` salvo se `:old="false"` |
| `errorKey` | string\|null | `FieldKey::toErrorKey($name)` | Override raro (campos array com chave de validação distinta) |
| `old` | bool | `true` | Desliga merge automático com `old()` |
| `clearable` | bool | `false` | Wrapper + botão `×` (controller `clear-input`) |
| `autoSelect` | bool | `false` | Controller `auto-select` no `<input>` |
| `mask` | string\|null | `null` | Preset (`cpf`, `phone-br`, `money`...) ou string Maska crua |
| `counter` | int\|null | `null` | Habilita `char-counter` + seta `maxlength` |
| `countdown` | bool | `false` | Counter regressivo |
| `class` | string | `""` | Mergeado nas classes do `<input>` (não do wrapper) |
| `wrapperClass` | string | `""` | Classes do wrapper (quando existe) |

**Auto-binding:**

- `id` setado sempre (derivado de `name` se ausente) — habilita `<label for>`.
- `aria-invalid="true"` quando `$errors->has($errorKey)`.
- `aria-describedby="{id}-error"` setado **sempre**, mesmo sem erro, para o nó de erro estável (evita re-anúncio do screen reader).
- `aria-required="true"` quando `required`.
- `data-invalid` boolean attribute reflete estado de erro (hook CSS).

**Pass-through:** atributos HTML não declarados como prop (`placeholder`, `pattern`, `disabled`, `autofocus`, `data-*`, `aria-*`) via `$attributes->merge()`.

**Render logic:**

- Se `clearable` ou `counter` → wrapper `<span class="hwc-input">` com `data-controller="clear-input char-counter"` (apenas os ativos); `<input>` vira target.
- Caso contrário → `<input>` direto.
- Controllers de elemento (`auto-select`, `input-mask`) compostos no `data-controller` do `<input>`.
- Controllers de wrapper (`clear-input`, `char-counter`) compostos no `data-controller` do wrapper.

**Slot override:** `<x-slot:counter>` customiza a marcação do contador (recebe `data-char-counter-target="counter"` automaticamente, `aria-live="polite"`).

**Exemplos:**

```blade
{{-- Caminho mínimo — name vira id, errorKey, value via old() --}}
<x-hwc::input name="email" type="email" required />

{{-- Boolean props absorvem controllers --}}
<x-hwc::input name="phone" mask="phone-br" />
<x-hwc::input name="price" mask="money" />
<x-hwc::input type="search" name="q" clearable auto-select />
<x-hwc::input name="title" :counter="80" countdown />

{{-- Array notation: errorKey e id derivados automaticamente --}}
<x-hwc::input
    name="variables[0][name]"
    :value="$indicator->variables[0]['name'] ?? null"
    placeholder="Ex.: IBGE/ODS Brasil"
    auto-select
/>
{{-- Resolve internamente: id="variables-0-name", errorKey="variables.0.name" --}}
```

#### `<x-hwc::label>`

**Props:**

| Prop | Tipo | Default | Efeito |
|---|---|---|---|
| `for` | string\|null | `id` (via `@aware`) ou derivado de `name` | `<label for="...">` |
| `value` | string\|null | — | Conteúdo do label (alternativa ao slot) |
| `required` | bool | `false` (via `@aware`) | Marcador visual de obrigatório |
| `requiredLabel` | string | `"*"` | Texto/caractere do marcador |
| `optional` | bool | `false` | Marcador "(opcional)" — mutuamente exclusivo com `required` |
| `info` | string\|null | `null` | Tooltip via controller `tooltip` (só renderiza ícone se publicado) |
| `class` | string | `""` | Mergeado |

`required` aqui é **decorativo**. O `required` real fica no `<x-hwc::input required />`. No caminho via `<x-hwc::field required>`, ambos pegam pelo `@aware`.

**Render:**

```html
<label for="..." class="hwc-label {{ class }}">
    {{ $value ?? $slot }}
    @if ($required) <span class="hwc-required" aria-hidden="true">*</span> @endif
    @if ($optional) <span class="hwc-optional">(opcional)</span> @endif
    @if ($info) <span data-controller="tooltip" data-tooltip-content-value="..."> [icon] </span> @endif
</label>
```

**Exemplos:**

```blade
<x-hwc::label for="email" required>E-mail</x-hwc::label>
<x-hwc::label for="bio" optional info="Máximo 500 caracteres">Biografia</x-hwc::label>
```

#### `<x-hwc::error>`

**Props:**

| Prop | Tipo | Default | Efeito |
|---|---|---|---|
| `name` | string\|null | via `@aware` | Chave em `$errors` (já em dot notation) |
| `errorKey` | string\|null | `FieldKey::toErrorKey($name)` | Override quando name HTML ≠ chave de validação |
| `messages` | array\|null | `$errors->get($errorKey)` | Override manual |
| `id` | string | `{inputId}-error` | Casa com `aria-describedby` do input |
| `class` | string | `""` | Mergeado |
| `as` | string | auto (`div`/`ul`) | Tag — `div` se 1 msg, `ul` se múltiplas; pode forçar |

**Render:**

Sempre renderiza o nó (com `hidden` quando vazio), para `aria-describedby` do input ser estável e não causar reflow ao aparecer.

```html
<div id="..." role="alert" aria-live="polite" @class(['hwc-error', class, 'hidden' => empty($messages)])>
    @if (count($messages) === 1)
        {{ $messages[0] }}
    @elseif (count($messages) > 1)
        <ul>@foreach ($messages as $m) <li>{{ $m }}</li> @endforeach</ul>
    @endif
</div>
```

**Exemplos:**

```blade
<x-hwc::error name="email" />
<x-hwc::error name="variables[0][name]" /> {{-- errorKey derivado: variables.0.name --}}
<x-hwc::error error-key="custom.validation.path" />
```

#### `<x-hwc::field>`

Wrapper de conveniência que cola label+input+error e propaga `name`/`id`/`errorKey`/`required` via `@aware`.

**Props:**

| Prop | Tipo | Default | Efeito |
|---|---|---|---|
| `name` | string | — | Propagado para todos os filhos via `@aware` |
| `id` | string | `FieldKey::toId($name)` | Propagado |
| `errorKey` | string | `FieldKey::toErrorKey($name)` | Propagado |
| `label` | string\|null | `null` | Se setado, renderiza `<x-hwc::label>` automaticamente |
| `description` | string\|null | `null` | Texto auxiliar abaixo do label |
| `required` | bool | `false` | Propagado para label (decorativo) e input (atributo HTML) |
| `class` | string | `""` | Mergeado no `<div>` wrapper |

**Render:**

```blade
<div class="hwc-field {{ class }}">
    @if ($label) <x-hwc::label>{{ $label }}</x-hwc::label> @endif
    @if ($description) <p class="hwc-description">{{ $description }}</p> @endif
    {{ $slot }}
    <x-hwc::error />
</div>
```

**Exemplos:**

```blade
{{-- Caminho 90% — wrapper resolve tudo --}}
<x-hwc::field name="email" label="E-mail" required>
    <x-hwc::input type="email" auto-select clearable />
</x-hwc::field>

{{-- Caso real: campo de array com auto-derivação --}}
<x-hwc::field name="variables[0][name]" label="Variáveis" required>
    <x-hwc::input
        type="text"
        :value="$indicator->variables[0]['name'] ?? null"
        placeholder="Ex.: IBGE/ODS Brasil"
        auto-select
    />
</x-hwc::field>

{{-- Override quando id derivado não serve --}}
<x-hwc::field name="variables[0][name]" id="variable" label="Variáveis" required>
    <x-hwc::input type="text" :value="..." />
</x-hwc::field>

{{-- Override quando name HTML ≠ chave de validação --}}
<x-hwc::field name="variables[0][name]" error-key="indicator.variables.0.name" label="...">
    <x-hwc::input type="text" :value="..." />
</x-hwc::field>
```

#### `<x-hwc::textarea>`

**Props:**

- `autoresize` (bool): cresce com o conteúdo.
- `counter` (int|null): igual ao `<x-hwc::input>`.
- `countdown` (bool): igual.

**Render logic:** mesmo padrão de wrapper condicional do `<x-hwc::input>`. `autoresize` aplica direto no `<textarea>`. Slot principal recebe o conteúdo inicial.

**Exemplos:**

```blade
<x-hwc::textarea name="bio" autoresize :counter="500">{{ old('bio') }}</x-hwc::textarea>
<x-hwc::textarea name="tweet" autoresize :counter="280" countdown />
```

#### `<x-hwc::form>`

**Props booleanas (empilháveis):**

- `remote`: submit assíncrono via Turbo (`remote-form`).
- `auto-submit`: submete em `change`/`input` com debounce.
- `unsaved-changes`: avisa antes de sair com mudanças.
- `clean-query-params`: remove params vazios da URL ao submeter `GET`.

**Pass-through:** todos os atributos `<form>` (`action`, `method`, `enctype`, `class`, etc.).

**Render logic:** todas as props ativas são unidas no `data-controller` do `<form>`, separadas por espaço. Slot principal contém o conteúdo do formulário.

**Exemplos:**

```blade
<x-hwc::form :action="route('posts.update', $post)" method="put" unsaved-changes>
    @csrf @method('put')
    <x-hwc::input name="title" :value="$post->title" />
    <x-hwc::textarea name="body" autoresize>{{ $post->body }}</x-hwc::textarea>
    <button>Salvar</button>
</x-hwc::form>

<x-hwc::form :action="route('search')" method="get" auto-submit clean-query-params>
    <x-hwc::input type="search" name="q" clearable />
    <select name="category">…</select>
</x-hwc::form>

<x-hwc::form :action="route('comments.store')" method="post" remote>
    <x-hwc::textarea name="body" autoresize />
    <button>Enviar</button>
</x-hwc::form>
```

#### `<x-hwc::file-input>`

**Props:**

- `resetable` (bool): renderiza botão de reset ao lado.

**Render logic:** se `resetable`, wrapper com `data-controller="reset-files"`, input com `data-reset-files-target="input"`, botão com `data-reset-files-target="reset"`.

**Exemplo:**

```blade
<x-hwc::file-input name="avatar" resetable />
```

#### `<x-hwc::checkbox-group>`

**Props:**

- `name` (string): nome dos inputs (geralmente `foo[]`).
- `options` (Collection|array): pares `value => label` para iterar.
- `selected` (array): valores marcados (default `[]`).
- `select-all` (bool): renderiza checkbox master que sincroniza com os items.

**Render logic:** wrapper com `data-controller="checkbox-select-all"` se `select-all`. Master com target `master`, cada item com target `item`.

**Exemplo:**

```blade
<x-hwc::checkbox-group
    name="user_ids[]"
    :options="$users->pluck('name', 'id')"
    :selected="old('user_ids', [])"
    select-all
/>
```

### Auto-derivação de chave/id

Laravel valida com **dot notation** (`variables.0.name`), HTML usa **bracket notation** (`variables[0][name]`). Helper puro `Emaia\LaravelHotwire\Support\FieldKey` faz a conversão mecânica:

```php
FieldKey::toErrorKey('variables[0][name]'); // 'variables.0.name'
FieldKey::toId('variables[0][name]');       // 'variables-0-name'
```

Aplicado nos quatro componentes da triade:

| Prop | Default derivation | Override |
|---|---|---|
| `id` | `FieldKey::toId($name)` | `id="variable"` explícito |
| `errorKey` | `FieldKey::toErrorKey($name)` | `errorKey="custom.key"` explícito |
| `value` | `old($errorKey, $value)` | `:old="false"` desliga merge |

**Casos cobertos pelos testes:**

| Input `name` | `toId` | `toErrorKey` |
|---|---|---|
| `email` | `email` | `email` |
| `variables[0][name]` | `variables-0-name` | `variables.0.name` |
| `users[][email]` | `users--email` | `users..email` (degenerado, determinístico) |
| `address.street` | `address-street` | `address.street` (já dot) |

Helper vive em `src/Support/FieldKey.php`. Testes unitários em `tests/Support/FieldKeyTest.php`.

### Mask presets

Tabela inicial em `src/Components/Form/MaskPresets.php`:

| Preset | Mask string | Notas |
|---|---|---|
| `money` | n/a | seta `data-input-mask-is-money-value="true"` |
| `cpf` | `###.###.###-##` | |
| `cnpj` | `##.###.###/####-##` | |
| `phone-br` | `["(##) ####-####", "(##) #####-####"]` | múltiplas máscaras (Maska resolve por tamanho) |
| `cep` | `#####-###` | |
| `date` | `##/##/####` | |
| `time` | `##:##` | |

Se `mask` recebe valor que **não** está no mapa, é usado como string crua, sem warning.

### `HasStimulusControllers` dinâmico

Componentes Form retornam o **conjunto máximo** de controllers possíveis em `stimulusControllers()` (estático), para o `hotwire:check` validar publicação:

```php
class Input extends Component implements HasStimulusControllers
{
    public static function stimulusControllers(): array
    {
        return ['input-mask', 'clear-input', 'autoselect', 'char-counter'];
    }
}
```

Em runtime, um método de instância `stimulusControllersUsed(): array` (novo, opcional na interface) retorna apenas os ativos para aquela invocação — usado pelo template pra montar `data-controller`. Default = retornar `static::stimulusControllers()`.

### Pass-through de atributos

Todos os componentes usam `$attributes->merge([…])` no elemento principal pra herdar atributos HTML que não viraram props. Convenção:

- **props** absorvem comportamento (`mask`, `autoresize`, `clearable`, …);
- **atributos HTML** viram pass-through (`name`, `value`, `class`, `placeholder`, …).

### TDD — ordem de execução

**Triade fundamental primeiro** (sem dependência entre si, depois compõe):

1. **`Support\FieldKey`** — helper puro, testes unitários cobrindo a tabela de casos.
2. **`<x-hwc::error>`** — sem dependência. Testes: nó sempre presente, `hidden` quando vazio, `role="alert"`+`aria-live`, `as="div"`/`ul"` automático, override via `messages` prop, `errorKey` derivado de `name`.
3. **`<x-hwc::label>`** — props `for`, `required` (decorativo), `optional`, `info`. Testes: `for` derivado de `name`, marcador required/optional, slot vs `value` prop.
4. **`<x-hwc::input>` flat (sem wrapper):**
   1. `name` derivando `id` e `errorKey`
   2. `value` mergeando com `old()`; `:old="false"` desliga
   3. `errorKey` override explícito
   4. ARIA: `aria-invalid`, `aria-describedby`, `aria-required`
   5. `mask` (controller no input)
   6. `auto-select` (controller no input)
5. **`<x-hwc::input>` com wrapper:**
   1. `clearable` (wrapper + botão + controller `clear-input`)
   2. `:counter` + `countdown` (wrapper + slot opcional + `aria-live`)
   3. Combinação `clearable` + `:counter` (controllers compostos no wrapper, sem conflito de targets)
6. **`<x-hwc::field>`** — `@aware` propaga `name`/`id`/`errorKey`/`required` para label/input/error filhos. Testes: render mínimo, override de `id`/`errorKey`, `description` slot.

**Componentes do lote anterior** (textarea/form/file-input/checkbox-group): mantém ordem original abaixo.

7. **`<x-hwc::textarea>`:** `auto-resize`, depois `:counter` + `countdown`.
8. **`<x-hwc::form>`:** uma prop booleana por vez (`remote`, `auto-submit`, `unsaved-changes`, `clean-query-params`); cobrir empilhamento.
9. **`<x-hwc::file-input>`:** sem props; depois com `resetable`.
10. **`<x-hwc::checkbox-group>`:** sem `select-all`; depois com `select-all`.

Cada passo: teste de render (Pest) → implementação mínima → teste de `stimulusControllers()` → registro no catalog → doc `docs/components/{nome}.md`.

### Testes específicos

- **Render por combinação de props:** input com `mask` e `clearable` simultâneo cria wrapper certo, controllers separados em elemento certo, sem conflito de targets.
- **Pass-through:** atributos arbitrários (`required`, `pattern`, `data-foo="bar"`) chegam no elemento final.
- **Slot override:** `<x-slot:counter>` substitui a marcação default e mantém o target.
- **Empilhamento de form:** `data-controller` resultante tem todos os controllers ativos separados por espaço, na ordem das props.
- **`stimulusControllers()`:** retorna a união máxima — `hotwire:check` aceita componentes Form com qualquer subset usado nas views.

### Open design questions

1. **Posição do counter customizado** — sempre depois do input/textarea, ou permitir antes via prop `counter-position`? Decisão padrão: depois.
2. **Mask preset desconhecido** — fallback string crua silencioso, ou validar contra catálogo? Decisão padrão: fallback.
3. **`<x-hwc::form>` deve oferecer prop `confirm`** integrando com `confirm-dialog`? Fora do escopo Form Essentials inicial; potencial Fase 2.
4. **Acessibilidade do contador** — aplicar `aria-live="polite"` automaticamente? Recomendação: sim.
5. **`<x-hwc::checkbox-group>` precisa de `<x-slot:item>`** pra customização de cada checkbox? Decisão padrão: não na primeira versão.
6. **Compat de `name` em `<x-hwc::checkbox-group>`** — aceitar `name="users"` e converter para `users[]` automaticamente, ou exigir `users[]` explícito? Decisão padrão: explícito.
7. **Auto-merge de `old()` no input** — on por default vs explícito via `:old="true"`. Decisão: on por default; surpreende em forms read-only mas é o caminho 95%. Escape via `:old="false"`.
8. **`<x-hwc::field>` auto-renderiza `<x-hwc::input>`?** — `<x-hwc::field name="x" type="email" label="..." />` sem slot. Mais conciso mas vira "componente mágico". Decisão: não — manter slot explícito.
9. **`<x-hwc::field required>` propaga para input (HTML real) e label (marcador)?** Decisão: ambos — single source of truth.
10. **CSS baseline** — shippar `hwc-input`/`hwc-label`/`hwc-error`/`hwc-field` como classes vazias (anchors) ou nem isso? Decisão: shippar vazias — ponto de extensão sem opinião.
11. **`<x-hwc::error>` sempre renderiza o nó** (`hidden` quando vazio) ou só renderiza se há erro? Decisão: sempre — `aria-describedby` estável, sem reflow ao aparecer.

### Out of scope (Form Essentials)

- Dropdown/select customizado — Fase 3.
- Multi-step wizard — fora do escopo do pacote.
- Validação client-side — fora do escopo.
- Estilos opinionados — pacote shippa só hooks vazios (`hwc-input`, `hwc-label`, `hwc-error`, `hwc-field`); usuário aplica CSS dele.

## Test Plan

- Unit:
  - carregamento do catálogo
  - integridade de paths/classes/docs
  - resolução de controllers por componente
  - resolução de npm deps por controller
- Feature:
  - `hotwire:components`
  - `hotwire:check`
  - `hotwire:controllers --list`
  - registro de componentes no service provider
- Component:
  - renderização dos componentes existentes e novos
- JS:
  - controllers críticos: `dialog`, `confirm-dialog`, `tooltip`, `optimistic`, `auto-submit`
  - usar `happy-dom` apenas para fluxos unitários simples:
    - manipulação básica de DOM
    - dispatch de eventos
    - observers/mutations simples
    - estados internos do controller
    - loading templates e correlação de eventos Turbo
  - não forçar `happy-dom` para fluxos que dependem fortemente de browser real:
    - focus trap com `Tab` / `Shift+Tab`
    - `Escape`
    - click outside com geometry real
    - integração visual e comportamental de overlays
  - para componentes críticos e interativos, adicionar browser tests reais com Playwright:
    - `modal`
    - `dialog` / `confirm-dialog`
    - futuros `dropdown` / `tooltip` complexos, se surgirem
  - quando possível, extrair helpers puros do controller para reduzir acoplamento ao DOM fake
- Docs integrity:
  - paths declarados no registry existem
  - componentes públicos têm docs associadas

## Assumptions

- O registry será estático e explícito, não inferido dinamicamente como contrato principal.
- O foco continua sendo kit Hotwire, com evolução possível para design system depois.

## Hook De Extensão Do Registry (Item 6 — pendente de decisão)

Hoje o catálogo é hardcoded em `require __DIR__.'/catalog.php'`. Apps ou packages de terceiros que queiram registrar seus próprios componentes/controllers no registry precisam editar o arquivo do pacote. Para um pacote `0.x` isso não bloqueia, mas vale prever um hook público.

### Design proposto

1. **Definitions carregam o próprio `basePath`**
   `ControllerDefinition` e `ComponentDefinition` ganham um campo `string $basePath`. Cada entrada (do package OU de extensão externa) sabe onde seus arquivos vivem.
   - `sourcePath()` deixa de receber arg e usa `$this->basePath`.
   - Adiciona `docsPath()` que retorna `$this->basePath.'/'.$this->docs`.
   - Mantém `relativeDir()`, `name()`, `filename()`, `publishKey()` operando apenas sobre o `source` relativo.
2. **`HotwireRegistry::extend(array $catalog, string $basePath): void`**
   Método estático que bufferiza extensões em `private static array $extensions = []` e invalida o cache do singleton. Próximo `make()` reconstrói mesclando o catálogo do package com todas as extensões na ordem de chegada.
3. **`HotwireRegistry::mergeCatalog(array $catalog, string $basePath): self`**
   Método de instância que retorna nova registry com o catálogo extra mesclado (immutable, preserva o padrão value-object). Compartilha o builder interno com `fromCatalog()` via helper privado `buildFrom(...)`.
4. **`reset()` também limpa as extensões**
   Para isolamento de testes: limpa singleton + buffer.
5. **Conflito de chaves**
   Extensão sobrescreve silenciosamente entradas com mesma chave. Documentar em README. Alternativa: log/warn ou throw — decidir quando aparecer caso real.

### Uso esperado

```php
// Service provider de outro package:
public function boot(): void
{
    HotwireRegistry::extend([
        'controllers' => [
            'my-ctrl' => [
                'source' => 'resources/js/controllers/my_controller.js',
                'docs'   => 'docs/my-ctrl.md',
                'category' => 'forms',
            ],
        ],
        'components' => [
            'my-comp' => [
                'class' => MyComponent::class,
                'view'  => 'mypkg::components.my-comp',
                'docs'  => 'docs/my-comp.md',
                'category' => 'forms',
                'controllers' => ['my-ctrl'],
            ],
        ],
    ], dirname(__DIR__));
}
```

### Trade-offs

- **Mudança invasiva nos call sites de `sourcePath($basePath)`**: `CheckCommand`, `ListComponentsCommand`, `PublishControllersCommand` e `HotwireRegistryTest` passam a chamar `sourcePath()` sem arg. ~5 arquivos, todas trocas mecânicas.
- **API de catálogo (array shape) vira contrato público** em vez de exigir construção de DTOs externamente. Reduz acoplamento à assinatura dos value objects.
- **Cache invalidation custa pouco**: `extend()` zera `self::$instance`; próximo `make()` reconstrói. Em prática, extensões são registradas no `boot()` antes do primeiro `make()`, então rebuild é raro.

### Variantes a considerar antes de implementar

- Manter `sourcePath($basePath)` legado e adicionar `sourcePath()` paralelo (evita breaking change em caller externo, mas duplica API).
- Expor variante tipada `register(ComponentDefinition|ControllerDefinition ...$defs)` em vez (ou além) de `extend(array, string)`. Catalog array é mais estável; DTOs dão mais segurança de tipo. Ainda não há demanda concreta.
- Adicionar campo `basePath` nas defs **opcional** (default = registry's basePath) para reduzir churn — mas torna definitions sensíveis ao contexto de construção, o que mistura responsabilidades.

## Macro `closeModal` + controller auxiliar de auto-close

### Objetivo

Permitir que o servidor feche um `<x-hwc::dialog>` aberto via Turbo Frame respondendo com um Turbo Stream — DX equivalente ao macro `flash()`. Hoje o ciclo "abrir → submeter → fechar + atualizar lista" exige redirect ou JS no cliente.

### Esboço proposto pelo usuário

```php
TurboStreamBuilder::macro('closeModal', function () {
    return $this->append('modal', '<span data-controller="dialog-auto-close"></span>');
});
```

```js
// resources/js/controllers/dialog_auto_close_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        const root = this.element.closest('[data-controller~="dialog--modal"]');
        if (!root) return;
        const ctrl = this.application.getControllerForElementAndIdentifier(root, "dialog--modal");
        ctrl?.close();
        this.element.remove();
    }
}
```

### Pontos a resolver antes de implementar

1. **Identifier do dialog não bate com o esboço**
   `dialog_controller.js` está no nível raiz → identifier é `dialog`, **não** `dialog--modal`. O esboço veio de outro projeto (substrate folder `dialog/modal_controller.js`). Lookup precisa virar `dialog`. A class do controller hoje se chama `ModalController` mas o identifier é `dialog` — vocabulário misturado, mas o que importa para Stimulus é o identifier registrado pelo loader.

2. **Target id `'modal'` não existe**
   `<x-hwc::dialog>` gera `id="{{ $id }}"` com default `uniqid('modal-')` — não há um id fixo `'modal'` na página. O macro precisa receber o id do dialog:
   ```php
   TurboStreamBuilder::macro('closeModal', function (string $id) {
       return $this->append($id, '<span data-controller="dialog-auto-close"></span>');
   });
   ```
   Convenção sugerida: usuário define `id` explícito no dialog (`<x-hwc::dialog id="edit-post" />`) e referencia o mesmo id no macro.

3. **Onde injetar o span**
   Append no root do dialog (`<div id="...">`) faz com que o span vire irmão dos targets internos. `closest('[data-controller~="dialog"]')` acha o root corretamente. Self-remove no fim do `connect()` evita lixo no DOM.

4. **Naming do controller e arquivo**
   Convenções do package (CLAUDE.md): nome compound, no UI-role folder, `{name}_controller.{js|ts}`. Opções:
   - `dialog_auto_close_controller.js` → identifier `dialog-auto-close` ✅ (alinha com esboço do usuário)
   - `auto_close_dialog_controller.js` → identifier `auto-close-dialog`

   Recomendação: **`dialog-auto-close`** — prefixo `dialog-` agrupa visualmente com `dialog` no `hotwire:controllers --list`.

5. **Class name do controller TS/JS**
   Esboço tinha `ClosemodalController` (case incorreto + vocabulário misturado). Renomear para `DialogAutoCloseController`. Default export anônimo (`export default class extends Controller {}`) também é aceitável e evita o problema — vários controllers do package fazem assim.

6. **Catalog entry (`src/Registry/catalog.php`)**
   ```php
   'dialog-auto-close' => [
       'source' => 'resources/js/controllers/dialog_auto_close_controller.js',
       'docs' => 'docs/controllers/dialog-auto-close.md',
       'category' => 'overlay',
   ],
   ```
   E adicionar `'dialog-auto-close'` ao array `controllers` da entry `dialog` em `components`? **Não** — o auto-close não é dependência do componente em si, é uma ferramenta opcional. Documentar a relação no doc do dialog basta.

7. **Doc do macro**
   Onde colocar?
   - `docs/components/dialog/readme.md` — seção "Turbo integration" tem o lugar natural. ✅
   - `docs/controllers/dialog-auto-close.md` — doc próprio do controller, focado no uso direto via HTML (caso o usuário queira disparar sem o macro).

8. **Cleanup defensivo**
   - Verificar se o id existe antes de tentar pegar o controller (`getControllerForElementAndIdentifier` retorna `null` se Stimulus ainda não conectou) — `optional chaining` já cobre.
   - Race condition: se o Turbo Stream chega antes do dialog estar conectado, `closest()` acha o elemento mas `getControllerForElementAndIdentifier` retorna `null`. Tratar com `requestAnimationFrame` ou observer? **Caso de borda**: dialog tem que estar aberto pra fazer sentido fechar — se está aberto, está conectado. Não precisa de retry.

### Variantes a considerar

- **Custom Turbo Stream Action** em vez de Stimulus aux:
  ```html
  <turbo-stream action="close-dialog" target="edit-post"></turbo-stream>
  ```
  ```js
  Turbo.StreamActions.close_dialog = function() {
      const target = document.getElementById(this.getAttribute("target"));
      // ...
  };
  ```
  **Trade-off**: semanticamente mais limpo (não polui o DOM com `<span>`) mas exige registrar action customizada no app do usuário e sai do padrão atual do package (que é só Stimulus + componentes Blade). Mantém-se o esboço com Stimulus por consistência.

- **Macro genérico `dispatch(string $id, string $event)`**: emite um evento DOM customizado em vez de chamar `controller.close()` diretamente. Mais flexível (`dialog#close`, `dialog#open`, `flash#dismiss`...) mas exige conhecimento do nome de evento. Deixar para depois — `closeModal` resolve o caso concreto.

- **Reaproveitar `clearContent()` + observer existentes**: o observer no `dynamicContent` já fecha o dialog automaticamente quando o conteúdo é removido (`turbo_stream()->update('frame-id', '')`). Para dialogs alimentados via Turbo Frame, o usuário **já consegue fechar hoje** sem macro novo. O macro `closeModal` cobre o caso de **dialog estático** (sem Turbo Frame) — vale documentar essa distinção.

### Trade-offs gerais

- Adiciona um novo controller ao catálogo → mais um arquivo a manter, entry em `catalog.php`, doc, teste de integridade do catálogo passa a cobrir.
- Vocabulário "modal" vs "dialog": macro chama `closeModal` mas componente é `dialog`. Considerar `closeDialog` para alinhar — mas "modal" é o termo mais reconhecível em UX. Decisão de naming.
- Macro acopla o stream builder ao componente Blade do package. Coerente com `flash()` que já faz o mesmo.

## Conjunto avançado de Dialog + APIs server-side

### Objetivo

Cobrir os fluxos completos de "modal as a page" com DX server-first:

- view única que renderiza como página standalone ou como conteúdo de Turbo Frame modal;
- abrir/fechar dialogs a partir do servidor sem JS no cliente;
- empilhar múltiplos dialogs simultâneos sem regredir foco/escape/scroll lock;
- compor `refresh + closeDialog + flash` em uma única response (já viável com macros).

### Padrão "frame-or-page" (proposto pelo usuário)

Snippet do usuário:
```blade
@if (request()->header('turbo-frame') === 'modal')
    <turbo-frame id="modal">{{ $slot }}</turbo-frame>
@else
    <x-layouts.dashboard>{{ $slot }}</x-layouts.dashboard>
@endif
```

Mesma view (`edit.blade.php`) renderiza:
- como página completa quando acessada via URL direta (refresh/back/copy-link funciona);
- como conteúdo de modal quando o link tem `data-turbo-frame="modal"`.

#### Caminho de implementação no package

1. **Não escrever a request macro** — `request()->wasFromTurboFrame('modal')` já existe em `emaia/laravel-hotwire-turbo:TurboServiceProvider`. Documentar essa opção em vez de duplicar.

2. **Documentar o padrão "frame-or-page"** num cookbook (`docs/recipes/frame-or-page.md` ou seção em `docs/components/dialog/readme.md`). Mostrar:
   - layout `modal-base.blade.php` na app do usuário;
   - controller respondendo com a view (mesma resposta cobre os dois casos);
   - link com `data-turbo-frame="modal"` para abrir como modal, ou click direto para abrir como página.

3. **Componente helper opcional** `<x-hwc::frame-or>`:
   ```blade
   <x-hwc::frame-or frame="modal">
       <x-slot:frame>
           <turbo-frame id="modal">{{ $slot }}</turbo-frame>
       </x-slot:frame>
       <x-slot:page>
           <x-layouts.dashboard>{{ $slot }}</x-layouts.dashboard>
       </x-slot:page>
   </x-hwc::frame-or>
   ```
   **Trade-off**: encapsula o `@if` mas adiciona indireção de slots aninhados. **Recomendação: não shippar** — o `@if` direto com `wasFromTurboFrame()` é mais legível. Apenas documentar.

### Server-side open

Espelho do `closeDialog`. Use cases:

- abrir um dialog estático com conteúdo pré-existente em resposta a uma ação (ex.: tutorial, paywall);
- redirecionar um fluxo para confirmar antes de continuar.

#### Variante A — `dialog-auto-open` controller

```js
// resources/js/controllers/dialog_auto_open_controller.js
export default class extends Controller {
    connect() {
        const root = this.element.closest('[data-controller~="dialog"]');
        const ctrl = root && this.application.getControllerForElementAndIdentifier(root, "dialog");
        ctrl?.open();
        this.element.remove();
    }
}
```

Macro:
```php
TurboStreamBuilder::macro('openDialog', function (string $id) {
    return $this->append($id, '<span data-controller="dialog-auto-open"></span>');
});
```

**Aparente limitação**: o dialog precisa estar no DOM. Mas isso se resolve compondo o conteúdo na mesma response — ver "Open com conteúdo dinâmico" abaixo.

#### Open com conteúdo dinâmico (composição em um payload)

A forma mais robusta de abrir um dialog **inexistente** é renderizar o componente completo com o gatilho de open dentro, num único `append`:

```php
return turbo_stream()->append('body', Blade::render(<<<'BLADE'
    <x-hwc::dialog :id="$id">
        {{ $content }}
        <span data-controller="dialog-auto-open"></span>
    </x-hwc::dialog>
BLADE, ['id' => 'edit-post', 'content' => view('posts.edit-form', $data)]));
```

Quando o fragmento aterrissa no DOM, o Stimulus conecta os controllers em ordem do documento:
1. `dialog` conecta primeiro;
2. `dialog-auto-open` conecta em seguida, acha o ancestral e chama `open()`;
3. o span se auto-remove.

Como tudo está no **mesmo chunk de HTML**, não há race entre streams.

#### Por que não dois streams encadeados?

```php
// risco de race
return turbo_stream()
    ->append('body', $dialogMarkup)
    ->append($id, '<span data-controller="dialog-auto-open"></span>');
```

Stimulus conecta controllers via `MutationObserver`, que dispara em microtask. Se o segundo `append` é processado antes do `dialog` controller terminar `connect()`, o `dialog-auto-open` chama `getControllerForElementAndIdentifier(...)` e recebe `null`. Em prática Turbo processa streams sequencialmente e o ciclo dá tempo, mas é frágil. **Preferir o payload único.**

#### Macro de conveniência `openDialogWith`

Encapsula o pattern acima:

```php
TurboStreamBuilder::macro('openDialogWith', function (string $id, $content, array $attrs = []) {
    return $this->append('body', Blade::render(<<<'BLADE'
        <x-hwc::dialog :id="$id" :attributes="$attrs">
            {!! $content !!}
            <span data-controller="dialog-auto-open"></span>
        </x-hwc::dialog>
    BLADE, compact('id', 'content', 'attrs')));
});
```

Uso:
```php
return turbo_stream()->openDialogWith('edit-post', view('posts.edit-form', $data));
```

**Pontos a resolver antes de shippar `openDialogWith`**:
- O `<x-hwc::dialog>` exige slot `trigger` opcional. Sem trigger, o componente renderiza só o overlay — verificar se o template já cobre esse caso (parece que sim, `@if (isset($trigger))`).
- Conteúdo é injetado via `{!! !!}` se for `View|Htmlable`, ou via Blade prop. Decidir API: `string|View|Htmlable`.
- Cleanup: o dialog injetado fica no DOM depois de fechado. `clearContent()` zera o conteúdo dinâmico, mas o root persiste. Considerar variante que se auto-remove no `modal:closed` event (mais um pequeno controller `dialog-self-destruct`?) ou aceitar que dialogs dinâmicos vivem até a próxima navegação Turbo.

#### Variante alternativa — Frame-driven (já funciona hoje)

Para conteúdo dinâmico via Turbo Frame, aponta um link com `data-turbo-frame="modal"` pra URL que retorna o conteúdo. O `dynamicContent` observer abre automaticamente. **Nenhum macro novo necessário** — só documentar a comparação:

| Cenário                                                      | Caminho recomendado                       |
|--------------------------------------------------------------|-------------------------------------------|
| Click do user → abrir dialog com conteúdo do servidor        | Turbo Frame + `<x-hwc::dialog>` com frame |
| Resposta de form/action → abrir dialog (sem novo request)    | `openDialogWith` (composição em payload)  |
| Dialog estático já na página → abrir via stream              | `openDialog` (auto-open em dialog vivo)   |
| Dialog dinâmico → fechar via stream                          | `closeDialog` (já implementado)           |

**Decisão**: shippar **três** macros como conjunto coerente:
1. `openDialog($id)` — abre dialog que já existe no DOM (controller `dialog-auto-open`)
2. `openDialogWith($id, $content)` — cria + abre em uma resposta (composição inline)
3. `closeDialog($id)` ✅ já feito

### Stack de dialogs

#### Problema

Hoje cada `<x-hwc::dialog>` é independente. Abrir um segundo dialog enquanto o primeiro está aberto causa:

- ambos competem por foco (focus trap em dois containers);
- Escape fecha o primeiro que receber o evento (depende da ordem de listener registrado);
- click outside no segundo dialog pode bater no primeiro;
- scroll lock é adicionado/removido inconsistentemente no `disconnect()` cruzado.

#### Variante A — Stack global compartilhado

Um singleton (`window.__hwcDialogStack` ou um Stimulus controller `dialog-stack` no `<body>`) que mantém uma lista LIFO de dialogs abertos. Cada `dialog#open()` faz `push(self)`, `dialog#close()` faz `pop`. Listeners globais (Escape, click outside) consultam só `peek()`.

**Trade-offs**:
- ✅ resolve foco/escape/scroll lock corretamente;
- ❌ acopla controllers a um estado externo, regridindo a "self-contained" que é hoje;
- ❌ teste em isolamento fica mais difícil.

#### Variante B — z-index dinâmico + foco delegado

Não compartilha estado: cada dialog, ao abrir, calcula `z-index = max(otherDialogsZ) + 10` e instala focus trap só em si mesmo. Escape fecha o dialog do qual o evento veio (event.target dentro de `this.modalTarget`).

**Trade-offs**:
- ✅ menor mudança;
- ❌ scroll lock ainda precisa de coordenação (ex.: contador no `<body>` em vez de toggle).

#### Variante C — Não suportar stack, documentar como anti-pattern

Empilhar modais é controverso em UX. Pode-se documentar que o package suporta **um dialog por vez** e que stack deve ser feito por composição (substituir conteúdo do mesmo dialog).

**Recomendação**: adiar decisão até haver demanda concreta. A variante (B) é o meio-termo se vier; (C) é defensável.

### Novos macros sugeridos

| Macro                             | Status               | Comentário                                                                                  |
|-----------------------------------|----------------------|---------------------------------------------------------------------------------------------|
| `flash($type, $msg, ?$desc)`      | ✅ documentado        | já existe                                                                                   |
| `closeDialog($id)`                | ✅ implementado       | já existe + controller `dialog-auto-close`                                                  |
| `openDialog($id)`                 | 🟡 proposto          | requer controller `dialog-auto-open` (variante A). Edge case — documentar no cookbook       |
| `closeAllDialogs()`               | 🟡 proposto          | dispara evento DOM `hwc:dialog:close-all`; controller `dialog` adiciona listener            |
| `replaceDialog($id, $content)`    | 🔴 não recomendar     | composição via Turbo Frame já cobre — `update('frame-id', $view)` mantém o dialog aberto    |

### `closeAllDialogs()` — caminho

```php
TurboStreamBuilder::macro('closeAllDialogs', function () {
    return $this->append('body', '<span data-controller="dialog-close-all"></span>');
});
```

Controller `dialog-close-all` no connect: `document.dispatchEvent(new CustomEvent('hwc:dialog:close-all'))`, depois `remove()`. `dialog_controller.js` ganha:
```js
connect() {
    // ...
    document.addEventListener('hwc:dialog:close-all', () => this.close());
}
```

Listener global é leve, e o controller existente já tem `disconnect()` cleanup pra remover.

### Cookbook de docs proposto

Criar `docs/recipes/` com:
- `frame-or-page.md` — padrão de view única dual-mode
- `server-driven-dialogs.md` — quando usar Turbo Frame vs `openDialog`/`closeDialog` macro
- `composing-streams.md` — `refresh + closeDialog + flash` chain

Mantém `docs/components/dialog/readme.md` enxuto (api do componente) e empurra patterns avançados para receitas dedicadas.

### Ordem sugerida de implementação

1. **Cookbook frame-or-page** (só doc, zero código). Maior ROI imediato — documenta um padrão que já funciona com `wasFromTurboFrame()`.
2. **`dialog-auto-open` + macro `openDialog`** — simétrico ao close, baixo risco.
3. **`closeAllDialogs()` + listener no `dialog_controller`** — útil em fluxos multi-step.
4. **Stack support** — só se aparecer caso de uso real. Variante (C) — documentar como não-suportado — até lá.

### Open questions

- Renomear `ModalController` (class) para `DialogController` para alinhar com o identifier `dialog`? Cosmético, mas reduz confusão pra contribuidores. Item separado.
- "Modal" vs "Dialog" no vocabulário externo (props, eventos `modal:opened`, slot `loading_template`): manter ou padronizar pra `dialog:`? Breaking change — adiar.

## Padronização de vocabulário: tudo como `modal`

### Contexto

Hoje o package tem vocabulário misturado:

| Onde                     | Termo |
|--------------------------|-------|
| Component / identifier   | `dialog` |
| Class do controller       | `ModalController` |
| Doc title                | `# Modal` (já!) |
| Target principal         | `data-dialog-target="modal"` |
| Eventos                  | `modal:opened`, `modal:closed` |
| ID default               | `uniqid('dialog-')` (mas `Dialog::__construct` ainda gera `dialog-` enquanto `view` tinha `modal-` — verificar) |
| Component-relacionado    | `confirm-dialog`, `dialog-auto-close` |

Como o componente **sempre** renderiza como modal (sempre `aria-modal="true"`, backdrop, focus trap, scroll lock), `modal` descreve melhor o que ele faz. `dialog` é mais amplo (HTML `<dialog>` suporta modal e não-modal).

### Decisão

Padronizar tudo como **`modal`**.

### Escopo (breaking change major)

#### Renomes de arquivos

- `src/Components/Dialog.php` → `src/Components/Modal.php`
- `resources/views/components/dialog/dialog.blade.php` → `resources/views/components/modal/modal.blade.php`
- `resources/js/controllers/dialog_controller.js` → `resources/js/controllers/modal_controller.js`
- `resources/js/controllers/dialog_auto_close_controller.js` → `resources/js/controllers/modal_auto_close_controller.js`
- `docs/components/dialog/` → `docs/components/modal/`
- `docs/controllers/dialog.md` → `docs/controllers/modal.md`
- `docs/controllers/dialog-auto-close.md` → `docs/controllers/modal-auto-close.md`
- `docs/recipes/server-driven-dialogs.md` → `docs/recipes/server-driven-modals.md`
- `tests/Components/DialogTest.php` → `tests/Components/ModalTest.php`

#### Substituições in-file

| De                                       | Para                                  |
|------------------------------------------|---------------------------------------|
| `<x-hwc::dialog>`                        | `<x-hwc::modal>`                      |
| `data-controller="dialog"`               | `data-controller="modal"`             |
| `data-dialog-target="..."`               | `data-modal-target="..."`             |
| `data-dialog-*-value`                    | `data-modal-*-value`                  |
| `data-dialog-*-class`                    | `data-modal-*-class`                  |
| Stimulus action `dialog#open`            | `modal#open`                          |
| `dialog-auto-close`                      | `modal-auto-close`                    |
| Macro `closeDialog`                      | `closeModal`                          |
| `uniqid('dialog-')`                      | `uniqid('modal-')`                    |
| Catalog key `'dialog'`                   | `'modal'`                             |
| Catalog key `'dialog-auto-close'`        | `'modal-auto-close'`                  |
| `Dialog::class`                          | `Modal::class`                        |
| `hotwire::components.dialog.dialog`      | `hotwire::components.modal.modal`     |

#### Mantém-se inalterado

- Class `ModalController` (já está correta)
- Eventos `modal:opened`, `modal:closed` (já corretos)
- Target `modal` dentro do componente (já correto)
- Slot `loading_template` (não é dialog/modal)

### Question aberta: `confirm-dialog`

Duas opções:

**A) Renomear para `confirm-modal`** — consistência total com a padronização.
- ✅ Vocabulário 100% uniforme.
- ❌ "Confirm dialog" é um termo padrão da indústria (alert dialog, confirm dialog, prompt dialog na browser API).
- ❌ Mais um breaking change agregado.

**B) Manter `confirm-dialog`** — "confirm dialog" é nome próprio de uma UX pattern reconhecida.
- ✅ Reconhecibilidade.
- ✅ ARIA tem `role="alertdialog"` especificamente pra esse uso, reforçando "dialog" no contexto.
- ❌ Quebra a uniformidade ("modal" pra um, "dialog" pro outro).

**Recomendação**: **B** (manter `confirm-dialog`). O componente genérico modal e o componente confirmation prompt são entidades diferentes — `<x-hwc::modal>` é o container reutilizável, `<x-hwc::confirm-dialog>` é uma UX pattern fechada. O nome "confirm-dialog" carrega significado próprio.

Mas se a regra "tudo modal" for hard rule, vai pra (A).

### Migração / breaking change

- Bump major version (próxima release).
- CHANGELOG com seção "Renaming dialog → modal" listando todos os renames.
- Considerar fornecer aliases temporários no service provider (registrar `dialog` apontando pra mesma view de modal por 1 ciclo) — ou cortar limpo. **Recomendação**: cortar limpo, é um package em 0.x.

### Ordem de execução sugerida

1. Renomear arquivos PHP (`Dialog.php`, `dialog.blade.php`).
2. Renomear arquivo JS do controller principal + auto-close.
3. Atualizar `catalog.php` (entries + class refs).
4. Substituir conteúdo nos arquivos (controllers, view, components, tests).
5. Renomear arquivos de doc + atualizar conteúdo.
6. Renomear receita do cookbook + atualizar links.
7. Atualizar `README.md`, `CLAUDE.md`, `plan.md`.
8. `composer test && composer analyse && composer format`.

Cada passo verificável isoladamente. Se possível, commits separados por etapa pra facilitar bisect/rollback.
