// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        super.connect();
        this.boundSelect = () => this.element.select();
        this.element.addEventListener("focus", this.boundSelect);
    }

    disconnect() {
        this.element.removeEventListener("focus", this.boundSelect);
        super.disconnect();
    }
}
