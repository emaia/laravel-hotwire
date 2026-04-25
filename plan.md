# Plano De Trabalho: Registry Único E Roadmap Do Laravel Hotwire

## Summary

Introduzir um **registry único** como catálogo oficial do pacote para centralizar metadados de componentes Blade, controllers Stimulus, dependências npm, documentação e categorias. Esse registry passa a alimentar comandos, docs e validações, reduzindo duplicação e inconsistência.

## Registry Único

### Objetivo

Substituir múltiplas listas paralelas por uma única fonte de verdade consumida por:

- `hotwire:components`
- `hotwire:check`
- `hotwire:controllers --list`
- futuras verificações de docs/cobertura
- eventual geração de catálogo ou site de documentação

### Estrutura proposta

Criar um pequeno subsistema em `src/Registry/` com três peças:

- `HotwireRegistry.php`
  - façade de leitura do catálogo;
  - expõe consultas por componente, controller, categoria e dependências.
- `ComponentDefinition.php`
  - value object para metadados públicos de componentes Blade.
- `ControllerDefinition.php`
  - value object para metadados públicos de controllers Stimulus.

### Fonte dos dados

Criar um arquivo estático em PHP, `src/Registry/catalog.php`, retornando arrays simples. O código transforma esses arrays em value objects.

Motivo para arquivo PHP simples:
- fácil de manter;
- tipável no carregamento;
- sem dependência de parser extra;
- mais estável do que inferir tudo em runtime.

### Shape dos dados

#### Controllers

```php
return [
    'controllers' => [
        'dialog' => [
            'identifier' => 'dialog',
            'source' => 'resources/js/controllers/dialog_controller.js',
            'docs' => 'docs/controllers/dialog.md',
            'category' => 'overlay',
            'npm' => [],
            'internal' => false,
        ],
        'tooltip' => [
            'identifier' => 'tooltip',
            'source' => 'resources/js/controllers/tooltip_controller.js',
            'docs' => 'docs/controllers/tooltip.md',
            'category' => 'utility',
            'npm' => [
                'tippy.js' => '^6.3.7',
            ],
            'internal' => false,
        ],
    ],
];
```

#### Components

```php
return [
    'components' => [
        'dialog' => [
            'key' => 'dialog',
            'class' => \Emaia\LaravelHotwire\Components\Dialog::class,
            'view' => 'hotwire::components.dialog.dialog',
            'docs' => 'docs/components/dialog/readme.md',
            'category' => 'overlay',
            'controllers' => ['dialog'],
            'aliases' => [],
            'experimental' => false,
        ],
        'flash-message' => [
            'key' => 'flash-message',
            'class' => \Emaia\LaravelHotwire\Components\FlashMessage::class,
            'view' => 'hotwire::components.flash-message.flash-message',
            'docs' => 'docs/components/flash-message/readme.md',
            'category' => 'feedback',
            'controllers' => ['toast'],
            'aliases' => [],
            'experimental' => false,
        ],
    ],
];
```

### Campos do contrato

#### `ControllerDefinition`

- `identifier`
- `source`
- `docs`
- `category`
- `npm`
- `internal`
- `aliases` opcional
- `experimental` opcional
- `deprecated` opcional

#### `ComponentDefinition`

- `key`
- `class`
- `view`
- `docs`
- `category`
- `controllers`
- `aliases`
- `experimental`
- `deprecated`

### Regras importantes

- O registry descreve; ele não executa lógica de negócio.
- `npm` passa a vir do catálogo, não de parsing heurístico de imports JS.
- Parsing de imports pode continuar existindo apenas como verificação de integridade em teste.
- `HasStimulusControllers` pode ser mantida temporariamente para compatibilidade, mas o consumidor principal passa a ser o registry.
- `LaravelHotwireServiceProvider::COMPONENTS` deixa de ser lista primária e passa a ser derivado do registry, ou é removido depois da migração.

## Como O Registry Será Usado

### Service provider

- Registrar componentes Blade iterando sobre `HotwireRegistry::components()`.
- Preservar prefixo configurável e alias `hotwire`.

### `hotwire:components`

- Ler somente `components()`.
- Exibir:
  - categoria
  - tag Blade
  - controllers exigidos
  - status de publicação dos controllers
  - docs

### `hotwire:check`

- Detectar componentes usados nas views.
- Resolver controllers requeridos via registry.
- Resolver dependências npm via registry dos controllers.
- Validar:
  - controller publicado
  - controller atualizado
  - npm dependency presente
  - docs opcionais em checks internos futuros

### `hotwire:controllers --list`

- Ler somente `controllers()`.
- Mostrar:
  - categoria
  - identifier
  - arquivo
  - deps npm
  - status

### Testes de integridade

Adicionar testes que garantem:

- todo componente do registry referencia classe existente;
- toda view/documentação referenciada existe;
- todo controller do registry aponta para arquivo existente;
- todo controller requerido por componente existe no registry;
- categorias usam conjunto conhecido;
- aliases não colidem.

## Roadmap De Implementação

### Etapa 1: Introduzir o subsistema de registry

- Criar `src/Registry/HotwireRegistry.php`
- Criar `src/Registry/ComponentDefinition.php`
- Criar `src/Registry/ControllerDefinition.php`
- Criar `src/Registry/catalog.php`
- Adicionar testes unitários do carregamento e validação do catálogo

### Etapa 2: Migrar consumo interno

- Atualizar service provider para registrar componentes a partir do registry
- Atualizar `hotwire:components`
- Atualizar `hotwire:check`
- Atualizar `hotwire:controllers --list` para usar o mesmo catálogo
- Manter compatibilidade temporária com `HasStimulusControllers`

### Etapa 3: Remover duplicação antiga

- Eliminar `LaravelHotwireServiceProvider::COMPONENTS` como fonte primária
- Reduzir o papel de `HasStimulusControllers` para compatibilidade ou removê-lo numa etapa posterior
- Parar de inferir deps npm por leitura de imports em runtime

### Etapa 4: Documentação e integridade

- Sincronizar README e docs com o catálogo
- Adicionar teste que falha se catálogo e docs divergirem
- Padronizar categorias públicas:
  - `overlay`
  - `feedback`
  - `forms`
  - `turbo`
  - `utility`
  - `dev`

## Plano De Produto

### Fase 1: Fundação E Baixo Risco

- Introduzir registry único
- Corrigir inconsistências de docs/nomes
- Revisar `dialog` e `confirm-dialog` mantendo implementação por `div`
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
- Docs integrity:
  - paths declarados no registry existem
  - componentes públicos têm docs associadas

## Assumptions

- O registry será estático e explícito, não inferido dinamicamente como contrato principal.
- `HasStimulusControllers` pode coexistir temporariamente para reduzir risco de migração.
- O foco continua sendo kit Hotwire, com evolução possível para design system depois.
