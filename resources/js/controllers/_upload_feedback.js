// @hotwire-package

export function createUploadFeedback({ dropzone, status, announcer, onChange = () => {} }) {
    let baselineElement = null;
    let defaultText = "";
    let defaultHidden = false;
    let stickyError = null;
    let suspended = false;

    const captureBaseline = () => {
        const element = status();
        if (!element) return;

        const declaredText = element.dataset.fileUploadDefaultFeedback;
        if (element === baselineElement && (declaredText === undefined || declaredText === defaultText)) return;

        baselineElement = element;
        defaultText = declaredText ?? element.textContent;
        defaultHidden = element.matches('[data-slot="file-upload-feedback"]') || element.hidden;
    };

    captureBaseline();

    const hasExternalStatus = () => {
        const statusElement = status();
        const dropzoneElement = dropzone();

        return statusElement !== null
            && (dropzoneElement === null || !dropzoneElement.contains(statusElement));
    };

    const restoreBaseline = () => {
        captureBaseline();
        const element = status();
        if (!element) return;

        element.replaceChildren(document.createTextNode(defaultText));
        element.hidden = defaultHidden;
    };

    const projectAria = ({ busy, serverInvalid }, state = null) => {
        const element = dropzone();
        if (!element) return;

        if (state === null) {
            delete element.dataset.state;
        } else {
            element.dataset.state = state;
        }
        element.toggleAttribute("aria-busy", state === "uploading" || busy);
        if (state === "error" || serverInvalid) {
            element.setAttribute("aria-invalid", "true");
        } else {
            element.removeAttribute("aria-invalid");
        }
    };

    const projectManagedPreview = (snapshot, invalid) => {
        const element = dropzone();
        if (!element) return;

        delete element.dataset.state;
        element.toggleAttribute("aria-busy", snapshot.busy);
        if (invalid || snapshot.serverInvalid) {
            element.setAttribute("aria-invalid", "true");
        } else {
            element.removeAttribute("aria-invalid");
        }
    };

    const reset = (snapshot) => {
        if (suspended) return;

        stickyError = null;
        restoreBaseline();
        projectAria(snapshot);
        onChange();
    };

    const present = ({ text, state }, snapshot, { force = false } = {}) => {
        if (suspended) return false;
        captureBaseline();

        if (state === "error") {
            stickyError = text;
        } else if (stickyError && !force) {
            if (snapshot.preview && !hasExternalStatus()) {
                projectManagedPreview(snapshot, true);
            } else {
                projectAria(snapshot, "error");
            }
            onChange();

            return false;
        }

        if (snapshot.preview && !hasExternalStatus() && !force) {
            projectManagedPreview(snapshot, state === "error" || stickyError !== null);
            onChange();

            return true;
        }

        const statusElement = status();
        if (statusElement) {
            statusElement.hidden = false;
            statusElement.replaceChildren(document.createTextNode(text));
        }
        projectAria(snapshot, state);
        onChange();

        return true;
    };

    const reconcile = (snapshot, { clearError = false } = {}) => {
        if (suspended) return;
        captureBaseline();
        if (clearError) stickyError = null;

        if (snapshot.preview && !hasExternalStatus()) {
            if (snapshot.itemErrorText) stickyError = snapshot.itemErrorText;
            projectManagedPreview(snapshot, snapshot.itemErrorText !== null || stickyError !== null);
            onChange();

            return;
        }

        if (snapshot.itemErrorText) {
            stickyError = snapshot.itemErrorText;
            present({ text: snapshot.itemErrorText, state: "error" }, snapshot, { force: true });
            return;
        }

        if (stickyError) {
            present({ text: stickyError, state: "error" }, snapshot, { force: true });
            return;
        }

        if (snapshot.pendingText) {
            present({ text: snapshot.pendingText, state: "uploading" }, snapshot, { force: true });
            return;
        }

        if (snapshot.completedText) {
            present({ text: snapshot.completedText, state: "done" }, snapshot, { force: true });
            return;
        }

        reset(snapshot);
    };

    return {
        get error() {
            return stickyError;
        },

        announce(message) {
            if (suspended) return;

            const element = announcer();
            if (element) element.textContent = message;
        },

        beginSelection(snapshot) {
            if (snapshot.itemErrorText) {
                stickyError = snapshot.itemErrorText;
                present({ text: stickyError, state: "error" }, snapshot, { force: true });

                return;
            }

            reset(snapshot);
            if (!snapshot.preview && snapshot.pendingText) {
                present({ text: snapshot.pendingText, state: "uploading" }, snapshot);
            }
        },

        clearError() {
            stickyError = null;
        },

        present,
        reconcile,
        reset,

        suspend() {
            suspended = true;
        },
    };
}
