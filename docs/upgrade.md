# Upgrade guide

Manual steps required when upgrading to a release that introduces a breaking change. The package follows semver `X.Y.Z`; **breaking visual changes** are also called out here because they aren't enforceable by code but can surprise apps relying on the prior appearance.

---

## Unreleased

### Field context keys are scoped

Field now exposes its descendant context through family-specific component-data keys so intermediate Blade components
declaring generic `name`, `id`, `errorKey`, or `required` props cannot replace the owning Field's form, validation, or
ARIA identity. Field-aware controls, labels, errors, and selection groups consume the scoped keys. Explicit control
props continue to win; an explicit `:required="false"` now opts a control out of a required Field.

Application subcomponents that consume Field context must update their `@aware` declarations:

| Field context | Before | After |
| --- | --- | --- |
| Name | `@aware(['name' => null])` | `@aware(['fieldName' => null])` |
| Control id | `@aware(['id' => null])` | `@aware(['fieldId' => null])` |
| Error key | `@aware(['errorKey' => null])` | `@aware(['fieldErrorKey' => null])` |
| Required state | `@aware(['required' => false])` | `@aware(['fieldRequired' => false])` |

The Field root no longer exposes its other props as generic component data either. Their scoped names are `fieldLabel`,
`fieldDescription`, `fieldRequiredLabel`, `fieldError`, `fieldOrientation`, `fieldClass`, `fieldWrapperId`,
`fieldDisabled`, `fieldInvalid`, `fieldSet`, and `fieldLabelId`.

`<hw:field id="...">` now defines the nested control id base and keeps labels, controls, selection items, and errors on
the same ARIA identity. If application code previously used `id` to identify the wrapper `<div>`, replace it with
`wrapper-id`:

```blade
<hw:field name="email" id="email-control" wrapper-id="email-field">
    <hw:input />
</hw:field>
```

A control with an explicit `name` different from the Field name now derives its id from that control name instead of
reusing the Field id. This prevents sibling controls with different names from rendering duplicate ids. Controls that
inherit or repeat the Field name still inherit its id. Labels and errors use the same resolver, keeping explicit names,
`for`, error ids, and `aria-describedby` aligned.

Application components calling `FieldKey::controlId()` directly must rename that call to `FieldKey::resolveId()`; the
argument order is unchanged.

Field now resolves label ownership from the controls and selection groups that render in its slot. A sole selection
group's own `field.label` suppresses the Field's automatic label, and only that group emits the set role and
`aria-labelledby`. Nested groups start a new ownership boundary. Explicit `aria-label` and `aria-labelledby` attributes
remain authoritative. Multiple direct selection groups each reference the same Field label without adding a competing
role to the Field wrapper. A single control keeps `label for`, including controls whose name ends in `[]`.

Fields containing several unrelated controls omit `for` and name their `role="group"` wrapper with the automatic label.
When a selection group supplies its own `aria-label` or `aria-labelledby`, the Field's separate visible text renders as
a styled `<span>` rather than an unassociated `<label>`.

Multi Select now registers its visible trigger with Field and blocks its popup search input from taking over the label.
Package overlay roots (Alert Dialog, Drawer, Dropdown, Hover Card, Modal, Popover, and Sheet) block both Field ownership
contexts, so nested controls and selection groups cannot register with a surrounding Field.

Raw HTML and application components cannot participate in this internal registration. If they form a set, declare it
on Field and provide the automatic label id explicitly:

```blade
<hw:field label="Choices" set="radiogroup" label-id="choices-label">
    <input type="radio" name="choice" value="a">
    <input type="radio" name="choice" value="b">
</hw:field>
```

When the visible label already exists elsewhere, `label-id` remains active without the `label` prop and names direct
selection groups or an explicit set through that external element. With `set`, selection-group roots cede their own
generated roles and labels to the Field wrapper.

### Selection group context keys are scoped

Radio Group, Checkbox Group, and Toggle Group now publish root and item data under their own family prefixes. This keeps
items attached to the correct owner through intermediate components and across nested groups. All three disabled keys
now follow the same convention: `radioGroupDisabled`, `checkboxGroupDisabled`, and `toggleGroupDisabled`; the old Toggle
Group `groupDisabled` key has been removed.

Update application subcomponents that consume group context:

| Component family | Before | After |
| --- | --- | --- |
| Radio Group name | `@aware(['name' => null])` | `@aware(['radioGroupName' => null])` |
| Radio Group id | `@aware(['id' => null])` | `@aware(['radioGroupId' => null])` |
| Radio Group error key | `@aware(['errorKey' => null])` | `@aware(['radioGroupErrorKey' => null])` |
| Radio Group selection | `@aware(['selected' => null])` | `@aware(['radioGroupSelected' => null])` |
| Radio Group old input | `@aware(['old' => true])` | `@aware(['radioGroupOld' => true])` |
| Radio Group auto-submit | `@aware(['autoSubmit' => false])` | `@aware(['radioGroupAutoSubmit' => false])` |
| Radio Group auto-submit delay | `@aware(['autoSubmitDelay' => null])` | `@aware(['radioGroupAutoSubmitDelay' => null])` |
| Checkbox Group name | `@aware(['name' => null])` | `@aware(['checkboxGroupName' => null])` |
| Checkbox Group id | `@aware(['id' => null])` | `@aware(['checkboxGroupId' => null])` |
| Checkbox Group error key | `@aware(['errorKey' => null])` | `@aware(['checkboxGroupErrorKey' => null])` |
| Checkbox Group selection | `@aware(['selected' => []])` | `@aware(['checkboxGroupSelected' => []])` |
| Checkbox Group old input | `@aware(['old' => true])` | `@aware(['checkboxGroupOld' => true])` |
| Checkbox Group select all | `@aware(['selectAll' => false])` | `@aware(['checkboxGroupSelectAll' => false])` |
| Checkbox Group auto-submit | `@aware(['autoSubmit' => false])` | `@aware(['checkboxGroupAutoSubmit' => false])` |
| Checkbox Group auto-submit delay | `@aware(['autoSubmitDelay' => null])` | `@aware(['checkboxGroupAutoSubmitDelay' => null])` |
| Toggle Group name | `@aware(['name' => null])` | `@aware(['toggleGroupName' => null])` |
| Toggle Group type | `@aware(['type' => 'multiple'])` | `@aware(['toggleGroupType' => 'multiple'])` |
| Toggle Group selection | `@aware(['selected' => []])` | `@aware(['toggleGroupSelected' => []])` |
| Toggle Group old input | `@aware(['old' => true])` | `@aware(['toggleGroupOld' => true])` |
| Toggle Group id | `@aware(['id' => null])` | `@aware(['toggleGroupId' => null])` |
| Toggle Group error key | `@aware(['errorKey' => null])` | `@aware(['toggleGroupErrorKey' => null])` |
| Toggle Group variant | `@aware(['variant' => 'default'])` | `@aware(['toggleGroupVariant' => 'default'])` |
| Toggle Group size | `@aware(['size' => 'default'])` | `@aware(['toggleGroupSize' => 'default'])` |
| Toggle Group disabled | `@aware(['groupDisabled' => false])` | `@aware(['toggleGroupDisabled' => false])` |

Root and item props that application descendants intentionally consume follow the same prefix pattern, such as
`radioGroupOptions`, `checkboxGroupSelectAllLabel`, `toggleGroupConnected`, `radioGroupItemValue`,
`checkboxGroupItemChecked`, and `toggleGroupItemPressed`.

