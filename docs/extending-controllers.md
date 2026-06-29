# Extending controllers

Since `0.32.0`, package controllers auto-load from the vendor directory — your app no longer needs to publish a controller to use it. When you want to *extend* a vendor controller (e.g. build a `gallery` controller on top of the package's `carousel`), there are three paths:

## Option A — Use the `@hotwire` Vite alias (recommended)

`hotwire:install` adds a `@hotwire` alias to your `vite.config.{ts,mjs,js}` pointing at `vendor/emaia/laravel-hotwire/resources/js/`. Use it from your own controllers:

```js
// resources/js/controllers/gallery_controller.js
import CarouselController from "@hotwire/controllers/carousel_controller.js";

export default class extends CarouselController {
    static targets = [...CarouselController.targets, "caption"];

    onSelect(index) {
        super.onSelect(index);
        this.captionTarget.textContent = `Slide ${index + 1}`;
    }
}
```

In your view:

```blade
<div data-controller="gallery">
    ...
</div>
```

Both controllers stay loaded:

- `data-controller="carousel"` continues to resolve to the vendor implementation (auto-loaded).
- `data-controller="gallery"` resolves to your local controller, which extends the vendor's class.

The vendor's `carousel` is the single source of truth — when the package updates, your `gallery` inherits the fix without a manual sync.

## Option B — Fork the parent controller

When you want to *modify* the parent (not extend), use the publish command:

```bash
php artisan hotwire:controllers carousel
```

This copies the vendor's file into `resources/js/controllers/carousel_controller.js`. Your local file then shadows the vendor's via the loader's registration order (`{...packageControllers, ...userControllers}` — user wins).

Edit the local file directly. If you remove the `// @hotwire-package` marker on the first line, `hotwire:check --fix` will leave it alone going forward (status reports as `diverged (user-owned)` instead of `outdated`).

Use this option when extending isn't enough — when you need to change the parent's behaviour for the *same* `data-controller` identifier rather than a new one.

## Option C — Vendor-relative import (no install / no fork)

If `hotwire:install` couldn't auto-add the Vite alias (custom `vite.config.js` shape), you can either paste the snippet the command printed, or import via the vendor-relative path:

```js
// resources/js/controllers/gallery_controller.js
import CarouselController from "../../../vendor/emaia/laravel-hotwire/resources/js/controllers/carousel_controller.js";

export default class extends CarouselController {
    // ...
}
```

Works without any config change. The downsides are ergonomic — the path is verbose, doesn't survive a non-standard `vendor-dir` in `composer.json`, and IDE auto-import won't suggest it.

## Importing helpers

The same alias works for the shared helper modules:

```js
import { createOverlay } from "@hotwire/controllers/_overlay.js";
import { FocusTrap } from "@hotwire/controllers/_focus_trap.js";
import { attachMorphRecovery } from "@hotwire/controllers/_turbo_morph_recovery.js";
```

When you extend a vendor controller without forking, you typically don't need to import its helpers directly — they're already loaded as part of the parent class. Import them yourself only when you're composing a *new* controller from scratch and want to reuse the package's primitives.
