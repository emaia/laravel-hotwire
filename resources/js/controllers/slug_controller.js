import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["source", "slug", "preview"];
    static values = {
        separator: { type: String, default: "-" },
        auto: { type: Boolean, default: true },
        maxLength: { type: Number, default: 0 },
    };

    initialize() {
        this.sync = this.sync.bind(this);
        this.lock = this.lock.bind(this);
    }

    connect() {
        this.locked = !this.autoValue || this.slugTarget.value.trim() !== "";
        this.sourceTarget.addEventListener("input", this.sync);
        this.slugTarget.addEventListener("input", this.lock);
        this.reflect();

        if (this.locked) {
            this.updatePreview(this.slugTarget.value);
        } else {
            this.sync();
        }
    }

    disconnect() {
        this.sourceTarget.removeEventListener("input", this.sync);
        this.slugTarget.removeEventListener("input", this.lock);
    }

    sync() {
        if (this.locked) return;
        this.write(this.slugify(this.sourceTarget.value));
    }

    lock() {
        this.locked = true;
        this.reflect();
        this.updatePreview(this.slugTarget.value);
    }

    relink() {
        this.locked = false;
        this.reflect();
        this.sync();
    }

    write(value) {
        // Setting .value programmatically does not fire "input", so this never
        // re-triggers lock().
        this.slugTarget.value = value;
        this.updatePreview(value);
    }

    updatePreview(value) {
        if (this.hasPreviewTarget) this.previewTarget.textContent = value;
    }

    reflect() {
        this.element.setAttribute("data-slug-locked", String(this.locked));
    }

    slugify(text) {
        const sep = this.separatorValue;
        const slug = text
            .toString()
            .normalize("NFD")
            .replace(/\p{Mn}/gu, "")
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, sep)
            .replace(new RegExp(`\\${sep}+`, "g"), sep)
            .replace(new RegExp(`^\\${sep}+|\\${sep}+$`, "g"), "");

        return this.truncate(slug);
    }

    truncate(slug) {
        const max = this.maxLengthValue;
        if (max <= 0 || slug.length <= max) return slug;

        const sep = this.separatorValue;
        let cut = slug.slice(0, max);
        const at = cut.lastIndexOf(sep);
        if (at > 0) cut = cut.slice(0, at);

        return cut.replace(new RegExp(`\\${sep}+$`, "g"), "");
    }
}