Radio Group, Checkbox Group, and Toggle Group items now throw when rendered without their owning root. For Turbo Streams,
replace the item's inner content or render the owning group instead of streaming a dependent item alone.

### Floating overlay context keys are scoped

Dropdown, Popover and Hover Card no longer expose root or dependent subcomponent context through generic component-data
keys such as `id`, `open`, `side`, `align`, offsets, strategy, delay values, `motion`, trigger variant props or
`stimulus`. Their trigger and content subcomponents now consume family-specific keys so an unrelated intermediate Blade
component cannot replace the owner overlay's ARIA, state or placement context.

Update application subcomponents that consume the old internal keys:

| Component family | Before | After |
| --- | --- | --- |
| Dropdown id | `@aware(['id' => ''])` | `@aware(['dropdownId' => null])` |
| Dropdown open state | `@aware(['open' => false])` | `@aware(['dropdownOpen' => false])` |
| Dropdown close on select | `@aware(['closeOnSelect' => true])` | `@aware(['dropdownCloseOnSelect' => true])` |
| Dropdown stimulus | `@aware(['stimulus' => null])` | `@aware(['dropdownStimulus' => null])` |
| Dropdown content side | `@aware(['side' => 'bottom'])` | `@aware(['dropdownContentSide' => 'bottom'])` |
| Dropdown content align | `@aware(['align' => 'start'])` | `@aware(['dropdownContentAlign' => 'start'])` |
| Dropdown content side offset | `@aware(['sideOffset' => 4])` | `@aware(['dropdownContentSideOffset' => 4])` |
| Dropdown content align offset | `@aware(['alignOffset' => 0])` | `@aware(['dropdownContentAlignOffset' => 0])` |
| Dropdown content strategy | `@aware(['strategy' => 'absolute'])` | `@aware(['dropdownContentStrategy' => 'absolute'])` |
| Dropdown content flip | `@aware(['flip' => true])` | `@aware(['dropdownContentFlip' => true])` |
| Dropdown content shift | `@aware(['shift' => true])` | `@aware(['dropdownContentShift' => true])` |
| Dropdown content mobile side | `@aware(['mobileSide' => null])` | `@aware(['dropdownContentMobileSide' => null])` |
| Dropdown content mobile align | `@aware(['mobileAlign' => null])` | `@aware(['dropdownContentMobileAlign' => null])` |
| Dropdown content mobile media | `@aware(['mobileMedia' => '(max-width: 767px)'])` | `@aware(['dropdownContentMobileMedia' => '(max-width: 767px)'])` |
| Dropdown content collapsed side | `@aware(['collapsedSide' => null])` | `@aware(['dropdownContentCollapsedSide' => null])` |
| Dropdown content collapsed align | `@aware(['collapsedAlign' => null])` | `@aware(['dropdownContentCollapsedAlign' => null])` |
| Dropdown content collapsed condition | `@aware(['collapsedWhen' => ...])` | `@aware(['dropdownContentCollapsedWhen' => ...])` |
| Dropdown content motion | `@aware(['motion' => 'default'])` | `@aware(['dropdownContentMotion' => 'default'])` |
| Dropdown content width | `@aware(['width' => ''])` | `@aware(['dropdownContentWidth' => ''])` |
| Dropdown content menu class | `@aware(['menuClass' => ''])` | `@aware(['dropdownContentMenuClass' => ''])` |
| Dropdown trigger as child | `@aware(['asChild' => false])` | `@aware(['dropdownTriggerAsChild' => false])` |
| Dropdown item href | `@aware(['href' => null])` | `@aware(['dropdownItemHref' => null])` |
| Dropdown item variant | `@aware(['variant' => 'default'])` | `@aware(['dropdownItemVariant' => 'default'])` |
| Dropdown item disabled | `@aware(['disabled' => false])` | `@aware(['dropdownItemDisabled' => false])` |
| Dropdown item inset | `@aware(['inset' => false])` | `@aware(['dropdownItemInset' => false])` |
| Dropdown item type | `@aware(['type' => 'button'])` | `@aware(['dropdownItemType' => 'button'])` |
| Dropdown item frame | `@aware(['frame' => null])` | `@aware(['dropdownItemFrame' => null])` |
| Dropdown label inset | `@aware(['inset' => false])` | `@aware(['dropdownLabelInset' => false])` |
| Popover id | `@aware(['id' => ''])` | `@aware(['popoverId' => null])` |
| Popover open state | `@aware(['open' => false])` | `@aware(['popoverOpen' => false])` |
| Popover side | `@aware(['side' => 'bottom'])` | `@aware(['popoverSide' => 'bottom'])` |
| Popover align | `@aware(['align' => 'start'])` | `@aware(['popoverAlign' => 'start'])` |
| Popover side offset | `@aware(['sideOffset' => 4])` | `@aware(['popoverSideOffset' => 4])` |
| Popover align offset | `@aware(['alignOffset' => 0])` | `@aware(['popoverAlignOffset' => 0])` |
| Popover strategy | `@aware(['strategy' => 'fixed'])` | `@aware(['popoverStrategy' => 'fixed'])` |
| Popover flip | `@aware(['flip' => true])` | `@aware(['popoverFlip' => true])` |
| Popover shift | `@aware(['shift' => true])` | `@aware(['popoverShift' => true])` |
| Popover stimulus | `@aware(['stimulus' => null])` | `@aware(['popoverStimulus' => null])` |
| Popover content motion | `@aware(['motion' => 'default'])` | `@aware(['popoverContentMotion' => 'default'])` |
| Popover header tag | `@aware(['tag' => 'div'])` | `@aware(['popoverHeaderTag' => 'div'])` |
| Popover header slot name | `@aware(['slotName' => 'popover-header'])` | `@aware(['popoverHeaderSlotName' => 'popover-header'])` |
| Popover title tag | `@aware(['tag' => 'h2'])` | `@aware(['popoverTitleTag' => 'h2'])` |
| Popover title slot name | `@aware(['slotName' => 'popover-title'])` | `@aware(['popoverTitleSlotName' => 'popover-title'])` |
| Popover description tag | `@aware(['tag' => 'p'])` | `@aware(['popoverDescriptionTag' => 'p'])` |
| Popover description slot name | `@aware(['slotName' => 'popover-description'])` | `@aware(['popoverDescriptionSlotName' => 'popover-description'])` |
| Hover Card id | `@aware(['id' => ''])` | `@aware(['hoverCardId' => null])` |
| Hover Card open state | `@aware(['open' => false])` | `@aware(['hoverCardOpen' => false])` |
| Hover Card side | `@aware(['side' => 'bottom'])` | `@aware(['hoverCardSide' => 'bottom'])` |
| Hover Card align | `@aware(['align' => 'start'])` | `@aware(['hoverCardAlign' => 'start'])` |
| Hover Card side offset | `@aware(['sideOffset' => 4])` | `@aware(['hoverCardSideOffset' => 4])` |
| Hover Card align offset | `@aware(['alignOffset' => 0])` | `@aware(['hoverCardAlignOffset' => 0])` |
| Hover Card strategy | `@aware(['strategy' => 'fixed'])` | `@aware(['hoverCardStrategy' => 'fixed'])` |
| Hover Card flip | `@aware(['flip' => true])` | `@aware(['hoverCardFlip' => true])` |
| Hover Card shift | `@aware(['shift' => true])` | `@aware(['hoverCardShift' => true])` |
| Hover Card open delay | `@aware(['openDelay' => 10])` | `@aware(['hoverCardOpenDelay' => 10])` |
| Hover Card close delay | `@aware(['closeDelay' => 100])` | `@aware(['hoverCardCloseDelay' => 100])` |
| Hover Card stimulus | `@aware(['stimulus' => null])` | `@aware(['hoverCardStimulus' => null])` |
| Hover Card trigger element | `@aware(['as' => 'button'])` | `@aware(['hoverCardTriggerAs' => 'button'])` |
| Hover Card trigger variant | `@aware(['variant' => 'link'])` | `@aware(['hoverCardTriggerVariant' => 'link'])` |
| Hover Card trigger size | `@aware(['size' => 'default'])` | `@aware(['hoverCardTriggerSize' => 'default'])` |
| Hover Card trigger type | `@aware(['type' => 'button'])` | `@aware(['hoverCardTriggerType' => 'button'])` |
| Hover Card content motion | `@aware(['motion' => 'default'])` | `@aware(['hoverCardContentMotion' => 'default'])` |

