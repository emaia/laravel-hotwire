import { Controller } from "@hotwired/stimulus";
import { dispatchOptimistic } from "./_dispatch";

export default class extends Controller {
    connect() {
        this.onClick = this.onClick.bind(this);
        this.element.addEventListener("click", this.onClick);
    }

    disconnect() {
        this.element.removeEventListener("click", this.onClick);
    }

    onClick(event) {
        if (!this.#willTurboHandle(event)) return;

        dispatchOptimistic(this.element);
    }

    #willTurboHandle(event) {
        if (event.defaultPrevented) return false;
        if (event.button !== undefined && event.button !== 0) return false;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

        const link = this.element;
        if (link.dataset.turbo === "false") return false;
        return !(link.target && link.target !== "" && link.target !== "_self");
    }
}
