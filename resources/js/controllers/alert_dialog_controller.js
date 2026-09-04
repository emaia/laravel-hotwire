// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { captureAction, replayAction, resolveActionElement } from "./_action_replay.js";
import { createOverlay } from "./_overlay.js";

export default class AlertDialogController extends Controller {
    static targets = ["modal", "backdrop", "dialog", "title", "description", "cancel", "confirm"];

    static classes = ["lockScroll"];

    static values = {
        lockScroll: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
        shared: { type: Boolean, default: false },
        initialFocus: { type: String, default: "auto" },
    };

    pendingAction = null;
    replayingAction = false;
    confirmationInProgress = false;
    overlay = null;
    overlayTargets = null;
    connected = false;
    hasConnected = false;
    overlayRefreshQueued = false;
    disconnectTimer = null;
    sharedDefaults = null;
    sharedTargetsChanged = new Map();
    sharedRefreshQueued = false;
    sharedMorphRefreshQueued = false;
    sharedPendingMorphAttributes = new Map();
    sharedMorphOperations = [];
    beforeMorphAttribute = (event) => this.#trackCanceledSharedMorphAttribute(event);
    morphElement = (event) => this.#refreshSharedMorphPresentation(event);

    get isOpen() {
        return this.overlay?.isOpen ?? false;
    }

    connect() {
        this.connected = true;
        if (this.sharedValue && this.sharedDefaults === null) this.#captureSharedDefaults();
        if (this.sharedValue) {
            this.element.addEventListener("turbo:before-morph-attribute", this.beforeMorphAttribute);
            this.element.addEventListener("turbo:morph-element", this.morphElement);
        }
        if (this.disconnectTimer !== null) {
            clearTimeout(this.disconnectTimer);
            this.disconnectTimer = null;
        }
        if (this.overlay && this.#overlayTargetsMatch()) {
            this.overlay.restoreAfterReconnect();

            return;
        }

        const reopen = this.overlay?.isOpen ?? false;
        const stackPosition = this.overlay?.stackPosition ?? null;
        const topLayerPosition = this.overlay?.topLayerPosition ?? null;
        this.overlay?.cleanup();
        this.overlay = null;
        this.overlayTargets = null;
        this.#setupOverlay(reopen, stackPosition, topLayerPosition, !this.hasConnected);
        this.hasConnected = true;
    }

    disconnect() {
        this.connected = false;
        this.overlayRefreshQueued = false;
        this.sharedRefreshQueued = false;
        this.sharedMorphRefreshQueued = false;
        this.sharedTargetsChanged.clear();
        this.sharedPendingMorphAttributes.clear();
        this.sharedMorphOperations = [];
        this.element.removeEventListener("turbo:before-morph-attribute", this.beforeMorphAttribute);
        this.element.removeEventListener("turbo:morph-element", this.morphElement);
        if (this.disconnectTimer !== null) clearTimeout(this.disconnectTimer);
        this.disconnectTimer = setTimeout(() => {
            this.disconnectTimer = null;
            if (this.connected) return;

            this.pendingAction = null;
            this.replayingAction = false;
            this.confirmationInProgress = false;
            this.#resetSharedPresentation();
            this.overlay?.cleanup();
            this.overlay = null;
            this.overlayTargets = null;
            this.sharedDefaults = null;
        }, 0);
    }

