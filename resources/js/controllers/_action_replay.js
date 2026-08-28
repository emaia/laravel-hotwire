// @hotwire-package
const LINK_ATTRIBUTES = new Set([
    "download",
    "href",
    "hreflang",
    "ping",
    "referrerpolicy",
    "rel",
    "target",
    "type",
]);
const SUBMIT_ATTRIBUTES = new Set([
    "form",
    "formaction",
    "formenctype",
    "formmethod",
    "formnovalidate",
    "formtarget",
    "name",
    "type",
    "value",
]);
const GENERIC_ATTRIBUTES = new Set(["data-action", "name", "type", "value"]);
const TURBO_ATTRIBUTES = new Set([
    "data-turbo",
    "data-turbo-action",
    "data-turbo-confirm",
    "data-turbo-method",
    "data-turbo-stream",
    "data-turbo-submits-with",
]);
const ACTION_KEY_ATTRIBUTE = "data-hotwire-action-key";
const FORM_KEY_ATTRIBUTE = "data-hotwire-action-form-key";
const retainedKeys = new Map();
let generatedKey = 0;

export function captureAction(event, root) {
    const target = event.target instanceof Element
        ? event.target.closest("a, button") ?? event.target
        : null;
    if (!(target instanceof Element) || !root.contains(target)) return null;

    const boundary = event.currentTarget instanceof Element && root.contains(event.currentTarget)
        ? event.currentTarget
        : root;
    const form = target.form ?? null;
    const kind = actionKind(target);

    const action = {
        kind,
        tagName: target.localName,
        targetId: target.id,
        targetKey: target.id === "" ? elementKey(target, ACTION_KEY_ATTRIBUTE, "action") : "",
        boundaryId: boundary.id,
        boundaryKey: boundary.id === "" ? elementKey(boundary, ACTION_KEY_ATTRIBUTE, "boundary") : "",
        boundaryTagName: boundary.localName,
        boundarySlot: boundary.getAttribute("data-slot"),
        boundaryAction: boundary.getAttribute("data-action"),
        boundaryPath: pathFrom(root, boundary),
        formId: form?.id ?? "",
        formKey: form && form.id === "" ? elementKey(form, FORM_KEY_ATTRIBUTE, "form") : "",
        attributes: capturedAttributes(target, kind),
        turboEnabled: inheritedAttribute(target, root, "data-turbo"),
        frameTarget: resolvedFrameTarget(target, form, kind),
    };

    retainAction(action);

    return action;
}

export function resolveActionElement(action, root) {
    const identified = action.targetId === "" ? null : findById(root, action.targetId);
    if (identified && matchesAction(identified, action, root)) return identified;

    if (action.targetId !== "") return null;

    const keyed = findByKey(root, ACTION_KEY_ATTRIBUTE, action.targetKey);
    if (keyed && matchesAction(keyed, action, root)) return keyed;
    if (action.kind === "click") return null;

    const boundary = resolveBoundary(action, root);
    if (!boundary) return null;
    const matches = [boundary, ...boundary.querySelectorAll("a, button")]
        .filter((candidate) => matchesAction(candidate, action, root));

    return matches.length === 1 ? matches[0] : null;
}

export function replayAction(action, root) {
    const target = resolveActionElement(action, root);
    if (target && isDisabled(target)) return false;

    if (action.kind === "click") {
        if (!target) return false;

        target.click();

        return true;
    }

    const boundary = resolveBoundary(action, root);
    if (!boundary) return false;

    const replayContainer = boundary === root ? root : boundary.parentElement;
    if (!replayContainer || (replayContainer !== root && !root.contains(replayContainer))) return false;

    const proxy = document.createElement(action.tagName);
    for (const [name, value] of action.attributes) proxy.setAttribute(name, value);
    if (!proxy.hasAttribute("data-turbo") && action.turboEnabled !== null) {
        proxy.setAttribute("data-turbo", action.turboEnabled);
    }

    proxy.hidden = true;
    proxy.tabIndex = -1;
    proxy.setAttribute("aria-hidden", "true");
    proxy.setAttribute("data-hotwire-action-replay", "");

    let temporaryFormId = null;
    let form = null;
    if (action.kind === "submit") {
        form = resolveForm(action, root);
        if (!form) return false;

        if (form.id === "") {
            temporaryFormId = uniqueId("hotwire-action-form");
            form.id = temporaryFormId;
        }

        proxy.setAttribute("form", form.id);
    }

    replayContainer.append(proxy);
    try {
        if (action.kind === "submit" && proxy.form === null) return false;
        if (action.frameTarget !== "") proxy.setAttribute("data-turbo-frame", action.frameTarget);

        const submitter = action.kind === "submit" ? target : null;
        if (submitter && submitter.form === form && !submitter.disabled) {
            form.requestSubmit(submitter);
            proxy.type = "button";
            proxy.removeAttribute("form");
            proxy.click();

            return true;
        }

        proxy.click();

        return true;
    } finally {
        proxy.remove();
        if (temporaryFormId && form?.id === temporaryFormId) form.removeAttribute("id");
    }
}

export function releaseAction(action, root) {
    for (const [attribute, key] of actionKeyEntries(action)) {
        const identity = `${attribute}:${key}`;
        const retained = retainedKeys.get(identity) ?? 1;
        if (retained > 1) {
            retainedKeys.set(identity, retained - 1);

            continue;
        }

        retainedKeys.delete(identity);
        for (const searchRoot of new Set([root, document.documentElement])) {
            for (const element of elementsByKey(searchRoot, attribute, key)) element.removeAttribute(attribute);
        }
    }
}

