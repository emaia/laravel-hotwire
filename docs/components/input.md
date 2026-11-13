# Input

Form input with auto-derived `id`/`errorKey` from `name`, automatic `old()` merge, ARIA wiring, a stable text-control root,
and optional Stimulus behaviors (`mask`, `clearable`, `auto-select`). Native search inputs are clearable by default.

## Quick example

```blade
<hw:input name="email" type="email" required />
```

Renders an `<input>` with:

- `id="email"`, `name="email"`
- `value` from `old('email', $value)`
- `aria-invalid="true"` and `data-invalid` when `$errors->has('email')`
- `aria-required="true"` when `required` is present

## Props

| Prop                | Type                | Default             | Description                                                  |
|---------------------|---------------------|---------------------|--------------------------------------------------------------|
| `name`              | `string\|null`      | —                   | Pass-through. Drives `id` and `errorKey` if those aren't set |
| `id`                | `string\|null`      | derived from `name` | Override the auto-derived id                                 |
| `type`              | `string`            | `"text"`            | Pass-through                                                 |
| `value`             | `mixed`             | `null`              | Merged with `old($errorKey, $value)` unless `:old="false"`   |
| `checked`           | `bool`              | `false`             | Initial checked state for `type="checkbox"` / `type="radio"` |
| `errorKey`          | `string\|null`      | derived from `name` | Override for arrays where HTML `name` ≠ validation key       |
| `old`               | `bool`              | `true`              | Disable `old()` auto-merge                                   |
| `clearable`         | `bool\|null`        | `null`              | Clear button; defaults to enabled for `type="search"`, disabled otherwise |
| `auto-select`       | `bool`              | `false`             | Selects content on focus (controller `auto-select`)          |
| `mask`              | `string\|null`      | `null`              | Preset (`cpf`, `phone-br`, ...) or raw Maska string          |
| `auto-submit`       | `bool\|string`      | `false`             | Add auto-submit wiring; text inputs default to debounced     |
| `auto-submit-delay` | `int\|string\|null` | `null`              | Per-field debounce override when auto-submit is debounced    |
| `class`             | `string`            | `""`                | Layout classes on the stable root; native input for unwrapped types |
| `input-class`       | `string`            | `""`                | Classes on the native `<input>`                              |

Any other HTML attribute (`placeholder`, `pattern`, `disabled`, `data-*`, `aria-*`) passes through.

## Layout and native control

Text-like inputs always render a `data-slot="input-wrapper"` root, whether or not clearing is enabled. Use `class` for
outer layout and `input-class` for control appearance, height and native states:

```blade
<div class="flex gap-2">
    <hw:input name="q" type="search" class="flex-1" input-class="font-mono disabled:opacity-50" />
    <hw:button type="submit">Search</hw:button>
</div>
```

The root has no field semantics. `id`, `name`, `style`, `required`, `disabled`, ARIA and user-provided Stimulus attributes
remain on the actual input, so labels, validation, form submission and custom controllers keep targeting the native
control. A user-provided `data-slot` also applies to that input, not to the root.

Text-like types include `text`, `search`, `email`, `password`, `url`, `tel`, `number`, `date`, `datetime-local`, `month`,
`time` and `week`. Unknown types follow HTML's text fallback. `hidden`, `checkbox`, `radio`, `range`, `color`, `file`,
`button`, `submit`, `reset` and `image` remain unwrapped; their `class` and `input-class` are merged on the native input,
and `clearable` is ignored. Prefer the dedicated components where available.

## Search

```blade
<hw:input name="q" type="search" :value="request('q')" :old="false" aria-label="Search products" />
```

`type="search"` keeps the native search semantics and adds the package's tabbable clear button. The foundation suppresses
the browser's search cancel button only for this custom-clear composition, avoiding duplicate controls. Disabled and
readonly inputs do not expose or perform custom clearing, including controls inside a disabled fieldset.

Use `:clearable="false"` to keep only the browser's native search UI. The component root stays unchanged:

```blade
<hw:input name="q" type="search" :clearable="false" aria-label="Search products" />
```

