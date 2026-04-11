---
name: stimulus
description: Stimulus JS framework -- client-side behavior via HTML data attributes, zero server round-trips. Use when creating controllers for DOM manipulation, handling click/input/submit events, managing targets and values, wiring outlets between controllers, wrapping third-party JS libraries, or building toggles, dropdowns, modals, tabs, clipboard interactions. Code triggers [data-controller, data-action, data-target, data-*-value, data-*-class, data-*-outlet, connect(), disconnect(), static targets, static values]. Also trigger when the user asks "how do I add a click handler", "how to toggle a class", "how to build a dropdown/modal/tabs", "how to wrap a JS library", "add keyboard shortcuts", "lazy-load a controller", "listen to global events", "communicate between controllers". Do NOT trigger for partial page updates without JS (use turbo), or reusable blade components.
---

# Stimulus

Modest JavaScript framework that connects JS objects to HTML via data attributes. Stimulus does not render HTML -- it
augments server-rendered HTML with behavior.

The mental model: HTML is the source of truth, JavaScript controllers attach to elements, and data attributes are the
wiring.

## This Package's Setup

Controllers live in `resources/js/controllers/{namespace}/{name}_controller.{js|ts}` and are automatically discovered
by `@emaia/stimulus-dynamic-loader` via Vite's `import.meta.glob`:

```javascript
// resources/js/controllers/index.js
import { Stimulus } from "../libs/stimulus";
import { registerControllers } from "@emaia/stimulus-dynamic-loader";

const controllers = import.meta.glob("./**/*_controller.{js,ts}", {
    eager: false,
});

registerControllers(Stimulus, controllers);
```

### Naming Convention

| File path | Stimulus identifier |
|-----------|---------------------|
| `form/autosubmit_controller.js` | `form--autosubmit` |
| `dialog/modal_controller.js` | `dialog--modal` |
| `notification/toast_controller.js` | `notification--toast` |

Rule: subdirectories become `--` separators, underscores become hyphens.

### Artisan Commands

```bash
# Create a new controller (interactive scaffolding)
php artisan hotwire:make-controller form/autosave
php artisan hotwire:make-controller form/autosave --ts

# Publish package controllers to your app
php artisan hotwire:controllers
php artisan hotwire:controllers --list

# Check which controllers are needed by components in your views
php artisan hotwire:check
```

## Quick Reference

```
data-controller="name"              attach controller to element
data-name-target="item"             mark element as a target
data-action="event->name#method"    bind event to controller method
data-name-key-value="..."           pass typed data to controller
data-name-key-class="..."           configure CSS class names
data-name-other-outlet=".selector"  reference another controller instance
```

## Controller Skeleton

```javascript
// resources/js/controllers/example/feature_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'output'];
    static values = { url: String, delay: { type: Number, default: 300 } };
    static classes = ['loading'];
    static outlets = ['other'];

    connect() {
        // Called when controller connects to DOM
    }

    disconnect() {
        // Called when controller disconnects -- clean up here
    }

    submit(event) {
        // Action method
    }
}
```

## HTML Wiring Examples

### Basic Controller

```html
<div data-controller="hello">
    <input data-hello-target="name" type="text">
    <button data-action="click->hello#greet">Greet</button>
    <span data-hello-target="output"></span>
</div>
```

### Values from Server (Blade)

Pass server data to controllers via value attributes. Values are typed and automatically parsed.

```html
<div data-controller="map"
     data-map-latitude-value="{{ $place->lat }}"
     data-map-longitude-value="{{ $place->lng }}"
     data-map-zoom-value="12">
</div>
```

Available types: `String`, `Number`, `Boolean`, `Array`, `Object`. Values trigger `{name}ValueChanged()` callbacks when
mutated.

### Actions

The format is `event->controller#method`. Default events exist per element type (click for buttons, input for inputs,
submit for forms) so the event can be omitted.

```html
<!-- Explicit event -->
<button data-action="click->hello#greet">Greet</button>

<!-- Default event (click for button) -->
<button data-action="hello#greet">Greet</button>

<!-- Multiple actions on same element -->
<input type="text"
       data-action="focus->field#highlight blur->field#normalize input->field#validate">

<!-- Prevent default -->
<form data-action="submit->form#validate:prevent">

<!-- Keyboard shortcuts -->
<div data-action="keydown.esc@window->modal#close">
    <input data-action="keydown.enter->modal#submit keydown.ctrl+s->modal#save">

    <!-- Global events (window/document) -->
    <div data-action="resize@window->sidebar#adjust click@document->sidebar#closeOutside">
```

### CSS Classes

Externalize CSS class names so controllers stay generic:

```html
<button data-controller="button"
        data-button-loading-class="opacity-50 cursor-wait"
        data-button-active-class="bg-blue-600"
        data-action="click->button#submit">
    Submit
</button>
```

```javascript
// In controller
this.element.classList.add(...this.loadingClasses);
```

### Multiple Controllers

An element can have multiple controllers:

```html
<div data-controller="dropdown tooltip"
     data-action="mouseenter->tooltip#show mouseleave->tooltip#hide">
    <button data-action="click->dropdown#toggle">Menu</button>
    <ul data-dropdown-target="menu" hidden>...</ul>
</div>
```

### Outlets (Cross-Controller Communication)

Reference other controller instances by CSS selector:

```html
<div data-controller="player"
     data-player-playlist-outlet="#playlist">
    <button data-action="click->player#playNext">Next</button>
</div>

<ul id="playlist" data-controller="playlist">
    <li data-playlist-target="track">Song 1</li>
    <li data-playlist-target="track">Song 2</li>
</ul>
```

```javascript
// In player controller
static outlets = ['playlist'];

playNext() {
    const tracks = this.playlistOutlet.trackTargets;
    // ...
}
```

## Key Principles

**HTML drives, JS responds.** Controllers don't create markup -- they attach behavior to existing HTML. If you find
yourself generating DOM in a controller, consider whether a Blade component would be better.

**One controller, one concern.** A dropdown controller handles dropdowns. A tooltip controller handles tooltips. Compose
multiple controllers on the same element rather than building mega-controllers.

**Clean up in disconnect().** If `connect()` adds event listeners, timers, or third-party library instances,
`disconnect()` must remove them. Turbo navigation will disconnect and reconnect controllers as pages change.

**Values over data attributes.** Use Stimulus values (typed, with change callbacks) rather than raw `data-*` attributes
for data that the controller needs to read or watch.

## References

- **Full API** (lifecycle, targets, values, actions, classes, outlets): [references/api.md](references/api.md)
- **Patterns** (debounce, fetch, modals, forms, etc.): [references/patterns.md](references/patterns.md)
- **Gotchas** (common mistakes, debugging, Turbo compatibility): [references/gotchas.md](references/gotchas.md)
