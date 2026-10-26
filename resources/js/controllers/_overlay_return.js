// @hotwire-package

let installed = false;

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

    return {
        name,
        form,
        region: regions[0],
        opener: link,
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
        context.region.getAttribute("data-overlay-return-region") === context.name
    );
}

export function cleanupOverlayReturn(record) {
    if (record.preventReturnResubmit) record.root.removeEventListener("submit", record.preventReturnResubmit, true);
    for (const button of record.returnDisabledButtons ?? []) button.disabled = false;
    record.preventReturnResubmit = null;
    record.returnDisabledButtons = [];
}

function lockReturnForm(record) {
    // A failed return has already saved the record; replacing its form must not allow accidental creation again.
    const otherForms = record.otherFormsAtSubmission ?? new Set();
    const forms = [...record.frame.querySelectorAll("form")].filter((form) => !otherForms.has(form));
    record.preventReturnResubmit = (event) => {
        if (record.frame.contains(event.target) && !otherForms.has(event.target)) event.preventDefault();
    };
    record.root.addEventListener("submit", record.preventReturnResubmit, true);
    record.returnDisabledButtons = forms
        .flatMap((form) => [...form.elements])
        .filter((element) => ["submit", "image"].includes(element.type) && !element.disabled);
    for (const button of record.returnDisabledButtons) button.disabled = true;
}

function applyReturn(record, fragment) {
    const { region } = record.returnContext;
    region.replaceChildren(fragment.cloneNode(true));
    record.returnApplied = true;
    region.dispatchEvent(new Event("input", { bubbles: true }));
    region.dispatchEvent(new Event("change", { bubbles: true }));
}

function reportReturnFailure(record) {
    record.returnFailed = true;
    lockReturnForm(record);
    if (!record.frame.isConnected) return;

    let status = record.frame.querySelector("[data-hotwire-overlay-return-error]");
    if (!status) {
        status = document.createElement("p");
        status.setAttribute("data-hotwire-overlay-return-error", "");
        status.setAttribute("role", "alert");
        status.tabIndex = -1;
        record.frame.append(status);
    }
    status.textContent = "Saved, but the return destination changed. Close this dialog and review the parent form.";
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
        if (!record || record.returnApplied || record.returnFailed) return;
        if (!record.returnContext || !returnIsLive(record)) {
            reportReturnFailure(record);
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
        record.controller()?.close();
    };
}
