# Stimulus attribute helpers

The primary `stimulus()` entry point returns a fluent `Emaia\LaravelHotwire\Support\Stimulus` builder
that is chainable, `Htmlable` (so `{{ }}` renders it without double-escaping) and `Arrayable` (so it
merges into a component's attribute bag). Three convenience aliases are also available:

```php
stimulus(): Stimulus
stimulus_controller(string $name, array $values = [], array $classes = [], array $outlets = []): Stimulus
stimulus_action(string $controller, string $method, ?string $event = null, array $params = []): Stimulus
stimulus_target(string $controller, string $target): Stimulus
```

`stimulus_controller(...)` is an alias for `stimulus()->controller(...)`. `stimulus_action()` and
`stimulus_target()` are shortcuts for `stimulus()->action(...)` and `stimulus()->target(...)`. All
helper signatures match their corresponding builder methods.

## Controllers, values, classes, outlets

```blade
<div
    {{
        stimulus()
            ->controller(
                'chart',
                ['name' => 'Likes', 'maxItems' => 4],
                ['busy' => 'opacity-50'],
                ['legend' => '.legend']
            )
    }}
></div>
```

```html

<div
    data-controller="chart"
    data-chart-name-value="Likes"
    data-chart-max-items-value="4"
    data-chart-busy-class="opacity-50"
    data-chart-legend-outlet=".legend"
></div>
```

- Value/class/outlet/param keys are kebab-cased (`maxItems` → `max-items`).
- Scalars render verbatim; booleans, arrays and objects are JSON-encoded (`true` → `"true"`,
  `[1,2,3]` → `"[1,2,3]"`) — exactly what Stimulus `Boolean`/`Array`/`Object` values expect.
- `null` values, classes, outlets and params are omitted entirely (no empty attribute); `''` and
  `0` are kept. Non-encodable values (e.g. a circular reference) throw a `JsonException` rather than
  silently rendering an empty attribute.
- Controller identifiers are kept verbatim, so substrate controllers work too
  (`turbo--progress` → `data-turbo--progress-*`).
- Repeated controller tokens, identical action descriptors, and repeated target names are
  deduplicated.
- Controller names and keys are emitted verbatim into attribute **names** (keys only get
  kebab-cased), so they must be valid Stimulus identifiers — they are not sanitized. Dynamic or
  user-supplied data belongs in **values/params**, which are the escaped path.
- Use named arguments to skip positional gaps: `stimulus()->controller('hello', outlets: ['other' => '.target'])`.

To register several controllers that need no config, use `controllers()` instead of chaining
`controller()`:

```blade
<div
    {{
        stimulus()
            ->controllers('tabs', 'tab-url')
            ->action('tab-url', 'update', 'tabs:change')
    }}
></div>
```

```html

<div data-controller="tabs tab-url" data-action="tabs:change->tab-url#update"></div>
```

`controllers(string ...$names)` is variadic — spread an array with `->controllers(...$names)`. It
composes with `controller()` (use the singular when a controller needs values/classes/outlets), and
both deduplicate.

## Actions and targets

```blade
<button
    {{
        stimulus()
            ->action('clipboard', 'copy', 'click', ['format' => 'text'])
            ->action('analytics', 'track', 'click')
    }}
>
    Copy
</button>
```

```html

<button data-action="click->clipboard#copy click->analytics#track" data-clipboard-format-param="text">Copy</button>
```

Omit the event for a default-event action (`stimulus_action('toggle', 'switch')` →
`data-action="toggle#switch"`). `stimulus_target` accepts multiple space-separated names and merges
repeated calls for the same controller into one attribute.

## Stacking everything on one element

```blade
<textarea
    {{
        stimulus()
            ->controller('char-counter', ['max' => 280])
            ->controller('auto-resize', ['maxRows' => 12])
            ->target('char-counter', 'field')
            ->action('char-counter', 'count', 'input')
            ->action('auto-resize', 'grow', 'input')
    }}
></textarea>
```

```html
<textarea
    data-controller="char-counter auto-resize"
    data-char-counter-max-value="280"
    data-auto-resize-max-rows-value="12"
    data-char-counter-target="field"
    data-action="input->char-counter#count input->auto-resize#grow"
></textarea>
```

Repeated `->controller()` tokens are deduplicated; `->action()` segments accumulate in order.

## Merging into component attributes

`toArray()` returns the raw (unescaped) attribute map. For a plain Blade attribute bag, Laravel's
`$attributes->merge()` applies those attributes as **defaults** on the element and escapes them on
render:

```blade
<input
    {{
        $attributes->merge(
            stimulus()
                ->controller('input-mask', ['mask' => $mask])
                ->action('input-mask', 'format', 'input')
                ->toArray()
        )
    }}
/>
```

Blade's native `merge()` does not union `data-controller`/`data-action`; it only concatenates `class`
and `style`. Components shipped by this package that expose a `stimulus` prop use the internal
`StimulusAttributes` merger instead. That merger deduplicates `data-controller`, `data-action`, and
`data-*-target` tokens across internal attributes, regular HTML attributes, and `:stimulus`.
Internal controller/action/target tokens render first, followed by plain HTML attributes and then
`:stimulus`.

For components with package-owned Stimulus wiring, component-owned `data-{identifier}-*`
configuration stays protected. Use the component's explicit props for those values, and use regular
`data-controller` / `data-action` or `:stimulus` for additional controllers:

```blade
<hw:tabs
    id="settings"
    :active="request()->query('tab')"
    :stimulus="stimulus()->controller('tab-url')->action('tab-url', 'update', 'tabs:change')"
>
    ...
</hw:tabs>
```

The `stimulus` prop is available on controller-backed components and primitives: `alert-dialog`,
`button`, `carousel`, `chart`, `checkbox-group`, `conditional-field`, `dropdown`, `file`,
`file-upload`, `flash-container`, `flash-message`, `form`, `input`, `map`, `modal`, `rich-text`,
`scroll-progress`, `tabs`, `textarea`, and `timeago`.

## Escaping

`toHtml()`/`__toString()` escape `&`, `<` and `"` — the characters that can break a double-quoted
attribute. `>` is intentionally left intact so action arrows (`click->c#m`) and child-combinator
outlet selectors (`.a > .b`) survive. `toArray()` returns values unescaped because the attribute bag
escapes them for you.
