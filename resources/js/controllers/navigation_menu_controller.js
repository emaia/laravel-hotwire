// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import { isComposing } from "./_composition.js";
import { createFloating } from "./_floating.js";
import { getFocusableElements } from "./_focus_trap.js";
import { createTopLayer } from "./_top_layer.js";

const ESCAPE_SCOPE = "data-hotwire-escape-scope";
const ESCAPE_BOUNDARY = `[${ESCAPE_SCOPE}], [data-hotwire-top-layer], dialog[open]`;
const VIEWPORT_PROPERTY = "--navigation-menu-viewport-";
const FLOATING_PROPERTIES = ["--available-width", "--available-height", "--anchor-width", "--anchor-height", "--transform-origin"];

export default class extends Controller {
    static targets = ["list", "trigger", "link", "content", "viewport"];
    static values = {
        hover: { type: Boolean, default: true },
        openDelay: { type: Number, default: 100 },
        closeDelay: { type: Number, default: 180 },
    };

    disconnect() {
        this.close();
    }

    triggerTargetDisconnected(trigger) {
        if (this.active?.trigger === trigger) this.close();
    }

    contentTargetDisconnected(content) {
        if (this.active?.content === content) this.close();
    }

    viewportTargetDisconnected(viewport) {
        if (this.active?.viewport === viewport) this.close();
    }

    hoverTrigger(event) {
        if (!this.mouseHover(event) || this.panelHasFocus()) return;
        const trigger = event.currentTarget;
        if (this.active || this.openDelayValue <= 0) {
            this.open(trigger);
            return;
        }
        this.cancelOpen();
        this.openTimer = setTimeout(() => this.open(trigger), this.openDelayValue);
    }

    hoverLink(event) {
        if (this.mouseHover(event) && this.isInList(event.currentTarget) && !this.panelHasFocus()) this.close();
    }

    toggle(event) {
        if (event.defaultPrevented) return;
        this.toggleTrigger(event.currentTarget);
    }

    toggleTrigger(trigger) {
        if (this.active?.trigger === trigger && this.active.activated) this.close();
        else this.open(trigger, { activated: true });
    }

    focusOut({ target, relatedTarget }) {
        if (!this.active || !relatedTarget) return;
        const { trigger, content } = this.active;
        if (relatedTarget === trigger || content.contains(relatedTarget)) return;
        if (target === trigger || content.contains(target) || !this.element.contains(relatedTarget)) this.close();
    }

    keydown(event) {
        if (event.defaultPrevented || isComposing(event)) return;
        const { target, key, shiftKey } = event;
        const active = this.active;
        if (key === "Escape" && active) {
            if (target.closest?.(`[${ESCAPE_SCOPE}]`) !== this.element) return;
            if (hasPendingSearchClear(target)) return;
            event.preventDefault();
            event.stopPropagation();
            if (active.content.contains(target)) this.returnFocus(active.trigger);
            this.close();
        } else if (key === "Tab" && active) {
            this.tabThroughPanel(event, active);
        } else if (this.isInList(target) && !event.altKey && !event.ctrlKey && !event.metaKey && !shiftKey) {
            const activatesPanel = this.vertical
                ? key === (this.rtl ? "ArrowLeft" : "ArrowRight")
                : key === "ArrowUp" || key === "ArrowDown";
            if (activatesPanel && this.triggerTargets.includes(target) && !target.matches(":disabled")) {
                event.preventDefault();
                if (!event.repeat) {
                    if (this.vertical) this.open(target, { activated: true });
                    else this.toggleTrigger(target);
                }
            } else {
                this.moveInList(event);
            }
        }
    }

    dismissPreviewOnTab(event) {
        if (event.key !== "Tab" || event.defaultPrevented || isComposing(event) || !this.active || this.active.activated) return;
        // A preview must not enter the native tab order, including Shift+Tab from outside the menu.
        if (!this.active.content.contains(event.target)) this.close();
    }

    reconcileRender({ target, type }) {
        if (target.contains(this.element)) {
            this.close();
        } else if (type === "turbo:morph-element" && this.active && this.element.contains(target)) {
            // Only changes to the disclosure shell invalidate it, not a render inside the active panel.
            const { trigger, content, viewport } = this.active;
            if ([trigger, content, viewport].some(element => target.contains(element))) this.close();
        }
    }

