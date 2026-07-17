// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createOverlay } from "./_overlay.js";

const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;
const MOBILE_QUERY = "(max-width: 767px)";

export default class extends Controller {
    static targets = ["modal", "backdrop", "dialog"];

    static classes = [
        "hidden",
        "visible",
        "backdropHidden",
        "backdropVisible",
        "dialogHidden",
        "dialogVisible",
        "lockScroll",
    ];

    static values = {
        open: { type: Boolean, default: true },
        openDuration: { type: Number, default: 500 },
        closeDuration: { type: Number, default: 300 },
        persist: { type: Boolean, default: true },
        cookieName: { type: String, default: "sidebar_state" },
    };

    connected = false;
    applyingValue = false;
    currentOpen = true;
    currentMobileOpen = false;
    mediaQuery = null;
    overlay = null;
    mobileOpenFrame = null;
    pendingNavigationLink = null;
    skipNavigationLink = null;

    connect() {
        this.currentOpen = this.hasOpenValue ? this.openValue : this.element.dataset.state !== "collapsed";
        this.mediaQuery = window.matchMedia?.(MOBILE_QUERY) ?? null;
        this.mediaQuery?.addEventListener?.("change", this.handleMediaChange);

        if (this.hasModalTarget && this.hasBackdropTarget && this.hasDialogTarget) {
            this.overlay = createOverlay(this, {
                modalTarget: this.modalTarget,
                backdropTarget: this.backdropTarget,
                dialogTarget: this.dialogTarget,
                hiddenClasses: this.hiddenClasses,
                visibleClasses: this.visibleClasses,
                backdropHiddenClasses: this.backdropHiddenClasses,
                backdropVisibleClasses: this.backdropVisibleClasses,
                dialogHiddenClasses: this.mobileDialogHiddenClasses,
                dialogVisibleClasses: this.mobileDialogVisibleClasses,
                lockScrollClasses: this.lockScrollClasses,
                openDuration: this.openDurationValue,
                closeDuration: this.closeDurationValue,
                escapeCapture: true,
                stopEscapePropagation: true,
                onOpen: () => this.syncMobileState("open"),
                onClose: () => {
                    this.syncMobileState("closed");
                    this.followPendingNavigationLink();
                },
                getTriggerElement: () => document.activeElement,
            });
        }

        this.element.addEventListener("click", this.handleNavigationClick, true);
        this.connected = true;
        this.sync();
        this.syncMobileState("closed");
    }

    disconnect() {
        this.element.removeEventListener("click", this.handleNavigationClick, true);
        this.mediaQuery?.removeEventListener?.("change", this.handleMediaChange);
        this.cancelPendingMobileOpen();
        this.pendingNavigationLink = null;
        this.skipNavigationLink = null;
        this.overlay?.cleanup();
        this.connected = false;
    }

    toggle() {
        if (this.isMobile) {
            this.toggleMobile();
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
        this.cancelPendingMobileOpen();

        if (this.currentMobileOpen || this.mobileState !== "closed") {
            this.overlay?.closeNow({ restoreFocus: false });
        } else if (this.hasModalTarget) {
            this.modalTarget.hidden = false;
        }

        this.syncMobileState("closed");
    }

    preserveStateForRender(event) {
        const nextRoot = this.nextRootForRender(event.detail?.newBody);
        if (!nextRoot) return;

        this.applyStateTo(nextRoot, this.currentOpen);
    }

    shortcut(event) {
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

    toggleMobile() {
        this.currentMobileOpen ? this.closeMobile() : this.openMobile();
    }

    openMobile() {
        this.currentMobileOpen = true;
        this.prepareMobileOverlayForOpen();
        this.syncMobileState("opening");

        if (this.hasModalTarget) {
            this.modalTarget.hidden = false;
        }

        this.cancelPendingMobileOpen();
        this.mobileOpenFrame = requestAnimationFrame(() => {
            this.mobileOpenFrame = null;
            if (!this.currentMobileOpen) return;

            this.overlay?.open();
        });
    }

    closeMobile() {
        this.currentMobileOpen = false;
        this.cancelPendingMobileOpen();
        this.syncMobileState("closing");
        this.overlay?.close();
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

    prepareMobileOverlayForOpen() {
        if (!this.hasModalTarget || !this.hasBackdropTarget || !this.hasDialogTarget) return;

        this.modalTarget.classList.add(...this.hiddenClasses);
        this.modalTarget.classList.remove(...this.visibleClasses);
        this.backdropTarget.classList.add(...this.backdropHiddenClasses);
        this.backdropTarget.classList.remove(...this.backdropVisibleClasses);
        this.dialogTarget.classList.add(...this.mobileDialogHiddenClasses);
        this.dialogTarget.classList.remove(...this.mobileDialogVisibleClasses);
    }

    cancelPendingMobileOpen() {
        if (this.mobileOpenFrame === null) return;

        cancelAnimationFrame(this.mobileOpenFrame);
        this.mobileOpenFrame = null;
    }

    handleMediaChange = () => {
        if (!this.isMobile) {
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
        root.dataset[`${this.identifier}OpenValue`] = open ? "true" : "false";
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

        const currentRoots = Array.from(document.querySelectorAll(selector));
        const index = currentRoots.indexOf(this.element);

        return nextRoots[index] ?? null;
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
        return Array.from(root.querySelectorAll('[data-slot="sidebar"][data-sidebar-collapsible]'));
    }

    triggerElementsFor(root) {
        return Array.from(root.querySelectorAll('[data-slot="sidebar-trigger"]'));
    }

    get isMobile() {
        return this.mediaQuery?.matches ?? false;
    }

    get mobileState() {
        return this.sidebarElements[0]?.dataset.mobileState ?? "closed";
    }

    get mobileDialogHiddenClasses() {
        const side = this.hasDialogTarget ? this.dialogTarget.dataset.side : "left";

        return side === "right" ? ["translate-x-full"] : ["-translate-x-full"];
    }

    get mobileDialogVisibleClasses() {
        return ["translate-x-0"];
    }
}
