// @hotwire-package

/**
 * Attributes that define what an action does. Captured to verify that a resolved
 * element still performs the action the user confirmed — never to rebuild it.
 */
const SIGNATURE_ATTRIBUTES = new Set([
    "data-action",
    "data-turbo",
    "data-turbo-action",
    "data-turbo-confirm",
    "data-turbo-frame",
    "data-turbo-method",
    "data-turbo-stream",
    "data-turbo-submits-with",
    "download",
    "formaction",
    "formenctype",
    "formmethod",
    "formnovalidate",
    "formtarget",
    "href",
    "name",
    "rel",
    "target",
    "type",
    "value",
]);

export function captureAction(event, root) {
    const target = event.target instanceof Element
        ? event.target.closest("a, button") ?? event.target
        : null;
    if (!(target instanceof Element) || !root.contains(target)) return null;

    const path = pathFrom(root, target);
    if (target.id === "" && path === null) return null;

    return {
        kind: actionKind(target),
        tagName: target.localName,
        targetId: target.id,
        path,
        signature: signatureOf(target),
    };
}

/**
 * Find the element the captured action belongs to, or null when it cannot be
 * identified unambiguously — a morph may have replaced or reordered the markup.
 */
export function resolveActionElement(action, root) {
    const identified = action.targetId === ""
        ? resolvePath(root, action.path)
        : findById(root, action.targetId);

    return identified && matchesAction(identified, action) ? identified : null;
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

    return sameSignature(signatureOf(candidate), action.signature);
}

function actionKind(element) {
    if (element.localName === "a" && element.hasAttribute("href")) return "link";

    return element.form && element.type === "submit" ? "submit" : "click";
}

function signatureOf(element) {
    return [...element.attributes]
        .filter(({ name }) => SIGNATURE_ATTRIBUTES.has(name))
        .map(({ name, value }) => [name, value])
        .sort(([left], [right]) => left.localeCompare(right));
}

function sameSignature(left, right) {
    if (left.length !== right.length) return false;

    return left.every(([name, value], index) => name === right[index][0] && value === right[index][1]);
}

function findById(root, id) {
    const matches = [root, ...root.querySelectorAll("[id]")].filter((element) => element.id === id);

    return matches.length === 1 ? matches[0] : null;
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