    modalTargetConnected(element) {
        this.#queueOverlayRefresh();
        this.#queueSharedTargetRefresh("modal", element);
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

    titleTargetConnected(element) {
        this.#queueSharedTargetRefresh("title", element);
    }

    descriptionTargetConnected(element) {
        this.#queueSharedTargetRefresh("description", element);
    }

    cancelTargetConnected(element) {
        this.#queueSharedTargetRefresh("cancel", element);
    }

    confirmTargetConnected(element) {
        this.#queueSharedTargetRefresh("confirm", element);
    }

    #setupOverlay(forceOpen = false, stackPosition = null, topLayerPosition = null, focusExisting = false) {
        this.overlayTargets = {
            modal: this.modalTarget,
            backdrop: this.backdropTarget,
            dialog: this.dialogTarget,
        };
        this.overlay = createOverlay(this, {
            modalTarget: this.overlayTargets.modal,
            backdropTarget: this.overlayTargets.backdrop,
            dialogTarget: this.overlayTargets.dialog,
            lockScrollClasses: this.lockScrollClasses,
            lockScroll: this.lockScrollValue,
            closeOnEscape: true,
            escapeCapture: true,
            stopEscapePropagation: true,
            initialFocus: () => this.initialFocusValue,
            initialFocusFallback: () => (this.hasCancelTarget ? this.cancelTarget : this.modalTarget),
            onEscape: () => this.cancel(),
            getTriggerElement: () =>
                this.pendingAction ? resolveActionElement(this.pendingAction, this.element) : null,
        });

        if (forceOpen) {
            this.overlay.setOpen({ notify: false, stackPosition, topLayerPosition });
        } else if (this.modalTarget.dataset.state === "open" && !this.modalTarget.hidden) {
            this.overlay.setOpen({ focus: focusExisting });
        } else {
            this.overlay.closeNow({ restoreFocus: false });
        }
    }

    intercept(event) {
        if (!this.#shouldIntercept(event)) return;

        const trigger = this.#sharedTriggerFor(event);
        if (this.sharedValue && !trigger) return;
        if (this.#sharedTriggerDisabled(trigger)) {
            this.#preventAction(event);

            return;
        }
        if (
            this.sharedValue
            && (this.confirmationInProgress || (this.pendingAction && this.isOpen))
        ) {
            this.#preventAction(event);

            return;
        }

        const action = captureAction(event, this.element);
        if (!action) return;

        this.#intercept(event, action, trigger);
    }

    interceptCapture(event) {
        this.intercept(event);
    }

    #shouldIntercept(event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey) return false;
        if (event.button !== undefined && event.button !== 0) return false;
        if (event.defaultPrevented || this.replayingAction) return false;

        return true;
    }

    #intercept(event, action, trigger = null) {
        this.#preventAction(event);

