# Image Viewer

Display existing image links in a viewer with zoom, pan, pinch gestures, keyboard navigation and optional original
downloads. Source HTML remains server-rendered and can be updated by Turbo without reconnecting the viewer.

## Quick example

```blade
<hw:image-viewer label="Product photos" download>
    <a href="/photos/original.jpg" data-turbo="false">
        <img src="/photos/thumbnail.jpg"
             data-original="/photos/original.jpg"
             data-image-key="photo-42"
             data-caption="Rear panel"
             alt="Rear panel of the camera">
    </a>
</hw:image-viewer>
```

The default slot owns the source HTML and its layout. Keep real links for no-JavaScript fallback. Normal clicks open the
viewer; modified clicks, links with `download`, and links targeting another browsing context retain native behavior.
The component does not fetch a JSON gallery or generate source thumbnails.

## Props

| Prop | Default | Description |
| --- | --- | --- |
| `id` | generated | Stable component root id |
| `label` | `Image viewer` | Accessible dialog and controls group name |
| `download` | `false` | Include an original download link |
| `loop` | `false` | Wrap previous/next navigation |
| `thumbnails` | `false` | Show a thumbnail strip inside the open viewer |
| `motion` | `default` | Preset-driven enter/exit transitions; `none` disables them |
| `labels` | English labels | Control labels keyed by `previous`, `next`, `zoomIn`, `zoomOut`, `zoom`, `reset`, `close`, `download`, `loading`, `error`, `viewport`, `controls`, `thumbnails`, `thumbnail` |
| `stimulus` | `null` | Additional Stimulus attributes on the root |

Other attributes, including `class`, apply to the root. The internal `data-image-viewer-*` namespace is protected.
There is no controller override prop; compose application controllers and the viewer's events instead.

## Image metadata

Attributes belong to each source `<img>`, not the wrapper.

| Attribute | Purpose |
| --- | --- |
| `data-original` | Full image URL; falls back to the enclosing link, then the image source |
| `data-image-key` | Unique stable identity across morph/reordering; falls back to image id, then DOM identity |
| `alt` | Accessible description of the displayed image |
| `data-caption` | Plain-text caption; falls back to `alt`, never interpreted as HTML |
| `data-download-url` | Optional download endpoint; otherwise uses the original URL |
| `data-download-name` | Suggested filename; the server/browser may override it |

Thumbnail `srcset`/`sizes` are not inherited by the full image. Images from nested viewer controllers are not included.
Only HTTP(S), blob and image data URLs are accepted. This URL check is not authorization: protect private media on the server.
Source `crossorigin` and `referrerpolicy` apply to both the original loader and the displayed image, before assigning
their source URL. Each image's policies replace the previous image's policies.

## Interaction and Turbo

- Fit does not upscale small originals. Zoom ranges from fit to 400% of natural dimensions.
- The zoom selector offers Fit, 25%, 50%, 75%, 100%, 150%, 200%, 300% and 400%; 100% means natural image dimensions.
  Percentages below fit are disabled. Wheel, pinch and zoom buttons may produce intermediate values, which the selector displays.
  Arrow keys on the native selector change its zoom choice rather than navigating the gallery.
- Wheel zooms within the image viewport. Double click toggles fit/natural-size zoom. Pinch zooms and a single pointer pans.
- The viewport uses the default cursor when the image cannot pan, `grab` when it can pan and `grabbing` during a drag.
- At fit, a horizontal touch swipe navigates; after pinch, the remaining finger pans rather than navigating.
- `+`/`-` zoom, `0` fits, Home/End select first/last. Left/Right navigate, except while the zoomed viewport itself is
  focused, where all arrow keys pan. Escape closes only the top overlay. Ctrl/Meta browser zoom shortcuts are not intercepted.
- Source additions, removal, reordering and metadata changes update a surviving controller automatically. Stable keys
  preserve selection; removing the active image selects the nearest remaining index. Empty galleries close.
- Morphing the presentation for the same image and request preserves zoom, pan and loading/error feedback without
  restarting the request.
  Navigating to the current selection is a no-op, including loop navigation with one image and Home/End at a boundary.
- If a keyed source replacement retains the opening image, closing returns focus to its replacement control, not to a
  different image selected later. Changing only a fallback link's `href` also updates an open viewer.
- Late loads cannot replace a more recent selection. Failed originals show an error rather than silently enlarging a thumbnail.
- Captions have no character limit or silent truncation. Official presets currently cap their height at 20dvh and scroll
  the full text; this is a layout choice, not a content-length standard. Overflowing captions enter the tab order and
  retain native scrolling keys rather than triggering gallery navigation.
- Cache preparation closes immediately without restoring focus; disconnect releases observers, pointer capture, loads and overlay effects.
- Opening and closing animate the backdrop/panel through the shared Presence lifecycle. Reduced motion and `motion="none"`
  disable those transitions. Image fit uses untransformed viewport dimensions while the panel enters; zoom/pan do not inherit panel transitions.

