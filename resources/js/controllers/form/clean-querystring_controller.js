import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    cleanQueryString() {
        this.element.addEventListener("formdata", function (event) {
            let formData = event.formData;
            for (let [name, value] of Array.from(formData.entries())) {
                if (value === "") formData.delete(name);
            }
        });
    }

    submit(e) {
        this.cleanQueryString();
        this.element.requestSubmit();
    }
}
