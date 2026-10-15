// @hotwire-package

import { captureOverlayReturn, cleanupOverlayReturn, registerOverlayStreamActions } from "./_overlay_return.js";

const instances = new Map();
const hosts = new Map();

function instanceForFrame(frameId) {
    return [...instances.values()].find((record) => record.frame.id === frameId) ?? null;
}

function openOverlay(stream) {
    const hostId = stream.getAttribute("target");
    const owners = [...(hosts.get(hostId) ?? [])];
    if (owners.length !== 1 || owners[0].family !== stream.getAttribute("data-overlay-family")) {
        console.error(`Overlay open requires exactly one matching host [${hostId}].`);
        return;
    }

    const origin = stream.getAttribute("data-overlay-origin");
    const parent = origin === null ? null : instanceForFrame(origin);
    if (origin !== null && (!parent || parent.retired || !parent.root.isConnected || !parent.controller()?.isOpen)) {
        console.error(`Overlay open origin [${origin}] is no longer active.`);
        return;
    }

    void owners[0].openStream(stream, parent).catch((error) => console.error(error));
}

function retire(record) {
    if (record.retired) return;
    record.retired = true;
    cleanupOverlayReturn(record);
    instances.delete(record.root);
    record.owner.delete(record);
    if (record.owner.size === 0) record.observer.disconnect();

    for (const child of [...instances.values()]) {
        if (child.parent !== record) continue;
        retire(child);
        child.controller()?.overlay?.closeNow({ restoreFocus: false });
        child.root.remove();
    }
}

export function dismissOverlayInstance(controller) {
    const record = instances.get(controller.element);
    if (!record || controller.isOpen || controller.overlay?.isOpening) return;

    retire(record);
    record.root.remove();
}

