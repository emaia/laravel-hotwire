// @hotwire-package
import { markSnapshotGenerated } from "./_snapshot.js";

export function createImageThumbnails(element, template, identifier) {
    const initial = template.content?.firstElementChild;
    if (initial?.tagName !== "BUTTON" || !initial.querySelector("img")) {
        throw new Error("Image viewer thumbnails require a button template containing an image.");
    }
    let entries = [];
    let previousPrototype = null;
    element.replaceChildren();

    function render(items, activeIndex, reveal = false) {
        const prototype = template.content?.firstElementChild;
        if (prototype?.tagName !== "BUTTON" || !prototype.querySelector("img")) {
            throw new Error("Image viewer thumbnails require a button template containing an image.");
        }
        // Reuse keyed buttons and restore focus if reordering moves a focused node.
        const focused = entries.find((entry) => entry.button.contains(element.ownerDocument.activeElement));
        const available = new Map(entries.map((entry) => [entry.identity, entry.button]));
        const next = items.map((item, index) => {
            const identity = item.key || item.node;
            const button =
                previousPrototype === prototype
                    ? (available.get(identity) ?? prototype.cloneNode(true))
                    : prototype.cloneNode(true);
            available.delete(identity);
            markSnapshotGenerated(button, "image-viewer");
            button.setAttribute(`data-${identifier}-index-param`, String(index));
            const label = (prototype.dataset.label || "View image :index of :total")
                .replaceAll(":index", String(index + 1))
                .replaceAll(":total", String(items.length));
            button.setAttribute("aria-label", item.alt ? `${label} — ${item.alt}` : label);
            button.title = item.alt || label;
            const image = button.querySelector("img");
            image.referrerPolicy = item.node.referrerPolicy;
            if (item.node.hasAttribute("crossorigin")) image.crossOrigin = item.node.crossOrigin;
            else image.removeAttribute("crossorigin");
            if (item.thumbnail) {
                if (image.getAttribute("src") !== item.thumbnail) image.setAttribute("src", item.thumbnail);
            } else {
                image.removeAttribute("src");
            }
            return { identity, button };
        });
        const retained = new Set(next.map((entry) => entry.button));
        for (const child of [...element.children]) {
            if (!retained.has(child)) child.remove();
        }
        let cursor = element.firstElementChild;
        for (const { button } of next) {
            if (button !== cursor) element.insertBefore(button, cursor);
            cursor = button.nextElementSibling;
        }
        entries = next;
        previousPrototype = prototype;
        sync(activeIndex, reveal);
        if (focused) {
            const replacement = entries.find((entry) => entry.identity === focused.identity) ?? entries[activeIndex];
            if (replacement && element.ownerDocument.activeElement !== replacement.button) {
                replacement.button.focus({ preventScroll: true });
            }
        }
    }

    function sync(index, reveal = false) {
        entries.forEach(({ button }, position) => {
            const active = position === index;
            button.tabIndex = active ? 0 : -1;
            active ? button.setAttribute("aria-current", "true") : button.removeAttribute("aria-current");
        });
        if (reveal) entries[index]?.button.scrollIntoView({ block: "nearest", inline: "nearest", behavior: "instant" });
    }

    return {
        element,
        template,
        render,
        sync,
        focus: (index) => entries[index]?.button.focus({ preventScroll: true }),
        indexOf: (target) => entries.findIndex((entry) => entry.button.contains(target)),
        clear: () => {
            element.replaceChildren();
            entries = [];
        },
    };
}
