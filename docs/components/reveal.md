# Reveal

Render a staggered entrance cascade for direct children or explicitly selected nested items. CSS owns the first-paint
animation; the controller progressively adds per-item scroll triggering, Turbo integration, and synchronization events.

## Basic usage

Direct children are reveal items by default, with no per-item markup:

```blade
<hw:reveal>
    @foreach ($tasks as $task)
        <x-task-card :task="$task" />
    @endforeach
</hw:reveal>
```

The root and items are polymorphic:

```blade
<hw:reveal as="ul">
    <li>First</li>
    <li>Second</li>
</hw:reveal>
```

## Explicit nested items

Use `<hw:reveal.item>` when the animated units are not direct children. The parent supplies sequential indexes across
the nested markup:

```blade
<hw:reveal as="section" delay="110ms" motion="flat">
    <hw:form>
        <hw:reveal.item>
            <hw:field name="title" label="Title" />
        </hw:reveal.item>

        <div>
            <hw:reveal.item as="section">
                <hw:field name="description" label="Description" />
            </hw:reveal.item>
        </div>
    </hw:form>
</hw:reveal>
```

The first explicit item switches the root out of direct-child mode. Raw `data-reveal-item` markup is also supported as
an escape hatch. The controller assigns missing indexes in document order, but that happens only after it connects. Set
`--reveal-index` server-side when the visual order differs or when the stagger must be correct on the first paint rather
than progressively corrected after controller loading.

