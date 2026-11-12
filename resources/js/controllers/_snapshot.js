// @hotwire-package

const STATE = "data-hotwire-snapshot";
const GENERATED = "data-hotwire-generated";
const installedDocuments = new WeakSet();

/** Register detached-body preparation before lazy controllers can connect. */
export function installSnapshotNormalization(document) {
    if (installedDocuments.has(document)) return;
    installedDocuments.add(document);
    document.addEventListener("turbo:before-render", (event) => {
        if (event.detail?.renderMethod === "morph") return;
        normalizeSnapshot(event.detail?.newBody);
    });
    document.addEventListener("turbo:before-morph-attribute", (event) => {
        // Server HTML has no runtime provenance; keep it while morph retains the live node.
        if (event.detail?.mutationType === "remove" && [STATE, GENERATED].includes(event.detail.attributeName)) event.preventDefault();
    });
}

/** Normalize only incoming HTML; never tear down a live instance. */
export function normalizeSnapshot(root) {
    if (!root || root.isConnected) return;
    for (const element of [root, ...root.querySelectorAll(`[${STATE}], [${GENERATED}]`)]) {
        if (element.hasAttribute(GENERATED)) {
            element.remove();
            continue;
        }
        restoreSnapshotState(element);
    }
}

/** Record only attributes owned by the caller, retaining explicit application defaults. */
export function rememberSnapshotState(element, { attributes = [], styles = [], classes = [] } = {}) {
    const state = readState(element);
    state.attributes ??= {};
    state.styles ??= {};
    state.classes ??= {};
    if (styles.length) state.styleOrder ??= styleNames(element);
    for (const name of attributes) {
        if (!(name in state.attributes)) state.attributes[name] = element.getAttribute(name);
    }
    for (const name of styles) {
        if (!(name in state.styles)) state.styles[name] = [element.style.getPropertyValue(name), element.style.getPropertyPriority(name)];
    }
    for (const name of classes) {
        if (!(name in state.classes)) state.classes[name] = element.classList.contains(name);
    }
    element.setAttribute(STATE, JSON.stringify(state));
}

/** Record a package-added ARIA token without claiming other descriptions. */
export function rememberSnapshotToken(element, attribute, token) {
    const state = readState(element);
    state.tokens ??= {};
    state.tokens[attribute] = [...new Set([...(state.tokens[attribute] ?? []), token])];
    element.setAttribute(STATE, JSON.stringify(state));
}

/** Release live ownership when a temporary global effect has been undone. */
export function forgetSnapshotState(element, { attributes = [], styles = [], classes = [] } = {}) {
    const state = readState(element);
    for (const [key, names] of Object.entries({ attributes, styles, classes })) {
        for (const name of names) delete state[key]?.[name];
        if (state[key] && !Object.keys(state[key]).length) delete state[key];
    }
    if (!state.styles) delete state.styleOrder;
    Object.keys(state).length ? element.setAttribute(STATE, JSON.stringify(state)) : element.removeAttribute(STATE);
}

/** Mark generated roots rather than guessing ownership from library class names. */
export function markSnapshotGenerated(element, owner) {
    element.setAttribute(GENERATED, owner);
}

/** Remove cloned library roots before mounting an instance, including without the global hook. */
export function normalizeWidget(element, owner) {
    for (const child of [...element.children]) {
        if (child.getAttribute(GENERATED) === owner) child.remove();
    }
}

/** Capture synchronous library-owned host mutations and newly generated roots. */
export function mountSnapshotWidget(element, owner, mount) {
    normalizeWidget(element, owner);
    const children = new Set(element.children);
    const attributes = new Map(element.getAttributeNames().map((name) => [name, element.getAttribute(name)]));
    const classes = new Set(element.classList);
    const styles = new Map(styleNames(element).map((name) => [name, [element.style.getPropertyValue(name), element.style.getPropertyPriority(name)]]));
    const result = mount();
    const state = readState(element);
    state.attributes ??= {};
    state.classes ??= {};
    state.styles ??= {};
    for (const name of new Set([...attributes.keys(), ...element.getAttributeNames()])) {
        if ([STATE, "class", "style"].includes(name) || name in state.attributes) continue;
        if (element.getAttribute(name) !== (attributes.get(name) ?? null)) state.attributes[name] = attributes.get(name) ?? null;
    }
    for (const name of new Set([...classes, ...element.classList])) {
        if (!(name in state.classes) && classes.has(name) !== element.classList.contains(name)) state.classes[name] = classes.has(name);
    }
    for (const name of new Set([...styles.keys(), ...styleNames(element)])) {
        const original = styles.get(name) ?? ["", ""];
        if (!(name in state.styles) && (original[0] !== element.style.getPropertyValue(name) || original[1] !== element.style.getPropertyPriority(name))) state.styles[name] = original;
    }
    if (Object.keys(state.styles).length) state.styleOrder ??= [...styles.keys()];
    element.setAttribute(STATE, JSON.stringify(state));
    for (const child of element.children) {
        if (!children.has(child)) markSnapshotGenerated(child, owner);
    }
    return result;
}

function restoreSnapshotState(element) {
    if (!element.hasAttribute(STATE)) return;
    const state = readState(element);
    for (const [name, value] of Object.entries(state.attributes ?? {})) {
        value === null ? element.removeAttribute(name) : element.setAttribute(name, value);
    }
    for (const [name, [value, priority]] of Object.entries(state.styles ?? {})) {
        value === "" ? element.style.removeProperty(name) : element.style.setProperty(name, value, priority);
    }
    if (state.styleOrder) restoreStyleOrder(element, state.styleOrder);
    for (const [name, present] of Object.entries(state.classes ?? {})) element.classList.toggle(name, present);
    for (const [name, tokens] of Object.entries(state.tokens ?? {})) {
        const remaining = (element.getAttribute(name) ?? "").split(/\s+/).filter((token) => token && !tokens.includes(token));
        remaining.length ? element.setAttribute(name, remaining.join(" ")) : element.removeAttribute(name);
    }
    element.removeAttribute(STATE);
}

function restoreStyleOrder(element, order) {
    const style = element.style;
    // CSSOM setters can move logical properties past physical declarations; retain current application-owned values.
    const declarations = new Map(styleNames(element).map(name => [name, [style.getPropertyValue(name), style.getPropertyPriority(name)]]));
    style.cssText = "";
    for (const name of new Set([...order, ...declarations.keys()])) {
        const declaration = declarations.get(name);
        if (declaration) style.setProperty(name, ...declaration);
    }
}

function readState(element) {
    try {
        return JSON.parse(element.getAttribute(STATE) ?? "{}");
    } catch {
        return {};
    }
}

function styleNames(element) {
    return Array.from({ length: element.style.length }, (_, index) => element.style.item(index));
}