Dropdown, Popover and Hover Card triggers and content now throw when rendered without their owning root. Render the owner
in the same Blade tree so it can supply the scoped ARIA, state and placement context. These subcomponents cannot stand on
their own: the Stimulus controller is mounted on the root element, so a trigger or panel rendered outside it has no
controller to attach to. For Turbo Streams, replace content inside the floating panel or render the owning root instead
of streaming a dependent subcomponent alone.

Floating overlay triggers are stricter than Modal, Sheet and Drawer triggers, which still render standalone. A floating
trigger carries `aria-controls` and `aria-expanded` pointing at one specific panel, and is a Stimulus target of the root
controller, so it is meaningless without its root. A Modal, Sheet or Drawer trigger only carries an optional `frame`,
which can be passed explicitly.

### Modal overlay context keys are scoped

Modal, Sheet and Drawer no longer expose their overlay configuration through generic component-data keys such as `id`,
`size`, `class`, `closeButton`, `fixedTop`, `side`, `direction`, `axis`, `backdrop`, `frame`, `stimulus`, `motion`,
`viewTransition`, `lockScroll`, `closeOnEscape` and `closeOnClickOutside`. Their package subcomponents now consume
family-specific keys so an unrelated intermediate Blade component cannot replace the owner overlay's ARIA, frame,
backdrop, motion or placement context.

Update application subcomponents that consume the old internal keys:

| Component family | Before | After |
| --- | --- | --- |
| Modal id | `@aware(['id' => ''])` | `@aware(['modalId' => null])` |
| Modal size | `@aware(['size' => 'md'])` | `@aware(['modalSize' => 'md'])` |
| Modal panel class | `@aware(['class' => ''])` | `@aware(['modalClass' => ''])` |
| Modal close icon | `@aware(['closeButton' => true])` | `@aware(['modalCloseButton' => true])` |
| Modal top alignment | `@aware(['fixedTop' => false])` | `@aware(['modalFixedTop' => false])` |
| Modal frame | `@aware(['frame' => null])` | `@aware(['modalFrame' => null])` |
| Modal stimulus | `@aware(['stimulus' => null])` | `@aware(['modalStimulus' => null])` |
| Modal motion | `@aware(['motion' => 'default'])` | `@aware(['modalMotion' => 'default'])` |
| Modal view transition | `@aware(['viewTransition' => false])` | `@aware(['modalViewTransition' => false])` |
| Sheet id | `@aware(['id' => ''])` | `@aware(['sheetId' => null])` |
| Sheet side | `@aware(['side' => 'right'])` | `@aware(['sheetSide' => 'right'])` |
| Sheet size | `@aware(['size' => ...])` | no replacement; size is root-only styling |
| Sheet backdrop | `@aware(['backdrop' => true])` | `@aware(['sheetBackdrop' => true])` |
| Sheet frame | `@aware(['frame' => null])` | `@aware(['sheetFrame' => null])` |
| Sheet lock scroll | `@aware(['lockScroll' => true])` | no replacement; lock scroll is root-only controller config |
| Sheet escape close | `@aware(['closeOnEscape' => true])` | no replacement; escape close is root-only controller config |
| Sheet outside close | `@aware(['closeOnClickOutside' => true])` | no replacement; outside close is root-only controller config |
| Sheet stimulus | `@aware(['stimulus' => null])` | no replacement; stimulus is root-only wiring |
| Sheet motion | `@aware(['motion' => 'default'])` | `@aware(['sheetMotion' => 'default'])` |
| Sheet view transition | `@aware(['viewTransition' => false])` | `@aware(['sheetViewTransition' => false])` |
| Drawer id | `@aware(['id' => ''])` | `@aware(['drawerId' => null])` |
| Drawer direction | `@aware(['direction' => 'down'])` | `@aware(['drawerDirection' => 'down'])` |
| Drawer side | `@aware(['side' => ...])` | no replacement; use `drawerDirection` |
| Drawer size | `@aware(['size' => ...])` | no replacement; size is root-only styling |
| Drawer axis | `@aware(['axis' => 'y'])` | `@aware(['drawerAxis' => 'y'])` |
| Drawer backdrop | `@aware(['backdrop' => true])` | `@aware(['drawerBackdrop' => true])` |
| Drawer frame | `@aware(['frame' => null])` | `@aware(['drawerFrame' => null])` |
| Drawer lock scroll | `@aware(['lockScroll' => true])` | no replacement; lock scroll is root-only controller config |
| Drawer escape close | `@aware(['closeOnEscape' => true])` | no replacement; escape close is root-only controller config |
| Drawer outside close | `@aware(['closeOnClickOutside' => true])` | no replacement; outside close is root-only controller config |
| Drawer stimulus | `@aware(['stimulus' => null])` | no replacement; stimulus is root-only wiring |
| Drawer motion | `@aware(['motion' => 'default'])` | `@aware(['drawerMotion' => 'default'])` |
| Drawer view transition | `@aware(['viewTransition' => false])` | `@aware(['drawerViewTransition' => false])` |

Modal content, Sheet content and Drawer content now throw when rendered without their owning root. Render the owner in
the same Blade tree so it can supply the scoped controller, frame and overlay context. For Turbo Streams, replace the
content inside the overlay's frame or render the owning root instead of rendering the dependent content subcomponent
alone. Modal, Sheet and Drawer triggers still render standalone. Pass `frame` or `data-turbo-frame` explicitly to a
standalone Modal trigger when it should target a layout-shared Modal frame.

### Component-aware context keys are scoped

Tabs, Accordion and Side Panel no longer expose the generic `identifier` component-data key. Side Panel also no longer
exposes its resolved id as `panelId`; the public `panel-id` input prop is unchanged. These generic keys allowed an
unrelated intermediate Blade component to silently replace context consumed through `@aware`.

Update application subcomponents that consume the old keys:

| Component family | Before | After |
| --- | --- | --- |
| Tabs | `@aware(['identifier' => 'tabs'])` | `@aware(['tabsIdentifier' => 'tabs'])` |
| Accordion | `@aware(['identifier' => 'accordion'])` | `@aware(['accordionIdentifier' => 'accordion'])` |
| Side Panel controller | `@aware(['identifier' => 'side-panel'])` | `@aware(['sidePanelIdentifier' => 'side-panel'])` |
| Side Panel panel id | `@aware(['panelId' => null])` | `@aware(['sidePanelPanelId' => null])` |