    outside(event) {
        if (!this.element.contains(event.target)) this.close();
    }

    escapeOutside(event) {
        const { target } = event;
        if (!this.active || event.defaultPrevented || isComposing(event) || !target.closest || this.element.contains(target)) return;
        const parentScope = this.element.parentElement?.closest(ESCAPE_BOUNDARY) ?? null;
        if (target.closest(ESCAPE_BOUNDARY) !== parentScope || hasPendingSearchClear(target)) return;
        // Catch hover-only dismissal before a surrounding overlay handles Escape on document.
        if (parentScope) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
        this.close();
    }

    leave(event) {
        if (!this.active || !this.mouseHover(event)) return;
        this.cancelClose();
        this.closeTimer = setTimeout(() => {
            this.closeTimer = undefined;
            const active = this.active;
            if (!this.panelHasFocus() && !(active?.activated && active.trigger.matches(":focus-visible"))) this.close();
        }, Math.max(0, this.closeDelayValue));
    }

    cancelClose() {
        clearTimeout(this.closeTimer);
        this.closeTimer = undefined;
    }

    cancelOpen() {
        clearTimeout(this.openTimer);
        this.openTimer = undefined;
    }

    open(trigger, { activated = false } = {}) {
        this.cancelOpen();
        if (!this.isInList(trigger) || trigger.matches(":disabled") || !this.hasViewportTarget) return;
        const viewport = this.viewportTarget;
        const content = this.contentTargets.find((panel) => panel.id === trigger.getAttribute("aria-controls") && viewport.contains(panel));
        if (!content) return;

        this.cancelClose();
        const previous = this.active;
        if (previous?.trigger === trigger && previous.content === content) {
            if (activated) previous.activated = true;
            return;
        }

        if (previous) {
            this.releasePanel(previous);
            if (previous.viewport !== viewport) this.resetViewport(previous);
        }

        const topLayer = previous?.viewport === viewport ? previous.topLayer : createTopLayer(viewport, { suppressAutofocus: true });
        const active = { trigger, content, viewport, topLayer, activated };
        this.active = active;
        this.element.setAttribute(ESCAPE_SCOPE, "");
        this.setTriggerState(trigger, true);
        this.setVisible(viewport, true);
        this.setVisible(content, true);
        if (previous) {
            const triggers = this.triggerTargets;
            content.dataset.motion = triggers.indexOf(trigger) > triggers.indexOf(previous.trigger) ? "from-end" : "from-start";
        }
        topLayer.show();
        content.scrollTop = 0;
        content.scrollLeft = 0;

        const frame = content.firstElementChild;
        if (frame?.matches("turbo-frame[data-navigation-menu-src]:not([src])")) {
            frame.setAttribute("src", frame.dataset.navigationMenuSrc);
        }

        const measure = () => {
            if (this.active !== active) return;
            const { width, height } = content.getBoundingClientRect();
            viewport.style.setProperty(`${VIEWPORT_PROPERTY}width`, `${Math.ceil(width)}px`);
            viewport.style.setProperty(`${VIEWPORT_PROPERTY}height`, `${Math.ceil(height)}px`);
        };
        measure();
        const view = viewport.ownerDocument.defaultView;
        if (view?.ResizeObserver) {
            active.resize = new view.ResizeObserver(measure);
            active.resize.observe(content, { box: "border-box" });
        }

        const anchorToMenu = this.element.dataset.anchor === "menu";
        const anchor = anchorToMenu ? (this.vertical ? this.listTarget : this.element) : trigger;
        const side = this.vertical ? (this.rtl ? "left" : "right") : "bottom";
        active.floating = createFloating(anchor, viewport, {
            side,
            align: anchorToMenu && !this.vertical ? "center" : "start",
            strategy: "fixed",
            sideOffset: 4,
            applyPosition({ x, y, side: resolvedSide }) {
                if (resolvedSide === "top") {
                    // Anchor the near edge so it stays put while CSS animates the height.
                    viewport.style.setProperty(`${VIEWPORT_PROPERTY}bottom`, `${y + viewport.offsetHeight}px`);
                } else {
                    viewport.style.removeProperty(`${VIEWPORT_PROPERTY}bottom`);
                }
                viewport.style.setProperty(`${VIEWPORT_PROPERTY}x`, `${x}px`);
                viewport.style.setProperty(`${VIEWPORT_PROPERTY}y`, `${y}px`);
            },
        });
        void active.floating.start().then((positioned) => {
            if (positioned && this.active === active) viewport.setAttribute("data-positioned", "");
        }).catch(() => {
            if (this.active === active) this.close();
        });
    }

