import { Controller } from "@hotwired/stimulus";

// Core dispatcher for Optimistic UI.
//
// Scans its element subtree for <template data-optimistic-stream> nodes and
// converts each into a live <turbo-stream> that Turbo executes synchronously,
// updating the DOM before any network round-trip.
//
// This controller does not bind to any event by itself — it's invoked by
// trigger wrappers (e.g. form--optimistic, link--optimistic) that decide when
// to fire the optimistic update. Reconciliation happens via the server's
// turbo-stream response (a refresh with method="morph" works best).
export default class extends Controller {
    dispatch() {
        const templates = this.element.querySelectorAll("template[data-optimistic-stream]");
        templates.forEach((template) => this.#dispatchOne(template));
    }

    #dispatchOne(template) {
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
