# Widgets and Turbo snapshots

Turbo caches HTML, not JavaScript instances. A Frame promoted with
`data-turbo-action="advance"` or `replace` captures the entire page before updating
the Frame, including initialized widgets outside it. Restoring that HTML must not
mount another editor, map, or transient overlay over orphaned generated markup.

## Restoration contract

The generated loader installs a dependency-free normalizer before registering
lazy controllers. On `turbo:before-render`, it prepares only
`event.detail.newBody`, only when detached and the render method is not `morph`.
The same synchronous, idempotent preparation applies to history restoration,
cached previews, and ordinary replacement renders. It does not destroy live
instances or change the current document.

Package code records ownership when generating DOM or applying transient
attributes, classes, and styles. Restoration removes those generated roots and
restores only recorded properties. It does not infer ownership from controller
names or library CSS classes, so supported subclasses with custom identifiers use
the same preparation. Templates and unowned application content remain intact.
Morph retains the live node's ownership metadata; application
`data-turbo-permanent` declarations remain under Turbo's normal preservation rules.

The `data-hotwire-snapshot` and `data-hotwire-generated` attributes are internal
bookkeeping, not a public extension API. Do not remove them from initialized DOM.
This prepares HTML for consumption; it is not a cache-writing event or a universal
JavaScript serializer.

Existing apps must regenerate their loader with `php artisan hotwire:check --fix`
or `php artisan hotwire:install` and rebuild assets. The check detects older generated
loader content even when its controller selection, preload and eager policy are unchanged;
regeneration preserves that policy. In a manually maintained loader, install the
integration before lazy controller registration:

```js
import { installSnapshotNormalization } from "../../../vendor/emaia/laravel-hotwire/resources/js/controllers/_snapshot.js";

installSnapshotNormalization(document);
```

## State policy

- **Rich Text:** preserve the synchronized HTML/JSON textarea draft, discard
  generated editor DOM, and mount one editable instance. Selection, undo history,
  and arbitrary plugin state are not serialized.
- **Map:** discard generated Leaflet panes/controls and reapply configured center,
  zoom, markers, and GeoJSON source. User panning, zoom changes, open popups, and
  arbitrary in-memory layers are not automatically serialized. Persist them in
  application state when needed.
- **Tooltip:** discard transient clones and their ARIA description tokens without
  clearing other descriptions or the trigger template. A snapshot opening alone
  does not reopen the tooltip.
- **Overlays:** restore the initial static markup state, retaining explicit
  application-provided initial open state. Shared Presence/top-layer behavior
  covers Modal, Alert Dialog, Sheet, Drawer, Sidebar, Dropdown, Popover, Hover Card,
  and Multi Select. Reusable-host opening clones are discarded rather than
  restored as ownerless dialogs. Scroll-lock cleanup preserves preexisting
  application classes, unrelated body styles and inline padding declaration precedence. Morph preservation and
  Sidebar's desktop state policy remain unchanged.
- **Uploads:** reuse `hydrateItems()`: discard unfinished operations and preserve
  completed attachments/hidden values. A cloned snapshot cannot resume an XHR,
  File-object pipeline, or object URL.
- **Morph guard:** remove package-owned permanent claims from incoming snapshots,
  not permanent declarations owned by the application.
- **Carousel and Chart:** reconstruct Embla/ECharts UI from configuration.
  Runtime-only plugin/chart state is not guaranteed to survive.

## Turbo compatibility and the live document

The regression matrix exercises Turbo **8.0.23** and a test-only variant applying
[Turbo PR #1555](https://github.com/hotwired/turbo/pull/1555), revision `4d2876d`.
The PR is not a released dependency requirement or a patch shipped by this
package. Equivalent document heads isolate this issue from head/frame-only cache
problems.

In the published behavior, a promoted Frame's synthetic Visit can dispatch
`turbo:before-cache` against the page that remains visible, despite caching a
previously detached snapshot. Existing handlers can close live overlays, remove
a temporary tooltip, stop an upload, or tear down a carousel outside the Frame.
Preparing `newBody` fixes subsequent HTML restoration, **not that upstream
notification**. PR #1555 skips that notification for supplied snapshots and removes
temporary elements from the cached copy. No production Turbo internals are patched;
`shouldCacheSnapshot` is not a guaranteed application API.

An upstream preparation event could expose the detached snapshot after recomposing
the previous Frame contents and before cache consumers can use it. It would need
an explicit snapshot target/payload and synchronous mutation/order guarantees,
separate from `before-cache`, whose handlers use `document` and live instances.
It would still require library-specific integration. That proposal is not a
prerequisite for this implementation and does not extend PR #1555.

## Explicit no-cache fallback

For pages that cannot tolerate the published notification behavior or widgets
with unsupported runtime-only state, opt out in the document head **before**
capture:

```blade
<hw:meta cache="no-cache" />
{{-- Equivalent granular component: --}}
<hw:meta.cache control="no-cache" />
```

Ensure Frame responses preserve the page's head policy. `no-preview` suppresses
previews, not Back/Forward snapshot restoration. No-cache does not retroactively
invalidate every previously cached entry. Fetching the page again can lose
unsaved drafts and client-only state: this is an explicit per-page tradeoff, not
the default fix.