Tabs triggers and panels, Accordion items, and Side Panel panels and triggers now throw when rendered without their owning
root. Render the owner in the same Blade tree so it can supply the scoped controller, state and ARIA context. For Turbo
Streams, replace the subcomponent's inner content or render the owning root instead of rendering the subcomponent alone.

### Stimulus lazy loader v2 and critical controller policy

The generated controller loader now requires `@emaia/stimulus-lazy-loader ^2.0.0`. Re-run the installer to update an
existing dependency and regenerate `resources/js/controllers/index.js`:

```bash
php artisan hotwire:install --skip-install
bun install
```

Or use `php artisan hotwire:check --fix`; it detects v1 in either `dependencies` or `devDependencies` and updates the
same section.

The v2 loader removes the old fixed debounce and Turbo event listeners. The generated stub can now mix lazy and eager
controllers, driven by the new `controllers.preload` and `controllers.eager` config arrays. Both default to empty, so
upgrading does not move any controller into the initial bundle automatically.

If your app has a hand-written `resources/js/controllers/index.js`, it remains protected. Update its loader dependency
and registry manually, or run `hotwire:install --force` to return to the generated plan.

### Toast is native: Sonner removed, components renamed

The toast stack is now implemented by the package. `@emaia/sonner` is gone from the runtime, the catalog and the
lockfile, and React and React DOM stop being installed as its peers.

**Rename.** `<hw:flash-container>` becomes `<hw:toaster>` and `<hw:flash-message>` becomes `<hw:toast>`. There are no
compatibility aliases — the old tags no longer resolve. The `FlashContainer` and `FlashMessage` classes are now
`Toaster` and `Toast`.

```blade
{{-- before --}}
<hw:flash-container position="bottom-right" />
<hw:flash-message />

{{-- after --}}
<hw:toaster position="bottom-end" />
<hw:toast />
```

The viewport's default `id` changes from `flash-container` to `toaster`, which is also the default Turbo Stream
append target. Update any `->append('flash-container', …)` in your controllers and macros.

**`position` values changed.** `-left` and `-right` become `-start` and `-end`, matching `align` on Popover,
Dropdown and Hover Card, and following the document's writing direction:

| before          | after           |
|-----------------|-----------------|
| `top-left`      | `top-start`     |
| `top-right`     | `top-end`       |
| `bottom-left`   | `bottom-start`  |
| `bottom-right`  | `bottom-end`    |

`top-center` and `bottom-center` are unchanged.

**Props removed.** One line each:

- `rich-colors` — types are shown by glyph now; `error` is the only tinted one, since `destructive` is the only
  status colour in the token set.
- `theme` — the toast inherits `html[data-theme]` through the tokens; there is no theme logic in JavaScript.
- `invert` — a Sonner-ism that competed with the package's own `data-theme`.
- `gap`, `offset`, `mobile-offset` — now the CSS custom properties `--toast-gap`, `--toast-offset` and
  `--toast-mobile-offset`, settable on `[data-slot="toaster"]`.
- `dir` — inherited from the document; the stylesheet uses logical properties.
- `hotkey` — replaced by a landmark plus a fixed <kbd>F6</kbd>, as in Radix and Base UI. The name also collided with
  Button's `hotkey` prop, which means something else entirely.
- `custom-aria-label` — a single label repeated on every toast overrode each card's own text for screen readers.
  Cards are announced from their title and description.
- `swipe-directions` — swipe-to-dismiss is not part of the native implementation. Sonner inherited the gesture from
  its own runtime; here it would have to be written, and it is tracked separately. Toasts are dismissed by the close
  button, which is now always visible, or by their timer.

**Emitting from JavaScript changed.** Applications that imported Sonner directly will break:

```js
// before
import { toast } from "@emaia/sonner/vanilla";
toast.success("Saved");

// after — no import
window.toaster.success("Saved");
```

`window.toaster` now exposes `toast()`, `success()`, `error()`, `warning()`, `info()`, `dismiss(id)` and
`destroy()`. Nothing else on it is contract.

