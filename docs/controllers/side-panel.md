# Side Panel Controller

Controls an inline panel's expanded state, accessibility attributes, and cookie persistence.

**Identifier:** `side-panel`

## Values

| Value        | Type      | Default | Description                                  |
| ------------ | --------- | ------- | -------------------------------------------- |
| `open`       | `boolean` | `true`  | Current expanded state.                      |
| `persist`    | `boolean` | `true`  | Whether changes write a cookie.              |
| `name`       | `string`  | none    | Stable identity used to match Turbo renders. |
| `cookieName` | `string`  | none    | Cookie written when persistence is enabled.  |

Persistence is a no-op when `cookieName` is absent. The Blade component always supplies a name derived from its required
`name` prop.

## Targets

| Target    | Description                            |
| --------- | -------------------------------------- |
| `panel`   | Receives `inert` while collapsed.      |
| `trigger` | Receives synchronized `aria-expanded`. |

Stimulus excludes targets owned by nested instances of the same controller. This makes nested Side Panels independent
without document-wide selectors or instance ids.

## Actions

| Action                   | Description                                           |
| ------------------------ | ----------------------------------------------------- |
| `toggle`                 | Toggle expanded/collapsed state.                      |
| `open`                   | Expand the panel.                                     |
| `close`                  | Collapse the panel.                                   |
| `preserveStateForRender` | Copy current state into Turbo's next body pre-render. |

## Events

Changes dispatch `<identifier>:change`; the default identifier emits `side-panel:change`:

```js
{ open: boolean, state: "expanded" | "collapsed" }
```

## Standalone Usage

```html
<div
    data-controller="side-panel"
    data-side-panel-name-value="project-navigation"
    data-side-panel-open-value="true"
    data-side-panel-cookie-name-value="side_panel_project-navigation_state"
    data-state="expanded"
>
    <aside id="project-navigation-panel" data-side-panel-target="panel">Navigation</aside>

    <button
        type="button"
        data-side-panel-target="trigger"
        data-action="side-panel#toggle"
        aria-controls="project-navigation-panel"
        aria-expanded="true"
    >
        Toggle
    </button>
</div>
```
