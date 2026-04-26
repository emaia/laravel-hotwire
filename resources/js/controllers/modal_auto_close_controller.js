import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        const root = this.element.closest('[data-controller~="modal"]');
        const ctrl = root && this.application.getControllerForElementAndIdentifier(root, "modal");
        ctrl?.close();
        this.element.remove();
    }
}
