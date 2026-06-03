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
|---------|--------------------------------------------------------------------------|
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
    <div role="tablist" aria-label="Settings" data-action="click->tabs#select keydown->tabs#navigate">
        <button role="tab" id="tab-general" aria-controls="panel-general" data-tabs-target="tab">General</button>
        <button role="tab" id="tab-advanced" aria-controls="panel-advanced" data-tabs-target="tab">Advanced</button>
    </div>

    <!-- Panel WITH focusable content: no tabindex — the link is already reachable -->
    <div role="tabpanel" id="panel-general" aria-labelledby="tab-general" data-tabs-target="panel">
        <a href="/docs">Read the docs</a>
    </div>

    <!-- Text-only panel: needs tabindex="0" to stay reachable by keyboard -->
    <div
        role="tabpanel"
        id="panel-advanced"
        aria-labelledby="tab-advanced"
        data-tabs-target="panel"
        tabindex="0"
        hidden
    >
        Advanced settings…
    </div>
</div>
```

## Vertical orientation

Add `aria-orientation="vertical"` to the tablist to navigate with `ArrowUp`/`ArrowDown` instead of left/right:

```html

<div role="tablist" aria-orientation="vertical" data-action="click->tabs#select keydown->tabs#navigate">…</div>
```

## Reacting to changes

The controller dispatches a `tabs:change` event on its element whenever the active tab changes, with
`{ index, tab, panel }` in `event.detail`. It fires only on an actual change (click or keyboard) — **not** on the
initial render or on Turbo morph reconnects, so listeners like analytics aren't triggered on page load:

```html

<div data-controller="tabs" data-action="tabs:change->analytics#track">…</div>
```

## Keyboard support

| Key                          | Action                              |
|------------------------------|-------------------------------------|
| `ArrowRight` / `ArrowDown`\* | Move to the next tab (wraps around) |
| `ArrowLeft` / `ArrowUp`\*    | Move to the previous tab (wraps)    |
| `Home`                       | Move to the first tab               |
| `End`                        | Move to the last tab                |

\* Up/Down keys apply when the tablist has `aria-orientation="vertical"`.

## Selecting a tab on the server

Render the desired tab with `aria-selected="true"` and its panel without `hidden`; the controller honors it on connect:

```html

<button role="tab" id="tab-advanced" aria-controls="panel-advanced" aria-selected="true" data-tabs-target="tab">
    Advanced
</button>
```

Alternatively set `data-tabs-selected-index-value="1"` on the controller element — the value is the **zero-based
index** of the tab to activate on connect (`0` = first tab, `1` = second, …). If any tab already has
`aria-selected="true"`, that wins, and the value is ignored; an out-of-range index falls back to `0`.

### Picking the initial tab from a query string

For a URL like `/settings?tab=1`:

```blade
<div data-controller="tabs" data-tabs-selected-index-value="{{ (int) request('tab', 0) }}"></div>
```

Or map a friendly name (`?tab=billing`) to an index:

```blade
@php
    $names = ['general', 'advanced', 'billing'];
    $active = array_search(request('tab'), $names);
    $active = $active === false ? 0 : $active;
@endphp

<div data-controller="tabs" data-tabs-selected-index-value="{{ $active }}"></div>
```

### Syncing the active tab back to the URL

The companion of the section above: write the active tab to the query string whenever it changes, so a reload or a
shared link reopens the same tab. A small user controller listens for `tabs:change` and rewrites the URL — `replaceState`
adds no new history entry (no spurious back-button steps) and keeps Turbo's restoration state intact:

```js
// resources/js/controllers/tab_url_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    update(event) {
        const { tab, index } = event.detail;
        const name = tab.dataset.tabName ?? index; // a name is more robust than a positional index

        const url = new URL(window.location);
        url.searchParams.set("tab", name);

        // replaceState adds no history entry and preserves Turbo's restoration state.
        history.replaceState(history.state, "", url);
    }
}
```

Mount it alongside `tabs` and wire the `tabs:change` event to it, tagging each tab with the `data-tab-name` the server reads:

```blade
<div
    data-controller="tabs tab-url"
    data-action="tabs:change->tab-url#update"
    data-tabs-selected-index-value="{{ $active }}"
>
    <div role="tablist" aria-label="Settings" data-action="click->tabs#select keydown->tabs#navigate">
        <button role="tab" id="tab-general" aria-controls="panel-general" data-tabs-target="tab" data-tab-name="general">General</button>
        <button role="tab" id="tab-advanced" aria-controls="panel-advanced" data-tabs-target="tab" data-tab-name="advanced">Advanced</button>
        <button role="tab" id="tab-billing" aria-controls="panel-billing" data-tabs-target="tab" data-tab-name="billing">Billing</button>
    </div>

    {{-- panels --}}
</div>
```

Use the same `data-tab-name` values the server maps when [picking the initial tab](#picking-the-initial-tab-from-a-query-string), so the round-trip (load → switch → reload) lands on the same tab. `tab-url` is a controller you add to your app. Since `tabs:change` fires only on an actual change — never on the initial render or a Turbo morph — the URL is only rewritten when the user switches, not on page load.

### Opening the tab that has validation errors

When a form spans several tabs, reopen the one holding the failed fields so the user actually sees the error instead of
landing back on the first tab:

```blade
@php
    // tab 0 = Profile, tab 1 = Security
    $activeTab = $errors->hasAny(['password', 'password_confirmation', 'current_password']) ? 1 : 0;
@endphp

<div data-controller="tabs" data-tabs-selected-index-value="{{ $activeTab }}">
    <div role="tablist" aria-label="Account" data-action="click->tabs#select keydown->tabs#navigate">
        <button
            role="tab"
            id="tab-profile"
            aria-controls="panel-profile"
            data-tabs-target="tab"
            @if ($activeTab === 0) aria-selected="true" @endif
        >
            Profile
        </button>
        <button
            role="tab"
            id="tab-security"
            aria-controls="panel-security"
            data-tabs-target="tab"
            @if ($activeTab === 1) aria-selected="true" @endif
        >
            Security
        </button>
    </div>

    <div
        role="tabpanel"
        id="panel-profile"
        aria-labelledby="tab-profile"
        data-tabs-target="panel"
        @if ($activeTab !== 0) hidden @endif
    >
        {{-- profile fields --}}
    </div>
    <div
        role="tabpanel"
        id="panel-security"
        aria-labelledby="tab-security"
        data-tabs-target="panel"
        @if ($activeTab !== 1) hidden @endif
    >
        {{-- security fields --}}
    </div>
</div>
```

> **Progressive enhancement:** `data-tabs-selected-index-value` is only read by the controller once it connects. Before
> that (JS disabled or still loading), the browser shows whichever panel is not `hidden`. So drive both the markup and
> the
> value from the same variable — as above — to avoid a flash of the wrong tab. The controller reconciles the final state
> on connect, but the pre-JS render is the markup's responsibility.
