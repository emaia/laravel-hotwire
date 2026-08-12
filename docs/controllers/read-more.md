# Read More

Measure a viewport around an intrinsic content body, expose whether it is `static`, `collapsed`, or
`expanded`, and keep its optional trigger, fade, labels, and icon synchronized.

Use [`<hw:read-more>`](../components/read-more.md) for accessible markup, first-paint structural CSS,
and Nova preset styling.

**Identifier:** `read-more`

**Load:** Automatically after `php artisan hotwire:install`; publish with
`php artisan hotwire:controllers read-more` only to customize it.

## Requirements

- No external dependencies.
- `ResizeObserver` enables automatic remeasurement. The initial measurement and `refresh` action
  still work when it is unavailable.

## Targets

| Target      | Required | Description                                                     |
| ----------- | -------- | --------------------------------------------------------------- |
| `viewport`  | Yes      | Wrapper measured and constrained during collapse and motion.    |
| `content`   | Yes      | Unclamped inner body observed for intrinsic size changes.       |
| `trigger`   | No       | Toggle button receiving visibility and `aria-expanded` updates. |
| `fade`      | No       | Decorative overflow cue hidden when content is static.          |
| `moreLabel` | No       | Label visible in the collapsed state.                           |
| `lessLabel` | No       | Label visible in the expanded state.                            |
| `icon`      | No       | Receives `data-state="collapsed\|expanded"`.                    |

Missing required targets produce a safe `static` fallback rather than throwing.

## Values

| Value             | Type    | Default | Description                                             |
| ----------------- | ------- | ------- | ------------------------------------------------------- |
| `collapsedHeight` | Number  | `320`   | Preview height in pixels.                               |
| `expanded`        | Boolean | `false` | Requested state, written back when a transition starts. |

## Actions

| Action     | Description                                                        |
| ---------- | ------------------------------------------------------------------ |
| `toggle`   | Toggle between collapsed and expanded when content overflows.      |
| `expand`   | Expand overflowing content. Idempotent when already expanded.      |
| `collapse` | Collapse overflowing content. Idempotent when already collapsed.   |
| `refresh`  | Remeasure immediately; useful after application-managed rendering. |

## Events

| Event              | Detail                  | Description                                                    |
| ------------------ | ----------------------- | -------------------------------------------------------------- |
| `read-more:change` | `{ expanded: boolean }` | Fires after an explicit expand or collapse, not initial setup. |

## Markup contract

The viewport and intrinsic content body are separate so the observer can detect content growth even
while the outer viewport is clamped. The viewport's own `scrollHeight` supplies the expanded height,
so content borders and margins are included in the box being constrained:

```html
<section
    data-slot="read-more"
    data-controller="read-more"
    data-state="collapsed"
    data-read-more-collapsed-height-value="200"
    data-read-more-expanded-value="false"
    style="--read-more-collapsed-height: 200px"
>
    <div data-slot="read-more-viewport" data-read-more-target="viewport">
        <div id="article-content" data-slot="read-more-content" data-read-more-target="content" tabindex="-1">
            Long content
        </div>
        <div data-slot="read-more-fade" data-read-more-target="fade" aria-hidden="true" hidden></div>
    </div>

    <button
        type="button"
        data-read-more-target="trigger"
        data-action="read-more#toggle"
        aria-controls="article-content"
        aria-expanded="false"
        hidden
    >
        <span data-read-more-target="moreLabel">Read more</span>
        <span data-read-more-target="lessLabel" hidden>Read less</span>
    </button>
</section>
```

The package structural stylesheet reads `--read-more-collapsed-height` before controller connection.
For correct first-paint clamping, standalone markup should set that custom property to match
`collapsedHeight`. If omitted, the controller initializes it once in `connect()` and rewrites it only
when the Stimulus value itself changes, rather than on every measurement. Mark the content
`tabindex="-1"` when focus should move there if a focused trigger is hidden after remeasurement.

## Measurement and lifecycle

- `connect()` measures once, sets `data-ready`, and starts observing the intrinsic content body.
- Width changes, webfont changes, images, and dynamic content trigger remeasurement through
  `ResizeObserver`.
- Observer notifications are coalesced into one animation frame, keeping geometry writes outside the
  observer delivery cycle and avoiding resize-loop errors.
- Replacing either required target during a Turbo morph remeasures immediately. Content replacement
  also disconnects the old observation and observes the new target when `ResizeObserver` is
  available.
- `disconnect()` releases the observer and cancels pending frames so Turbo morphs and revisits cannot
  accumulate callbacks.
- A one-pixel tolerance prevents fractional layout rounding from producing a false overflow state.

Expansion temporarily sets `data-transitioning` while structural CSS animates to the measured
height. Once motion settles, the controller removes that attribute; CSS releases `max-block-size`
and restores `overflow: visible`, so dropdowns and other positioned descendants are not clipped.
Only the viewport's `max-block-size` transition is awaited; unrelated animations cannot hold the
component in its clipped transition state. Browsers may expose that logical property as the physical
`max-height`, so both names are recognized. A 750ms safety timeout releases the transition state if
the matching animation is stalled. An interrupted expansion temporarily sets `data-pinning` and
captures the current rendered height before reversing direction.

The requested `expanded` value is preserved while content is short. If later content growth causes
overflow, the previous requested state becomes effective again.
