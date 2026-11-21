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
| `toggleTools` | Expand or collapse the `tools` group and update `aria-expanded` on `toolsToggle` |
| `reset` | Fit the current orientation without upscaling or clearing rotation/flips |
| `rotateLeft`, `rotateRight` | Rotate the displayed image 90° in the requested direction |
| `flipHorizontal`, `flipVertical` | Mirror the displayed image across the corresponding screen axis |
| `close` | Close the viewer and return focus |
| `closeForCache` | Close immediately without returning focus |

Clicks on the source region, keyboard events, pointer/wheel gestures, source observation and cache preparation are
connected automatically. Application event handlers can wire the actions without replacing controller identifiers.

## Markup contract

Use the Blade component for the supported presentation. When using the controller directly, provide the same
server-rendered anatomy: `source`, `overlay`, `backdrop`, `dialog`, `viewport`, `image`, `caption`, `counter`, `loading`,
and `error` targets. Optional targets are `previous`, `next`, `zoomIn`, `zoomOut`, `reset`, `zoomLabel`, `zoomSelect`,
`rotateLeft`, `rotateRight`, `flipHorizontal`, `flipVertical`, `download`, `tools` and `toolsToggle`.
The overlay starts `hidden inert`; the dialog needs an accessible name, dialog semantics and `tabindex="-1"`.
Opening focuses this dialog rather than its first control; the shipped CSS removes its focus outline because the
dialog is not an operable control. Keyboard/swipe navigation uses its reading direction;
arrow-key pan inside a zoomed viewport remains physical.
The displayed image starts hidden with no source.
The controller marks only that image `aria-busy` while loading; keep live status messages outside the busy subtree.
The dialog's `data-size="full"` fills the viewport; shipped presets remove its window border, corners and shadow.
This is a CSS presentation option, not native browser fullscreen.

An optional `zoomSelect` target offers `fit`, numeric natural-scale values (`1` = 100%) and a disabled `current` option
for intermediate values. The `fit` option can carry a translated `data-label`.
Rotation and flip controls use their matching action/target names. Flip buttons are momentary actions without
`aria-pressed`; orientation controls are disabled while no image is ready.
Side arrows reuse `previous`/`next` targets and actions outside the gesture viewport, without duplicating toolbar controls.

A touch or pen tap on the viewport toggles `data-controls="hidden"` on the dialog after a short double-tap window;
a double tap toggles zoom instead, and the browser's emulated `dblclick` is ignored for those pointers. Swipes, drags
and pinches never toggle. Opening clears the attribute, and focus entering the dialog outside the viewport clears it too.
The optional `tools` target wraps zoom and orientation controls behind a `toolsToggle` disclosure button
(`aria-controls` naming the group); the controller sets `data-state="open|closed"` on the group and collapses it when
the controls hide or the viewer opens. Structural CSS shows the trigger and collapses the group only on touch screens.
The optional `zoomLabel` target receives the current percentage; the component draws it over a narrower zoom selector
on touch screens.
Put the caption before the thumbnail strip. The component owns the structural stage around the viewport and arrows.

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
