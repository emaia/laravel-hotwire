# Tabs

Accessible tabs following the [WAI-ARIA APG tabs pattern](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/): roving
`tabindex`, arrow/Home/End keyboard navigation and automatic activation (focusing a tab shows its panel). Selection
state is read from the DOM on connect, so server-rendered selection and Turbo morphs are preserved.

**Identifier:** `tabs`  
**Install:** `php artisan hotwire:controllers tabs`

## Requirements

- No external dependencies.

## Targets

| Target  | Description                                                              |
|---------|-------------------------------------------------------------------------|
| `tab`   | Each tab button (`role="tab"`, with `aria-controls` pointing to a panel) |
| `panel` | Each tab panel (`role="tabpanel"`, matched by `id`/`aria-controls`)      |

## Stimulus Values

| Value            | Type     | Default | Description                                                         |
|------------------|----------|---------|---------------------------------------------------------------------|
| `selected-index` | `Number` | `0`     | Index of the active tab. Read on connect and kept in sync on change |

## Markup contract

- Each `tab` must declare `aria-controls` pointing to the `id` of its panel — that's how panels are resolved.
- Bind the keyboard/click actions on the tablist (event delegation):
  `data-action="click->tabs#select keydown->tabs#navigate"`.
- The controller manages each tab's `aria-selected`/`tabindex` and each panel's `hidden` attribute for you.
- **Panel `tabindex` is yours to set, not the controller's.** Per the APG, add `tabindex="0"` to a panel only when it
  has no focusable content of its own (so it stays reachable by keyboard); omit it when the panel already contains
  focusable elements. The controller never touches panel `tabindex`.

## Basic usage

```html

<div data-controller="tabs">
    <div role="tablist" aria-label="Settings"
         data-action="click->tabs#select keydown->tabs#navigate">
        <button role="tab" id="tab-general" aria-controls="panel-general" data-tabs-target="tab">
            General
        </button>
        <button role="tab" id="tab-advanced" aria-controls="panel-advanced" data-tabs-target="tab">
            Advanced
        </button>
    </div>

    <div role="tabpanel" id="panel-general" aria-labelledby="tab-general"
         data-tabs-target="panel" tabindex="0">
        General settings…
    </div>
    <div role="tabpanel" id="panel-advanced" aria-labelledby="tab-advanced"
         data-tabs-target="panel" tabindex="0" hidden>
        Advanced settings…
    </div>
</div>
```

## Selecting a tab on the server

Render the desired tab with `aria-selected="true"` and its panel without `hidden`; the controller honors it on connect:

```html

<button role="tab" id="tab-advanced" aria-controls="panel-advanced"
        data-tabs-target="tab" aria-selected="true">Advanced
</button>
```

Alternatively set `data-tabs-selected-index-value="1"` on the controller element.

## Vertical orientation

Add `aria-orientation="vertical"` to the tablist to navigate with `ArrowUp`/`ArrowDown` instead of left/right:

```html

<div role="tablist" aria-orientation="vertical"
     data-action="click->tabs#select keydown->tabs#navigate">
    …
</div>
```

## Reacting to changes

The controller dispatches a `tabs:change` event on its element whenever the active tab changes, with
`{ index, tab, panel }` in `event.detail`. It fires only on an actual change (click or keyboard) — **not** on the
initial render or on Turbo morph reconnects, so listeners like analytics aren't triggered on page load:

```html

<div data-controller="tabs" data-action="tabs:change->analytics#track">
    …
</div>
```

## Keyboard support

| Key                         | Action                              |
|-----------------------------|-------------------------------------|
| `ArrowRight` / `ArrowDown`* | Move to the next tab (wraps around) |
| `ArrowLeft` / `ArrowUp`*    | Move to the previous tab (wraps)    |
| `Home`                      | Move to the first tab               |
| `End`                       | Move to the last tab                |

\* Up/Down keys apply when the tablist has `aria-orientation="vertical"`.
