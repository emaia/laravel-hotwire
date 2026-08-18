// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { isComposing } from "./_composition.js";
import { createOverlay } from "./_overlay.js";

const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;
const MOBILE_QUERY = "(max-width: 767px)";
const WRAPPER_SELECTOR = '[data-slot="sidebar-wrapper"]';
const DEFAULT_COOKIE_NAME = "sidebar_state";

export default class extends Controller {
    static targets = ["modal", "backdrop", "dialog"];

    static classes = ["lockScroll"];

    static values = {
        open: { type: Boolean, default: true },
        persist: { type: Boolean, default: true },
        cookieName: { type: String, default: DEFAULT_COOKIE_NAME },
    };

    connected = false;
    applyingValue = false;
    currentOpen = true;
    currentMobileOpen = false;
    mediaQuery = null;
    overlay = null;
    overlayRefreshQueued = false;
    pendingNavigationLink = null;
    skipNavigationLink = null;
    mobileTriggerElement = null;

    connect() {
        this.currentOpen = this.hasOpenValue ? this.openValue : this.element.dataset.state !== "collapsed";
        this.mediaQuery = window.matchMedia?.(MOBILE_QUERY) ?? null;
        this.mediaQuery?.addEventListener?.("change", this.handleMediaChange);

        this.#setupOverlay();

        this.element.addEventListener("click", this.handleNavigationClick, true);
        this.connected = true;
        if (this.isMobile) {
            this.overlay?.closeNow({ restoreFocus: false });
        } else {
            this.revealDesktopOverlay();
        }
        this.sync();
        this.syncMobileState("closed");
    }

    disconnect() {
        this.connected = false;
        this.overlayRefreshQueued = false;
        this.element.removeEventListener("click", this.handleNavigationClick, true);
        this.mediaQuery?.removeEventListener?.("change", this.handleMediaChange);
        this.pendingNavigationLink = null;
        this.skipNavigationLink = null;
        this.mobileTriggerElement = null;
        this.overlay?.cleanup();
        this.overlay = null;
        if (!this.isMobile) this.revealDesktopOverlay();
    }

    modalTargetConnected() {
        this.#queueOverlayRefresh();
    }

    modalTargetDisconnected() {
        this.#queueOverlayRefresh();
    }

    backdropTargetConnected() {
        this.#queueOverlayRefresh();
    }

    backdropTargetDisconnected() {
        this.#queueOverlayRefresh();
    }

    dialogTargetConnected() {
        this.#queueOverlayRefresh();
    }

    dialogTargetDisconnected() {
        this.#queueOverlayRefresh();
    }

    toggle(event) {
        if (this.isMobile) {
            this.toggleMobile(event);
            return;
        }

        this.setOpen(this.element.dataset.state === "collapsed");
    }

    open() {
        this.setOpen(true);
    }

    close() {
        if (this.isMobile && this.currentMobileOpen) {
            this.closeMobile();
            return;
        }

        this.setOpen(false);
    }

    clickOutside(event) {
        if (!this.currentMobileOpen || this.dialogTarget.contains(event.target)) return;
        if (!this.overlay?.isTop) return;

        this.closeMobile();
    }

    closeForCache() {
        this.pendingNavigationLink = null;
        if (this.overlay?.phase !== "closed" || (this.isMobile && this.hasModalTarget && !this.modalTarget.hidden)) {
            this.overlay?.closeNow({ restoreFocus: false });
        }

        this.syncMobileState("closed");
        this.mobileTriggerElement = null;
        if (!this.isMobile) this.revealDesktopOverlay();
    }

    preserveStateForRender(event) {
        const nextRoot = this.nextRootForRender(event.detail?.newBody);
        if (!nextRoot) return;

        this.applyStateTo(nextRoot, this.currentOpen);
    }

    shortcut(event) {
        if (isComposing(event)) return;
        if (event.key?.toLowerCase() !== "b" || (!event.metaKey && !event.ctrlKey)) return;

        event.preventDefault();
        this.toggle();
    }

    openValueChanged() {
        if (this.applyingValue) return;
        this.currentOpen = this.openValue;
        if (!this.connected) return;

        this.sync();
    }

    setOpen(open) {
        this.currentOpen = open;
        this.applyingValue = true;
        this.openValue = open;
        this.applyingValue = false;
        this.sync();

        if (this.persistValue) {
            document.cookie = `${this.cookieNameValue}=${open}; path=/; max-age=${COOKIE_MAX_AGE}`;
        }
        this.dispatch("change", { detail: { open: this.openValue, state: this.state } });
    }

