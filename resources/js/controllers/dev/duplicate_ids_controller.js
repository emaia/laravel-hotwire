// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const automaticIdPattern = /^hw-.+-(?:page|frame-.+)-\d+$/;

export default class extends Controller {
    connect() {
        this.duplicateIds = new Set();
        this.scan();

        this.observer = new MutationObserver(() => this.scan());
        this.observer.observe(this.element, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ["id"],
        });
    }

    disconnect() {
        this.observer?.disconnect();
    }

    scan() {
        const elements = [
            ...(this.element.matches('[id^="hw-"]') ? [this.element] : []),
            ...this.element.querySelectorAll('[id^="hw-"]'),
        ];
        const byId = new Map();

        for (const element of elements) {
            if (!automaticIdPattern.test(element.id)) continue;

            const matches = byId.get(element.id) ?? [];
            matches.push(element);
            byId.set(element.id, matches);
        }

        const duplicateIds = new Set();

        for (const [id, matches] of byId) {
            if (matches.length < 2) continue;

            duplicateIds.add(id);

            if (!this.duplicateIds.has(id)) {
                console.warn(
                    `[Laravel Hotwire] Duplicate package-style component id "${id}". Pass a model or explicit id when rendering across requests or unstable sibling order.`,
                    matches,
                );
            }
        }

        this.duplicateIds = duplicateIds;
    }
}