**CSS.** Selectors built on Sonner's internals (`[data-sonner-toast]`, `[data-sonner-toaster]`) no longer match.
Style the package slots instead: `toast`, `toast-icon`, `toast-content`, `toast-body`, `toast-title`,
`toast-description`, `toast-close`, and `toaster` for the viewport. The visual appearance is defined by the preset
and the stack geometry by `structural.css`; see
[the component docs](./components/toaster.md#styling) for the custom properties to tune.

**Visual change.** The card follows the shadcn Toast: popover tokens, `rounded-2xl`, lucide glyphs, close button
always visible, and a collapsed stack that scales and clamps to the frontmost card. It does not match Sonner
pixel for pixel, deliberately.

### Navbar items expose `data-active` instead of `data-current`

Navbar items now carry `data-active="true|false"` for the current-state axis, matching Pagination, Sidebar and the
shadcn vocabulary. `aria-current="page"` is unchanged.

Only application CSS that targets the attribute needs updating; the component API and the `current` prop are the same:

```css
/* before */
[data-slot="navbar-item"][data-current="true"] { … }

/* after */
[data-slot="navbar-item"][data-active="true"] { … }
```

### Component mechanics moved out of the presets

The rules that make a component work rather than look a certain way now live in
`resources/css/structural.css`, which every shipped preset imports: the Accordion `::details-content` collapse, the
carousel track geometry, the top-layer `[popover]` reset and the `@source inline(...)` runtime safelist.

Nothing to do if you import a shipped preset. If you maintain a preset of your own, add the import alongside the
token and custom-variant ones, and delete any copy of those rules you were carrying:

```css
@import "../../vendor/emaia/laravel-hotwire/resources/css/structural.css";
```

The carousel geometry previously arrived from `resources/js/controllers/carousel.css`, imported by the controller. That
file is gone; publishing the carousel controller no longer publishes a stylesheet with it.

### Localize Timeago with Intl

Timeago no longer depends on `date-fns`. It now formats relative times with the browser's
`Intl.RelativeTimeFormat` and `Intl.NumberFormat` APIs. Remove `date-fns` from your application if nothing else uses
it; the package no longer installs or checks for that dependency.

Localized wording may change after upgrading, including automatic terms such as `yesterday` instead of `1 day ago`.
If you previously subclassed the controller to assign a `date-fns` locale, remove the subclass and pass a BCP 47
locale directly:

```blade
<hw:timeago :datetime="$post->created_at" locale="pt-BR" />
```

For raw Stimulus markup, set `data-timeago-locale-value="pt-BR"`. When no locale is provided, Timeago uses the
document's `<html lang>` value and then the browser default.

### Replace Frame Or Page contextual slots

`<hw:frame-or-page>` no longer accepts the eager `frameContent` and `pageContent` named slots. Replace
them with class-based contextual subcomponents. Content outside the subcomponents remains shared:

```diff
 <hw:frame-or-page frame="modal" layout="dashboard">
-    <x-slot:frameContent>Modal controls</x-slot:frameContent>
-    <x-slot:pageContent>Page controls</x-slot:pageContent>
+    <hw:frame-or-page.frame>Modal controls</hw:frame-or-page.frame>
+    <hw:frame-or-page.page>Page controls</hw:frame-or-page.page>
     Shared form
 </hw:frame-or-page>
```

The removed slots throw with migration guidance rather than silently dropping content. The new branches
are lazy: Blade does not evaluate the body of a discarded branch.

To let one route target several hosts, replace `frame` with `frames`, add `layout`, and optionally filter
frame-only content by target:

```blade
<hw:frame-or-page :frames="['modal', 'settings-panel']" layout="dashboard">
    <hw:frame-or-page.frame target="modal">Modal controls</hw:frame-or-page.frame>
    <hw:frame-or-page.frame target="settings-panel">Sheet controls</hw:frame-or-page.frame>
    <hw:frame-or-page.page>Page controls</hw:frame-or-page.page>
    Shared form
</hw:frame-or-page>
```

Exactly one of `frame` or `frames` is required, and more than one configured frame requires `layout`.
Do not put the layout outside `<hw:frame-or-page>`: an external wrapper cannot be skipped for frame
requests. Create a route-specific layout component when you need fixed layout props and pass its name to
the `layout` prop.

### Close overlays explicitly before refresh morphs

Modal, Alert Dialog, Drawer, Sheet, and the mobile Sidebar now preserve their controller-owned presence and active
top-layer attributes while Turbo morphs their contents. An open overlay stays open even when the server response contains
the usual closed `data-state` or `data-mobile-state`, `hidden`, and `inert` attributes.

If an application previously closed an overlay by returning closed markup in a page or Turbo Stream refresh morph, make
the close explicit before rendering that response. Frame-backed Modal, Drawer, and Sheet instances already close through
their empty `update`/`replace` or `refresh` stream lifecycle. For a reusable static Modal, append a
[`modal-auto-close`](./controllers/modal-auto-close.md) marker; custom static overlay flows can call their controller's
public `close()` action (`cancel()` for Alert Dialog) before applying the refresh. Morphing still updates ordinary
descendants and `data-motion`.

### Refresh published Frame Src controllers

`turbo--frame-src` now listens on its own element instead of `document` so one instance cannot affect unrelated frame
requests. Mount it on the submitting form, its Turbo Frame, or another ancestor of the form. Instances on a sibling or
descendant no longer receive the bubbling `turbo:before-fetch-request` event and must be moved.

Applications using the vendor-loaded controller update automatically. Refresh package-owned published copies with:

```bash
php artisan hotwire:check --fix
```

The command will not overwrite a marker-free customized `frame_src_controller.js`; manually port its scoped listener,
nearest-frame source resolution, case-insensitive header handling, and explicit-header preservation.

Laravel Hotwire now requires `emaia/laravel-hotwire-turbo ^0.12.0`. Custom `_turbo_frame_src` inputs and
`X-Turbo-Frame-Src` headers must contain either a root-relative path beginning with exactly one `/`, or an absolute
HTTP(S) URL with a trusted host. Path-relative, query-relative, fragment-relative, malformed, and unsafe values are
rejected. When no explicit or session fallback is safe, validation throws instead of redirecting to `/`.

### Rename Pagination frame targets

Pagination and its `link`, `previous`, and `next` subcomponents now expose only `frame`. Replace `turbo-frame` or
`turboFrame` props with `frame`; there is no compatibility alias. Raw `data-turbo-frame` remains available as a native
attribute on actionable subcomponents.

```diff
- <hw:pagination :paginator="$users" turbo-frame="results" />
+ <hw:pagination :paginator="$users" frame="results" />
```

### Use supported `as` values

Polymorphic `as` values are now normalized and validated. Update custom tags to the component allowlist: Button
`button|a`; Badge `span|a`; Item `div|a|button`; Sticky `div|header|footer|aside|nav|section`; Button Group Text
`div|span|p`; Hover Card Trigger `button|a`; Navbar Item `a|button|span`; Modal Trigger/Close `button|a`; Attachment
Trigger `button|a|div|span`; Conditional Field `fieldset|div`. Native button types are limited to
`button|submit|reset`. Attachment Action delegates its `as` value to Button and therefore uses the same `button|a`
allowlist. Conditional Field prefers `as`; its documented `tag` input remains as a validated compatibility input.

### Update strict `as-child` triggers

`dropdown.trigger as-child` now requires exactly one root `<button>` or `<a>`. Empty slots, text-only content,
non-interactive roots, and multiple root elements now throw during rendering. Wrap custom trigger content in one native
button or anchor before upgrading.

### Keep one managed overlay frame host

Modal, Drawer, and Sheet reject multiple content subcomponents when `frame` is set. Use exactly one matching
`modal.content`, `drawer.content`, or `sheet.content`; do not add a raw `<turbo-frame>` with the same id because the root
component owns that host.

---

## Upgrading to `0.58.0`

`0.58.0` replaces the Dropzone-backed File Upload component and controller with a native file input, drag-and-drop
handling and `XMLHttpRequest` uploads. This is a breaking migration for applications that used Dropzone-specific props,
markup, CSS or controller extensions.

### Replace Dropzone component options

The `options` prop and `preview_template` slot have been removed. Replace Dropzone configuration with the File Upload
props for that behavior, such as `accept`, `max-size-bytes`, `max-files`, `parallel-uploads`, `param-name`, `delete-url`,
`mode` and `output-mode`. Native attachment cards now use the package's reusable
[`Attachment`](components/attachment.md) primitive. When the endpoint returns a raw Turbo Stream body, the component now
uses `mode="turbo-stream"` and disables its built-in card and newly emitted upload-response input automatically:

```blade
<hw:file-upload
    url="{{ route('uploads.store') }}"
    mode="turbo-stream"
/>
```

The old `turbo-stream`, `preview` and `emit-hidden` props are replaced without aliases before this release. Managed JSON
uploads use `output-mode="full|preview|hidden|none"`; `full` is the default. Raw stream responses are server-owned and
require `output-mode="none"`, which is resolved automatically. Explicit `value` and matching `old()` values remain as
preserved hidden inputs for edit and validation round-trips. An optional `stream` string inside a managed JSON response is
a separate hybrid protocol: it works without another prop and preserves the selected output mode.

```diff
- <hw:file-upload turbo-stream />
+ <hw:file-upload mode="turbo-stream" />

- <hw:file-upload :preview="false" :emit-hidden="false" />
+ <hw:file-upload output-mode="none" />

- <hw:file-upload :preview="false" />
+ <hw:file-upload output-mode="hidden" />

- <hw:file-upload :emit-hidden="false" />
+ <hw:file-upload output-mode="preview" />
```

Dropzone `dict*` option names are no longer accepted, and `messages` no longer maps short keys to Dropzone dictionaries.
Use the native keys documented in [`File Upload`](components/file-upload.md#messages), including `idle`, `idleMultiple`,
`hint`, `button`, `uploading`, `uploaded`, `uploadFailed`, `serverRejected`, `clearAll`, `cleared`, `removed`, `removeFile`, `retry`,
`fileTooBig`, `invalidFileType`, `maxFilesExceeded` and `deleteFailed`:

```diff
- :options="['dictDefaultMessage' => 'Drop files', 'dictFileTooBig' => 'Too large']"
+ :messages="['idleMultiple' => 'Drop files', 'fileTooBig' => 'Too large']"
```

There is no native equivalent for Dropzone-only options or messages outside the documented File Upload API.

Named `dropzone` slots now resolve `dropzone-variant="auto"` to the content-sized `bare` surface. The slot owns its
dimensions, aspect ratio, border, background, radius, clipping, hover, focus and state treatment; the package retains
the interaction wiring and an absolute image preview layer that does not affect layout. If a custom slot should keep the
package's dashed drop area, set `dropzone-variant="default"` explicitly.

### Replace Dropzone markup and CSS

Remove custom `.dropzone`, `.dz-*`, `data-dz-*` and `preview_template` markup. Restyle the native UI through its semantic
hooks: `data-slot="file-upload"`, `data-slot="file-upload-dropzone"`, `data-slot="attachment-group"`,
`data-slot="attachment"`, `data-state="idle|uploading|processing|error|done"`, `data-density` and `data-view`. See the
complete [File Upload styling hooks](components/file-upload.md#styling-hooks).

### Port customized controllers

The `defaultOptions()` and `afterInit()` extension hooks, `this.dropzone` instance and Dropzone events have been removed.
Move supported configuration to component props. In controller subclasses, use normal Stimulus lifecycle overrides and
the public `file-upload:*` events instead; call `super.connect()`/`super.disconnect()` when overriding lifecycle methods
and clean up any app listeners in `disconnect()`.

Applications loading the controller from `vendor` receive the native implementation after the Composer update. Refresh
package-owned published copies with:

```bash
php artisan hotwire:check --fix
```

The command replaces outdated files that still carry the `// @hotwire-package` marker. It will not overwrite a
marker-free customized `file_upload_controller.js`; manually port those customizations to the native controller and
remove any Dropzone imports or assumptions.

### Remove Dropzone from the application

File Upload no longer declares `@deltablot/dropzone`. `hotwire:check --fix` does not remove unused application
dependencies, so remove it explicitly with your package manager, for example:

```bash
npm uninstall @deltablot/dropzone
```

Use the equivalent `bun remove`, `pnpm remove` or `yarn remove` command when applicable, and remove any app-level
Dropzone CSS imports.

---

## Upgrading to `0.57.0`

`0.57.0` unifies floating surfaces and modal overlays on the state-driven Presence lifecycle, with actual finite CSS
motion, interruptible rapid reopen, `motion="none"`, and reduced motion.

**Modal overlays.** Modal, Alert Dialog, Drawer, Sheet and mobile Sidebar no longer use fixed JavaScript duration timers
or visual Stimulus classes.

### Replace duration props

Alert Dialog, Drawer and Sheet no longer accept `open-duration` or `close-duration`. Modal and mobile Sidebar no longer
support their equivalent raw Stimulus values. Use the uniform motion API to disable motion:

```diff
- <hw:alert-dialog :open-duration="500" :close-duration="100">
+ <hw:alert-dialog motion="default">

- <hw:drawer :open-duration="450" :close-duration="450">
+ <hw:drawer>

- <hw:sheet :open-duration="300" :close-duration="300">
+ <hw:sheet>

+ <hw:modal motion="none">
+ <hw:sidebar motion="none">
```

`default` is implied. Customize speed in CSS on the animated backdrop or panel instead of passing milliseconds to
JavaScript.

### Replace raw overlay state and classes

Modal, Alert Dialog, Drawer and Sheet overlay targets now start with:

```html
data-state="closed"
data-motion="default"
hidden
inert
```

Replace custom `data-open` selectors with `data-state` selectors. Remove the following visual class attributes for each
controller identifier:

```text
data-*-hidden-class
data-*-visible-class
data-*-backdrop-hidden-class
data-*-backdrop-visible-class
data-*-dialog-hidden-class
data-*-dialog-visible-class
data-*-open-duration-value
data-*-close-duration-value
```

Keep `data-*-lock-scroll-class` when body scroll locking is enabled. Presence owns native `hidden` and `inert`; closed
CSS must define only a visual state and must not use `display: none`.

Scope state rules to the overlay's direct animated children. Broad descendant selectors leak parent state into nested
overlays and can make a child Modal or Alert Dialog open without motion:

```diff
- [data-slot="modal-overlay"][data-state="open"] [data-slot="modal-positioner"] {
+ [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] {
      opacity: 1;
  }
```

Mobile Sidebar preserves `data-state="expanded|collapsed"` for desktop state and uses
`data-mobile-state="open|closed"` for Presence. Put `data-motion="default|none"` on the sidebar surface.

### Review lifecycle timing

- `opened` and `closed` events now follow actual finite CSS motion instead of configured milliseconds.
- Alert Dialog replays the confirmed click only after actual exit motion settles.
- Deferred Turbo Streams and mobile Sidebar navigation also wait for actual exit motion.
- `turbo:before-cache` closes synchronously without restoring trigger focus.
- Target replacement during Turbo morph rebuilds Presence, focus trap and top-layer ownership around the new nodes.
- Rapid reopen invalidates stale close callbacks and top-layer teardown.
- With no transition or finite animation, completion is immediate.

### Refresh published controllers

Vendor-loaded controllers update automatically. Refresh package-owned published copies and transitive helpers with:

```bash
php artisan hotwire:check --fix
```

The command will not overwrite marker-free customized controllers. Port those manually, including the new `_presence.js`
dependency reached through `_overlay.js`.

**Floating surfaces.** Dropdown, Popover, Hover Card, Multi Select and Tooltip replace the class-driven transition engine
with Presence. Exit motion is interruptible, enter waits for resolved Floating UI placement, and the lifecycle coordinates
`hidden`, `inert`, and native top-layer cleanup.

### Replace floating `data-open` selectors

The floating content of all five surfaces now uses `data-state="open|closed"`; Tooltip applies it to its generated
floating element. Replace selectors scoped to floating content:

```diff
- [data-slot="popover-content"][data-open="false"] {
+ [data-slot="popover-content"][data-state="closed"] {
      opacity: 0;
  }
```

Closed server-rendered content starts with `hidden inert`. During exit it is already `data-state="closed"` and inert, but
remains without `hidden` until motion finishes. Do not add `display: none`, a `hidden` utility, or equivalent hiding to a
floating closed-state selector; Presence owns the `hidden` attribute.

Trigger state is namespaced so composing a Dropdown trigger with Toggle, Sidebar, or another controller does not overwrite
that component's generic `data-state`:

| Surface | Trigger state |
|---|---|
| Dropdown | `data-dropdown-state="open|closed"` |
| Popover | `data-popover-state="open|closed"` |
| Hover Card | `data-hover-card-state="open|closed"` |
| Multi Select | `data-multi-select-state="open|closed"` |

`aria-expanded` remains synchronized on each trigger. Replace any trigger-only `data-state` selectors with the matching
namespaced attribute; keep `data-state` selectors on floating content.

This state migration is limited to Dropdown, Popover, Hover Card, Multi Select and Tooltip. Modal-style overlays continue
to use their existing overlay state contract.

### Replace component motion options

Boolean `transition` props have been removed. Use the semantic `default|none` motion API instead:

| Surface | Before | After |
|---|---|---|
| Dropdown content | `<hw:dropdown.content :transition="false">` | `<hw:dropdown.content motion="none">` |
| Popover content | `<hw:popover :transition="false">` | `<hw:popover.content motion="none">` |
| Hover Card content | `<hw:hover-card :transition="false">` | `<hw:hover-card.content motion="none">` |
| Multi Select root | No per-instance option | `<hw:multi-select motion="none" />` |
| Tooltip controller | Fixed built-in timing | `data-tooltip-motion-value="default|none"` |

`default` is the default and can be omitted. Tooltip remains a standalone controller API; use its Stimulus value rather
than a Blade content prop.

### Migrate custom floating CSS

The `_transition.js` helper and all `data-transition-*` attributes have been removed. Delete those attributes from custom
markup and move visual states into CSS keyed by `data-state`:

```css
[data-slot="dropdown-menu"] {
    opacity: 1;
    scale: 1;
    translate: 0 0;
    transition: opacity 150ms ease, scale 150ms ease, translate 150ms ease;
}

[data-slot="dropdown-menu"][data-state="closed"] {
    opacity: 0;
    scale: .95;
    translate: 0 -.25rem;
    pointer-events: none;
}
```

The selected preset transitions only `opacity`, `scale`, and `translate`; it no longer transitions `display`. Custom finite
CSS animations are also supported. Presence suppresses transition and animation while preparing the first placement,
temporarily enforces that suppression for `motion="none"` and `prefers-reduced-motion: reduce`, detects the actual CSS
duration otherwise, and invalidates stale teardown on rapid reopen. CSS transitions reverse naturally from their current
interpolated state.

The Stimulus `hidden` classes were also removed from Dropdown, Popover, Hover Card, and Multi Select. Remove
`data-dropdown-hidden-class`, `data-popover-hidden-class`, `data-hover-card-hidden-class`, and
`data-multi-select-hidden-class` from custom roots, and remove their corresponding class from floating content. Presence
now owns the native `hidden` attribute; a leftover class such as `class="hidden"` will prevent the surface from opening.

### Refresh published controllers

Applications using controllers directly from `vendor` receive the new helpers automatically after Composer updates. If
you published any of the five controllers for customization, refresh package-owned copies and their shared helpers:

```bash
php artisan hotwire:check --fix
```

The command replaces outdated files that still carry the package marker. It refuses to overwrite user-owned files without
that marker; manually port custom changes in those files, remove imports of `_transition.js`, and add `_presence.js` plus
`_top_layer.js` where the updated controller requires them. Delete any stale published `_transition.js` after its imports
are gone.

### Review Dropdown mobile placement

Mobile placement now has priority over collapsed placement as a complete `(side, align)` profile. While `mobile-media`
matches, a missing `mobile-side` falls back to normal `side`, and a missing `mobile-align` falls back to normal `align`.
Neither missing value falls through to `collapsed-side` or `collapsed-align`; collapsed overrides apply only outside the
mobile viewport.

For example, `side="top" align="start" mobile-side="bottom" collapsed-side="right" collapsed-align="end"` resolves to
`bottom-start` on mobile, including inside a collapsed Sidebar.

### Update collapsed Sidebar tooltip selectors

If an icon-only Sidebar uses conditional tooltips, include the mobile state so a persisted desktop collapse does not show
redundant tooltips over visible labels in the mobile drawer:

```html
<!-- Before -->
data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"

<!-- After -->
data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]"
```

### Placement and top-layer behavior

Floating content remains closed and inert until its first placement resolves. Even `open="true"` is server-rendered as
`data-state="closed" hidden inert` to avoid an unpositioned flash; the controller then opens it without enter motion.
Triggers still reflect the configured logical open state. These Floating UI surfaces require Stimulus and do not provide
a no-JavaScript expanded fallback.
`data-side` and `data-align` always describe the resolved placement after any flip, and stale asynchronous results are
ignored. All five surfaces use native top-layer promotion when supported; Tooltip can therefore render above Modal and
Drawer. Toaster remains separate.

`popover:opened` and `hover-card:opened` now fire after the first placement, as soon as content becomes interactive and
enter motion begins. They do not wait for the CSS transition to finish. Their `closed` events continue to fire when
closing begins.

Top-layer promotion changes the containing block. `strategy="fixed"` uses viewport-relative coordinates;
`strategy="absolute"` uses page/document coordinates while native top layer is active, not the nearest positioned
ancestor. In browsers without native Popover support, `absolute` falls back to normal offset-parent behavior and may be
clipped by ancestors.

When the selected preset is not loaded, reset the browser's native Popover positioning defaults with
`[data-hotwire-top-layer][popover] { inset: auto; margin: 0; }` and define border and padding for each floating surface.
Standalone Tooltip CSS must also set `overflow: visible` so its arrow is not clipped.

Target replacement and `turbo:before-cache` now clean up Presence, positioning, and top-layer state immediately.

---

## Upgrading to `0.32.0`

`0.32.0` introduces the design system foundation (semantic tokens, OKLCH palette, dark mode via `data-theme`, `Variants` helper, embedded icon subset). All shipped components were repainted to consume the new tokens — visible without code changes in the host app, but the painted result is different.

### What changes automatically (no action required)

- Modal, Confirm-dialog, Dropdown, Form primitives (Input, Label, Select, Textarea, File, Error, Description), Flash-message, Toaster, Spinner and the auxiliary components ship with the new token-aligned palette and spacing.
- All controllers ship from the vendor directory via `import.meta.glob` — no `php artisan hotwire:controllers <name>` step is required to make a `<hw:*>` work in a fresh app.
- `hotwire:install` adds a `@hotwire` Vite alias to your `vite.config.{ts,mjs,js}` so user code can extend a vendor controller via a clean import (`import CarouselController from '@hotwire/carousel_controller.js'`). The alias is added idempotently — re-running `hotwire:install` is a no-op when the key is already present. If your config doesn't match the Laravel-stock shape, the command prints the snippet for manual paste instead of writing the file. See [extending-controllers.md](extending-controllers.md).
- The `Icon` component (`<hw:icon name="..." />`) replaces inline SVGs in the shipped components.

### hotwire:install dependency modes

The `hotwire:install` command exposes three modes for adding npm dependencies to your app's `package.json`. The default favours zero-friction DX (every component works out of the box); the other two are for projects that want to opt into a leaner footprint.

| Command | What it adds | Loader stub shape |
|---|---|---|
| `php artisan hotwire:install` | Core deps (`@hotwired/stimulus`, `@hotwired/turbo`, `@emaia/stimulus-lazy-loader`) **plus every catalog dep** declared by package controllers (Floating UI, echarts, leaflet, embla-carousel, tiptap stack, maska, sonner). Everything works without further setup. | Globs every package controller — no exclusions |
| `php artisan hotwire:install --with-deps=carousel,chart,map` | Core deps **plus only the npm deps required by the listed controllers**. Accepts comma-separated values or repeated `--with-deps=X` flags. | Globs zero-dep controllers + only the opted-in com-dep controllers; everything else is excluded so `vite build` never resolves their missing imports |
| `php artisan hotwire:install --core-only` | Core deps **only**. No catalog deps. | Globs zero-dep controllers only; every com-dep controller excluded |

End-user runtime cost is identical across the three modes: Vite's dynamic-import code-splitting ships only the chunks for controllers that actually mount in the DOM. The trade-off is purely on the dev side — `node_modules` size, install time and `vite build` time scale with what's installed.

`--core-only` and `--with-deps` are mutually exclusive — the command fails if both are passed.

`--with-deps=<name>` validates each controller name against the catalog and fails fast on a typo.

### Package manager install runs by default

`hotwire:install` runs your package manager (bun / pnpm / yarn / npm, auto-detected from the lockfile) right after writing `package.json`. In interactive mode it prompts with `Run bun install now?` (default yes); in `--no-interaction` mode it runs without prompting.

| Flag | Effect |
|---|---|
| (no flag) | Default behaviour — runs the package manager (prompted in interactive mode, automatic in `--no-interaction`) |
| `--skip-install` | Skip the package manager step entirely; leaves dep fetching to the caller (useful in CI pipelines that wrap their own `npm ci` step) |
| `--fix` | Auto-apply `hotwire:check --fix` during the post-install verification — pairs with `--no-interaction` for end-to-end automation |

Fully-automated install for CI:

```bash
php artisan hotwire:install --with-deps=modal,dropdown --fix --no-interaction
```

This: scaffolds, adds deps + `@hotwire` alias, runs `bun install`, runs `hotwire:check --fix` (regenerates the loader stub and adds any drifted npm deps), runs `bun install` again if needed, and never prompts. The previous `--install` flag has been removed — install is now the default. The previous `hotwire:check --install` is similarly inverted to `--skip-install`.

### Loader stub is now generated

Starting `0.32.0`, `resources/js/controllers/index.js` is **generated** by `hotwire:install` (and re-generated by `hotwire:check --fix`) rather than copied bit-for-bit from a stub. The file starts with this marker:

```js
// AUTO-GENERATED by hotwire:install — DO NOT EDIT MANUALLY.
// Re-run `php artisan hotwire:install` (or `hotwire:check --fix`) to regenerate.
```

`hotwire:install` recognises the marker and silently regenerates the file (no `--force` prompt) when you re-run install with different flags. A hand-written `index.js` without the marker is treated as user-owned and never touched without explicit `--force`.

### Detecting drift between install config and view usage

When you install with `--with-deps=carousel` and later add `<hw:chart>` to a view, the build will succeed (chart is excluded from the stub) but Stimulus won't register the chart controller — the component renders inert. To catch this:

- `hotwire:check` reports `chart  excluded from loader stub  (used in views; re-run install with --with-deps including chart, or hotwire:check --fix)`.
- `hotwire:check --fix` regenerates the stub to include `chart`, and adds the missing npm dep to `package.json`. Run your package manager install command (`bun install`, etc.) afterwards.
- `hotwire:install` automatically runs `hotwire:check` after a `--with-deps` or `--core-only` install, so any drift in pre-existing views surfaces immediately.

### What you must do manually

#### 1. Add the `@source` directive for package CSS

Package styles now live in CSS preset files. Tailwind v4 needs to scan those package CSS files so utilities used in presets and runtime safelists are generated.

Open your application's `resources/css/app.css` and add the package CSS source:

```diff
+ @source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css';
```

Apps installed via `hotwire:install` from `0.33.0` onwards get this automatically — the change applies only to apps installed on an earlier version.

#### 2. Re-publish the CSS stub if you customised it

If you ran `hotwire:install` before `0.32.0` and have *not* customised `resources/css/app.css`, the simplest path is:

```bash
php artisan hotwire:install --only=css --force
```

If you *have* customised the file, copy the new pieces manually:

- `@import "tailwindcss";`
- `@custom-variant turbo-*` / `form-busy` / `frame-busy` / `in-turbo-frame` / `modal` / `dark` directives.
- `@theme inline { … }` block mapping `--color-*` tokens to the underlying CSS variables (used by Tailwind utilities like `bg-primary`, `text-muted-foreground`).
- `@layer base { * { border-color: var(--border); outline-color: var(--ring); } body { background-color: var(--background); color: var(--foreground); } }`.
- `:root { … }` light palette and `[data-theme="dark"] { … }` dark overrides.

Full reference: [`docs/theming.md`](theming.md).

#### 3. Wire up the dark mode trigger (optional)

`[data-theme="dark"]` on `<html>` activates the dark palette. There is no packaged toggle yet. If you want dark mode now, set the attribute yourself (server-side, inline script, or via your own toggle).

```html
<html data-theme="dark">
```

### Visual diff — what apps relying on the old paint will see

If your app *relied* on the prior appearance of shipped components (e.g. screenshots, design specs), expect these substitutions in the rendered HTML:

| Component area | Before (`0.31.x`) | After (`0.32.0`) |
|---|---|---|
| Body background | not styled by the package | `var(--background)` via `@layer base` |
| Modal panel | `bg-white` + `bg-gray-50` borders | `bg-background ring-1 ring-foreground/10` |
| Modal backdrop | `bg-slate-600/80` | `bg-black/10 backdrop-blur-xs` |
| Confirm-dialog confirm | `bg-red-600 hover:bg-red-700 text-white` | `bg-destructive text-destructive-foreground hover:bg-destructive/90` |
| Confirm-dialog cancel | `bg-white border-gray-300 text-gray-700` | `bg-background border-input text-secondary-foreground hover:bg-accent` |
| Input / Textarea / Select | `border-gray-300 bg-white text-gray-900` | `border-input bg-background text-foreground focus-visible:border-ring focus-visible:ring-ring/50` |
| Input error state | `border-red-500` | `aria-invalid:border-destructive aria-invalid:ring-destructive/20` |
| Label | `text-gray-700` | `text-foreground` |
| Description | `text-gray-600` | `text-muted-foreground` |
| Error message | `text-red-600` | `text-destructive` |
| Spinner / Scroll-progress | hardcoded hues | semantic tokens (`text-foreground/50`, `bg-primary`) |
| Inline SVG close buttons | one-off `<svg>` per component | `<hw:icon name="x" />` |

Custom classes you pass through `class="..."` on the component are unaffected — only the package's own defaults moved.

### Verifying the upgrade

1. Run `php artisan hotwire:check` — confirms catalog npm deps are present, reports any controller files diverging from the vendor's `// @hotwire-package` marker.
2. Run `bun run build` (or `vite build`) and visually inspect the resulting `dist/assets/*.css`. Confirm that semantic tokens (`--background`, `--foreground`, `--primary`, …) are defined.
3. Open the components in a browser:
   - Light mode: `<html>` with no `data-theme` attribute.
   - Dark mode: set `data-theme="dark"` on `<html>` and confirm the palette inverts.

### Rollback

If the visual change is disruptive and you need to ship before adopting:

- Pin to `^0.31.0` in `composer.json` until you can schedule the visual migration.
- The class substitutions are not one-way — you can keep overriding the package classes per-component via the `class="..."` attribute on each `<hw:*>` instance if a holistic re-theme is not yet feasible.

---

## Upgrading to `0.33.0`

`0.33.0` moves shipped component styling from inline Tailwind classes in Blade views to CSS presets based on semantic attributes.

### Update `resources/css/app.css`

Re-run the CSS installer if your app has not customised the file:

```bash
php artisan hotwire:install --only=css --force
```

If you customised it, keep your app CSS and add the preset source/import shape manually:

```css
@import "tailwindcss";

@source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css';

@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';
```

Available preset: `nova`.

### Update component CSS overrides

Overrides that targeted package Tailwind classes should move to semantic selectors:

```css
/* Before: coupled to internal classes */
.my-page .bg-primary { ... }

/* After: coupled to component intent */
.my-page [data-slot="button"][data-variant="default"] { ... }
```

Props and public HTML attributes are preserved. Custom `class="..."` values still pass through to the rendered element.