    toggleMobile(event) {
        this.currentMobileOpen ? this.closeMobile() : this.openMobile(event);
    }

    openMobile(event) {
        if (this.currentMobileOpen) return;

        this.mobileTriggerElement = event?.currentTarget ?? document.activeElement;
        this.currentMobileOpen = true;
        this.sync();

        return this.overlay?.open();
    }

    closeMobile() {
        if (!this.currentMobileOpen && !this.overlay?.isOpening) return;

        this.currentMobileOpen = false;
        this.sync();

        return this.overlay?.close();
    }

    handleNavigationClick = (event) => {
        if (!this.shouldDelayNavigation(event)) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        this.pendingNavigationLink = event.target.closest("a[href]");
        this.closeMobile();
    };

    sync() {
        this.applyStateTo(this.element, this.currentOpen, this.isMobile ? this.currentMobileOpen : this.currentOpen);
    }

    syncMobileState(state) {
        const open = state === "open" || state === "opening";

        this.currentMobileOpen = open;
        this.sidebarElements.forEach((sidebar) => {
            sidebar.dataset.mobileState = state;
        });
        this.triggerElements.forEach((trigger) => {
            if (this.isMobile) trigger.setAttribute("aria-expanded", open ? "true" : "false");
        });
    }

    handleMediaChange = () => {
        if (!this.isMobile) {
            this.overlay?.closeNow({ restoreFocus: false });
            this.syncMobileState("closed");
            this.revealDesktopOverlay();
        } else {
            this.overlay?.closeNow({ restoreFocus: false });
            this.syncMobileState("closed");
        }

        this.sync();
    };

    shouldDelayNavigation(event) {
        if (!this.isMobile || !this.currentMobileOpen || event.defaultPrevented) return false;
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return false;
        if (event.button !== undefined && event.button !== 0) return false;

        const link = event.target.closest("a[href]");
        if (!link || !this.element.contains(link) || !this.dialogTarget.contains(link)) return false;
        if (link === this.skipNavigationLink) return false;
        if (link.target && link.target !== "_self") return false;
        if (link.hasAttribute("download")) return false;

        const href = link.getAttribute("href") || "";
        if (href === "" || href.startsWith("#")) return false;
        if (/^(mailto|tel):/i.test(href)) return false;

        return true;
    }

    followPendingNavigationLink() {
        const link = this.pendingNavigationLink;
        this.pendingNavigationLink = null;
        if (!link || !link.isConnected) return;

        this.skipNavigationLink = link;
        link.click();
        this.skipNavigationLink = null;
    }

    applyStateTo(root, open, triggerOpen = open) {
        const state = open ? "expanded" : "collapsed";

        root.dataset.state = state;
        // Not dataset: a kebab-case identifier makes an invalid dataset key, which throws on write.
        root.setAttribute(`data-${this.identifier}-open-value`, open ? "true" : "false");
        this.sidebarElementsFor(root).forEach((sidebar) => {
            sidebar.dataset.state = state;
            const collapsible = sidebar.dataset.sidebarCollapsible || "offcanvas";
            sidebar.dataset.collapsible = open ? "" : collapsible;
        });
        this.triggerElementsFor(root).forEach((trigger) => {
            trigger.setAttribute("aria-expanded", triggerOpen ? "true" : "false");
        });
    }

    nextRootForRender(newBody) {
        if (!newBody) return null;

        const selector = `[data-controller~='${this.identifier}']`;
        const nextRoots = Array.from(newBody.querySelectorAll(selector));

        if (this.element.id) {
            const matchingId = nextRoots.find((root) => root.id === this.element.id);
            if (matchingId) return matchingId;
        }

        const candidates = nextRoots.filter((root) => this.matchesProvider(root));
        const currentRoots = Array.from(document.querySelectorAll(selector)).filter((root) => this.matchesProvider(root));
        const index = currentRoots.indexOf(this.element);

        return candidates[index] ?? null;
    }

    /**
     * Tell whether `root` is this provider rather than another one sharing the identifier.
     *
     * Position alone pairs the wrong roots once the next page drops the outer provider or
     * nests them differently, which would hand a nested provider the outer state. The cookie
     * name and the wrapper depth are what the markup already carries to tell them apart, so
     * an unrecognised root yields no match and the server-rendered state stands.
     */
    matchesProvider(root) {
        return this.cookieNameOf(root) === this.cookieNameValue && this.wrapperDepthOf(root) === this.wrapperDepthOf(this.element);
    }