    close() {
        this.cancelOpen();
        this.cancelClose();
        const active = this.active;
        if (!active) return;
        this.active = null;
        this.element.removeAttribute(ESCAPE_SCOPE);
        this.releasePanel(active);
        this.resetViewport(active);
    }

    releasePanel({ trigger, content, resize, floating }) {
        resize?.disconnect();
        floating?.stop();
        this.setTriggerState(trigger, false);
        this.setVisible(content, false);
        delete content.dataset.motion;
    }

    resetViewport({ viewport, topLayer }) {
        topLayer.hide();
        this.setVisible(viewport, false);
        viewport.removeAttribute("data-positioned");
        viewport.removeAttribute("data-side");
        viewport.removeAttribute("data-align");
        for (const property of ["width", "height", "x", "y", "bottom"]) viewport.style.removeProperty(`${VIEWPORT_PROPERTY}${property}`);
        for (const property of FLOATING_PROPERTIES) viewport.style.removeProperty(property);
    }

    setTriggerState(trigger, open) {
        trigger.setAttribute("aria-expanded", String(open));
        trigger.dataset.state = open ? "open" : "closed";
    }

    setVisible(element, open) {
        element.hidden = !open;
        element.toggleAttribute("inert", !open);
        element.dataset.state = open ? "open" : "closed";
    }

    mouseHover(event) {
        return this.hoverValue && event.pointerType === "mouse";
    }

    panelHasFocus() {
        return this.active?.content.contains(this.element.ownerDocument.activeElement);
    }

    isInList(element) {
        return this.hasListTarget && this.listTarget.contains(element);
    }

    get vertical() {
        return this.element.dataset.orientation === "vertical";
    }

    get rtl() {
        return this.element.matches(":dir(rtl)");
    }

    moveInList(event) {
        const next = this.vertical ? "ArrowDown" : (this.rtl ? "ArrowLeft" : "ArrowRight");
        const previous = this.vertical ? "ArrowUp" : (this.rtl ? "ArrowRight" : "ArrowLeft");
        if (event.key !== next && event.key !== previous && event.key !== "Home" && event.key !== "End") return;
        const entries = getFocusableElements(this.listTarget).filter((entry) => this.triggerTargets.includes(entry) || this.linkTargets.includes(entry));
        const index = entries.indexOf(event.target);
        if (index < 0) return;
        const to = event.key === "Home" ? 0 : event.key === "End" ? entries.length - 1 :
            (index + (event.key === next ? 1 : -1) + entries.length) % entries.length;
        event.preventDefault();
        entries[to].focus();
    }

    tabThroughPanel(event, { trigger, content, activated }) {
        const { target, shiftKey } = event;
        if (target !== trigger && !content.contains(target)) return;
        const focusables = getFocusableElements(content);
        if (target === trigger && activated && !shiftKey && focusables.length) {
            event.preventDefault();
            focusables[0].focus();
        } else if (target === focusables[0] && shiftKey) {
            event.preventDefault();
            this.returnFocus(trigger);
        } else if (target === focusables.at(-1) && !shiftKey) {
            // Continue the browser's own Tab order from the trigger, not from the end of the viewport.
            this.returnFocus(trigger);
            this.close();
        }
    }

    returnFocus(trigger) {
        trigger.focus({ preventScroll: true });
    }
}

function hasPendingSearchClear(target) {
    return target.matches?.('input[type="search"][data-hotwire-escape-clear]') && target.value && !target.readOnly;
}
