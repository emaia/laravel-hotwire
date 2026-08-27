// @hotwire-package
//
// Re-initialize wrapper controllers (chart, map, carousel, rich text) whose
// embedded DOM was wiped by a Turbo morph that preserved the host element.
// Stimulus doesn't emit disconnect/connect in this case because this.element is
// the same node; only its contents changed, leaving the wrapper library
// (ECharts, Leaflet, Embla, Tiptap) pointing at orphan references.
//
// Usage inside a controller's connect():
//
//     this.detachMorphRecovery = attachMorphRecovery(this, {
//         isStale: () => !this.element.querySelector("canvas"),
//         recover: () => this.initChart(),
//     });
//
// And in disconnect():
//
//     this.detachMorphRecovery?.();
//
// The listener is bound to `this.element` (not the document) so each controller
// pays only for the morph events that affect its own subtree.
export function attachMorphRecovery(controller, { isStale, recover }) {
    let attached = true;
    let recoveryQueued = false;

    const onMorph = () => {
        if (recoveryQueued) return;

        recoveryQueued = true;
        queueMicrotask(() => {
            if (!attached) return;

            try {
                if (!document.contains(controller.element)) return;
                if (!isStale()) return;
                recover();
            } finally {
                recoveryQueued = false;
            }
        });
    };

    controller.element.addEventListener("turbo:morph-element", onMorph);

    return () => {
        attached = false;
        controller.element.removeEventListener("turbo:morph-element", onMorph);
    };
}
