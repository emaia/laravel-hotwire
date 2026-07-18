# Color Scheme

Persists a `light`, `dark` or `system` mode and synchronizes `html[data-theme]` with the resolved colour scheme.

**Identifier:** `color-scheme`  
**Install:** controllers auto-load after `php artisan hotwire:install`; publish only when customising with `php artisan hotwire:controllers color-scheme`.

## Requirements

- No external dependencies.

## Values

| Value | Type | Default | Description |
| --- | --- | --- | --- |
| `storageKey` | `String` | `hotwire.colorScheme` | Local storage key used to persist the mode. |
| `default` | `String` | `system` | Fallback mode when storage is empty or invalid. |
| `modes` | `String` | `light dark system` | Space-separated order used by `cycle`. |

## Actions

| Action | Description |
| --- | --- |
| `color-scheme#cycle` | Moves to the next configured mode. |
| `color-scheme#toggle` | Toggles between the resolved light and dark schemes. |
| `color-scheme#set` | Sets `event.params.mode`, e.g. `data-color-scheme-mode-param="dark"`. |
| `color-scheme#light` | Stores `light`. |
| `color-scheme#dark` | Stores `dark`. |
| `color-scheme#system` | Stores `system`. |

## Events

After a user-triggered mode change, the controller dispatches `color-scheme:change` on `window`:

```js
window.addEventListener('color-scheme:change', (event) => {
    console.log(event.detail.mode, event.detail.scheme)
})
```

The payload contains the persisted `mode` and resolved `scheme`:

```js
{ mode: 'system', scheme: 'dark' }
```

## Synchronization

The controller listens for:

- `storage` events so multiple browser tabs stay in sync.
- `prefers-color-scheme` changes while the active mode is `system`.
- `color-scheme:change` so multiple toggles on the same page update together.

Listeners are removed in `disconnect()` so Turbo visits and morphs do not leave duplicate handlers behind.

Most apps should use `<hw:color-scheme.script>` and `<hw:color-scheme.toggle>` instead of wiring this controller manually.
