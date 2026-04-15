import { Controller } from "@hotwired/stimulus";

// Trigger wrapper: dispatches optimistic UI when the form is submitted.
//
// Pair on the same <form> with the optimistic--dispatch core controller:
//   <form data-controller="optimistic--dispatch form--optimistic" ...>
export default class extends Controller {
    connect() {
        this.onSubmitStart = this.onSubmitStart.bind(this);
        this.element.addEventListener("turbo:submit-start", this.onSubmitStart);
    }

    disconnect() {
        this.element.removeEventListener("turbo:submit-start", this.onSubmitStart);
    }

    onSubmitStart(event) {
        if (event.target !== this.element) return;

        const dispatcher = this.application.getControllerForElementAndIdentifier(
            this.element,
            "optimistic--dispatch",
        );
        if (dispatcher) dispatcher.dispatch();
    }
}