When a component must own the Reveal root without an extra wrapper, prefer that component's explicit integration when
available. [`<hw:sidebar reveal>`](sidebar.md#reveal-integration), for example, mounts Reveal directly on its existing
surface and accepts explicit `data-reveal-item` descendants.

## Scroll trigger

Use `trigger="scroll"` for long pages and lists below the fold:

```blade
<hw:reveal trigger="scroll" :threshold="0.2" root-margin="0px 0px -10% 0px">
    ...
</hw:reveal>
```

The controller observes items individually, leaves anything already visible untouched, and restarts the stagger index
for each batch entering the viewport. For an item taller than the observer root, the threshold is capped at the maximum
ratio that can fit in the viewport. At the end of the document it releases items left inside a negative root-margin
dead zone. `once` defaults to `true`; set `:once="false"` to reveal an item again after it leaves and re-enters.

This is presentation, not lazy loading: all content remains in the document and accessibility tree.

## Motion and timing

Nova provides three motions:

| Motion | Effect |
| ------ | ------ |
| `rise` | Fade, blur, and vertical movement. |
| `flat` | Fade and vertical movement without `filter`. |
| `fade` | Opacity only. |

Use `motion="flat"` to omit `filter`, including when the blur itself causes a rendering conflict. Use `motion="fade"`
when an item contains a fixed-position dropdown, popover, tooltip, or overlay: both `filter` and `transform` create a
containing block while present, and only `fade` omits both. Reveal uses `animation-fill-mode: backwards`, so those
properties are released after every animation instead of changing fixed positioning permanently.

Timing props become CSS custom properties:

```blade
<hw:reveal stagger="45ms" duration="420ms" delay="90ms" :max-steps="8">
    ...
</hw:reveal>
```

`max-steps` caps the delay for long lists. Override `--reveal-blur`, `--reveal-shift`, or `--reveal-easing` on the root
for smaller adjustments. To supply an application keyframe while preserving the stagger formula and `backwards` fill,
set `--reveal-animation`:

```blade
<hw:reveal style="--reveal-animation: product-enter">
    ...
</hw:reveal>
```

## Render and document scope

`scope="render"` repeats the entrance when Turbo renders new page content. Use `scope="document"` for persistent chrome
such as a sidebar; after the first Turbo visit, subsequent renders do not replay that cascade.

```blade
<hw:reveal scope="document">
    ...
</hw:reveal>
```

Turbo previews can otherwise paint stale visible content before replaying its entrance. For page-level Reveal without a
View Transition, pair it with `<hw:meta cache="no-preview" />`. The umbrella `<hw:meta view-transition />` implies
`cache="no-preview"` automatically unless an explicit `cache` value, including `false`, is supplied.

Turbo Stream `replace`, `update`, and `morph` content that belongs to a Reveal is marked before insertion so updating an
already visible item does not make it disappear and enter again. Transient armed and completed state is removed before
Turbo caches the page.

## Integration

- A descendant [`animated-number`](../controllers/animated-number.md) restarts when its Reveal batch is shown, unless
  its own lazy observer is still waiting for the counter to enter the viewport.
- [`<hw:progress>`](progress.md) indicators animate from zero in sync with their owning item.
- Listen for `reveal:shown` to coordinate application behavior or a [`chart`](../controllers/chart.md):

```blade
<hw:reveal data-action="reveal:shown->analytics#track">
    ...
</hw:reveal>
```

The event fires for the initial visible batch and once per animation frame for scroll batches. It does not fire on
connect when every item is still offscreen.

## Props

| Prop          | Type                 | Default                    | Description |
| ------------- | -------------------- | -------------------------- | ----------- |
| `trigger`     | `string`             | `'load'`                   | `load` or per-item `scroll`. |
| `scope`       | `string`             | `'render'`                 | `render` or persistent `document` scope. |
| `motion`      | `string`             | `'rise'`                   | `rise`, `flat`, or `fade`. |
| `stagger`     | `?string`            | preset                     | Delay between item indexes. |
| `duration`    | `?string`            | preset                     | Entrance duration. |
| `delay`       | `?string`            | preset                     | Initial delay before the first item. |
| `max-steps`   | `int\|string\|null` | preset                     | Maximum stagger index used by the delay formula. |
| `threshold`   | `number\|string`     | `0.15`                     | IntersectionObserver threshold for `scroll`. |
| `root-margin` | `string`             | `'0px 0px -10% 0px'`      | IntersectionObserver root margin for `scroll`. |
| `once`        | `bool`               | `true`                     | Reveal each scroll item only once. |
| `as`          | `string`             | `'div'`                    | Root HTML element. |
| `stimulus`    | `Htmlable\|null`     | `null`                     | Additional Stimulus bindings merged on the root. |

Root tags support `div`, `section`, `main`, `header`, `footer`, `aside`, `nav`, `ul`, and `ol`. Item tags support `div`,
`article`, `section`, `header`, `footer`, `aside`, and `li`. Regular HTML attributes pass through. Reveal-owned
`data-reveal-*`, `data-slot`, and `data-motion` attributes are protected; use props to configure them.

## Progressive enhancement

Structural CSS contains the visibility, delay formula, armed state, Turbo safeguards, and reduced-motion fallback. Nova
only supplies motion keyframes and visual defaults. This split means the initial cascade starts before the lazy
controller chunk connects. If JavaScript is disabled or fails, content still completes its CSS animation and remains
visible; `trigger="scroll"` simply degrades to the load cascade.

With `prefers-reduced-motion: reduce`, animations are disabled, armed opacity is released, and the controller does not
observe or restart animated numbers.

Avoid making a Reveal item the same element whose visibility another state controls with `opacity`. CSS animations win
over normal opacity declarations while running, so a hidden item can flash through the Reveal keyframe and disappear
when `backwards` fill releases it. Animate a stable wrapper instead, or suppress Reveal in the conflicting state. Nova
does this for desktop group labels in an icon-collapsed Sidebar while preserving their mobile and expanded entrances.

## Turning the cascade off

Set `data-reveal="off"` on `<html>` to neutralise every Reveal in the document without editing the markup that emits
them:

```blade
<html data-reveal="{{ config('app.reveal', 'on') }}">
```

This silences the item and progress animations and releases armed opacity, so content that would be waiting on a scroll
is simply visible. It is the same treatment `prefers-reduced-motion: reduce` applies, under an author's switch rather
than the visitor's preference — which stays honoured either way.

Individual instances do not need the switch: drop the `<hw:reveal>` wrapper, or the `reveal` prop on components that
expose one.

## Styling hooks

- `data-slot="reveal"`
- `data-slot="reveal-item"`
- `data-reveal-item`
- `data-reveal-children` on roots using automatic direct children
- `data-motion="rise|flat|fade"`
- `data-reveal-scope="render|document"`
- `data-reveal-armed` while a scroll item waits outside the viewport
- `data-reveal-state="done"` after every finite animation settles
- `data-reveal-skip` on incoming replacement content

## Controller

This component depends on the [`reveal`](../controllers/reveal.md) controller.
