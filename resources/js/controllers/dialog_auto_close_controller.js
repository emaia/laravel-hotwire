import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        const root = this.element.closest('[data-controller~="dialog"]');
        const ctrl = root && this.application.getControllerForElementAndIdentifier(root, "dialog");
        ctrl?.close();
        this.element.remove();
    }
}
