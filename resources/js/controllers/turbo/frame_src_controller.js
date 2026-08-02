// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.element.addEventListener("turbo:before-fetch-request", this.#setHeader);
    }

    disconnect() {
        this.element.removeEventListener("turbo:before-fetch-request", this.#setHeader);
    }

    #setHeader = (event) => {
        const form = event.target;
        const headers = event.detail?.fetchOptions?.headers;

        if (!form?.matches?.("form") || !headers || hasHeader(headers, "X-Turbo-Frame-Src")) return;

        const targetFrameId = getHeader(headers, "Turbo-Frame");
        const frame = form.closest("turbo-frame");

        if (!targetFrameId || !frame || frame.id !== targetFrameId) return;

        const source = frameSource(frame);
        if (source) setHeader(headers, "X-Turbo-Frame-Src", source);
    };
}

function frameSource(frame) {
    let current = frame;

    while (current) {
        const source = current.getAttribute("src")?.trim();

        if (source) {
            try {
                return new URL(source, current.ownerDocument.baseURI).href;
            } catch (_error) {
                return null;
            }
        }

        current = current.parentElement?.closest("turbo-frame");
    }

    return frame.ownerDocument.location.href;
}

function hasHeader(headers, name) {
    if (typeof headers.has === "function") return headers.has(name);

    return headerKey(headers, name) !== undefined;
}

function getHeader(headers, name) {
    if (typeof headers.get === "function") return headers.get(name);

    const key = headerKey(headers, name);

    return key === undefined ? null : headers[key];
}

function setHeader(headers, name, value) {
    if (typeof headers.set === "function") {
        headers.set(name, value);
    } else {
        headers[name] = value;
    }
}

function headerKey(headers, name) {
    const normalized = name.toLowerCase();

    return Object.keys(headers).find((key) => key.toLowerCase() === normalized);
}
