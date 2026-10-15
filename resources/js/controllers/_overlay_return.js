// @hotwire-package

let installed = false;

function contextFields(form, declaration) {
    const names = declaration.trim().split(/\s+/).filter(Boolean);
    if (new Set(names).size !== names.length) throw new Error("Overlay return context contains duplicate fields.");

    return names.map((name) => {
        const controls = [...form.elements].filter((control) => control.name === name);
        const control = controls[0];
        const scalar =
            control instanceof HTMLSelectElement
                ? !control.multiple
                : control instanceof HTMLInputElement && ["text", "hidden", "number"].includes(control.type);
        if (
            !/^[A-Za-z_][A-Za-z0-9_]*$/.test(name) ||
            controls.length !== 1 ||
            !scalar ||
            control.matches(":disabled") ||
            control.form !== form
        ) {
            throw new Error("Overlay return context requires one enabled scalar control per declared field.");
        }

        return { name, control, value: control.value };
    });
}

export function captureOverlayReturn(link) {
    const name = link.getAttribute("data-overlay-return-to");
    if (name === null) return null;

    const form = link.closest("form");
    const regions = form
        ? [...form.querySelectorAll("[data-overlay-return-region]")].filter(
              (region) => region.closest("form") === form && region.getAttribute("data-overlay-return-region") === name,
          )
        : [];
    if (!name || !form || regions.length !== 1) {
        throw new Error("Overlay return requires exactly one named region in the initiating form.");
    }

    const region = regions[0];
    const source = region.getAttribute("data-overlay-return-src");
    const declaration = region.getAttribute("data-overlay-return-fields") ?? "";
    const renderer = source === null ? null : new URL(source, document.baseURI);
    if (
        renderer &&
        (!source ||
            /[\u0000-\u0020\u007f\\]/.test(source) ||
            source.startsWith("//") ||
            renderer.origin !== location.origin ||
            renderer.username ||
            renderer.password ||
            renderer.hash)
    ) {
        throw new Error("Overlay return renderer must be a safe same-origin URL.");
    }
    if (!renderer && declaration.trim()) throw new Error("Overlay return context fields require a renderer URL.");

    return {
        name,
        form,
        region,
        opener: link,
        source,
        renderer,
        declaration,
        fields: contextFields(form, declaration),
    };
}

function returnIsLive(record) {
    const context = record.returnContext;
    return (
        record.root.isConnected &&
        !record.retired &&
        record.controller()?.isOpen &&
        (!record.parent || (record.parent.root.isConnected && !record.parent.retired)) &&
        context?.form.isConnected &&
        context.region.isConnected &&
        context.opener.isConnected &&
        context.opener.closest("form") === context.form &&
        context.region.closest("form") === context.form &&
        context.region.getAttribute("data-overlay-return-region") === context.name &&
        context.region.getAttribute("data-overlay-return-src") === context.source &&
        (context.region.getAttribute("data-overlay-return-fields") ?? "") === context.declaration &&
        fieldsMatch(context)
    );
}

function fieldsMatch(context) {
    try {
        const current = contextFields(context.form, context.declaration);
        return (
            current.length === context.fields.length &&
            current.every(
                (entry, index) =>
                    entry.control === context.fields[index].control && entry.value === context.fields[index].value,
            )
        );
    } catch {
        return false;
    }
}

function rendererFeedback(record, message, retryable = false) {
    let status = record.frame.querySelector("[data-hotwire-overlay-return-error]");
    if (!status) {
        const template = record.root.querySelector("template[data-hotwire-overlay-return-feedback]");
        status = template.content.firstElementChild.cloneNode(true);
        status.querySelector("[data-hotwire-overlay-return-retry]").addEventListener("click", (event) => {
            event.stopPropagation();
            void renderResult(record);
        });
        record.frame.append(status);
    }
    status.querySelector("[data-hotwire-overlay-return-message]").textContent = message;
    const retry = status.querySelector("[data-hotwire-overlay-return-retry]");
    retry.hidden = !retryable;
    retry.disabled = record.returnPending;
    (retryable ? retry : status).focus();
}

function ensureReturnContext(record) {
    if (record.retired || !record.root.isConnected) return false;
    if (returnIsLive(record)) return true;

    record.returnFailed = true;
    rendererFeedback(record, "Saved, but the return context changed. Close this dialog and review the parent form.");
    return false;
}

export function cleanupOverlayReturn(record) {
    record.returnRequest?.abort();
    if (record.preventReturnResubmit) record.root.removeEventListener("submit", record.preventReturnResubmit, true);
}

