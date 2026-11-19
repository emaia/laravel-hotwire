# Image Viewer

Enhances image links with zoom, pan, pinch gestures, keyboard navigation and automatic gallery updates.
See [Image Viewer](../components/image-viewer.md) for image metadata, interaction and supported features.

**Identifier:** `image-viewer`.

## Values

| Value | Type | Default | Description |
| --- | --- | --- | --- |
| `loop` | Boolean | `false` | Wrap previous/next navigation |

## Actions

| Action | Description |
| --- | --- |
| `previous`, `next` | Select the adjacent image |
| `zoomIn`, `zoomOut` | Zoom around the viewport center |
| `changeZoom` | Apply the native zoom selector's percentage or Fit value |
| `selectThumbnail` | Select the image identified by the thumbnail button's `index` action parameter |
| `reset` | Fit the current original without upscaling |
| `close` | Close the viewer and return focus |
| `closeForCache` | Close immediately without returning focus |

Clicks on the source region, keyboard events, pointer/wheel gestures, source observation and cache preparation are
connected automatically. Application event handlers can wire the actions without replacing controller identifiers.

## Markup contract

Use the Blade component for the supported presentation. When using the controller directly, provide the same
server-rendered anatomy: `source`, `overlay`, `backdrop`, `dialog`, `viewport`, `image`, `caption`, `counter`, `loading`,
and `error` targets. Optional targets are `previous`, `next`, `zoomIn`, `zoomOut`, `reset`, `zoomLabel`, `zoomSelect`, and `download`.
The overlay starts `hidden inert`; the dialog needs an accessible name, dialog semantics and `tabindex="-1"`.
The displayed image starts hidden with no source.
The controller marks only that image `aria-busy` while loading; keep live status messages outside the busy subtree.
The dialog's `data-size="full"` fills the viewport; shipped presets remove its window border, corners and shadow.
This is a CSS presentation option, not native browser fullscreen.

An optional `zoomSelect` target offers `fit`, numeric natural-scale values (`1` = 100%) and a disabled `current` option
for intermediate values. The `fit` option can carry a translated `data-label`.

Optional thumbnail navigation uses a `thumbnails` target and a `thumbnailTemplate` target. Supply both together:
the list is controller-owned and starts empty; the template contains one native button with an image,
`data-action="image-viewer#selectThumbnail"` and a translated `data-label` with `:index`/`:total` placeholders.
Missing or malformed templates are configuration errors, not a fallback to JavaScript-generated UI.
The strip uses roving keyboard focus and native scrolling; see the component's thumbnail example.

The controller does not generate a fallback toolbar or accept HTML render callbacks. Keep the image source region
separate from the presentation so updates do not observe the controller's own rendering.
The geometry helper is internal, not a separately supported JavaScript API.

## Events

| Event | Detail |
| --- | --- |
| `image-viewer:opened` | — |
| `image-viewer:changed` | `{ index, image }`, source element after successful load |
| `image-viewer:closed` | — |

## Lifecycle

The source observer reconciles metadata and list changes without reconnecting Stimulus. Shell replacement rebuilds only
the presentation bindings, while stable image keys retain selection. Use unique `data-image-key` values for server-rendered
replacements. Disconnect aborts listeners, releases pointer capture, disconnects observers, invalidates pending image
callbacks and cleans the shared overlay. Cache preparation closes transient presentation.
