import { Controller } from "@hotwired/stimulus";

// Optimistic UI for Turbo forms.
//
// Attach to a <form> and drop <template data-form--optimistic-target="stream">
// children describing the optimistic turbo-stream action. On turbo:submit-start,
// each template is cloned into document.body as a <turbo-stream>, which Turbo
// executes synchronously — the UI updates before the network round-trip.
//
// Reconciliation happens on the real response: a turbo-stream refresh (morph)
// from the server reconciles the DOM to authoritative state on success, or
// reverts it on failure. No manual rollback is needed.
export default class extends Controller {
    static targets = ["stream"];

    connect() {
        this.onSubmitStart = this.onSubmitStart.bind(this);
        this.element.addEventListener("turbo:submit-start", this.onSubmitStart);
    }

    disconnect() {
        this.element.removeEventListener("turbo:submit-start", this.onSubmitStart);
    }

    onSubmitStart(event) {
        if (event.target !== this.element) return;

        this.streamTargets.forEach((template) => this.#dispatch(template));
    }

    #dispatch(template) {
        const action = template.dataset.optimisticAction || "replace";
        const targetId = template.dataset.optimisticTargetId || "";
        const targets = template.dataset.optimisticTargets || "";

        const stream = document.createElement("turbo-stream");
        stream.setAttribute("action", action);

        if (targets) {
            stream.setAttribute("targets", targets);
        } else if (targetId) {
            stream.setAttribute("target", targetId);
        } else if (action !== "refresh") {
            return;
        }

        const payload = document.createElement("template");
        payload.innerHTML = template.innerHTML;
        stream.appendChild(payload);

        document.body.appendChild(stream);
    }
}
