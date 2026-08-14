# Color Scheme

Persists a `light`, `dark` or `system` mode and synchronizes `html[data-theme]` plus
`html[data-color-scheme-mode]` with the resolved colour scheme.

**Identifier:** `color-scheme`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers color-scheme`.

## Requirements

- No external dependencies.

## Values

| Value | Type | Default | Description |
| --- | --- | --- | --- |
| `storageKey` | `String` | `hotwire.colorScheme` | Local storage key used to persist the mode. |
| `default` | `String` | `system` | Fallback mode when storage is empty or invalid. |
| `modes` | `String` | `light dark system` | Space-separated order used by `cycle`. |
| `viewTransition` | `Boolean` | `false` | Animates visible scheme changes triggered by a user action. |

## Actions

| Action | Description |
| --- | --- |
| `color-scheme#cycle` | Moves to the next configured mode. |
| `color-scheme#toggle` | Toggles between the resolved light and dark schemes. |
| `color-scheme#set` | Sets `event.params.mode`, e.g. `data-color-scheme-mode-param="dark"`. |
| `color-scheme#light` | Stores `light`. |
| `color-scheme#dark` | Stores `dark`. |
| `color-scheme#system` | Stores `system`. |

When `viewTransition` is enabled, action methods use `document.startViewTransition` only when the resolved scheme
actually changes and the user does not prefer reduced motion. Initialisation, system preference updates, storage events
and synchronization between controller instances remain instant so they do not compete for the document's single View
Transition.

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

With `viewTransition` enabled, the event is dispatched from the transition update callback rather than synchronously
from the action.

## Synchronization

The controller listens for:

- `storage` events so multiple browser tabs stay in sync.
- `prefers-color-scheme` changes while the active mode is `system`.
- `color-scheme:change` so multiple toggles on the same page update together.

Listeners are removed in `disconnect()` so Turbo visits and morphs do not leave duplicate handlers behind.

Most apps should use `<hw:color-scheme.script>` and `<hw:color-scheme.toggle>` instead of wiring this controller manually.
