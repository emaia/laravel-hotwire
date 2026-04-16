import { Controller } from "@hotwired/stimulus";
import { dispatchOptimistic } from "./_dispatch";

// Dispatches optimistic UI when a Turbo form is submitted.
//
//   <form data-controller="optimistic--form" ...>
//       <x-hwc::optimistic target="...">…</x-hwc::optimistic>
//   </form>
//
// Optional values:
//   data-optimistic--form-reset-value="true"  — resets the form after a
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

        dispatchOptimistic(this.element, {
            formData: new FormData(this.element),
        });
    }

    onSubmitEnd(event) {
        if (event.target !== this.element) return;
        if (!this.resetValue) return;
        if (!event.detail?.success) return;

        this.element.reset();
    }
}