    cookieNameOf(root) {
        return root.getAttribute(`data-${this.identifier}-cookie-name-value`) ?? DEFAULT_COOKIE_NAME;
    }

    wrapperDepthOf(element) {
        let depth = 0;
        let parent = element.parentElement;

        while (parent) {
            const wrapper = parent.closest(WRAPPER_SELECTOR);
            if (!wrapper) break;

            depth++;
            parent = wrapper.parentElement;
        }

        return depth;
    }

    get state() {
        return this.currentOpen ? "expanded" : "collapsed";
    }

    get sidebarElements() {
        return this.sidebarElementsFor(this.element);
    }

    get triggerElements() {
        return this.triggerElementsFor(this.element);
    }

    sidebarElementsFor(root) {
        return this.ownedBy(root, '[data-slot="sidebar"][data-sidebar-collapsible]');
    }

    triggerElementsFor(root) {
        return this.ownedBy(root, '[data-slot="sidebar-trigger"]');
    }

    /**
     * Match the elements under `root` that no nested provider claims first.
     *
     * The boundary is the wrapper slot rather than the controller identifier, because a
     * nested provider is free to run a custom controller. A root without the slot declares
     * no boundary of its own, so it keeps everything its nearest wrapper doesn't own.
     */
    ownedBy(root, selector) {
        return Array.from(root.querySelectorAll(selector)).filter((element) => {
            const wrapper = element.closest(WRAPPER_SELECTOR);

            return wrapper === root || wrapper === null || !root.contains(wrapper);
        });
    }

    get isMobile() {
        return this.mediaQuery?.matches ?? false;
    }

    get mobileState() {
        return this.sidebarElements[0]?.dataset.mobileState ?? "closed";
    }

    revealDesktopOverlay() {
        if (!this.hasModalTarget) return;

        this.modalTarget.hidden = false;
        this.modalTarget.removeAttribute("inert");
        delete this.modalTarget.dataset.presence;
    }

    #setupOverlay(forceOpen = false, stackPosition = null, topLayerPosition = null) {
        if (!this.hasModalTarget || !this.hasBackdropTarget || !this.hasDialogTarget) return;

        this.overlay = createOverlay(this, {
            modalTarget: this.modalTarget,
            backdropTarget: this.backdropTarget,
            dialogTarget: this.dialogTarget,
            lockScrollClasses: this.lockScrollClasses,
            escapeCapture: true,
            stopEscapePropagation: true,
            stateAttribute: "mobileState",
            onOpen: () => this.syncMobileState("open"),
            onClose: () => {
                this.syncMobileState("closed");
                this.followPendingNavigationLink();
                this.mobileTriggerElement = null;
            },
            getTriggerElement: () => this.mobileTriggerElement ?? document.activeElement,
        });

        if (forceOpen && this.isMobile) this.overlay.setOpen({ notify: false, stackPosition, topLayerPosition });
    }

    #queueOverlayRefresh() {
        if (!this.connected) return;

        if (this.overlayRefreshQueued) return;

        this.overlayRefreshQueued = true;
        queueMicrotask(() => {
            this.overlayRefreshQueued = false;
            if (!this.connected) return;
            if (this.overlay?.isOpening || this.overlay?.isClosing) {
                void this.overlay.settled.then(
                    () => this.#queueOverlayRefresh(),
                    () => this.#queueOverlayRefresh(),
                );

                return;
            }

            const reopen = this.currentMobileOpen && (this.overlay?.isOpen ?? false);
            const stackPosition = this.overlay?.stackPosition ?? -1;
            const topLayerPosition = this.overlay?.topLayerPosition ?? -1;
            this.overlay?.cleanup();
            this.overlay = null;
            if (!this.hasModalTarget || !this.hasBackdropTarget || !this.hasDialogTarget) return;

            this.#setupOverlay(reopen, stackPosition, topLayerPosition);
            if (reopen && this.isMobile) {
                this.syncMobileState("open");
            } else if (this.isMobile) {
                this.overlay?.closeNow({ restoreFocus: false });
                this.syncMobileState("closed");
            } else {
                this.syncMobileState("closed");
                this.revealDesktopOverlay();
            }
        });
    }
}
