// @hotwire-package
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
        this.onSlugInput = this.onSlugInput.bind(this);
        this.onSlugChange = this.onSlugChange.bind(this);
    }

    connect() {
        this.locked = !this.autoValue || this.slugTarget.value.trim() !== "";
        this.sourceTarget.addEventListener("input", this.sync);
        this.slugTarget.addEventListener("input", this.onSlugInput);
        this.slugTarget.addEventListener("change", this.onSlugChange);
        this.reflect();

        if (this.locked) {
            this.updatePreview(this.slugTarget.value);
        } else {
            this.sync();
        }
    }

    disconnect() {
        this.sourceTarget.removeEventListener("input", this.sync);
        this.slugTarget.removeEventListener("input", this.onSlugInput);
        this.slugTarget.removeEventListener("change", this.onSlugChange);
    }

    sync() {
        if (this.locked) return;
        this.write(this.slugify(this.sourceTarget.value));
    }

    onSlugInput() {
        this.locked = true;
        this.reflect();
        this.clean((text) => this.sanitize(text));
    }

    onSlugChange() {
        this.clean((text) => this.slugify(text));
    }

    clean(transform) {
        const el = this.slugTarget;
        const cleaned = transform(el.value);

        if (cleaned !== el.value) {
            const at = el.selectionStart;
            if (typeof at === "number") {
                const before = transform(el.value.slice(0, at)).length;
                el.value = cleaned;
                el.setSelectionRange?.(before, before);
            } else {
                el.value = cleaned;
            }
        }

        this.updatePreview(el.value);
    }

    relink() {
        this.locked = false;
        this.reflect();
        this.sync();
    }

    write(value) {
        this.slugTarget.value = value;
        this.updatePreview(value);
    }

    updatePreview(value) {
        if (this.hasPreviewTarget) this.previewTarget.textContent = value;
    }

    reflect() {
        this.element.setAttribute("data-slug-locked", String(this.locked));
    }

    sanitize(text) {
        const sep = this.separatorValue;
        return text
            .toString()
            .normalize("NFD")
            .replace(/\p{Mn}/gu, "")
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, sep)
            .replace(new RegExp(`^\\${sep}+`), "");
    }

    slugify(text) {
        const sep = this.separatorValue;
        const slug = this.sanitize(text).replace(new RegExp(`\\${sep}+$`), "");

        return this.truncate(slug);
    }

    truncate(slug) {
        const max = this.maxLengthValue;
        if (max <= 0 || slug.length <= max) return slug;

        const sep = this.separatorValue;
        let cut = slug.slice(0, max);
        const at = cut.lastIndexOf(sep);
        if (at > 0) cut = cut.slice(0, at);

        return cut.replace(new RegExp(`\\${sep}+$`), "");
    }
}
