# Select

Custom select menu with search filter, keyboard navigation and option groups.

**Identifier:** `select`  
**Install:** `php artisan hotwire:controllers select`

## Requirements

- No external dependencies.

## Targets

| Target          | Description                                          |
|-----------------|------------------------------------------------------|
| `trigger`       | Button that opens/closes the popover                 |
| `selectedLabel` | Element that displays the current selection text     |
| `popover`       | Dropdown container                                   |
| `listbox`       | Container for the option list                        |
| `input`         | Hidden input that stores the selected value          |
| `filter`        | Search input inside the popover header (optional)    |

## Values

| Value      | Type      | Default | Description                                           |
|------------|-----------|---------|-------------------------------------------------------|
| (via dataset) | —      | —       | `data-placeholder` — placeholder text for multi-select |
| `data-close-on-select` | Boolean | `false` | Closes popover after selecting in multi-select mode |

## Basic usage

```html
<div data-controller="select">
    <button type="button" data-select-target="trigger" aria-haspopup="listbox">
        <span data-select-target="selectedLabel">Apple</span>
    </button>

    <div data-select-target="popover" aria-hidden="true">
        <header>
            <input type="text" data-select-target="filter" placeholder="Search..." />
        </header>
        <div data-select-target="listbox">
            <div role="option" data-value="apple">Apple</div>
            <div role="option" data-value="banana">Banana</div>
            <div role="option" data-value="blueberry">Blueberry</div>
        </div>
    </div>

    <input type="hidden" name="value" value="apple" data-select-target="input" />
</div>
```

## Option groups

Wrap options in a `<div role="group">` with a `aria-labelledby` heading:

```html
<div role="group" aria-labelledby="group-label-fruits">
    <div role="heading" id="group-label-fruits">Fruits</div>
    <div role="option" data-value="apple">Apple</div>
    <div role="option" data-value="banana">Banana</div>
</div>
```

## Multi-select

Add `aria-multiselectable="true"` on the `listbox` target:

```html
<div data-select-target="listbox" aria-multiselectable="true">
    ...
</div>
```

## Custom data attributes per option

| Attribute       | Description                                 |
|-----------------|---------------------------------------------|
| `data-value`    | Value stored in the hidden input             |
| `data-filter`   | Custom filter text (defaults to textContent) |
| `data-keywords` | Extra search keywords (space/comma separated)|
| `data-force`    | Always visible in search (e.g. "Add new")   |
| `data-label`    | Custom label text for multi-select display  |