function isDisabled(element) {
    return element.matches(":disabled, [aria-disabled='true']");
}

function resolveForm(action, root) {
    if (action.formId !== "") {
        const form = findById(document.documentElement, action.formId);

        return form?.localName === "form" ? form : null;
    }

    const form = findByKey(document.documentElement, FORM_KEY_ATTRIBUTE, action.formKey);

    return form?.localName === "form" ? form : null;
}

function resolveBoundary(action, root) {
    if (action.boundaryId !== "") {
        const boundary = findById(root, action.boundaryId);

        return boundary;
    }

    const keyed = elementsByKey(root, ACTION_KEY_ATTRIBUTE, action.boundaryKey);
    if (keyed.length === 1) return keyed[0];
    if (keyed.length > 1) return null;

    const candidate = resolvePath(root, action.boundaryPath);
    if (!candidate || candidate.localName !== action.boundaryTagName) return null;
    if (candidate.getAttribute("data-slot") !== action.boundarySlot) return null;
    if (candidate.getAttribute("data-action") !== action.boundaryAction) return null;

    return candidate;
}

function capturedAttributes(target, kind) {
    const allowed = kind === "link"
        ? LINK_ATTRIBUTES
        : kind === "submit" ? SUBMIT_ATTRIBUTES : GENERIC_ATTRIBUTES;

    return [...target.attributes]
        .filter(({ name }) => allowed.has(name) || TURBO_ATTRIBUTES.has(name))
        .map(({ name, value }) => [name, value])
        .sort(([left], [right]) => left.localeCompare(right));
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

function resolvedFrameTarget(element, form, kind) {
    const frame = kind === "submit" ? form?.closest("turbo-frame") : element.closest("turbo-frame");
    const requested = element.getAttribute("data-turbo-frame") ||
        form?.getAttribute("data-turbo-frame") ||
        frame?.getAttribute("target") ||
        frame?.id ||
        "";

    if (requested === "_self") return frame?.id || "_top";
    if (requested !== "_parent") return requested;

    return frame?.parentElement.closest("turbo-frame")?.id || "_top";
}

function elementKey(element, attribute, prefix) {
    const current = element.getAttribute(attribute);
    if (current) {
        const identity = `${attribute}:${current}`;
        if (
            elementsByKey(document.documentElement, attribute, current).length === 1 ||
            retainedKeys.has(identity)
        ) return current;
    }

    const key = uniqueAttributeKey(`hotwire-${prefix}`, attribute);
    element.setAttribute(attribute, key);

    return key;
}

function retainAction(action) {
    for (const [attribute, key] of actionKeyEntries(action)) {
        const identity = `${attribute}:${key}`;
        retainedKeys.set(identity, (retainedKeys.get(identity) ?? 0) + 1);
    }
}

function actionKeyEntries(action) {
    const entries = [
        [ACTION_KEY_ATTRIBUTE, action.targetKey],
        [ACTION_KEY_ATTRIBUTE, action.boundaryKey],
        [FORM_KEY_ATTRIBUTE, action.formKey],
    ].filter(([, key]) => key !== "");

    return entries.filter(([attribute, key], index) =>
        entries.findIndex(([candidateAttribute, candidateKey]) =>
            candidateAttribute === attribute && candidateKey === key,
        ) === index,
    );
}

function findByKey(root, attribute, key) {
    const matches = elementsByKey(root, attribute, key);

    return matches.length === 1 ? matches[0] : null;
}

function elementsByKey(root, attribute, key) {
    if (key === "") return [];

    return [root, ...root.querySelectorAll(`[${attribute}]`)]
        .filter((element) => element.getAttribute(attribute) === key);
}

function findById(root, id) {
    const matches = [root, ...root.querySelectorAll("[id]")]
        .filter((element) => element.id === id);

    return matches.length === 1 ? matches[0] : null;
}

function matchesAction(candidate, action, root) {
    if (candidate.localName !== action.tagName) return false;
    if (actionKind(candidate) !== action.kind) return false;
    if (!sameAttributes(capturedAttributes(candidate, action.kind), action.attributes)) return false;
    if (action.kind === "click") return true;
    if (inheritedAttribute(candidate, root, "data-turbo") !== action.turboEnabled) return false;

    const form = candidate.form ?? null;
    if (action.kind === "submit" && form !== resolveForm(action, root)) return false;

    return resolvedFrameTarget(candidate, form, action.kind) === action.frameTarget;
}

function actionKind(element) {
    if (element.localName === "a" && element.hasAttribute("href")) return "link";

    return element.form && element.type === "submit" ? "submit" : "click";
}

function sameAttributes(left, right) {
    if (left.length !== right.length) return false;

    return left.every(([name, value], index) => name === right[index][0] && value === right[index][1]);
}

function uniqueId(prefix) {
    let id;
    do {
        generatedKey++;
        id = `${prefix}-${generatedKey}`;
    } while (document.getElementById(id));

    return id;
}

function uniqueAttributeKey(prefix, attribute) {
    let key;
    do {
        generatedKey++;
        key = `${prefix}-${generatedKey}`;
    } while (elementsByKey(document.documentElement, attribute, key).length > 0);

    return key;
}

function pathFrom(root, element) {
    const path = [];
    let current = element;

    while (current !== root) {
        const parent = current.parentElement;
        if (!parent) return null;

        path.unshift([...parent.children].indexOf(current));
        current = parent;
    }

    return path;
}

function resolvePath(root, path) {
    if (!Array.isArray(path)) return null;

    let current = root;
    for (const index of path) {
        current = current?.children[index] ?? null;
        if (!current) return null;
    }

    return current;
}
