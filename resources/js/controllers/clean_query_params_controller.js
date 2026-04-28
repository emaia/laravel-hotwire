import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.element.addEventListener("formdata", this.#clean);
    }

    disconnect() {
        this.element.removeEventListener("formdata", this.#clean);
    }

    #clean = (event) => {
        for (let [name, value] of Array.from(event.formData.entries())) {
            if (value === "") event.formData.delete(name);
        }
    };
}
