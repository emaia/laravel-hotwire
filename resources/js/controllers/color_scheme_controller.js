// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        storageKey: { type: String, default: "hotwire.colorScheme" },
        default: { type: String, default: "system" },
        modes: { type: String, default: "light dark system" },
        viewTransition: { type: Boolean, default: false },
    };

    connect() {
        this.pendingTransition = null;
        this.boundStorageChanged = this.storageChanged.bind(this);
        this.boundMediaChanged = this.mediaChanged.bind(this);
        this.boundGlobalChanged = this.globalChanged.bind(this);
        this.mediaQuery = window.matchMedia?.("(prefers-color-scheme: dark)") ?? null;

        window.addEventListener("storage", this.boundStorageChanged);
        window.addEventListener("color-scheme:change", this.boundGlobalChanged);
        this.mediaQuery?.addEventListener?.("change", this.boundMediaChanged);

        this.apply(this.currentMode, { dispatch: false });
    }

    disconnect() {
        this.restoreTransitions();
        window.removeEventListener("storage", this.boundStorageChanged);
        window.removeEventListener("color-scheme:change", this.boundGlobalChanged);
        this.mediaQuery?.removeEventListener?.("change", this.boundMediaChanged);
    }

    restoreTransitions() {
        delete document.documentElement.dataset.colorSchemeTransitioning;
    }

    toggle() {
        this.setMode(this.resolvedScheme === "dark" ? "light" : "dark");
    }

    cycle() {
        const modes = this.normalizedModes;
        const currentMode = this.currentMode;
        const cycleMode = modes.includes(currentMode) ? currentMode : this.resolveScheme(currentMode);
        const currentIndex = modes.indexOf(cycleMode);
        const nextIndex = currentIndex === -1 ? 0 : (currentIndex + 1) % modes.length;

        this.setMode(modes[nextIndex]);
    }

    set(event) {
        this.setMode(event?.params?.mode);
    }

    light() {
        this.setMode("light");
    }

    dark() {
        this.setMode("dark");
    }

    system() {
        this.setMode("system");
    }

    setMode(mode) {
        const nextMode = this.normalizeMode(mode);

        try {
            window.localStorage.setItem(this.storageKeyValue, nextMode);
        } catch (error) {}

        this.apply(nextMode, { dispatch: true, animate: true });
    }

    apply(mode, { dispatch = false, animate = false } = {}) {
        const nextMode = this.normalizeMode(mode);
        const scheme = this.resolveScheme(nextMode);
        const update = () => {
            document.documentElement.setAttribute("data-theme", scheme);
            document.documentElement.setAttribute("data-color-scheme-mode", nextMode);
            document.documentElement.style.colorScheme = scheme;
            this.element.dataset.mode = nextMode;
            this.element.dataset.scheme = scheme;

            if (dispatch) {
                window.dispatchEvent(new CustomEvent("color-scheme:change", {
                    detail: { mode: nextMode, scheme },
                }));
            }
        };

        if (this.pendingTransition) {
            if (animate && this.viewTransitionValue) {
                this.pendingTransition.update = update;

                return;
            }

            this.pendingTransition = null;
        }

        const currentScheme = document.documentElement.getAttribute("data-theme");

        if (
            animate
            && this.viewTransitionValue
            && scheme !== currentScheme
            && typeof document.startViewTransition === "function"
        ) {
            const reduce = window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches ?? false;

            if (!reduce) {
                const pendingTransition = { update };
                this.pendingTransition = pendingTransition;
                // The crossfade owns the motion for the whole swap; structural.css keys off this
                // to silence the per-element colour transitions that would run underneath it.
                document.documentElement.dataset.colorSchemeTransitioning = "";
                const transition = document.startViewTransition(() => {
                    if (this.pendingTransition !== pendingTransition) return;

                    this.pendingTransition = null;
                    pendingTransition.update();
                });
                transition.finished.finally(() => this.restoreTransitions());

                return;
            }
        }

        update();
    }

    storageChanged(event) {
        if (event.key !== this.storageKeyValue) return;

        this.apply(event.newValue ?? this.defaultValue, { dispatch: false });
    }

    mediaChanged() {
        if (this.currentMode !== "system") return;

        this.apply("system", { dispatch: true });
    }

    globalChanged(event) {
        if (!event.detail?.mode) return;

        this.apply(event.detail.mode, { dispatch: false });
    }

    resolveScheme(mode) {
        if (mode === "system") {
            return this.mediaQuery?.matches ? "dark" : "light";
        }

        return mode === "dark" ? "dark" : "light";
    }

    normalizeMode(mode) {
        const value = typeof mode === "string" ? mode : this.defaultValue;

        return this.allowedModes.includes(value) ? value : this.normalizeMode(this.defaultValue === value ? "system" : this.defaultValue);
    }

    get currentMode() {
        try {
            return this.normalizeMode(window.localStorage.getItem(this.storageKeyValue) || this.defaultValue);
        } catch (error) {
            return this.normalizeMode(this.defaultValue);
        }
    }

    get resolvedScheme() {
        return this.resolveScheme(this.currentMode);
    }

    get normalizedModes() {
        const modes = this.modesValue.split(/\s+/).filter((mode) => this.allowedModes.includes(mode));

        return modes.length > 0 ? modes : ["light", "dark", "system"];
    }

    get allowedModes() {
        return ["light", "dark", "system"];
    }
}
