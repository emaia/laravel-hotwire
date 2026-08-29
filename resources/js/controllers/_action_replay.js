// @hotwire-package

/**
 * Attributes that define what an action does. Captured to verify that a resolved
 * element still performs the action the user confirmed — never to rebuild it.
 */
const SIGNATURE_ATTRIBUTES = new Set([
    "aria-disabled",
    "command",
    "commandfor",
    "disabled",
    "download",
    "form",
    "formaction",
    "formenctype",
    "formmethod",
    "formnovalidate",
    "formtarget",
    "href",
    "hreflang",
    "name",
    "ping",
    "popover",
    "popovertarget",
    "popovertargetaction",
    "referrerpolicy",
    "rel",
    "src",
    "target",
    "type",
    "value",
]);
const FORM_SIGNATURE_ATTRIBUTES = new Set([
    "accept-charset",
    "action",
    "enctype",
    "method",
    "name",
    "novalidate",
    "target",
]);

export function captureAction(event, root) {
    const eventTarget = event.target instanceof Element ? event.target : null;
    const closestAction = eventTarget?.closest("a, button");
    const target = closestAction && root.contains(closestAction) ? closestAction : eventTarget;
    if (!(target instanceof Element) || !root.contains(target)) return null;

    const kind = actionKind(target);
    const form = kind === "submit" ? target.form : null;

    return {
        kind,
        tagName: target.localName,
        targetId: target.id,
        targetElement: target.id === "" ? target : null,
        signature: signatureOf(target),
        destination: destinationOf(target, form, kind),
        referenceContext: referenceContextOf(target),
        turboEnabled: turboContextOf(target),
        frameContext: frameContextOf(target, form, kind),
        form: form ? {
            id: form.id,
            element: form.id === "" ? form : null,
            signature: formSignatureOf(form),
            payload: formPayloadSignatureOf(form),
            turboEnabled: turboContextOf(form),
        } : null,
    };
}

/**
 * Find the element the captured action belongs to, or null when it cannot be
 * identified unambiguously after the surrounding markup changes.
 */
export function resolveActionElement(action, root) {
    const identified = action.targetId === ""
        ? action.targetElement
        : findById(root, action.targetId);

    if (!(identified instanceof Element) || !root.contains(identified)) return null;

    return matchesAction(identified, action) ? identified : null;
}

export function replayAction(action, root) {
    const target = resolveActionElement(action, root);
    if (!target || isDisabled(target)) return false;

    target.click();

    return true;
}

function isDisabled(element) {
    return element.matches(":disabled, [aria-disabled='true']");
}

function matchesAction(candidate, action) {
    if (candidate.localName !== action.tagName) return false;
    if (actionKind(candidate) !== action.kind) return false;
    if (!sameSignature(signatureOf(candidate), action.signature)) return false;
    if (destinationOf(candidate, candidate.form, action.kind) !== action.destination) return false;
    if (turboContextOf(candidate) !== action.turboEnabled) return false;

    const referenceContext = referenceContextOf(candidate);
    if (referenceContext === null || action.referenceContext === null) return false;
    if (!sameSignature(referenceContext, action.referenceContext)) return false;

    const form = action.kind === "submit" ? candidate.form : null;
    if (!matchesForm(form, action.form)) return false;

    const frameContext = frameContextOf(candidate, form, action.kind);

    return frameContext !== null && action.frameContext !== null &&
        sameSignature(frameContext, action.frameContext);
}

function actionKind(element) {
    if (element.localName === "a" && element.hasAttribute("href")) return "link";

    return element.form && element.type === "submit" ? "submit" : "click";
}

function signatureOf(element) {
    return [...element.attributes]
        .filter(({ name }) => isSignatureAttribute(name))
        .map(({ name, value }) => [name, value])
        .sort(([left], [right]) => left.localeCompare(right));
}

function isSignatureAttribute(name) {
    return SIGNATURE_ATTRIBUTES.has(name) ||
        name.startsWith("data-") ||
        name.startsWith("hx-") ||
        name.startsWith("wire:") ||
        name.startsWith("x-") ||
        name.startsWith("on");
}

function formSignatureOf(form) {
    return [...form.attributes]
        .filter(({ name }) => FORM_SIGNATURE_ATTRIBUTES.has(name) || isSignatureAttribute(name))
        .map(({ name, value }) => [name, value])
        .sort(([left], [right]) => left.localeCompare(right));
}

