import { Controller } from "@hotwired/stimulus";

// Core dispatcher for Optimistic UI.
//
// Scans its element subtree for <template data-optimistic-stream> nodes and
// converts each into a live <turbo-stream> that Turbo executes synchronously,
// updating the DOM before any network round-trip.
//
// This controller does not bind to any event by itself — it's invoked by
// trigger wrappers (e.g. form--optimistic, link--optimistic) that decide when
// to fire the optimistic update and may pass FormData for placeholder
// population via [data-field] elements.
export default class extends Controller {
    dispatch({ formData = null } = {}) {
        const templates = this.element.querySelectorAll("template[data-optimistic-stream]");
        templates.forEach((template) => this.#dispatchOne(template, formData));
    }

    #dispatchOne(template, formData) {
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

        this.#populateFields(payload.content, formData);
        this.#markOptimistic(payload.content);

        stream.appendChild(payload);
        document.body.appendChild(stream);
    }

    // Populates descendants carrying [data-field="name"] with the matching
    // FormData value using textContent (never innerHTML) to keep the path XSS-safe.
    #populateFields(root, formData) {
        if (!formData) return;

        root.querySelectorAll("[data-field]").forEach((field) => {
            const name = field.dataset.field;
            if (!name || !formData.has(name)) return;
            field.textContent = formData.get(name);
        });
    }

    // Tags every top-level payload element with data-optimistic so apps can
    // style the provisional state via CSS until the server morph replaces it.
    #markOptimistic(root) {
        root.querySelectorAll(":scope > *").forEach((node) => {
            if (!node.hasAttribute("data-optimistic")) {
                node.setAttribute("data-optimistic", "");
            }
        });
    }
}