        this.pendingAction = action;
        if (trigger) this.#applySharedPresentation(trigger);
        this.overlay?.open();
    }

    #preventAction(event) {
        event.preventDefault();
        event.stopPropagation();
    }

    async confirm() {
        if (this.confirmationInProgress) return;

        const action = this.pendingAction;
        this.confirmationInProgress = this.sharedValue && action !== null;
        try {
            this.#refreshTriggerElement();
            const closingOverlay = this.overlay;
            let closed = await closingOverlay?.close();
            if (
                !closed &&
                this.connected &&
                this.pendingAction === action &&
                this.overlay &&
                this.overlay !== closingOverlay
            ) {
                closed = await this.overlay.close();
            }
            if (!closed || this.pendingAction !== action) return;

            const sharedTrigger = this.sharedValue ? this.#currentSharedTrigger() : null;
            this.pendingAction = null;
            this.#resetSharedPresentation();
            let replayed = true;
            this.replayingAction = true;
            try {
                if (action) replayed = (!this.sharedValue || sharedTrigger !== null) && replayAction(action, this.element);
            } finally {
                this.replayingAction = false;
            }

            if (action && !replayed) {
                this.dispatch("dropped", {
                    detail: { kind: action.kind, triggerId: action.targetId || null },
                });
            }
        } finally {
            this.confirmationInProgress = false;
        }
    }

    cancel() {
        this.#refreshTriggerElement();
        const closing = this.overlay?.close();
        this.pendingAction = null;

        if (!this.sharedValue) return closing;

        return Promise.resolve(closing).then((closed) => {
            if (this.pendingAction === null) this.#resetSharedPresentation();

            return closed;
        });
    }

    closeForCache() {
        this.pendingAction = null;
        this.#resetSharedPresentation();
        this.overlay?.closeNow({ restoreFocus: false });
    }

    #sharedTriggerFor(event) {
        if (!this.sharedValue || !(event.target instanceof Element)) return null;

        const trigger = event.target.closest("[data-alert-dialog-trigger]");
        if (!trigger || !this.element.contains(trigger)) return null;

        const owner = trigger.closest('[data-controller~="alert-dialog"][data-alert-dialog-shared-value="true"]');

        return owner === this.element ? trigger : null;
    }

    #sharedTriggerDisabled(trigger) {
        return trigger?.hasAttribute("disabled")
            || trigger?.getAttribute("aria-disabled")?.trim().toLowerCase() === "true";
    }

    #captureSharedDefaults() {
        if (!this.sharedValue) return;

        this.sharedDefaults = {};
        if (this.hasModalTarget) this.#captureSharedTarget("modal", this.modalTarget);
        if (this.hasTitleTarget) this.#captureSharedTarget("title", this.titleTarget);
        if (this.hasDescriptionTarget) this.#captureSharedTarget("description", this.descriptionTarget);
        if (this.hasCancelTarget) this.#captureSharedTarget("cancel", this.cancelTarget);
        if (this.hasConfirmTarget) this.#captureSharedTarget("confirm", this.confirmTarget);
    }

    #captureSharedTarget(name, element, canceledAttributes = new Set(), contentNodes = null) {
        if (this.sharedDefaults === null) this.sharedDefaults = {};

        if (name === "modal") {
            if (!canceledAttributes.has("aria-labelledby")) {
                this.sharedDefaults.labelledBy = element.getAttribute("aria-labelledby");
            }
            if (!canceledAttributes.has("aria-describedby")) {
                this.sharedDefaults.describedBy = element.getAttribute("aria-describedby");
            }

            return;
        }

        if (name === "title" || name === "description") {
            this.sharedDefaults[name] = element.textContent;
            this.sharedDefaults[`${name}Nodes`] = contentNodes ?? [...element.childNodes];
            if (!canceledAttributes.has("hidden")) {
                this.sharedDefaults[`${name}Hidden`] = element.hidden;
            }

            return;
        }

        this.sharedDefaults[`${name}Label`] = element.textContent;
        this.sharedDefaults[`${name}Nodes`] = contentNodes ?? [...element.childNodes];
        if (!canceledAttributes.has("data-variant")) {
            this.sharedDefaults[`${name}Variant`] = element.getAttribute("data-variant");
        }
    }

    #applySharedPresentation(trigger) {
        if (!this.sharedValue || this.sharedDefaults === null) return;

        if (this.hasTitleTarget) {
            let title = this.#triggerOverride(trigger, "data-alert-dialog-title", this.sharedDefaults.title);
            const overridesTitle = this.#hasTriggerOverride(trigger, "data-alert-dialog-title");
            const hasAuthoredLabel = this.hasModalTarget
                && (this.modalTarget.getAttribute("aria-label")?.trim() ?? "") !== "";
            if (title === "" && !hasAuthoredLabel) title = "Confirm action";

            if (overridesTitle || title !== this.sharedDefaults.title) {
                this.titleTarget.textContent = title;
            } else {
                this.#restoreSharedContent("title", this.titleTarget);
            }
            this.titleTarget.hidden = title === "";
            if (this.hasModalTarget) {
                const labelledBy = title === ""
                    ? null
                    : this.sharedDefaults.labelledBy || this.titleTarget.id || null;
                this.#setOptionalAttribute(this.modalTarget, "aria-labelledby", labelledBy);
            }
        }
        if (this.hasDescriptionTarget) {
            const description = this.#triggerOverride(
                trigger,
                "data-alert-dialog-description",
                this.sharedDefaults.description,
                true,
            );
            if (this.#hasTriggerOverride(trigger, "data-alert-dialog-description", true)) {
                this.descriptionTarget.textContent = description;
            } else {
                this.#restoreSharedContent("description", this.descriptionTarget);
            }
            this.descriptionTarget.hidden = description === "";
            if (this.hasModalTarget) {
                const describedBy = description === ""
                    ? null
                    : this.sharedDefaults.describedBy || this.descriptionTarget.id || null;
                this.#setOptionalAttribute(this.modalTarget, "aria-describedby", describedBy);
            }
        }
        if (this.hasCancelTarget) {
            if (this.#hasTriggerOverride(trigger, "data-alert-dialog-cancel-label")) {
                this.cancelTarget.textContent = this.#triggerOverride(
                    trigger,
                    "data-alert-dialog-cancel-label",
                    this.sharedDefaults.cancelLabel,
                );
            } else {
                this.#restoreSharedContent("cancel", this.cancelTarget);
            }
            this.#setOptionalAttribute(
                this.cancelTarget,
                "data-variant",
                this.#triggerOverride(trigger, "data-alert-dialog-cancel-variant", this.sharedDefaults.cancelVariant),
            );
        }
        if (this.hasConfirmTarget) {
            if (this.#hasTriggerOverride(trigger, "data-alert-dialog-confirm-label")) {
                this.confirmTarget.textContent = this.#triggerOverride(
                    trigger,
                    "data-alert-dialog-confirm-label",
                    this.sharedDefaults.confirmLabel,
                );
            } else {
                this.#restoreSharedContent("confirm", this.confirmTarget);
            }
            this.#setOptionalAttribute(
                this.confirmTarget,
                "data-variant",
                this.#triggerOverride(trigger, "data-alert-dialog-confirm-variant", this.sharedDefaults.confirmVariant),
            );
        }
    }

    #resetSharedPresentation() {
        if (!this.sharedValue || this.sharedDefaults === null) return;

        if (this.hasTitleTarget) {
            this.#restoreSharedContent("title", this.titleTarget);
            this.titleTarget.hidden = this.sharedDefaults.titleHidden;
        }
        if (this.hasDescriptionTarget) {
            this.#restoreSharedContent("description", this.descriptionTarget);
            this.descriptionTarget.hidden = this.sharedDefaults.descriptionHidden;
        }
        if (this.hasModalTarget) {
            this.#setOptionalAttribute(this.modalTarget, "aria-labelledby", this.sharedDefaults.labelledBy);
            this.#setOptionalAttribute(this.modalTarget, "aria-describedby", this.sharedDefaults.describedBy);
        }
        if (this.hasCancelTarget) {
            this.#restoreSharedContent("cancel", this.cancelTarget);
            this.#setOptionalAttribute(this.cancelTarget, "data-variant", this.sharedDefaults.cancelVariant);
        }
        if (this.hasConfirmTarget) {
            this.#restoreSharedContent("confirm", this.confirmTarget);
            this.#setOptionalAttribute(this.confirmTarget, "data-variant", this.sharedDefaults.confirmVariant);
        }
    }

    #triggerOverride(trigger, attribute, fallback, allowEmpty = false) {
        if (!trigger.hasAttribute(attribute)) return fallback;

        const value = trigger.getAttribute(attribute);

        return !allowEmpty && value.trim() === "" ? fallback : value;
    }

    #hasTriggerOverride(trigger, attribute, allowEmpty = false) {
        if (!trigger.hasAttribute(attribute)) return false;

        return allowEmpty || trigger.getAttribute(attribute).trim() !== "";
    }

    #restoreSharedContent(name, element) {
        const nodes = this.sharedDefaults?.[`${name}Nodes`];
        if (!nodes) return;

        const current = [...element.childNodes];
        if (current.length === nodes.length && current.every((node, index) => node === nodes[index])) return;

        element.replaceChildren(...nodes);
    }

    #setOptionalAttribute(element, name, value) {
        if (value === null || value === undefined) {
            element.removeAttribute(name);
        } else {
            element.setAttribute(name, value);
        }
    }

    #queueSharedTargetRefresh(name, element) {
        if (!this.connected || !this.sharedValue) return;

        this.sharedTargetsChanged.set(name, element);
        if (this.sharedRefreshQueued) return;

        this.sharedRefreshQueued = true;
        queueMicrotask(() => {
            this.sharedRefreshQueued = false;
            if (!this.connected) return;

            for (const [targetName, target] of this.sharedTargetsChanged) {
                if (target.isConnected) this.#captureSharedTarget(targetName, target);
            }
            this.sharedTargetsChanged.clear();

            const trigger = this.#currentSharedTrigger();
            if (trigger) this.#applySharedPresentation(trigger);
        });
    }

    #refreshSharedMorphPresentation(event) {
        if (!this.connected || !this.sharedValue) return;

        const name = this.#sharedTargetName(event.target);
        if (name === null) return;

        const attributes = this.sharedPendingMorphAttributes.get(event.target) ?? [];
        this.sharedPendingMorphAttributes.delete(event.target);
        // Turbo emits this after child updates, so retain the new authored nodes before overrides are reapplied.
        this.sharedMorphOperations.push({
            name,
            snapshot: event.target.cloneNode(true),
            contentNodes: [...event.target.childNodes],
            attributes,
        });
        if (this.sharedMorphRefreshQueued) return;

        this.sharedMorphRefreshQueued = true;
        queueMicrotask(() => {
            queueMicrotask(() => {
                this.sharedMorphRefreshQueued = false;
                if (!this.connected) return;

                for (const operation of this.sharedMorphOperations) {
                    const canceled = new Set(
                        operation.attributes
                            .filter(({ event: attributeEvent }) => attributeEvent.defaultPrevented)
                            .map(({ attributeName }) => attributeName),
                    );
                    this.#captureSharedTarget(
                        operation.name,
                        operation.snapshot,
                        canceled,
                        operation.contentNodes,
                    );
                }
                this.sharedMorphOperations = [];

                const trigger = this.#currentSharedTrigger();
                if (trigger) this.#applySharedPresentation(trigger);
            });
        });
    }

    #trackCanceledSharedMorphAttribute(event) {
        if (!this.connected || !this.sharedValue || this.#sharedTargetName(event.target) === null) return;

        const attributeName = event.detail?.attributeName;
        if (typeof attributeName !== "string") return;

        const attributes = this.sharedPendingMorphAttributes.get(event.target) ?? [];
        attributes.push({ event, attributeName });
        this.sharedPendingMorphAttributes.set(event.target, attributes);
    }

    #sharedTargetName(element) {
        if (!(element instanceof Element)) return null;

        const owner = element.closest('[data-controller~="alert-dialog"][data-alert-dialog-shared-value="true"]');
        if (owner !== this.element) return null;

        if (this.hasModalTarget && element === this.modalTarget) return "modal";
        if (this.hasTitleTarget && element === this.titleTarget) return "title";
        if (this.hasDescriptionTarget && element === this.descriptionTarget) return "description";
        if (this.hasCancelTarget && element === this.cancelTarget) return "cancel";
        if (this.hasConfirmTarget && element === this.confirmTarget) return "confirm";

        return null;
    }

    #currentSharedActionElement() {
        const actionElement = this.pendingAction ? resolveActionElement(this.pendingAction, this.element) : null;
        const trigger = actionElement?.closest("[data-alert-dialog-trigger]") ?? null;
        const owner = trigger?.closest('[data-controller~="alert-dialog"][data-alert-dialog-shared-value="true"]');

        return owner === this.element ? actionElement : null;
    }

    #currentSharedTrigger() {
        return this.#currentSharedActionElement()?.closest("[data-alert-dialog-trigger]") ?? null;
    }

    #refreshTriggerElement() {
        const trigger = this.sharedValue
            ? this.#currentSharedActionElement()
            : this.pendingAction ? resolveActionElement(this.pendingAction, this.element) : null;
        this.overlay?.setTriggerElement(trigger);
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

            const reopen = this.overlay?.isOpen ?? false;
            const stackPosition = this.overlay?.stackPosition ?? -1;
            const topLayerPosition = this.overlay?.topLayerPosition ?? -1;
            this.overlay?.cleanup();
            this.overlay = null;
            this.overlayTargets = null;
            if (!this.hasModalTarget || !this.hasBackdropTarget || !this.hasDialogTarget) return;

            this.#setupOverlay(reopen, stackPosition, topLayerPosition);
        });
    }

    #overlayTargetsMatch() {
        return this.hasModalTarget && this.hasBackdropTarget && this.hasDialogTarget &&
            this.overlayTargets?.modal === this.modalTarget &&
            this.overlayTargets?.backdrop === this.backdropTarget &&
            this.overlayTargets?.dialog === this.dialogTarget;
    }

    clickOutside(event) {
        event.stopPropagation();

        if (
            this.closeOnClickOutsideValue &&
            this.overlay?.isOpen &&
            this.overlay?.isTop &&
            !this.dialogTarget.contains(event.target) &&
            event.target !== this.dialogTarget
        ) {
            this.cancel();
        }
    }

}
