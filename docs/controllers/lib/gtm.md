# GTM (Google Tag Manager)

Loads Google Tag Manager with lazy loading support (loads only after the first user interaction) and fires custom events via `data-action`.

**Identifier:** `lib--gtm`

## Requirements

- No external dependencies.
- GTM container ID (format `GTM-XXXXXXX`).

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `id` | `String` | — | GTM container ID (required, format `GTM-XXXXXXX`) |
| `lazy` | `Boolean` | `true` | Loads the script only after scroll, mousemove or touchstart |

## Actions

| Action | Description |
|--------|-------------|
| `lib--gtm#event` | Sends a custom event to the dataLayer |

## Action Params

| Param | Type | Description |
|-------|------|-------------|
| `event-name` | `String` | Event name in the dataLayer (required) |
| `event-payload` | `Object` | Additional data sent with the event |

## Basic usage — lazy loading (default)

```html
<body
    data-controller="lib--gtm"
    data-lib--gtm-id-value="GTM-XXXXXXX"
>
    ...
</body>
```

The GTM script loads only when the user first interacts (scroll, mousemove or touchstart).

## Immediate loading

```html
<body
    data-controller="lib--gtm"
    data-lib--gtm-id-value="GTM-XXXXXXX"
    data-lib--gtm-lazy-value="false"
>
    ...
</body>
```

## Sending custom events

```html
<button
    data-action="lib--gtm#event"
    data-lib--gtm-event-name-param="button_click"
    data-lib--gtm-event-payload-param='{"category": "cta", "label": "hero"}'
>
    Get started
</button>
```

Result in `dataLayer`:

```js
{ event: "button_click", category: "cta", label: "hero" }
```

## Simple event (no payload)

```html
<a
    href="/pricing"
    data-action="lib--gtm#event"
    data-lib--gtm-event-name-param="view_pricing"
>
    View plans
</a>
```

Result in `dataLayer`:

```js
{ event: "view_pricing" }
```

## Form tracking

```html
<form
    data-action="submit->lib--gtm#event"
    data-lib--gtm-event-name-param="form_submit"
    data-lib--gtm-event-payload-param='{"form": "contact"}'
>
    ...
    <button type="submit">Send</button>
</form>
```

## How it works

1. On `initialize()`, creates `window.dataLayer` if it doesn't exist.
2. On `connect()`, validates the ID format (`GTM-XXXXXXX`).
3. If `lazy` (default), registers listeners on `scroll`, `mousemove` and `touchstart`. On the first interaction, loads the script and removes the listeners.
4. If not lazy, loads the script immediately.
5. The script is loaded only once (`window.gtmDidInit` prevents duplication).
