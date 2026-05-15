# combobox

Custom combobox/select menu with search filter, keyboard navigation and option groups.

**Identifier:** `combobox`
**Install:** `php artisan hotwire:controllers combobox`

## Requirements

- No external dependencies.

## Targets

| Target          | Description                                            |
|-----------------|--------------------------------------------------------|
| `trigger`       | Button that opens/closes the popover                   |
| `selectedLabel` | Element that displays the current selection text       |
| `popover`       | Dropdown container                                     |
| `listbox`       | Container for the option list                          |
| `input`         | Hidden input that stores the selected value            |
| `filter`        | Search input inside the popover header (optional)      |

## Classes

The controller exposes two configurable classes via Stimulus' `static classes` API:

| Class          | Default                  | Applied to                                                     |
|----------------|--------------------------|----------------------------------------------------------------|
| `active`       | (unset, opt-in)          | The option currently highlighted by keyboard or hover          |
| `placeholder`  | (unset, opt-in)          | The trigger label when no value is selected (multi-mode)       |

Configure them on the root element:

```html
<div
    data-controller="combobox"
    data-combobox-active-class="bg-accent ring-2"
    data-combobox-placeholder-class="text-muted-foreground"
>
    ...
</div>
```

Both accept space-separated lists. When unset the controller is a no-op for class manipulation — the `aria-selected` / `aria-activedescendant` attributes alone drive the visuals.

## Dataset hints

| Attribute              | Description                                                |
|------------------------|------------------------------------------------------------|
| `data-placeholder`     | Placeholder text shown when nothing is selected (multi)    |
| `data-close-on-select` | `"true"` closes the popover after a multi-select action    |

## Basic usage

```html
<div
    data-controller="combobox"
    data-combobox-active-class="active"
    data-combobox-placeholder-class="text-muted-foreground"
>
    <button type="button" data-combobox-target="trigger" aria-haspopup="listbox">
        <span data-combobox-target="selectedLabel">Apple</span>
    </button>

    <div data-combobox-target="popover" aria-hidden="true">
        <header>
            <input type="text" data-combobox-target="filter" placeholder="Search..." />
        </header>
        <div data-combobox-target="listbox">
            <div role="option" data-value="apple">Apple</div>
            <div role="option" data-value="banana">Banana</div>
            <div role="option" data-value="blueberry">Blueberry</div>
        </div>
    </div>

    <input type="hidden" name="value" value="apple" data-combobox-target="input" />
</div>
```

## Option groups

Wrap options in a `<div role="group">` with an `aria-labelledby` heading:

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
<div data-combobox-target="listbox" aria-multiselectable="true">
    ...
</div>
```

In multi-mode the hidden input stores a JSON-encoded array of values.

## Custom data attributes per option

| Attribute       | Description                                  |
|-----------------|----------------------------------------------|
| `data-value`    | Value stored in the hidden input             |
| `data-filter`   | Custom filter text (defaults to textContent) |
| `data-keywords` | Extra search keywords (space/comma separated)|
| `data-force`    | Always visible in search (e.g. "Add new")    |
| `data-label`    | Custom label text for multi-select display   |
