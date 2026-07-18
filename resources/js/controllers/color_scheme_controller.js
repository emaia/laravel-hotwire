// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        storageKey: { type: String, default: "hotwire.colorScheme" },
        default: { type: String, default: "system" },
        modes: { type: String, default: "light dark system" },
    };

    connect() {
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
        window.removeEventListener("storage", this.boundStorageChanged);
        window.removeEventListener("color-scheme:change", this.boundGlobalChanged);
        this.mediaQuery?.removeEventListener?.("change", this.boundMediaChanged);
    }

    toggle() {
        this.setMode(this.resolvedScheme === "dark" ? "light" : "dark");
    }

    cycle() {
        const modes = this.normalizedModes;
        const currentIndex = modes.indexOf(this.currentMode);
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

        this.apply(nextMode, { dispatch: true });
    }

    apply(mode, { dispatch = false } = {}) {
        const nextMode = this.normalizeMode(mode);
        const scheme = this.resolveScheme(nextMode);

        document.documentElement.setAttribute("data-theme", scheme);
        document.documentElement.style.colorScheme = scheme;
        this.element.dataset.mode = nextMode;
        this.element.dataset.scheme = scheme;

        if (dispatch) {
            window.dispatchEvent(new CustomEvent("color-scheme:change", {
                detail: { mode: nextMode, scheme },
            }));
        }
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
