import { afterEach, beforeEach, expect, mock, test } from "bun:test";

const createCalls = [];
const destroyMock = mock(() => {});

const { mountController } = await import("../../resources/js/helpers/test_stimulus.js");
const { default: ToasterController } = await import(
    "../../resources/js/controllers/toaster_controller.js"
);

// The manager is covered in _toaster.test.js. Here the seam records the options the controller
// derives from its values, and stands in for the instance published on window.
class TestToasterController extends ToasterController {
    connect() {
        this.element.showPopoverCalls = 0;
        this.element.hidePopoverCalls = 0;
        this.element.showPopover = () => {
            this.element.showPopoverCalls += 1;
        };
        this.element.hidePopover = () => {
            this.element.hidePopoverCalls += 1;
        };

        super.connect();
    }

    createToaster(options) {
        createCalls.push(options);

        return { destroy: destroyMock, element: this.element };
    }
}

let mounted;

beforeEach(() => {
    createCalls.length = 0;
    destroyMock.mockClear();
    if (typeof window !== "undefined") window.toaster = null;
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- Contract ---
//
// A single instance published on window, kept alive across reconnects, exposing destroy(), and the
// container living in the top layer above other overlays.

test.serial("publishes a single instance on window.toaster exposing destroy()", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    expect(window.toaster).toBeDefined();
    expect(typeof window.toaster.destroy).toBe("function");
});

test.serial("creates the instance even when the element id already shadows window.toaster", async () => {
    // An element with id="toaster" is exposed as window.toaster by named access on the Window
    // object, before any controller runs. A truthiness guard would read the div and skip creation.
    await mount(`<div id="toaster" data-controller="toaster"></div>`);

    expect(createCalls).toHaveLength(1);
    expect(typeof window.toaster.destroy).toBe("function");
    expect(window.toaster).not.toBe(mounted.root);
});

test.serial("keeps the same instance across reconnects", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    const first = window.toaster;
    mounted.controller.connect();

    expect(window.toaster).toBe(first);
});

test.serial("idempotent: re-connect skips create when an instance already exists", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    expect(createCalls).toHaveLength(1);

    mounted.controller.connect();

    expect(createCalls).toHaveLength(1);
});

test.serial("recreates the instance when the global toaster was destroyed", async () => {
    await mount(`<div data-controller="toaster"></div>`);
    window.toaster.destroyed = true;
    mounted.controller.connect();

    expect(createCalls).toHaveLength(2);
    expect(window.toaster.destroyed).toBeUndefined();
});

// --- Top layer ---

test.serial("shows the toaster container in the top layer when supported", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    expect(mounted.root.getAttribute("popover")).toBe("manual");
    expect(mounted.root.hasAttribute("data-hotwire-top-layer")).toBe(true);
    expect(mounted.root.showPopoverCalls).toBe(1);
});

test.serial("moves the toaster above newly shown top-layer overlays", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    document.dispatchEvent(new CustomEvent("hotwire:top-layer:show", {
        detail: { element: document.createElement("div") },
    }));

    expect(mounted.root.hidePopoverCalls).toBe(1);
    expect(mounted.root.showPopoverCalls).toBe(2);
});

test.serial("ignores its own top-layer show event", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    document.dispatchEvent(new CustomEvent("hotwire:top-layer:show", {
        detail: { element: mounted.root },
    }));

    expect(mounted.root.hidePopoverCalls ?? 0).toBe(0);
    expect(mounted.root.showPopoverCalls).toBe(1);
});

// --- Options ---

test.serial("derives default options from its values", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    expect(createCalls).toHaveLength(1);
    expect(createCalls[0]).toEqual({
        className: "",
        closeButton: true,
        containerAriaLabel: "",
        duration: 4000,
        expand: false,
        position: "bottom-center",
        visibleToasts: 3,
    });
});

test.serial("passes overridden values through", async () => {
    await mount(`
        <div data-controller="toaster"
             data-toaster-position-value="top-right"
             data-toaster-duration-value="2000"
             data-toaster-close-button-value="false"
             data-toaster-expand-value="true"
             data-toaster-visible-toasts-value="5"
             data-toaster-container-aria-label-value="Alerts"
        ></div>
    `);

    expect(createCalls[0].position).toBe("top-right");
    expect(createCalls[0].duration).toBe(2000);
    expect(createCalls[0].closeButton).toBe(false);
    expect(createCalls[0].expand).toBe(true);
    expect(createCalls[0].visibleToasts).toBe(5);
    expect(createCalls[0].containerAriaLabel).toBe("Alerts");
});


// --- disconnect ---

test.serial("disconnect with autoDisconnect=true destroys toaster and clears reference", async () => {
    await mount(`<div data-controller="toaster" data-toaster-auto-disconnect-value="true"></div>`);

    expect(window.toaster).toBeDefined();
    mounted.controller.disconnect();

    expect(destroyMock).toHaveBeenCalledTimes(1);
    expect(window.toaster).toBeNull();
});

test.serial("disconnect with autoDisconnect=false (default) keeps toaster alive", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    mounted.controller.disconnect();

    expect(destroyMock).not.toHaveBeenCalled();
    expect(window.toaster).toBeDefined();
});

async function mount(html) {
    mounted = await mountController("toaster", TestToasterController, html);
}

test.serial("recreates the instance when its viewport left the document", async () => {
    // Without data-turbo-permanent the viewport is a new element after every Drive visit. The
    // manager left behind is still live, so the id guard alone would keep writing toasts into the
    // detached node.
    await mount(`<div data-controller="toaster"></div>`);

    const stale = window.toaster;
    mounted.root.remove();
    mounted.controller.connect();

    expect(destroyMock).toHaveBeenCalledTimes(1);
    expect(createCalls).toHaveLength(2);
    expect(window.toaster).not.toBe(stale);
});

test.serial("keeps the instance while its viewport is still in the document", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    const first = window.toaster;
    mounted.controller.connect();

    expect(destroyMock).not.toHaveBeenCalled();
    expect(window.toaster).toBe(first);
});
