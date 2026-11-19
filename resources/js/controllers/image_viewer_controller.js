// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import { createOverlay } from "./_overlay.js";
import { rememberSnapshotState } from "./_snapshot.js";
import { fitImage, panImage, resizeImage, zoomImage } from "./_image_viewport.js";

export default class extends Controller {
    static targets = [
        "source",
        "overlay",
        "backdrop",
        "dialog",
        "viewport",
        "image",
        "caption",
        "counter",
        "zoomLabel",
        "zoomSelect",
        "loading",
        "error",
        "previous",
        "next",
        "zoomIn",
        "zoomOut",
        "reset",
        "download",
    ];
    static values = { loop: { type: Boolean, default: false } };

    initialize() {
        this.pointers = new Map();
        this.items = [];
        this.captionTabIndexes = new WeakMap();
        this.generation = 0;
        this.onClick = this.activate.bind(this);
        this.onKeydown = this.keydown.bind(this);
        this.onCache = this.closeForCache.bind(this);
        this.onMorph = this.queueRefresh.bind(this);
    }

    connect() {
        this.connected = true;
        this.element.addEventListener("click", this.onClick);
        this.element.addEventListener("keydown", this.onKeydown);
        this.element.addEventListener("turbo:morph-element", this.onMorph);
        document.addEventListener("turbo:before-cache", this.onCache);
        this.observer = new MutationObserver((records) => {
            if (records.some((record) => record.target === this.element || this.source?.contains(record.target))) {
                this.queueRefresh();
            }
        });
        this.observer.observe(this.element, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: [
                "src",
                "srcset",
                "sizes",
                "alt",
                "id",
                "data-original",
                "data-caption",
                "data-image-key",
                "data-download-url",
                "data-download-name",
            ],
        });
        this.refresh();
    }

    disconnect() {
        this.connected = false;
        this.observer?.disconnect();
        this.element.removeEventListener("click", this.onClick);
        this.element.removeEventListener("keydown", this.onKeydown);
        this.element.removeEventListener("turbo:morph-element", this.onMorph);
        document.removeEventListener("turbo:before-cache", this.onCache);
        this.teardownShell();
    }

    sourceTargetConnected() {
        this.queueRefresh();
    }
    sourceTargetDisconnected() {
        this.queueRefresh();
    }
    overlayTargetConnected() {
        this.queueRefresh();
    }
    overlayTargetDisconnected() {
        this.queueRefresh();
    }
    viewportTargetConnected() {
        this.queueRefresh();
    }
    imageTargetConnected() {
        this.queueRefresh();
    }
    imageTargetDisconnected() {
        this.queueRefresh();
    }

    queueRefresh() {
        if (!this.connected || this.refreshQueued) return;
        this.refreshQueued = true;
        queueMicrotask(() => {
            this.refreshQueued = false;
            if (this.connected) this.refresh();
        });
    }

    refresh() {
        const wasOpen = this.overlay?.isOpen;
        const shellChanged =
            this.shell &&
            (!this.hasOverlayTarget ||
                !this.hasViewportTarget ||
                !this.hasImageTarget ||
                this.shell.overlay !== this.overlayTarget ||
                this.shell.viewport !== this.viewportTarget ||
                this.shell.image !== this.imageTarget);
        if (shellChanged) this.teardownShell();
        this.source = this.hasSourceTarget ? this.sourceTarget : null;
        if (
            !this.shell &&
            this.hasOverlayTarget &&
            this.hasBackdropTarget &&
            this.hasDialogTarget &&
            this.hasViewportTarget &&
            this.hasImageTarget
        ) {
            this.setupShell();
        }
        const old = this.active;
        const previousIndex = Math.max(0, this.items.indexOf(old));
        this.items = this.source
            ? [...this.source.querySelectorAll("img")]
                  .filter((img) => img.closest('[data-controller~="image-viewer"]') === this.element)
                  .map((img) => this.describe(img))
            : [];
        if (!this.items.length || !this.shell) {
            this.closeForCache();
            this.active = null;
            return;
        }
        const retained = old && this.items.find((item) => (old.key ? item.key === old.key : item.node === old.node));
        this.active = retained ?? this.items[Math.min(previousIndex, this.items.length - 1)];
        if (wasOpen && shellChanged) {
            this.overlay.setOpen({ notify: false });
            this.loadCurrent();
        } else if (this.overlay.isOpen) {
            const sameIdentity = old && (old.key ? this.active.key === old.key : this.active.node === old.node);
            if (
                !sameIdentity ||
                this.active.url !== old.url ||
                (this.geometry && this.imageTarget.getAttribute("src") !== this.active.url)
            ) {
                this.loadCurrent();
            } else {
                this.syncMetadata();
                this.render();
            }
        }
    }

    describe(node) {
        const link = node.closest("a[href]");
        return {
            node,
            link,
            key: node.dataset.imageKey || node.id || null,
            url: this.safeURL(
                node.dataset.original || link?.getAttribute("href") || node.currentSrc || node.getAttribute("src"),
            ),
            caption: node.dataset.caption ?? node.alt,
            alt: node.alt,
            download: this.safeURL(
                node.dataset.downloadUrl ||
                    node.dataset.original ||
                    link?.getAttribute("href") ||
                    node.currentSrc ||
                    node.getAttribute("src"),
            ),
            filename: node.dataset.downloadName || "",
        };
    }

    safeURL(value) {
        if (!value) return null;
        try {
            const url = new URL(value, this.element.ownerDocument.baseURI);
            if (["http:", "https:", "blob:"].includes(url.protocol) || /^data:image\//i.test(url.href)) return url.href;
        } catch (_) {
            // Invalid source metadata must not become a navigation or executable URL.
        }
        return null;
    }

    setupShell() {
        this.shell = { overlay: this.overlayTarget, viewport: this.viewportTarget, image: this.imageTarget };
        this.shellListeners = new AbortController();
        const listen = (name, handler, options = {}) =>
            this.viewportTarget.addEventListener(name, handler.bind(this), {
                ...options,
                signal: this.shellListeners.signal,
            });
        listen("pointerdown", this.pointerdown);
        listen("pointermove", this.pointermove);
        listen("pointerup", this.pointerup);
        listen("pointercancel", this.cancelPointers);
        listen("lostpointercapture", this.lostCapture);
        listen("wheel", this.wheel, { passive: false });
        listen("dblclick", this.doubleClick);
        listen("dragstart", (event) => event.preventDefault());
        listen("gesturestart", this.gesture, { passive: false });
        listen("gesturechange", this.gesture, { passive: false });
        listen("gestureend", this.gesture, { passive: false });
        this.backdropTarget.addEventListener(
            "click",
            () => {
                if (this.overlay?.isTop) void this.close();
            },
            { signal: this.shellListeners.signal },
        );
        rememberSnapshotState(this.imageTarget, {
            attributes: ["src", "alt", "hidden"],
            styles: ["--image-viewer-width", "--image-viewer-height", "--image-viewer-x", "--image-viewer-y"],
        });
        rememberSnapshotState(this.viewportTarget, { attributes: ["data-pan-state"] });
        this.overlay = createOverlay(this, {
            modalTarget: this.overlayTarget,
            backdropTarget: this.backdropTarget,
            dialogTarget: this.dialogTarget,
            lockScrollClasses: ["overflow-hidden"],
            // A source gallery can live inside a Dropdown whose bubble-phase Escape handler closes its menu.
            escapeCapture: true,
            stopEscapePropagation: true,
            getTriggerElement: () => this.trigger,
            onEscape: () => this.close(),
            onOpen: () => this.dispatch("opened"),
            onClose: () => {
                this.cancelLoad();
                this.cancelPointers();
                this.dispatch("closed");
            },
        });
        this.resizeObserver = new ResizeObserver(() => {
            if (!this.overlay?.isOpen) return;
            this.syncCaptionAccessibility();
            if (!this.geometry) return;
            const rect = this.viewportDimensions();
            this.geometry = resizeImage(this.geometry, rect.width, rect.height);
            this.render();
        });
        this.resizeObserver.observe(this.viewportTarget);
    }

    teardownShell() {
        this.cancelLoad();
        this.cancelPointers();
        this.shellListeners?.abort();
        this.resizeObserver?.disconnect();
        this.overlay?.cleanup();
        this.overlay = null;
        this.shell = null;
    }

    activate(event) {
        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        )
            return;
        if (!this.source?.contains(event.target)) return;
        const trigger = event.target.closest("a, button") ?? event.target.closest("img");
        if (!trigger || trigger.hasAttribute("download") || (trigger.target && trigger.target !== "_self")) return;
        this.refresh();
        const index = this.items.findIndex((item) => item.node === trigger || trigger.contains(item.node));
        if (index < 0 || !this.overlay) return;
        event.preventDefault();
        this.trigger = trigger;
        void this.show(index);
    }

    show(index) {
        const item = this.items[index];
        if (!item || !this.overlay) return;
        this.cancelLoad();
        this.active = item;
        this.syncMetadata();
        const opening = this.overlay.open();
        this.loadCurrent();
        return opening;
    }

    close() {
        this.cancelLoad();
        this.cancelPointers();
        return this.overlay?.close();
    }

    closeForCache() {
        this.cancelLoad();
        this.cancelPointers();
        this.overlay?.closeNow({ restoreFocus: false });
    }

    cancelLoad() {
        this.generation++;
        if (this.loader) {
            this.loader.onload = this.loader.onerror = null;
            this.loader.removeAttribute("src");
            this.loader = null;
        }
        this.geometry = null;
        this.syncPanState();
    }

    loadCurrent() {
        this.cancelLoad();
        this.cancelPointers();
        this.syncMetadata();
        this.imageTarget.hidden = true;
        this.imageTarget.removeAttribute("src");
        this.loadingTarget.hidden = false;
        this.errorTarget.hidden = true;
        this.viewportTarget.setAttribute("aria-busy", "true");
        this.syncControls();
        if (!this.active.url) return this.loadFailed();
        const generation = this.generation;
        const item = this.active;
        const loader = (this.loader = new Image());
        if (item.node.hasAttribute("crossorigin")) loader.crossOrigin = item.node.crossOrigin;
        loader.referrerPolicy = item.node.referrerPolicy;
        loader.onload = () => {
            if (generation !== this.generation || !this.connected || !this.overlay?.isOpen) return;
            const rect = this.viewportDimensions();
            this.geometry = fitImage(rect.width, rect.height, loader.naturalWidth, loader.naturalHeight);
            this.loader = null;
            loader.onload = loader.onerror = null;
            if (!this.geometry) return this.loadFailed();
            this.imageTarget.src = item.url;
            this.imageTarget.alt = item.alt;
            this.loadingTarget.hidden = true;
            this.viewportTarget.removeAttribute("aria-busy");
            this.render();
            this.dispatch("changed", { detail: { index: this.items.indexOf(item), image: item.node } });
        };
        loader.onerror = () => {
            if (generation === this.generation && this.connected) this.loadFailed();
        };
        loader.src = item.url;
    }

    loadFailed() {
        this.geometry = null;
        this.loadingTarget.hidden = true;
        this.errorTarget.hidden = false;
        this.imageTarget.hidden = true;
        this.viewportTarget.removeAttribute("aria-busy");
        this.syncControls();
    }

    syncMetadata() {
        this.captionTarget.textContent = this.active.caption;
        this.syncCaptionAccessibility();
        this.imageTarget.alt = this.active.alt;
        this.counterTarget.textContent = `${this.items.indexOf(this.active) + 1} / ${this.items.length}`;
        if (this.hasDownloadTarget) {
            const link = this.downloadTarget;
            const url = this.active.url && this.active.download;
            url ? link.setAttribute("href", url) : link.removeAttribute("href");
            link.setAttribute("download", this.active.filename);
            link.hidden = !url;
        }
        this.syncControls();
    }

    syncCaptionAccessibility() {
        if (!this.hasCaptionTarget) return;
        const caption = this.captionTarget;
        if (!this.captionTabIndexes.has(caption)) {
            this.captionTabIndexes.set(caption, caption.getAttribute("tabindex"));
            rememberSnapshotState(caption, { attributes: ["tabindex"] });
        }
        const tabIndex = caption.scrollHeight > caption.clientHeight + 1 ? "0" : this.captionTabIndexes.get(caption);
        tabIndex === null ? caption.removeAttribute("tabindex") : caption.setAttribute("tabindex", tabIndex);
    }

    syncControls() {
        const focused = this.element.ownerDocument.activeElement;
        const index = this.items.indexOf(this.active);
        if (this.hasPreviousTarget)
            this.previousTarget.disabled = this.items.length < 2 || (!this.loopValue && index <= 0);
        if (this.hasNextTarget)
            this.nextTarget.disabled = this.items.length < 2 || (!this.loopValue && index >= this.items.length - 1);
        if (this.hasZoomInTarget)
            this.zoomInTarget.disabled = !this.geometry || this.geometry.scale >= this.geometry.maxScale;
        if (this.hasZoomOutTarget) this.zoomOutTarget.disabled = !this.geometry || this.atFit;
        if (this.hasResetTarget) this.resetTarget.disabled = !this.geometry || this.atFit;
        if (this.hasZoomLabelTarget)
            this.zoomLabelTarget.textContent = this.geometry ? `${Math.round(this.geometry.scale * 100)}%` : "";
        this.syncZoomSelect();
        if (focused?.disabled && this.overlay?.isOpen && this.overlay.isTop && this.dialogTarget.contains(focused)) {
            const preferred =
                focused === this.nextTargets[0]
                    ? this.previousTargets[0]
                    : focused === this.previousTargets[0]
                      ? this.nextTargets[0]
                      : focused === this.zoomInTargets[0]
                        ? this.zoomOutTargets[0]
                        : this.zoomInTargets[0];
            const available = [
                preferred,
                ...this.previousTargets,
                ...this.nextTargets,
                ...this.zoomInTargets,
                ...this.zoomOutTargets,
                this.dialogTarget.querySelector("button:not([disabled]), a[href]"),
                this.dialogTarget,
            ].find((element) => element && !element.disabled && !element.closest("[hidden], [inert]"));
            available?.focus({ preventScroll: true });
        }
    }

    syncZoomSelect() {
        if (!this.hasZoomSelectTarget) return;
        const select = this.zoomSelectTarget;
        select.disabled = !this.geometry;
        if (!this.geometry) return;
        const { scale, minScale, maxScale } = this.geometry;
        const fixed = [...select.options].filter((option) => Number(option.value) > 0);
        for (const option of fixed) {
            const unavailable = Number(option.value) < minScale - 0.0001 || Number(option.value) > maxScale;
            if (option.disabled !== unavailable) option.disabled = unavailable;
        }
        const fit = select.querySelector('option[value="fit"]');
        if (fit) {
            const label = `${fit.dataset.label ?? "Fit"} (${Math.round(minScale * 100)}%)`;
            if (fit.textContent !== label) fit.textContent = label;
        }
        const exact = fixed.find((option) => Math.abs(Number(option.value) - scale) < 0.0001);
        const value = this.atFit ? "fit" : (exact?.value ?? "current");
        const current = select.querySelector('option[value="current"]');
        if (current) {
            current.hidden = value !== "current";
            const label = `${Math.round(scale * 100)}%`;
            if (current.textContent !== label) current.textContent = label;
        }
        select.value = value;
    }

    changeZoom(event) {
        const value = event.target.value;
        if (value === "fit") this.reset();
        else if (value !== "current") this.zoomTo(Number(value));
    }

    render() {
        if (this.geometry) {
            const state = this.geometry;
            const style = this.imageTarget.style;
            style.setProperty("--image-viewer-width", `${state.naturalWidth * state.scale}px`);
            style.setProperty("--image-viewer-height", `${state.naturalHeight * state.scale}px`);
            style.setProperty("--image-viewer-x", `${state.x}px`);
            style.setProperty("--image-viewer-y", `${state.y}px`);
            this.imageTarget.hidden = false;
        }
        this.syncPanState();
        this.syncControls();
    }

    syncPanState() {
        const viewport = this.shell?.viewport;
        if (!viewport) return;
        const state = this.geometry;
        const pannable =
            state &&
            (state.naturalWidth * state.scale > state.viewportWidth + 0.01 ||
                state.naturalHeight * state.scale > state.viewportHeight + 0.01);
        if (!pannable) {
            viewport.removeAttribute("data-pan-state");
            return;
        }
        viewport.dataset.panState = this.pointers.size === 1 && !this.drag?.swipe ? "dragging" : "ready";
    }

    get atFit() {
        return !this.geometry || this.geometry.scale <= this.geometry.minScale + 0.0001;
    }

    previous() {
        this.navigate(-1);
    }
    next() {
        this.navigate(1);
    }
    navigate(step) {
        let index = this.items.indexOf(this.active) + step;
        if (this.loopValue) index = (index + this.items.length) % this.items.length;
        if (index >= 0 && index < this.items.length) void this.show(index);
    }

    zoomIn() {
        this.zoomTo(this.geometry?.scale * 1.25);
    }
    zoomOut() {
        this.zoomTo(this.geometry?.scale / 1.25);
    }
    zoomTo(scale, x, y) {
        if (!this.geometry) return;
        this.geometry = zoomImage(this.geometry, scale, x, y);
        this.render();
    }

    reset() {
        if (!this.geometry) return;
        const { viewportWidth, viewportHeight, naturalWidth, naturalHeight } = this.geometry;
        this.geometry = fitImage(viewportWidth, viewportHeight, naturalWidth, naturalHeight);
        this.render();
    }

    keydown(event) {
        if (!this.overlay?.isOpen || !this.overlay.isTop || !this.dialogTarget.contains(event.target)) return;
        if (event.target === this.captionTarget) return;
        if (
            event.isComposing ||
            event.ctrlKey ||
            event.metaKey ||
            event.altKey ||
            event.target.closest("input, textarea, select, [contenteditable]")
        )
            return;
        const directions = { ArrowLeft: [-40, 0], ArrowRight: [40, 0], ArrowUp: [0, -40], ArrowDown: [0, 40] };
        if (!this.atFit && event.target === this.viewportTarget && directions[event.key]) {
            this.geometry = panImage(this.geometry, ...directions[event.key]);
            this.render();
        } else if (event.key === "ArrowLeft") this.previous();
        else if (event.key === "ArrowRight") this.next();
        else if (event.key === "Home") void this.show(0);
        else if (event.key === "End") void this.show(this.items.length - 1);
        else if (["+", "="].includes(event.key)) this.zoomIn();
        else if (event.key === "-") this.zoomOut();
        else if (event.key === "0") this.reset();
        else return;
        event.preventDefault();
        event.stopPropagation();
    }

    point(event) {
        const rect = this.viewportTarget.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    }

    // Measure layout pixels so the panel's enter/exit scale never changes image fit.
    viewportDimensions() {
        return { width: this.viewportTarget.clientWidth, height: this.viewportTarget.clientHeight };
    }

    pointerdown(event) {
        if (
            !this.geometry ||
            !this.overlay?.isTop ||
            this.overlay.isOpening ||
            (event.pointerType === "mouse" && (event.button !== 0 || event.ctrlKey))
        )
            return;
        event.preventDefault();
        const point = this.point(event);
        if (!this.pointers.size) this.drag = { ...point, swipe: this.atFit && event.pointerType === "touch" };
        this.pointers.set(event.pointerId, point);
        if (this.pointers.size > 1) this.drag.swipe = false;
        this.viewportTarget.setPointerCapture(event.pointerId);
        this.syncPanState();
    }

    pointermove(event) {
        if (!this.geometry || !this.pointers.has(event.pointerId)) return;
        event.preventDefault();
        const before = [...this.pointers.values()];
        this.pointers.set(event.pointerId, this.point(event));
        const after = [...this.pointers.values()];
        if (after.length > 1) {
            const center = (points) => ({ x: (points[0].x + points[1].x) / 2, y: (points[0].y + points[1].y) / 2 });
            const distance = (points) => Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
            const oldCenter = center(before);
            const newCenter = center(after);
            const oldDistance = distance(before);
            if (oldDistance > 0)
                this.geometry = zoomImage(
                    this.geometry,
                    (this.geometry.scale * distance(after)) / oldDistance,
                    oldCenter.x,
                    oldCenter.y,
                );
            this.geometry = panImage(this.geometry, newCenter.x - oldCenter.x, newCenter.y - oldCenter.y);
        } else if (!this.drag?.swipe) {
            this.geometry = panImage(this.geometry, after[0].x - before[0].x, after[0].y - before[0].y);
        }
        this.render();
    }

    pointerup(event) {
        if (!this.pointers.has(event.pointerId)) return;
        const last = this.point(event);
        this.pointers.delete(event.pointerId);
        if (this.viewportTarget.hasPointerCapture(event.pointerId))
            this.viewportTarget.releasePointerCapture(event.pointerId);
        if (!this.pointers.size && this.drag?.swipe) {
            const dx = last.x - this.drag.x;
            const dy = last.y - this.drag.y;
            if (
                Math.abs(dx) > Math.max(50, Math.min(120, this.geometry.viewportWidth * 0.15)) &&
                Math.abs(dx) > Math.abs(dy) * 1.5
            ) {
                this.navigate(dx > 0 ? -1 : 1);
            }
        }
        if (!this.pointers.size) this.drag = null;
        this.syncPanState();
    }

    lostCapture(event) {
        if (this.pointers.has(event.pointerId)) this.cancelPointers();
    }

    cancelPointers() {
        const ids = [...this.pointers.keys()];
        this.pointers.clear();
        this.drag = null;
        this.gestureScale = null;
        for (const id of ids) {
            if (this.shell?.viewport.hasPointerCapture(id)) this.shell.viewport.releasePointerCapture(id);
        }
        this.syncPanState();
    }

    wheel(event) {
        if (!this.geometry || !this.overlay?.isTop || this.overlay.isOpening) return;
        event.preventDefault();
        if (this.gestureScale !== null && this.gestureScale !== undefined) return;
        const delta =
            event.deltaY * (event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? this.geometry.viewportHeight : 1);
        const point = this.point(event);
        this.zoomTo(this.geometry.scale * Math.exp(-Math.max(-100, Math.min(100, delta)) * 0.002), point.x, point.y);
    }

    doubleClick(event) {
        if (!this.geometry || this.overlay?.isOpening) return;
        event.preventDefault();
        const point = this.point(event);
        this.zoomTo(this.atFit ? Math.max(1, this.geometry.minScale * 2) : this.geometry.minScale, point.x, point.y);
    }

    gesture(event) {
        if (!this.geometry || !this.overlay?.isTop || this.overlay.isOpening) return;
        event.preventDefault();
        if (this.pointers.size > 1) return;
        if (event.type === "gesturestart") this.gestureScale = this.geometry.scale;
        if (event.type === "gesturechange" && this.gestureScale) this.zoomTo(this.gestureScale * event.scale);
        if (event.type === "gestureend") this.gestureScale = null;
    }
}
