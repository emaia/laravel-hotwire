// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const frameClaims = new WeakMap();

export default class extends Controller {
    #guarding = false;

    connect() {
        this.#guarding = true;
        this.handleBeforeCache = this.#prepareForCache.bind(this);
        this.#acquire();

        if (this.claimedFrame) {
            document.addEventListener("turbo:before-cache", this.handleBeforeCache);
        }
    }

    disconnect() {
        this.#guarding = false;
        document.removeEventListener("turbo:before-cache", this.handleBeforeCache);
        this.#release();
    }

    #prepareForCache() {
        this.#release();

        // Turbo queues its clone timer after before-cache returns. Defer queuing ours so the clone runs first.
        queueMicrotask(() => {
            setTimeout(() => {
                if (this.#guarding && this.element.isConnected) {
                    this.#acquire();
                }
            }, 0);
        });
    }

    #acquire() {
        const frame = this.#nearestUniqueFrame();
        if (!frame || frame === this.claimedFrame) return;

        this.#release();

        let claim = frameClaims.get(frame);

        if (!claim) {
            claim = {
                preexisting: frame.hasAttribute("data-turbo-permanent"),
                owners: new Set(),
            };
            frameClaims.set(frame, claim);
        }

        claim.owners.add(this);
        if (!frame.hasAttribute("data-turbo-permanent")) {
            frame.setAttribute("data-turbo-permanent", "");
        }
        this.claimedFrame = frame;
    }

    #release() {
        const frame = this.claimedFrame;
        if (!frame) return;

        const claim = frameClaims.get(frame);
        claim?.owners.delete(this);

        if (claim && claim.owners.size === 0) {
            if (!claim.preexisting) {
                frame.removeAttribute("data-turbo-permanent");
            }

            frameClaims.delete(frame);
        }

        this.claimedFrame = null;
    }

    #nearestUniqueFrame() {
        const frame = this.element.closest("turbo-frame");
        const id = frame?.id.trim();
        if (!frame || !id) return null;

        const matches = document.querySelectorAll(`[id="${cssEscape(id)}"]`);

        return matches.length === 1 ? frame : null;
    }
}

function cssEscape(value) {
    return window.CSS?.escape ? window.CSS.escape(value) : value.replace(/[^a-zA-Z0-9_-]/g, "\\$&");
}