Search never enables automatic submission by itself. Opt into `auto-submit` on the field and its ancestor form when
desired; see [Auto-submit](#auto-submit). A search icon remains optional composition through `InputGroup`.

For range selection, prefer the dedicated [`<hw:slider>`](./slider.md). It keeps the native input behavior while adding
Slider-specific orientation, visual fill and documentation without exposing text-input-only props.

## Addons And Icons

Use [`<hw:input-group>`](./input-group.md) when an input needs inline icons, text, shortcuts or buttons. The input stays
the real form control while the group composes any SVG/component you provide.

```blade
<hw:input-group>
    <hw:input name="q" type="search" placeholder="Search..." />

    <hw:input-group.addon align="inline-start">
        <x-lucide-search class="size-4" />
    </hw:input-group.addon>
</hw:input-group>
```

## Auto-derivation

Laravel validates with dot notation (`variables.0.name`); HTML uses brackets (`variables[0][name]`). The component does
the conversion for you:

```blade
<hw:input name="variables[0][name]" />
{{-- id="variables-0-name", errorKey="variables.0.name" --}}
```

Use `error-key` when the HTML name and the validation key diverge:

```blade
<hw:input name="payload[email]" error-key="user.email" />
```

## Mask presets

| Preset     | Mask                                       |
|------------|--------------------------------------------|
| `cpf`      | `###.###.###-##`                           |
| `cnpj`     | `##.###.###/####-##`                       |
| `cpf-cnpj` | `["###.###.###-##", "##.###.###/####-##"]` |
| `phone-br` | `["(##) ####-####", "(##) #####-####"]`    |
| `cep`      | `#####-###`                                |
| `date-br`  | `##/##/####`                               |
| `time`     | `##:##`                                    |

Unknown presets pass through as raw Maska strings.

## Auto-submit

Text-like inputs submit with debounce by default when `auto-submit` is set. Put the controller on an ancestor form:

```blade
<hw:form method="get" action="/items" auto-submit auto-submit-delay="300">
    <hw:input name="q" type="search" auto-submit auto-submit-delay="600" />
</hw:form>
```

Use `auto-submit="immediate"` to submit a text input without debounce. Checkable inputs use immediate `change` submit by
default.

When `clearable` and `auto-submit` are both enabled, clearing the field submits immediately so pending debounced typing is
cancelled and the empty result set is shown right away.

## Checkbox and radio

For new standalone checkboxes and switches, prefer `<hw:checkbox>` and `<hw:switch>`. The low-level checkable branch on
`<hw:input>` remains available for compatibility and custom composition.

For `type="checkbox"` and `type="radio"`, `value` is the HTML value attribute (what is posted when the input is marked),
and `checked` controls the initial state.

```blade
{{-- Single checkbox / switch --}}
<hw:input type="checkbox" name="notify" :checked="$user->notify" />

{{-- Checkbox group --}}
<hw:input type="checkbox" name="roles[]" value="admin"
    :checked="in_array('admin', $user->roles ?? [])" />

{{-- Radio --}}
<hw:input type="radio" name="plan" value="pro"
    :checked="$user->plan === 'pro'" />
```

When the page is re-rendered after a validation redirect (i.e., Laravel has flashed `_old_input`), the component derives
`checked` from the submitted state instead of `:checked` — so a checkbox the user just toggled survives the round-trip.
Specifically:

- **Single checkbox** (no explicit `value`): checked iff the key is present in old input.
- **Checkbox group** (`name="roles[]"`): checked iff the input's `value` appears in the flashed array.
- **Exclusive checkboxes** (several inputs sharing a `name`, each with a distinct `value`): checked iff the flashed
  scalar equals the input's `value` — same comparison as radio.
- **Radio**: checked iff the flashed value equals the input's `value`.

`old()`-driven derivation kicks in only while flash data exists; on a fresh load it falls back to `:checked`. Pass
`:old="false"` to opt out entirely.

The `clearable`, `mask`, and `auto-select` props are no-ops for checkable types.

### Automatic unique ids for groups

Multiple `<hw:input type="radio">` sharing a `name` — or `type="checkbox"` with `name="…[]"` — would otherwise
collide on `id`. The component avoids that by appending `-{slug(value)}` to the derived id:

```blade
<hw:input type="checkbox" name="size[]" value="default" />     {{-- id="size-default" --}}
<hw:input type="checkbox" name="size[]" value="comfortable" /> {{-- id="size-comfortable" --}}
<hw:input type="radio"    name="plan"   value="pro" />          {{-- id="plan-pro" --}}
```

The slug uses `Illuminate\Support\Str::slug`, so any string value is safe. Inside `<hw:field>`, `aria-describedby` still
points to the **base error id** (`plan-error`, `size-error`), so all inputs in the group bind to the same
`<hw:field.error>` node — which is what Laravel's per-name validation produces.

Passing an explicit `id` opts out of the auto-derivation: the component uses your id verbatim. Inside `<hw:field>`, the
error reference derives from that id.

## Inheriting from `<hw:field>`

`<hw:field>` propagates `name`, `id`, `errorKey`, and `required` through scoped Field context. It auto-renders
`<hw:field.label>`, `<hw:field.description>`, and `<hw:field.error>` when the corresponding props are set:

```blade
<hw:field name="email" label="E-mail" required>
    <hw:input type="email" auto-select />
</hw:field>
```

> **ARIA contract:** Field-owned inputs emit `aria-describedby="{id}-error"`, and Field auto-renders the matching
> `<hw:field.error>`. Standalone inputs do not invent an error reference; pass `aria-describedby` explicitly when you
> render your own help or error node. With `:error="false"`, render the referenced node manually if needed.

## Controller integrations

`hotwire:check` detects the enabled `auto-select`, `clear-input`, `input-mask`, and `auto-submit` integrations. These
controllers auto-load from the package; publish one only when you want to customize its source.

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="input-wrapper"` — stable structural root for text-like controls; mechanics are supplied by the foundation
- `data-slot="input"`
- `data-slot="clear-input-button"`
