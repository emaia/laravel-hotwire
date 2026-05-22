import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.boundBeforeFetch = this.#setHeader.bind(this);
        document.addEventListener("turbo:before-fetch-request", this.boundBeforeFetch);
    }

    disconnect() {
        document.removeEventListener("turbo:before-fetch-request", this.boundBeforeFetch);
    }

    #setHeader(event) {
        if (event.detail.fetchOptions.headers["Turbo-Frame"]) {
            event.detail.fetchOptions.headers["X-Turbo-Frame-Src"] = window.location.href;
        }
    }
}
