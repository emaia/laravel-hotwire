// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import tippy from "tippy.js"; // https://atomiks.github.io/tippyjs
import "tippy.js/dist/tippy.css";

export default class extends Controller {
    static values = {
        content: {
            type: String,
            default: "Tooltip",
        },
        side: {
            type: String,
            default: "top",
        },
        align: {
            type: String,
            default: "center",
        },
        enabledWhen: {
            type: String,
            default: "",
        },
    };

    observer = null;

    connect() {
        this.tippy?.destroy();
        this.tippy = tippy(this.element, {
            content: this.contentValue,
            placement: this.tippyPlacement,
            allowHTML: true,
            onShow: () => this.isEnabled() ? undefined : false,
        });
        this.observeEnablement();
    }

    disconnect() {
        this.observer?.disconnect();
        this.observer = null;

        if (this.tippy) {
            this.tippy.destroy();
        }
    }

    isEnabled() {
        if (!this.enabledWhenValue) return true;

        try {
            if (this.element.closest(this.enabledWhenValue)) return true;

            return Array.from(document.querySelectorAll(this.enabledWhenValue))
                .some((element) => element.contains(this.element));
        } catch (_error) {
            return false;
        }
    }

    get tippyPlacement() {
        return this.alignValue === "center" ? this.sideValue : `${this.sideValue}-${this.alignValue}`;
    }

    observeEnablement() {
        this.observer?.disconnect();
        this.observer = null;

        if (!this.enabledWhenValue) return;

        this.observer = new MutationObserver(() => {
            this.syncEnabledState();
        });

        this.observer.observe(this.observerRoot, {
            attributes: true,
            subtree: true,
        });
    }

    get observerRoot() {
        const root = this.element.getRootNode?.();

        return root?.body ?? root?.host ?? this.element;
    }

    syncEnabledState() {
        if (!this.isEnabled()) this.tippy?.hide?.();
    }
}
