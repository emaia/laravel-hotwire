# Changelog

All notable changes to `laravel-hotwire` will be documented in this file.

## 0.26.0 - 2026-06-12

<!-- Release notes generated using configuration in .github/release.yml at 0.26.0 -->
### What's Changed

#### Other Changes

* Add tests for optimistic controllers and _dispatch helper by @emaia in https://github.com/emaia/laravel-hotwire/pull/44
* Add make-controller catalog guard and _form_errors helper tests by @emaia in https://github.com/emaia/laravel-hotwire/pull/45
* Mark package-shipped controllers and refuse overwrites of user files by @emaia in https://github.com/emaia/laravel-hotwire/pull/46
* Expose user-owned files as a distinct status in publish/check output by @emaia in https://github.com/emaia/laravel-hotwire/pull/47

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.25.0...0.26.0

## 0.25.0 - 2026-06-11

### Controller bugfixes, tooltip placement, and full test coverage

Every shipped controller now has a Bun test of its main behaviour; three real bugs surfaced and were fixed along the way, and the suite gained file-level mock isolation.

- New Bun tests for 13 controllers: auto_select, gtm, modal_auto_close, remote_form, dev--log (#41), oembed, tooltip (#42), toaster (#43) — combined with the four added in #40, the catalog is now fully covered
- `auto_select`: focus listener handler is now stored, so `disconnect()` actually removes it (#41)
- `gtm`: lazy mode registers three document-level listeners; new `disconnect()` removes them (#41)
- `oembed`: when no `<figure>` wraps the `<oembed>`, the controller no longer replaces — and destroys — its own data-controller root (#42)
- `tooltip`: connect is idempotent (destroys the previous tippy instance); new `placement` value (default `"top"`) wired to tippy (#42)
- Suite runs with `bun test --isolate` (Bun 1.3.10+); each file gets its own JSGlobalObject so `mock.module` no longer leaks across files (#43). Drop the flag once Bun 1.4 makes isolation the default
- `modal_auto_close`: ancestor lookup anchored at `parentElement` to work around a happy-dom `[attr~="value"]` substring-match bug (#41)

See `docs/controllers/tooltip.md` for the new placement entry.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.24.0...0.25.0

## 0.24.0 - 2026-06-11

### TypeScript to JavaScript migration

Package-shipped Stimulus controllers are now standardised on plain JavaScript (`.js`). Users can still generate `.ts` controllers via `hotwire:make-controller --ts` — the convention applies only to what the package distributes.

- Six controllers migrated from `.ts` to `.js`: animated_number, char_counter, checkbox_select_all, copy_to_clipboard, hotkey, timeago
- 45 new Bun tests across four previously uncovered controllers
- Registry and `hotwire:check` PHP tests updated to reference `.js` extensions
- `CLAUDE.md` documents the `.js`-only convention for shipped controllers

See individual controller docs under `docs/controllers/` for usage.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.23.0...0.24.0

## 0.23.0 - 2026-06-10

### Chart controller and `<x-hwc::chart>` component (Apache ECharts)

Apache ECharts ^6.1.0 wrapper with server-rendered or URL-fetched options, ResizeObserver-driven resize, and subclass-friendly extensibility hooks that match the carousel pattern.

```blade
{{-- Inline option — the 80 % case --}}
<x-hwc::chart :option="[
    'title'  => ['text' => 'Sales by month'],
    'xAxis'  => ['type' => 'category', 'data' => ['Jan', 'Feb', 'Mar']],
    'yAxis'  => ['type' => 'value'],
    'series' => [['type' => 'bar', 'data' => [120, 200, 150]]],
]" height="320px" />

{{-- URL-fetched for heavy datasets --}}
<x-hwc::chart url="/api/charts/sales" height="320px" />

{{-- Subclass swap for custom defaults, extra chart types, or drill-down --}}
<x-hwc::chart controller="sales-chart" :option="$option" />




```
#### Controller features

- **`setOption` action** — partial or full option updates via `event.detail`, with an optional `{ option, replace }` envelope that maps to ECharts' `notMerge` semantics
- **Hooks for subclasses** — `defaultOption()` (applied as the first `setOption` call) and `afterInit()` (post-init hook for event listeners), matching the carousel extensibility pattern
- **Base bundle** — bar/line/pie charts, grid/tooltip/legend/title/dataset components, and canvas renderer (~120 KB tree-shaken); subclasses register extras (scatter, gauge, map, SVG renderer, etc.) via `echarts.use([...])`
- **ResizeObserver** — `chart.resize()` on container dimension changes
- **Dev-mode warning** — in `local` environment, logs a `Log::warning` when the inline option JSON exceeds 500 KB, pointing to the `url` prop

#### Component

`<x-hwc::chart>` validates that at least one of `option` or `url` is provided, embeds the JSON-encoded option as a `data-*` attribute, applies inline sizing, and passes through extra HTML attributes and user `data-controller` identifiers. The `controller` prop swaps the Stimulus identifier so subclasses mount with zero additional wiring.

#### Recipe

Three patterns in `docs/recipes/charts.md` — inline, URL-fetched, and subclass extension — plus an advanced drill-down pattern with smooth universal transitions and a history stack.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.22.2...0.23.0

## 0.22.2 - 2026-06-10

### hotwire:check output, reorganized

The scan output now groups by category — component controllers, components without controllers, standalones, and shared helpers — each alphabetical. A new `Needs attention:` block collects every outdated, missing, and not-published item and prints right above the summary, so the actionable items sit next to the prompt instead of being buried mid-list.

Same exit codes, same behavior — only the order of emission changes.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.22.1...0.22.2

## 0.22.1 - 2026-06-10

### Focus trap helper

Internal refactor: the focus trap code that lived inline in `modal_controller` and `confirm_dialog_controller` is now a shared `FocusTrap` helper at `resources/js/controllers/_focus_trap.js`. Both controllers shed ~30 LOC each and delegate to the helper.

#### What changes for users

Nothing. Modal and confirm-dialog behave identically — same focusable selector, same priming-on-open semantics, same trigger-element focus restoration on close. When you publish either controller with `php artisan hotwire:controllers`, the publish pipeline now ships `_focus_trap.js` alongside it as a shared dependency (the same way `_transition.js` and `_form_errors.js` already work).

#### Why

A future bug fix in focus trap logic — Tab cycling, priming, the focusable selector — now applies to both consumers in one place, instead of having to be repeated. `hotwire:check` also flags the helper as not published / outdated when applicable, consistent with the rest of the shared-dep checks.

### CI

- Bumped `actions/cache` from 4 to 5 (#36)

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.22.0...0.22.1

## 0.22.0 - 2026-06-10

### Conditional fields controller and `<x-hwc::conditional-field>` component

New `conditional-fields` Stimulus controller shows or hides dependent blocks based on the value of other form fields, with zero round-trips. The controller auto-detects triggers from `data-when-{name}` attributes on each dependent, and works on any container with named inputs — `<form>` is the common host, but filter bars, dashboards, and in-page configuration panels work too.

```html

<form data-controller="conditional-fields">
    <select name="ship_different">...</select>

    <fieldset data-conditional-fields-target="dependent"
              data-when-ship-different=":checked"
              hidden disabled>
        ...
    </fieldset>
</form>







```
#### Rule grammar

- Values are pipe-separated within a single `data-when-*` attribute (OR). `data-when-reason="bug|feature"` matches when `reason` is `bug` or `feature`. Pipe (rather than whitespace) is the separator so trigger values containing spaces — full names like `"Kris Jhonson"`, country labels, statuses like `"In Progress"` — match literally.
- Multiple `data-when-*` attributes on the same dependent AND-match across fields.
- Tokens `:checked` / `:unchecked` for boolean checkboxes.
- Checkbox groups (`name[]`) supported: the dependent matches when any of the wanted values is checked.

#### `<x-hwc::conditional-field>` Blade component

Recommended path — encodes the rule once on the server, renders `hidden disabled` initially when the current state does not match, and emits the matching `data-when-*` attributes for the controller. Eliminates the client/server drift that would otherwise flash the wrong fields on first paint.

```blade
<form data-controller="conditional-fields" action="/feedback" method="POST">
    @csrf

    <x-hwc::select
        name="reason"
        placeholder="Pick one…"
        :options="['bug' => 'Bug', 'feature' => 'Feature', 'other' => 'Other']"
    />

    <x-hwc::conditional-field :when="['reason' => ['bug', 'feature']]">
        <x-hwc::field name="details" label="What happened?">
            <x-hwc::textarea name="details" />
        </x-hwc::field>
    </x-hwc::conditional-field>
</form>







```
#### Edit forms — the `:model` prop

Pass the same model your `<x-hwc::input>` / `<x-hwc::select>` / `<x-hwc::textarea>` already read from. The component evaluates `old($field, data_get($model, $field))` for each trigger named in `when`, lining initial visibility up with the model on the first GET while keeping `old()` winning on validation retry.

```blade
<x-hwc::conditional-field :model="$message" :when="['reason' => 'other']">
    <x-hwc::input name="other_reason" :value="$message->other_reason" />
</x-hwc::conditional-field>







```
When the trigger name does not match the model attribute (nested attributes like `$user->address->country`, camelCase models, foreign-key vs display-value pickers), define an accessor on the model or pass an associative `$state` array as `:model` — `data_get` accepts arrays, so a single `$state` map at the top of the form can resolve every `when` key to its real source.

#### Recipe

New cookbook entry at `docs/recipes/conditional-fields.md` covers five real-world patterns — "other" reason, ship-to-different-address, subscription tiers, NPS survey follow-ups, and newsletter preferences — plus an edit-form `:model` example.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.21.0...0.22.0

## 0.21.0 - 2026-06-09

### Disclosure controller

New `disclosure` Stimulus controller — collapsible inline content with proper ARIA, the base primitive for "read more" sections, FAQ items, and accordion patterns.

```html

<div data-controller="disclosure">
    <button type="button"
            data-disclosure-target="trigger"
            data-action="disclosure#toggle"
            aria-expanded="false">Read more</button>
    <div data-disclosure-target="content" hidden>...</div>
</div>








```
Two-way `open` value (default `false`), idempotent `toggle` / `open` / `close` actions, and a `disclosure:change` event with `{ open: bool }` for hooking analytics, icon swaps, or chained UI off transitions. The `content` target is required; the `trigger` target is optional and receives `aria-expanded` sync when present.

#### Programmatic control via outlets

Open or close from another controller:

```js
static outlets = ["disclosure"];

revealHelp() {
    this.disclosureOutlet.open();
}








```
Always call the methods (not `outlet.openValue = true`) — they sync DOM and dispatch synchronously, while raw value writes go through Stimulus's MutationObserver path and update asynchronously.

### Accordion recipe

New cookbook entry at `docs/recipes/accordion.md` covering both paths:

- **Native `<details>`** for static FAQ-style accordions — gets ARIA, keyboard handling, single-open via the native `toggle` event, and `::details-content` animation for free.
- **Controller-based patterns** — independent disclosures, single-open via Stimulus outlets, server-rendered initial state, and URL-driven sections — for when state needs to be JS- or server-driven.

Includes a "when is `<details>` not the right answer" checklist so the choice between native and controller stays explicit.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.20.0...0.21.0

## 0.20.0 - 2026-06-09

### Password visibility controller

New `password-visibility` Stimulus controller toggles a password input between hidden and visible, keeping the optional button target's `aria-pressed` and `aria-label` in sync.

```html

<div data-controller="password-visibility">
    <input type="password" name="password" data-password-visibility-target="input"/>
    <button
        type="button"
        data-password-visibility-target="button"
        data-action="password-visibility#toggle"
    >👁</button>
</div>









```
`aria-label` is driven by the `show-label` / `hide-label` values (defaults `Show password` / `Hide password`). A `password-visibility:change` event with `{ visible: bool }` fires on every transition so a small companion controller — or another listener — can swap icons. `connect()` always forces `type="password"`: visibility is never persisted across Turbo morphs or Drive navigations.

### Autofocus controller

New `autofocus` Stimulus controller focuses the first matching field on `connect()` and on `turbo:frame-load`, filling the gap left by native HTML `[autofocus]` which does not fire on Drive visits or frame swaps.

```html

<form data-controller="autofocus" action="/messages" method="POST">
    <input type="text" name="title" autofocus/>
</form>









```
Three strategies are available via `strategy-value`: `autofocus-attribute` (default — first `[autofocus]`), `first-focusable` (first `<input>` / `<select>` / `<textarea>` / `<button>`), and `target` (the `field` Stimulus target). All strategies skip `[disabled]`, `[type="hidden"]`, `[tabindex="-1"]`, and descendants of `[hidden]` / `[aria-hidden="true"]`. The controller never steals focus from an element already active inside its scope, and focuses with `{ preventScroll: true }` unless `scroll-into-view-value="true"` opts in.

### Back to top controller

New `back-to-top` Stimulus controller toggles `data-visible="true|false"` on its element as `window.scrollY` crosses a configurable threshold, and exposes a `scrollToTop` action that respects `prefers-reduced-motion`.

```html

<button
    type="button"
    data-controller="back-to-top"
    data-action="back-to-top#scrollToTop"
    class="fixed bottom-6 right-6 transition-opacity
           data-[visible=false]:opacity-0 data-[visible=false]:pointer-events-none
           data-[visible=true]:opacity-100"
    aria-label="Back to top"
>↑</button>









```
Default threshold is `400` (strict greater-than). The scroll listener is throttled via `requestAnimationFrame` and cleaned up on disconnect. No styles are shipped — the controller only writes the `data-visible` attribute, so consumers drive the show/hide transition with Tailwind `data-[visible=...]` variants or plain CSS.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.19.1...0.20.0

## 0.19.1 - 2026-06-09

### 0.19.1

#### Eliminate the loading template race condition

The modal's loading template is now injected synchronously on `turbo:before-fetch-request` instead of being queued through `showLoading()` and a `setTimeout(0)` racing against `turbo:before-fetch-response`. Behavior is identical for users in every observed flow, with one quiet improvement: programmatic `frame.src` changes that previously skipped the template (because there was no click) now show it correctly.

The public `modal#showLoading` Stimulus action is removed — no code in the package referenced it and the Blade component never emitted `data-action="modal#showLoading"`. Custom markup that called it manually will need to drop the action.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.19.0...0.19.1

## 0.19.0 - 2026-06-09

### 0.19.0

#### Modal `size` prop

Single `size` prop replaces the previous `allow-small-width` and `allow-full-width` booleans. Presets follow a monotonically increasing scale (`sm < md < lg < xl`) at any viewport, so the chosen size is predictable regardless of screen width or browser zoom. Arbitrary CSS lengths are forwarded as inline `max-width`.

```blade
<x-hwc::modal size="sm">...</x-hwc::modal>      {{-- md:max-w-md, 448px --}}
<x-hwc::modal>...</x-hwc::modal>                 {{-- size=md default, md:max-w-xl, 576px --}}
<x-hwc::modal size="lg">...</x-hwc::modal>       {{-- md:max-w-3xl, 768px --}}
<x-hwc::modal size="xl">...</x-hwc::modal>       {{-- md:max-w-5xl, 1024px --}}
<x-hwc::modal size="full">...</x-hwc::modal>     {{-- fills the viewport, close button moves inside --}}
<x-hwc::modal size="auto">...</x-hwc::modal>     {{-- sizes to content, no width constraints --}}
<x-hwc::modal size="50vw">...</x-hwc::modal>     {{-- arbitrary CSS length --}}











```
`allow-small-width` and `allow-full-width` are removed. Use `size="auto"` to keep the old "no width constraints" behavior, or `size="50vw"` to keep the old "half viewport" default. The migration table in `docs/components/modal.md` maps every previous combination to the new prop.

#### Modal scroll container clips horizontal overflow

`overflow-x-hidden` is now applied to the modal's inner scroll container. Without it, the CSS quirk that promotes `overflow-x: visible` to `auto` when `overflow-y: auto` is set could raise a spurious horizontal scrollbar whenever content was wider than the dialog.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.18.0...0.19.0

## 0.18.0 - 2026-06-08

### Frame-or-page Blade component

New `<x-hwc::frame-or-page>` component renders a view as a Turbo Frame payload or wrapped in a layout based on the `Turbo-Frame` request header — one view, two presentations.

#### Usage

```blade
<x-hwc::frame-or-page frame="modal" layout="layouts.dashboard">
    <form>...</form>
</x-hwc::frame-or-page>












```
#### Model-aware frame ids

Pass a Model instead of a string; the component calls `dom_id()` to derive the frame id.

```blade
<x-hwc::frame-or-page :frame="$message" layout="layouts.dashboard">
    ...
</x-hwc::frame-or-page>












```
**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.17.1...0.18.0

## 0.17.1 - 2026-06-08

* Bump deps (php/js)

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.17.0...0.17.1

## 0.17.0 - 2026-06-04

### Carousel progress bar and slide counter

The `<x-hwc::carousel>` component now supports an opt-in progress bar and slide counter.

#### Progress bar

```blade
<x-hwc::carousel :progress="true"
                 progress-class="h-1 bg-red-500"
                 progress-wrapper-class="max-w-xs bg-gray-200 rounded-md h-1">














```
#### Slide counter

```blade
<x-hwc::carousel :counter="true"
                 counter-class="text-sm">














```
**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.16.0...0.17.0

## 0.16.0 — Carousel extensibility via subclassing - 2026-06-03

### Carousel extensibility via subclassing

The `<x-hwc::carousel>` component now supports a `controller` prop that lets you swap the mounted Stimulus identifier so subclasses can inherit from `CarouselController` and supply Embla plugins.

#### Extending the controller

```js
// resources/js/controllers/gallery_controller.js
import CarouselController from "./carousel_controller";
import Autoplay from "embla-carousel-autoplay";

export default class extends CarouselController {
    emblaPlugins() {
        return [Autoplay({ delay: 4000 })];
    }
}















```
```blade
<x-hwc::carousel controller="gallery">
    <div>slide 1</div>
    <div>slide 2</div>
</x-hwc::carousel>















```
Plugin imports load lazily with the subclass chunk. `play()` and `stop()` delegate to `embla.plugins()?.autoplay` when present.

#### Identifier-independent structural hooks

Viewport and container are no longer Stimulus targets — they use `data-carousel-viewport` and `data-carousel-container` hooks so a subclass reuses the same CSS and layout without per-identifier attributes.

#### Subclass values pass through

The root element filters only the component`s own `data-{identifier}-*` prefixes (`options-`, `active-dot-class`, `disabled-nav-class`). Any additional value your subclass declares (e.g. `data-gallery-delay-value`) passes through freely.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.15.2...0.16.0

## 0.15.2 - 2026-06-03

Internal refactor — no behavior change.

- Centralize package-manager detection and package.json devDependency writes in the PackageInstaller service, removing duplicated logic across the install, ui and check commands (#22).

Full Changelog: https://github.com/emaia/laravel-hotwire/compare/0.15.1...0.15.2

## 0.15.1 - 2026-06-03

Fixes hotwire:controllers --outdated missing drifted shared dependencies.

- A published controller now counts as outdated when its own file OR any of its already-published shared deps (e.g. carousel.css) differ from the package — so --outdated --force updates a stale dependency even when the controller file itself is unchanged (#21).
- Docs: README now lists the Carousel controller and documents hotwire:check's direct-controller detection.

Full Changelog: https://github.com/emaia/laravel-hotwire/compare/0.15.0...0.15.1

## 0.15.0 - 2026-06-03

hotwire:check now detects Stimulus controllers used directly, not just via components — data-controller attributes and the stimulus_controller() / stimulus()->controller()/controllers() / stimulus_action() / stimulus_target() helpers (#20).

- Only package-registered controllers are checked; user-defined ones are ignored.
- Comments, <script> and <style> blocks are stripped before scanning, so commented-out code is ignored.
- May surface new CI failures (exit 1): a package controller used via a raw data-controller, without its component and not yet published, is now reported.

Full Changelog: https://github.com/emaia/laravel-hotwire/compare/0.14.0...0.15.0

## 0.14.0 - 2026-06-03

Carousel for Hotwire — the Embla-powered `carousel` controller plus the `<x-hwc::carousel>` Blade component.

- Add carousel controller (Embla) (#18) — drag, loop, axis, breakpoints, reduced-motion, dot/nav wiring.
- Add Carousel Blade component (#19) — prev/next nav, pagination dots, responsive options, CSS-variable sizing, `prev_button`/`next_button`/`dot_template` slots, and a `nav-wrapper-class` prop to group the nav buttons.

Full Changelog: https://github.com/emaia/laravel-hotwire/compare/0.13.0...0.14.0

## 0.13.0 - 2026-06-02

### What's Changed

* Add Dropdown component by @emaia in https://github.com/emaia/laravel-hotwire/pull/17

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.6...0.13.0

## 0.12.6 - 2026-06-01

### What's Changed

* Add controllers() helper to Stimulus builder by @emaia in https://github.com/emaia/laravel-hotwire/pull/16

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.5...0.12.6

## 0.12.5 - 2026-06-01

### What's Changed

* Add dropdown controller by @emaia in https://github.com/emaia/laravel-hotwire/pull/15

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.4...0.12.5

## 0.12.4 - 2026-06-01

### What's Changed

* Add slug controller by @emaia in https://github.com/emaia/laravel-hotwire/pull/14

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.3...0.12.4

## 0.12.3 - 2026-06-01

### What's Changed

* Introduce `stimulus()` as the primary attribute-builder entry point by @emaia
* Add missing tabs controller reference to the README by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.2...0.12.3

## 0.12.2 - 2026-05-29

### What's Changed

* Add tabs controller by @emaia in https://github.com/emaia/laravel-hotwire/pull/13

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.1...0.12.2

## 0.12.1 - 2026-05-28

### What's Changed

* Improve the auto-submit controller by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.0...0.12.1

## 0.12.0 - 2026-05-28

### What's Changed

* add Stimulus attribute helpers for Blade by @emaia in https://github.com/emaia/laravel-hotwire/pull/12

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.11.0...0.12.0

## 0.11.0 - 2026-05-28

### What's Changed

* add per-toast position to flash-message by @emaia in https://github.com/emaia/laravel-hotwire/pull/11

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.10.0...0.11.0

## 0.10.0 - 2026-05-28

### What's Changed

* Update emaia/laravel-hotwire-turbo requirement from ^0.8.4 to ^0.9.2 by @dependabot[bot]
  in https://github.com/emaia/laravel-hotwire/pull/9
* add form components and controllers by @emaia in https://github.com/emaia/laravel-hotwire/pull/10

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.12...0.10.0

## 0.9.12 - 2026-04-30

### What's Changed

* Improve the modal component, controller, docs and recipes by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.11...0.9.12

## 0.9.11 - 2026-04-29

### Added

* `hotwire:check` now detects the npm dependencies required by the Stimulus controllers of used components (e.g.
  `@emaia/sonner` for `<x-hwc::flash-message>`) and reports those missing from the app's `package.json`.
  `--fix` additionally adds them to `devDependencies` alongside publishing controllers.
* `<x-hotwire::...>` is now recognized globally as an alias for the configured Blade component prefix, regardless of
  the value of `hotwire.prefix`.

### Fixed

* `hotwire:check` now recognizes the `hotwire::` alias alongside the configured prefix, so components written as
  `<x-hotwire::...>` are no longer silently skipped.
* `<x-hotwire::flash-message />` (and any other component) no longer renders without its backing PHP class when the
  configured prefix differs from `hotwire` — the service provider now registers both prefixes.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.2...0.9.11

## 0.9.2 - 2026-04-29

### What's Changed

* Add docs cli by @emaia in https://github.com/emaia/laravel-hotwire/pull/8

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.1...0.9.2

## 0.9.1 - 2026-04-28

### What's Changed

* Add `input-mask` and `money-input` controllers by @emaia
* Add an `--outdated` flag to `hotwire:controllers` to update only published controllers that changed by @emaia
* Improve the clean-query-params controller by @emaia
* Standardize controller names and refactor the docs by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.0...0.9.1

## 0.9.0 - 2026-04-27

### What's Changed

* Add global registry for components/controllers by @emaia in https://github.com/emaia/laravel-hotwire/pull/5
* Modal refactor by @emaia in https://github.com/emaia/laravel-hotwire/pull/6
* Confirm dialog refactor by @emaia in https://github.com/emaia/laravel-hotwire/pull/7

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.8.0...0.9.0

## 0.8.0 - 2026-04-22

### What's Changed

* Move controllers to flat structure by @emaia in https://github.com/emaia/laravel-hotwire/pull/3
* Bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 by @dependabot[bot]
  in https://github.com/emaia/laravel-hotwire/pull/4

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/emaia/laravel-hotwire/pull/4

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.7.6...0.8.0

## 0.7.5 - 2026-04-17

### What's Changed

* feat: add optimistic UI primitives (component + form/link/dispatch controllers)  by @emaia
  in https://github.com/emaia/laravel-hotwire/pull/2

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.7.4...0.7.5

## 0.7.4 - 2026-04-13

### What's Changed

* Bump dependencies and update the README by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.7.3...0.7.4
