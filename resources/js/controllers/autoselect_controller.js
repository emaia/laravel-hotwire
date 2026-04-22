import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        super.connect();
        this.element.addEventListener("focus", () => this.element.select());
    }

    disconnect() {
        super.disconnect();
        this.element.removeEventListener("focus", () => true);
    }
}
