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
            ...(this.element.hasAttribute("id") ? [this.element] : []),
            ...this.element.querySelectorAll("[id]"),
        ];
        const byId = new Map();

        for (const element of elements) {
            const matches = byId.get(element.id) ?? [];
            matches.push(element);
            byId.set(element.id, matches);
        }

        const duplicateIds = new Set();

        for (const [id, matches] of byId) {
            if (matches.length < 2) continue;

            duplicateIds.add(id);

            if (!this.duplicateIds.has(id)) {
                const message = automaticIdPattern.test(id)
                    ? `Duplicate package-style component id "${id}". Pass a model or explicit id when rendering across requests or unstable sibling order.`
                    : `Duplicate DOM id "${id}". Every id must be unique; use distinct explicit ids when rendering the same model or element more than once.`;

                console.warn(
                    `[Laravel Hotwire] ${message}`,
                    matches,
                );
            }
        }

        this.duplicateIds = duplicateIds;
    }
}