Rotation, flip, magnifier, automatic slideshow, fullscreen, inline mode and mixed media are not supported.

## Thumbnail navigation

```blade
<hw:image-viewer label="Product photos" thumbnails loop download>
    @foreach ($photos as $photo)
        <a href="{{ $photo->original_url }}" data-turbo="false">
            <img src="{{ $photo->thumbnail_url }}"
                 data-original="{{ $photo->original_url }}"
                 data-image-key="{{ $photo->id }}"
                 alt="{{ $photo->description }}">
        </a>
    @endforeach
</hw:image-viewer>
```

The strip uses the source thumbnail, not the full original. Its selected button has `aria-current="true"` and remains
in view after navigation. Tab enters the strip at that button; Left/Right select and focus an adjacent image in the
strip's reading direction, and Home/End select first/last. The button's accessible label includes its position and
the source image's `alt`. Customize `labels.thumbnail` using `:index` and `:total` placeholders.

Short strips are centered by the official presets. When thumbnails exceed the available width, the strip falls back
to a horizontally scrollable row so both ends remain reachable. The active thumbnail scrolls into view when navigation
changes selection. `loop` wraps previous/next and thumbnail arrow-key navigation from last to first and first to last;
omit it to stop at the ends.

Thumbnail images load lazily, but their buttons are not virtualized. For very large collections, paginate or limit the
source gallery in the application rather than rendering thousands of image controls.

Stable image keys preserve thumbnail buttons and keyboard focus across source updates. Additions, removal, reordering,
responsive thumbnail loads and changed thumbnail URLs update the strip without reconnecting the controller.
Thumbnail markup comes from an internal Blade template; no carousel dependency is needed. This is direct navigation,
not autoplay. With `thumbnails` omitted, existing presentation is unchanged.

## Download

`download` is opt-in. It downloads the original file, not the current zoom/pan presentation. For private media, provide
an authorized endpoint with `Content-Disposition: attachment`. Cross-origin URLs may ignore the HTML download attribute;
the component does not fetch files into Blob memory or bypass CORS. Download links disable Turbo.

## Events

`image-viewer:opened`, `image-viewer:closed` and `image-viewer:changed` bubble from the wrapper.
`changed` fires after a successful image load with `{ index, image }`, where `image` is the source element.

## Styling hooks

- `data-slot="image-viewer"`: structural root.
- `data-slot="image-viewer-source"`: structural source region; layout remains application-owned.
- `data-slot="image-viewer-overlay"`: transparent full-screen top-layer root; `hidden`, `inert`, `data-state` follow the shared overlay lifecycle. The backdrop, not this root, dims the page.
- `data-slot="image-viewer-backdrop"`: backdrop.
- `data-slot="image-viewer-dialog"`: viewer panel.
- `data-slot="image-viewer-toolbar"`: controls group.
- `data-slot="image-viewer-actions"`: structural trailing group for controls and zoom level; the selection counter stays separate at the leading edge.
- `data-slot="image-viewer-button"`: navigation, zoom, reset, download and close controls; native `disabled` where applicable.
- `data-slot="image-viewer-counter"`: selection count.
- `data-slot="image-viewer-zoom"`: native zoom percentage selector with Fit and current intermediate scale.
- `data-slot="image-viewer-zoom-control"`: structural native selector wrapper and decorative inset chevron.
- `data-slot="image-viewer-caption"`: text caption.
- `data-slot="image-viewer-status"`: loading/error feedback, controlled by `hidden`.
- `data-slot="image-viewer-viewport"`: structural clipped interaction area; `data-pan-state="ready|dragging"` indicates available or active pan, and is absent when the image cannot pan.
- `data-slot="image-viewer-image"`: structural displayed image.
- `data-slot="image-viewer-icon"`: structural decorative icon.
- `data-slot="image-viewer-thumbnails"`: optional horizontally scrollable strip.
- `data-slot="image-viewer-thumbnail"`: native selection button; `aria-current="true"` marks the selected image.
- `data-slot="image-viewer-thumbnail-image"`: structural thumbnail content inside its button.

These names are emitted as `data-slot`. Nova and Bloom share the same anatomy. Geometry is supplied by the private
`--image-viewer-width`, `--image-viewer-height`, `--image-viewer-x`, `--image-viewer-y` properties on the displayed image;
do not override those runtime values in a preset.
The image's x/y translation is deliberately physical, measured from the viewport's top-left corner regardless of RTL
reading direction; it must not mirror the bitmap or the pointer pivot.

## Implementation boundary

`_image_viewport.js` owns DOM-free fit/zoom/pan calculations. The controller owns loading, gestures and source
reconciliation. Blade owns all UI markup; the existing overlay helpers own focus, stacking, top layer and scroll lock.
`_image_thumbnails.js` reconciles the Blade-authored thumbnail buttons while retaining their identity and keyboard focus.
