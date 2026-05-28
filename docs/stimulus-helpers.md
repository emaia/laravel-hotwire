# Stimulus attribute helpers

Three global helpers build Stimulus `data-*` attributes from Blade without hand-writing the verbose
markup. They return a fluent `Emaia\LaravelHotwire\Support\Stimulus` builder that is chainable,
`Htmlable` (so `{{ }}` renders it without double-escaping) and `Arrayable` (so it merges into a
component's attribute bag).

```php
stimulus_controller(string $name, array $values = [], array $classes = [], array $outlets = []): Stimulus
stimulus_action(string $controller, string $method, ?string $event = null, array $params = []): Stimulus
stimulus_target(string $controller, string $target): Stimulus
```

Each helper is just `Stimulus::make()->{method}(...)`, so the helper and the chained method share the
same signature.

## Controllers, values, classes, outlets

```blade
<div {{ stimulus_controller('chart',
        ['name' => 'Likes', 'maxItems' => 4],
        ['busy' => 'opacity-50'],
        ['legend' => '.legend']) }}>
```

```html

<div data-controller="chart"
     data-chart-name-value="Likes"
     data-chart-max-items-value="4"
     data-chart-busy-class="opacity-50"
     data-chart-legend-outlet=".legend"></div>
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
- Use named arguments to skip positional gaps: `stimulus_controller('hello', outlets: ['other' => '.target'])`.

## Actions and targets

```blade
<button {{ stimulus_action('clipboard', 'copy', 'click', ['format' => 'text'])
            ->action('analytics', 'track', 'click') }}>Copy</button>
```

```html

<button data-action="click->clipboard#copy click->analytics#track"
        data-clipboard-format-param="text">Copy
</button>
```

Omit the event for a default-event action (`stimulus_action('toggle', 'switch')` →
`data-action="toggle#switch"`). `stimulus_target` accepts multiple space-separated names and merges
repeated calls for the same controller into one attribute.

## Stacking everything on one element

```blade
<textarea {{ stimulus_controller('char-counter', ['max' => 280])
    ->controller('auto-resize', ['maxRows' => 12])
    ->target('char-counter', 'field')
    ->action('char-counter', 'count', 'input')
    ->action('auto-resize', 'grow', 'input') }}></textarea>
```

```html
<textarea data-controller="char-counter auto-resize"
          data-char-counter-max-value="280"
          data-auto-resize-max-rows-value="12"
          data-char-counter-target="field"
          data-action="input->char-counter#count input->auto-resize#grow"></textarea>
```

Repeated `->controller()` tokens are deduplicated; `->action()` segments accumulate in order.

## Merging into a component attribute bag

`toArray()` returns the raw (unescaped) attribute map for `$attributes->merge()`, which applies the
Stimulus attributes as **defaults** on the element and escapes them on render:

```blade
<input {{ $attributes->merge(
    stimulus_controller('input-mask', ['mask' => $mask])
        ->action('input-mask', 'format', 'input')
        ->toArray()
) }}>
```

> **`merge()` does not union `data-controller`/`data-action`.** Blade's `merge()` only concatenates
> `class` and `style`; for every other attribute the value already on the element wins. So if the
> caller also passes a `data-controller`, *theirs* wins and the builder's is dropped. To combine a
> caller-supplied controller with your own, build the union in PHP and pass it into the builder — the
> same way this package's own components do it (`trim($userController.' input-mask')`), rather than
> relying on `merge()`.

## Escaping

`toHtml()`/`__toString()` escape `&`, `<` and `"` — the characters that can break a double-quoted
attribute. `>` is intentionally left intact so action arrows (`click->c#m`) and child-combinator
outlet selectors (`.a > .b`) survive. `toArray()` returns values unescaped because the attribute bag
escapes them for you.
