// Shared dispatch logic for Optimistic UI.
//
// Scans a root element for <template data-optimistic-stream> nodes and
// converts each into a live <turbo-stream> that Turbo executes synchronously.
// Used by optimistic--form, optimistic--link, and optimistic--dispatch
// controllers. Can also be imported directly in custom controllers.

export function dispatchOptimistic(root, { formData = null } = {}) {
    const templates = root.querySelectorAll("template[data-optimistic-stream]");
    templates.forEach((template) => dispatchOne(template, formData));
}

function dispatchOne(template, formData) {
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

    populateFields(payload.content, formData);
    markOptimistic(payload.content);

    stream.appendChild(payload);
    document.body.appendChild(stream);
}

function populateFields(root, formData) {
    if (!formData) return;

    root.querySelectorAll("[data-field]").forEach((field) => {
        const name = field.dataset.field;
        if (!name || !formData.has(name)) return;
        field.textContent = formData.get(name);
    });
}

function markOptimistic(root) {
    root.querySelectorAll(":scope > *").forEach((node) => {
        if (!node.hasAttribute("data-optimistic")) {
            node.setAttribute("data-optimistic", "");
        }
    });
}
