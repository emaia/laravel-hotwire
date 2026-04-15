import { Controller } from "@hotwired/stimulus";

// Trigger wrapper: dispatches optimistic UI when the form is submitted.
//
// Pair on the same <form> with the optimistic--dispatch core controller:
//   <form data-controller="optimistic--dispatch form--optimistic" ...>
//
// Optional values:
//   data-form--optimistic-reset-value="true"  — resets the form after a
//   successful submission (handy for chat/comment inputs).
export default class extends Controller {
    static values = {
        reset: { type: Boolean, default: false },
    };

    connect() {
        this.onSubmitStart = this.onSubmitStart.bind(this);
        this.onSubmitEnd = this.onSubmitEnd.bind(this);
        this.element.addEventListener("turbo:submit-start", this.onSubmitStart);
        this.element.addEventListener("turbo:submit-end", this.onSubmitEnd);
    }

    disconnect() {
        this.element.removeEventListener("turbo:submit-start", this.onSubmitStart);
        this.element.removeEventListener("turbo:submit-end", this.onSubmitEnd);
    }

    onSubmitStart(event) {
        if (event.target !== this.element) return;

        const dispatcher = this.application.getControllerForElementAndIdentifier(
            this.element,
            "optimistic--dispatch",
        );
        if (!dispatcher) return;

        const formData = new FormData(this.element);
        dispatcher.dispatch({ formData });
    }

    onSubmitEnd(event) {
        if (event.target !== this.element) return;
        if (!this.resetValue) return;
        if (!event.detail?.success) return;

        this.element.reset();
    }
}
