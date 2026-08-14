# Auto Select

Automatically selects all content of an input when it receives focus.

**Identifier:** `auto-select`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers auto-select`.

## Requirements

- No external dependencies.

## Usage

```html
<input
    type="text"
    value="selectable text"
    data-controller="auto-select"
/>
```

When clicking or tabbing into the input, all text is automatically selected. Useful for URL fields, invite codes, or any value the user typically copies in full.

## Example with readonly field

```html
<input
    type="text"
    value="https://mysite.com/invite/abc123"
    data-controller="auto-select"
    readonly
/>
```
