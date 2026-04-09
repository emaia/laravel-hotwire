import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["submitBtn"];

    connect() {}

    remoteSubmit() {
        if (this.hasSubmitBtnTarget) {
            this.submitBtnTarget.click();
        }
    }

    reset() {
        this.element.reset();
    }
}
