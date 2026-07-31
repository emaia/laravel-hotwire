// @hotwire-package

export function frameEventAffects(element, event, associatedFrameId = null) {
    const frame = event?.target;
    if (!frame || frame.tagName !== "TURBO-FRAME") return false;

    return frame.id === associatedFrameId
        || frame === element
        || frame.contains(element)
        || element.contains(frame);
}

export function submissionFrameId(form, event) {
    const submitter = event?.detail?.formSubmission?.submitter ?? event?.detail?.submitter;
    const containingFrame = form?.closest?.("turbo-frame");
    const target = submitter?.getAttribute?.("data-turbo-frame")
        || form?.getAttribute?.("data-turbo-frame")
        || containingFrame?.getAttribute?.("target")
        || containingFrame?.id
        || null;

    return target && target !== "_top" ? target : null;
}
