# Sheet Controller

Controls `<hw:sheet>` open and close behavior.

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
