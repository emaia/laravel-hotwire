import { Controller } from "@hotwired/stimulus";

function debounce(fn, ms) {
    let id;
    return (...args) => {
        clearTimeout(id);
        id = setTimeout(() => fn(...args), ms);
    };
}

export default class extends Controller {
    initialize() {
        this.debouncedSubmit = debounce(this.debouncedSubmit.bind(this), 300);
    }

    submit(e) {
        this.element.requestSubmit();
    }

    debouncedSubmit(evt) {
        this.submit();
    }

    submitOnChange() {
        this.submit();
    }
}
