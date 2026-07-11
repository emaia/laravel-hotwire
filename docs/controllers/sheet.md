# Sheet Controller

Controls `<hw:sheet>` open and close behavior.

`sheet` extends the drawer controller, so it supports the same dynamic frame targets and stream-close behavior with the `data-sheet-*` target names. Empty `update`/`replace` streams and `refresh` streams wait for the close animation before rendering.

## Actions

| Action | Description |
|--------|-------------|
| `sheet#open` | Open the sheet. |
| `sheet#close` | Close the sheet. |
| `sheet#toggle` | Toggle the sheet. |
| `sheet#clickOutside` | Close when the backdrop is clicked. |
| `sheet#closeForCache` | Close immediately before Turbo caches the page. |

## Events

| Event | Description |
|-------|-------------|
| `sheet:opened` | Dispatched after the open transition. |
| `sheet:closed` | Dispatched after the close transition. |