async function renderResult(record) {
    if (record.returnPending || record.returnApplied || record.returnFailed || !ensureReturnContext(record)) return;
    record.returnPending = true;
    rendererFeedback(record, "Saved. Updating the parent field…");
    const request = new AbortController();
    record.returnRequest = request;

    try {
        const url = new URL(record.returnContext.renderer);
        url.searchParams.set("result", record.returnResult);
        for (const field of record.returnContext.fields) url.searchParams.set(`context[${field.name}]`, field.value);
        const response = await fetch(url, {
            headers: { Accept: "text/html", "Turbo-Frame": record.frame.id },
            credentials: "same-origin",
            cache: "no-store",
            redirect: "error",
            signal: request.signal,
        });
        if (!ensureReturnContext(record)) return;
        if (response.status === 409) {
            record.returnFailed = true;
            rendererFeedback(
                record,
                "Saved, but the result is incompatible with the return context. Close this dialog and review the parent form.",
            );
            return;
        }
        if (!response.ok || !response.headers.get("Content-Type")?.toLowerCase().startsWith("text/html")) {
            throw new Error("Overlay return renderer must return an HTML fragment.");
        }
        const html = await response.text();
        if (!html.trim() || /<(?:!doctype\s+html|html|body)(?:\s|>)/i.test(html)) {
            throw new Error("Overlay return renderer must return a non-empty HTML fragment, not a page.");
        }
        if (!ensureReturnContext(record)) return;
        const template = document.createElement("template");
        template.innerHTML = html;
        applyReturn(record, template.content);
        if (record.closeRequested) record.controller()?.close();
    } catch (error) {
        if (error.name === "AbortError" || !ensureReturnContext(record)) return;
        record.returnPending = false;
        rendererFeedback(
            record,
            "Saved, but the field could not be updated. Retry the return without creating the record again.",
            true,
        );
    } finally {
        record.returnPending = false;
        if (record.returnRequest === request) record.returnRequest = null;
    }
}

function applyReturn(record, fragment) {
    const { region } = record.returnContext;
    region.replaceChildren(fragment.cloneNode(true));
    record.returnApplied = true;
    region.dispatchEvent(new Event("input", { bubbles: true }));
    region.dispatchEvent(new Event("change", { bubbles: true }));
}

function reportReturnFailure(
    record,
    message = "Saved, but the return destination changed. Close this dialog and review the parent form.",
) {
    record.returnFailed = true;
    if (!record.frame.isConnected) return;

    let status = record.frame.querySelector("[data-hotwire-overlay-return-error]");
    if (!status) {
        status = document.createElement("p");
        status.setAttribute("data-hotwire-overlay-return-error", "");
        status.setAttribute("role", "alert");
        status.tabIndex = -1;
        record.frame.append(status);
    }
    status.textContent = message;
    status.focus();
}

export function registerOverlayStreamActions(findInstance, StreamActions, openOverlay) {
    if (installed) return;
    if (
        Object.hasOwn(StreamActions, "hotwire-overlay-return") ||
        Object.hasOwn(StreamActions, "hotwire-overlay-close") ||
        Object.hasOwn(StreamActions, "hotwire-overlay-open")
    ) {
        console.error("The application already owns a Hotwire overlay Stream action; the package will not replace it.");
        return;
    }
    installed = true;

    StreamActions["hotwire-overlay-open"] = function () {
        openOverlay(this);
    };

    StreamActions["hotwire-overlay-return"] = function () {
        const record = findInstance(this.getAttribute("target"));
        if (!record || record.returnApplied || record.returnFailed || record.returnResult !== undefined) return;
        record.preventReturnResubmit = (event) => event.preventDefault();
        record.root.addEventListener("submit", record.preventReturnResubmit, true);
        for (const button of record.frame.querySelectorAll(
            'button:not([type]), button[type="submit"], input[type="submit"], input[type="image"]',
        ))
            button.disabled = true;
        if (!record.returnContext || !returnIsLive(record)) {
            reportReturnFailure(record);
            return;
        }

        if (record.returnContext.renderer) {
            record.returnResult = this.getAttribute("data-overlay-result");
            if (!record.returnResult || !record.root.querySelector("template[data-hotwire-overlay-return-feedback]")) {
                reportReturnFailure(
                    record,
                    "Saved, but the return response is missing its result identity or feedback template. Close this dialog and review the parent form.",
                );
                return;
            }
            void renderResult(record);
            return;
        }

        applyReturn(record, this.templateElement.content);
    };

    StreamActions["hotwire-overlay-close"] = function () {
        const target = this.getAttribute("target");
        const record = findInstance(target);
        if (!record) {
            const root = document.getElementById(target);
            if (
                root?.matches('[data-controller~="modal"], [data-controller~="sheet"], [data-controller~="drawer"]') &&
                !root.hasAttribute("data-hotwire-overlay-host") &&
                !root.hasAttribute("data-hotwire-overlay-instance")
            ) {
                root.dispatchEvent(new Event("hotwire:overlay-close"));
            }
            return;
        }
        if (record.retired || record.returnFailed) return;
        if (record.returnResult !== undefined && !record.returnApplied) {
            record.closeRequested = true;
            return;
        }
        record.controller()?.close();
    };
}