export function createOverlayHost(controller) {
    const root = controller.element;
    const family = controller.identifier;
    const hostId = root.getAttribute("data-hotwire-overlay-host");
    if (hostId || root.ownerDocument.defaultView?.customElements?.get("turbo-stream")) {
        void import("@hotwired/turbo").then(({ StreamActions }) =>
            registerOverlayStreamActions(instanceForFrame, StreamActions, openOverlay),
        );
    }
    if (!hostId) return null;

    const template = root.cloneNode(true);
    const owned = new Set();
    const frame = controller.dynamicContentTarget;
    if (frame.id !== hostId) throw new Error(`The ${family} host must own the frame [${hostId}].`);
    const observer = new MutationObserver(() => {
        for (const record of [...owned]) {
            if (
                record.root.isConnected &&
                (!record.parent || (record.parent.root.isConnected && !record.parent.retired))
            )
                continue;
            retire(record);
            record.controller()?.overlay?.closeNow({ restoreFocus: false });
            record.root.remove();
        }
    });

    function owns(link) {
        return link?.getAttribute("data-turbo-frame") === hostId && link.hasAttribute("href");
    }

    function openingFor(element) {
        const ancestor = element?.closest("[data-hotwire-overlay-instance]");
        return ancestor ? (instances.get(ancestor) ?? null) : null;
    }

    function cloneShell(token) {
        const instanceRoot = template.cloneNode(true);
        instanceRoot.removeAttribute("data-hotwire-overlay-host");
        instanceRoot.dataset.hotwireOverlayInstance = token;
        instanceRoot.setAttribute("data-turbo-temporary", "");
        const cloneFrame = instanceRoot.querySelector(`turbo-frame#${CSS.escape(hostId)}`);
        cloneFrame.replaceChildren();
        cloneFrame.removeAttribute("src");
        // An explicit opening cannot wait for a hidden host to intersect the viewport.
        cloneFrame.loading = "eager";

        const ids = new Map();
        for (const element of [instanceRoot, ...instanceRoot.querySelectorAll("[id]")]) {
            if (!element.id) continue;
            const id = element === cloneFrame ? `${hostId}--${token}` : `${element.id}--${token}`;
            ids.set(element.id, id);
            element.id = id;
        }

        for (const element of [instanceRoot, ...instanceRoot.querySelectorAll("*")]) {
            for (const attribute of [
                "aria-labelledby",
                "aria-describedby",
                "aria-controls",
                "aria-owns",
                "for",
                `data-${family}-frame-owner`,
            ]) {
                if (!element.hasAttribute(attribute)) continue;
                element.setAttribute(
                    attribute,
                    element
                        .getAttribute(attribute)
                        .split(/\s+/)
                        .map((id) => ids.get(id) ?? id)
                        .join(" "),
                );
            }
        }

        return { root: instanceRoot, frame: cloneFrame };
    }

    function createRecord(parent, returnContext = null) {
        const token = Array.from(crypto.getRandomValues(new Uint8Array(16)), (byte) =>
            byte.toString(16).padStart(2, "0"),
        ).join("");
        const clone = cloneShell(token);
        const record = {
            ...clone,
            token,
            owner: owned,
            observer,
            parent,
            returnContext,
            retired: false,
            controller: () => controller.application.getControllerForElementAndIdentifier(clone.root, family),
        };
        instances.set(record.root, record);
        owned.add(record);
        document.body.append(record.root);
        observer.observe(document.documentElement, { childList: true, subtree: true });

        return record;
    }

    async function connectedInstance(record) {
        await new Promise(requestAnimationFrame);
        if (record.retired || !record.root.isConnected) return null;
        const instance = record.controller();
        if (!instance) {
            retire(record);
            record.root.remove();
            return null;
        }

        return instance;
    }

    async function open(link) {
        const returnContext = captureOverlayReturn(link);
        if (returnContext?.renderer) {
            const feedback = template.querySelector("template[data-hotwire-overlay-return-feedback]")?.content;
            if (
                !feedback?.querySelector("[data-hotwire-overlay-return-error]") ||
                !feedback.querySelector("[data-hotwire-overlay-return-message]") ||
                !feedback.querySelector("button[data-hotwire-overlay-return-retry]")
            ) {
                throw new Error("A rich overlay return requires a complete feedback template on its host.");
            }
        }
        const record = createRecord(openingFor(link), returnContext);
        const instance = await connectedInstance(record);
        if (!instance) return;

        instance.triggerElement = link;
        instance.frameOverlay.prepareOpening(link);
        record.frame.src = link.href;
    }

    async function openStream(stream, parent) {
        const content = stream.templateElement.content.cloneNode(true);
        const source = stream.getAttribute("data-overlay-source");
        const forms = [...content.querySelectorAll("form")];
        if (forms.length && source === null) {
            throw new Error("A streamed overlay containing a form requires a source URL.");
        }
        if (source !== null) {
            const url = new URL(source, document.baseURI);
            if (source.startsWith("//") || url.origin !== location.origin || url.username || url.password || url.hash) {
                throw new Error("Overlay source must be a safe same-origin URL.");
            }
            for (const form of forms) {
                let input = form.querySelector('input[name="_turbo_frame_src"]');
                if (!input) {
                    input = document.createElement("input");
                    input.type = "hidden";
                    input.name = "_turbo_frame_src";
                    form.append(input);
                }
                input.value = url.href;
            }
        }

        const record = createRecord(parent);
        const instance = await connectedInstance(record);
        if (!instance) return;

        instance.triggerElement =
            parent && !parent.root.contains(document.activeElement)
                ? (parent.controller()?.modalTarget ?? parent.root)
                : document.activeElement;
        instance.frameOverlay.prepareOpening(stream);
        record.frame.replaceChildren(content);
    }

    const hostEntry = { family, openStream };
    const matchingHosts = hosts.get(hostId) ?? new Set();
    matchingHosts.add(hostEntry);
    hosts.set(hostId, matchingHosts);

    function click(event) {
        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.altKey ||
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey
        )
            return;
        const link = event.target.closest?.("a[href][data-turbo-frame]");
        if (!owns(link) || link.hasAttribute("download") || (link.target && link.target !== "_self")) return;
        if (document.querySelectorAll(`[data-hotwire-overlay-host="${CSS.escape(hostId)}"]`).length !== 1) {
            event.preventDefault();
            console.error(`The overlay frame [${hostId}] must have exactly one host.`);
            return;
        }

        event.preventDefault();
        void open(link).catch((error) => console.error(error));
    }

    function beforePrefetch(event) {
        if (owns(event.target)) event.preventDefault();
    }

    function beforeRequest(event) {
        const record = openingFor(event.target);
        if (!record || !owned.has(record) || record.retired) return;
        const headers = event.detail.fetchOptions.headers;
        const requestFrame = typeof headers.get === "function" ? headers.get("Turbo-Frame") : headers["Turbo-Frame"];
        const nearestFrame = event.target.closest?.("turbo-frame");
        if (
            (!record.frame.contains(event.target) && event.target !== record.frame) ||
            (requestFrame && requestFrame !== nearestFrame?.id) ||
            (nearestFrame !== record.frame && !requestFrame)
        )
            return;
        if (typeof headers.set === "function") headers.set("X-Hotwire-Overlay-Origin", record.frame.id);
        else headers["X-Hotwire-Overlay-Origin"] = record.frame.id;
        if (nearestFrame !== record.frame) return;
        if (event.target === record.frame) delete event.detail.fetchRequest;
        if (typeof headers.set === "function") {
            headers.set("X-Hotwire-Overlay-Host", hostId);
            headers.set("X-Hotwire-Overlay-Instance", record.token);
        } else {
            headers["X-Hotwire-Overlay-Host"] = hostId;
            headers["X-Hotwire-Overlay-Instance"] = record.token;
        }
    }

    function closed(event) {
        const record = instances.get(event.target.closest?.("[data-hotwire-overlay-instance]"));
        if (!record || event.target !== record.root) return;
        retire(record);
        queueMicrotask(() => record.root.remove());
    }

    function reset() {
        for (const record of owned) {
            retire(record);
            record.controller()?.overlay?.closeNow({ restoreFocus: false });
            record.root.remove();
        }
        owned.clear();
        observer.disconnect();
    }

    document.addEventListener("click", click, true);
    document.addEventListener("turbo:before-prefetch", beforePrefetch);
    document.addEventListener("turbo:before-fetch-request", beforeRequest);
    document.addEventListener(`${family}:closed`, closed);
    document.addEventListener("turbo:before-cache", reset);

    return {
        cleanup() {
            matchingHosts.delete(hostEntry);
            if (matchingHosts.size === 0) hosts.delete(hostId);
            document.removeEventListener("click", click, true);
            document.removeEventListener("turbo:before-prefetch", beforePrefetch);
            document.removeEventListener("turbo:before-fetch-request", beforeRequest);
            document.removeEventListener(`${family}:closed`, closed);
            document.removeEventListener("turbo:before-cache", reset);
            reset();
        },
    };
}