function formPayloadSignatureOf(form) {
    const payload = [];

    for (const control of form.elements) {
        if (!(control instanceof Element) || !control.name || control.matches(":disabled")) continue;

        const type = control.type?.toLowerCase() || "";
        if (["button", "image", "reset", "submit"].includes(type)) continue;
        if (["checkbox", "radio"].includes(type) && !control.checked) continue;

        if (control.localName === "select") {
            for (const option of control.selectedOptions) {
                if (!option.disabled && !option.parentElement?.matches("optgroup:disabled")) {
                    payload.push([control.name, option.value]);
                }
            }
        } else if (type === "file") {
            const files = [...(control.files ?? [])];
            if (files.length === 0) payload.push([control.name, "file:empty"]);

            for (const file of files) {
                payload.push([control.name, `file:${file.name}:${file.type}:${file.size}:${file.lastModified}`]);
            }
        } else if (["input", "textarea"].includes(control.localName)) {
            payload.push([control.name, String(control.value)]);
        }
    }

    return payload;
}

function matchesForm(form, captured) {
    if (captured === null) return form === null;
    if (!(form instanceof Element)) return false;

    const identified = captured.id === ""
        ? captured.element
        : findById(document.documentElement, captured.id);
    if (identified !== form) return false;
    if (!sameSignature(formSignatureOf(form), captured.signature)) return false;
    if (!sameSignature(formPayloadSignatureOf(form), captured.payload)) return false;

    return turboContextOf(form) === captured.turboEnabled;
}

function destinationOf(element, form, kind) {
    let value;

    if (kind === "link") {
        value = element.getAttribute("href");
    } else if (kind === "submit") {
        value = firstAttribute("formaction", element);
        if (value === null) value = form?.getAttribute("action") ?? "";
    } else {
        return null;
    }

    try {
        return value === ""
            ? new URL(document.location.href).href
            : new URL(value, document.baseURI).href;
    } catch (_error) {
        return value;
    }
}

function inheritedAttribute(target, root, name) {
    let current = target;
    while (current) {
        if (current.hasAttribute(name)) return current.getAttribute(name);
        if (current === root) break;

        current = current.parentElement;
    }

    return null;
}

function turboContextOf(element) {
    const value = inheritedAttribute(element, document.documentElement, "data-turbo");

    return value === null ? null : value !== "false";
}

function frameContextOf(element, form, kind) {
    const frame = kind === "submit" ? form?.closest("turbo-frame") : element.closest("turbo-frame");
    const explicit = kind === "submit"
        ? element.getAttribute("data-turbo-frame") || form?.getAttribute("data-turbo-frame")
        : element.getAttribute("data-turbo-frame");
    const target = explicit ||
        frame?.getAttribute("target") ||
        frame?.id ||
        "";
    const context = [["target", target]];
    appendElementContext(context, "owner", frame);

    if (target === "_parent") {
        appendElementContext(context, "parent", frame?.parentElement?.closest("turbo-frame"));
    }

    if (target === "" || target === "_top") return context;

    if (!appendReferencedElementContext(context, "target", target, frameSignatureOf)) return null;

    return context;
}

function referenceContextOf(element) {
    const context = [];

    for (const name of ["commandfor", "popovertarget"]) {
        if (!element.hasAttribute(name)) continue;

        const id = element.getAttribute(name);
        context.push([name, id]);
        if (id !== "" && !appendReferencedElementContext(context, name, id)) return null;
    }

    return context;
}

function firstAttribute(name, ...elements) {
    for (const element of elements) {
        if (element?.hasAttribute(name)) return element.getAttribute(name);
    }

    return null;
}

function appendElementContext(context, prefix, element) {
    context.push([`${prefix}-id`, element?.id || ""]);
    if (!element) return;

    context.push([`${prefix}-disabled`, element.hasAttribute("disabled") ? "true" : "false"]);
}

function appendReferencedElementContext(context, prefix, id, signature = signatureOf) {
    const matches = [document.documentElement, ...document.documentElement.querySelectorAll("[id]")]
        .filter((candidate) => candidate.id === id);
    context.push([`${prefix}-count`, String(matches.length)]);
    if (matches.length > 1) return false;
    if (matches.length === 0) return true;

    context.push([`${prefix}-tag`, matches[0].localName]);
    for (const [name, value] of signature(matches[0])) {
        context.push([`${prefix}:${name}`, value]);
    }

    return true;
}

function frameSignatureOf(frame) {
    return [["disabled", frame.hasAttribute("disabled") ? "true" : "false"]];
}

function sameSignature(left, right) {
    if (left.length !== right.length) return false;

    return left.every(([name, value], index) => name === right[index][0] && value === right[index][1]);
}

function findById(root, id) {
    const matches = [root, ...root.querySelectorAll("[id]")].filter((element) => element.id === id);

    return matches.length === 1 ? matches[0] : null;
}
