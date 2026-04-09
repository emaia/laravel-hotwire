import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String,
        alt: { type: String, default: "" },
        interval: { type: Number, default: 3000 },
        width: { type: Number, default: 0 },
        height: { type: Number, default: 0 },
        maxAttempts: { type: Number, default: 20 },
        imgClass: { type: String, default: "" },
        sources: { type: Array, default: [] },
    };

    connect() {
        this.attempts = 0;
        this.poll();
    }

    disconnect() {
        clearTimeout(this.timeoutId);
    }

    poll() {
        this.attempts++;

        const probe = new Image();

        probe.onload = () => {
            this.element.innerHTML = "";

            this.sourcesValue.forEach(({ media, srcset }) => {
                const source = document.createElement("source");
                source.media = media;
                source.srcset = srcset;
                this.element.appendChild(source);
            });

            const img = document.createElement("img");
            img.src = this.urlValue;
            img.alt = this.altValue;

            if (this.widthValue) img.width = this.widthValue;
            if (this.heightValue) img.height = this.heightValue;
            if (this.imgClassValue) img.className = this.imgClassValue;

            this.element.appendChild(img);
        };

        probe.onerror = () => {
            if (this.attempts < this.maxAttemptsValue) {
                this.timeoutId = setTimeout(() => this.poll(), this.intervalValue);
            }
        };

        probe.src = this.urlValue;
    }
}
