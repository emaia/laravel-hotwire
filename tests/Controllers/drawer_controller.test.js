import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import DrawerController from "../../resources/js/controllers/drawer_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.body.className = "";
});

function html(panelHidden = "translate-x-full", panelVisible = "translate-x-0") {
    return `
        <div data-controller="drawer"
             data-drawer-open-duration-value="1"
             data-drawer-close-duration-value="1"
             data-drawer-hidden-class="pointer-events-none"
             data-drawer-visible-class="pointer-events-auto"
             data-drawer-backdrop-hidden-class="opacity-0"
             data-drawer-backdrop-visible-class="opacity-100"
             data-drawer-dialog-hidden-class="${panelHidden}"
             data-drawer-dialog-visible-class="${panelVisible}"
             data-drawer-lock-scroll-class="overflow-hidden">
            <button id="trigger" data-drawer-target="trigger" data-action="drawer#toggle">Open</button>
            <div data-drawer-target="modal" data-open="false" hidden class="pointer-events-none">
                <div data-drawer-target="backdrop" data-action="click->drawer#clickOutside" class="opacity-0"></div>
                <div data-drawer-target="dialog" class="${panelHidden}">
                    <button id="close" data-action="drawer#close">Close</button>
                    <a id="inside" href="#inside">Inside</a>
                </div>
            </div>
        </div>
    `;
}

function frameHtml(identifier = "drawer") {
    return `
        <div id="${identifier}-shell"
             data-controller="${identifier}"
             data-${identifier}-open-duration-value="1"
             data-${identifier}-close-duration-value="1"
             data-${identifier}-hidden-class="pointer-events-none"
             data-${identifier}-visible-class="pointer-events-auto"
             data-${identifier}-backdrop-hidden-class="opacity-0"
             data-${identifier}-backdrop-visible-class="opacity-100"
             data-${identifier}-dialog-hidden-class="translate-x-full"
             data-${identifier}-dialog-visible-class="translate-x-0"
             data-${identifier}-lock-scroll-class="overflow-hidden">
            <a href="/items/1/edit" data-turbo-frame="${identifier}-frame">Edit</a>
            <a href="/items/1/comments" data-turbo-frame="${identifier}-frame" data-loading-template="#${identifier}-comments-skeleton">Comments</a>
            <template id="${identifier}-comments-skeleton"><div class="comments-skeleton">Loading comments...</div></template>
            <div data-${identifier}-target="modal" data-open="false" hidden class="pointer-events-none">
                <div data-${identifier}-target="backdrop" data-action="click->${identifier}#clickOutside" class="opacity-0"></div>
                <div data-${identifier}-target="dialog" class="translate-x-full">
                    <turbo-frame id="${identifier}-frame" data-${identifier}-target="dynamicContent"></turbo-frame>
                    <template data-${identifier}-target="loadingTemplate"><div class="loading-state">Loading...</div></template>
                </div>
            </div>
        </div>
    `;
}

async function mount(markup = html()) {
    mounted = await mountController("drawer", DrawerController, markup);
    await wait(0);
}

function click(element) {
    element.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
}

function modal() {
    return document.querySelector('[data-drawer-target="modal"]');
}

function panel() {
    return document.querySelector('[data-drawer-target="dialog"]');
}

test.serial("toggle opens and closes the drawer", async () => {
    await mount();

    click(document.getElementById("trigger"));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);
    expect(modal().hidden).toBe(false);
    expect(modal().dataset.open).toBe("true");
    expect(panel().classList.contains("translate-x-0")).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);

    click(document.getElementById("trigger"));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);
    expect(modal().hidden).toBe(true);
    expect(panel().classList.contains("translate-x-full")).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
});

test.serial("connect applies visible state when the drawer is pre-rendered open", async () => {
    await mount(`
        <div data-controller="drawer"
             data-drawer-hidden-class="pointer-events-none"
             data-drawer-visible-class="pointer-events-auto"
             data-drawer-backdrop-hidden-class="opacity-0"
             data-drawer-backdrop-visible-class="opacity-100"
             data-drawer-dialog-hidden-class="translate-x-full"
             data-drawer-dialog-visible-class="translate-x-0"
             data-drawer-lock-scroll-class="overflow-hidden">
            <div data-drawer-target="modal" data-open="true" hidden class="pointer-events-none">
                <div data-drawer-target="backdrop" class="opacity-0"></div>
                <div data-drawer-target="dialog" class="translate-x-full">
                    <p>Drawer content</p>
                </div>
            </div>
        </div>
    `);

    expect(mounted.controller.isOpen).toBe(true);
    expect(modal().hidden).toBe(false);
    expect(modal().dataset.open).toBe("true");
    expect(modal().classList.contains("pointer-events-auto")).toBe(true);
    expect(document.querySelector('[data-drawer-target="backdrop"]').classList.contains("opacity-100")).toBe(true);
    expect(panel().classList.contains("translate-x-0")).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
    expect(mounted.controller.openDurationValue).toBe(450);
    expect(mounted.controller.closeDurationValue).toBe(450);
});

test.serial("backdrop and Escape close the drawer", async () => {
    await mount();

    click(document.getElementById("trigger"));
    await wait(10);
    click(document.querySelector('[data-drawer-target="backdrop"]'));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);

    click(document.getElementById("trigger"));
    await wait(10);
    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("Escape stops peer document handlers while open", async () => {
    await mount();
    let peerSawEscape = false;
    const peer = (event) => {
        if (event.key === "Escape") peerSawEscape = true;
    };
    document.addEventListener("keydown", peer);

    click(document.getElementById("trigger"));
    await wait(10);
    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));

    expect(peerSawEscape).toBe(false);
    document.removeEventListener("keydown", peer);
});

test.serial("supports vertical transform classes", async () => {
    await mount(html("translate-y-full", "translate-y-0"));

    click(document.getElementById("trigger"));
    await wait(10);

    expect(panel().classList.contains("translate-y-0")).toBe(true);
    expect(panel().classList.contains("translate-y-full")).toBe(false);
});

test.serial("closeForCache closes immediately without waiting for transitions", async () => {
    await mount();

    click(document.getElementById("trigger"));
    await wait(10);

    mounted.controller.closeForCache();

    expect(mounted.controller.isOpen).toBe(false);
    expect(modal().hidden).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
});

test.serial("frame content opens the drawer and empty streams close after the animation", async () => {
    await mount(frameHtml());
    const frame = document.getElementById("drawer-frame");

    frame.innerHTML = "<p>Loaded drawer content</p>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);
    expect(modal().hidden).toBe(false);

    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "update");
    stream.setAttribute("target", "drawer-frame");
    stream.innerHTML = "<template></template>";

    let rendered = false;
    stream.performAction = () => {
        rendered = true;
        frame.innerHTML = "";
    };

    document.body.appendChild(stream);
    stream.dispatchEvent(new CustomEvent("turbo:before-stream-render", { bubbles: true }));

    expect(rendered).toBe(false);
    expect(frame.innerHTML).toContain("Loaded drawer content");

    await wait(10);

    expect(rendered).toBe(true);
    expect(mounted.controller.isOpen).toBe(false);
    expect(frame.innerHTML).toBe("");
});

test.serial("empty streams for the drawer root wait for the close animation", async () => {
    await mount(frameHtml());
    const root = document.getElementById("drawer-shell");
    const frame = document.getElementById("drawer-frame");

    frame.innerHTML = "<p>Loaded drawer content</p>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "update");
    stream.setAttribute("target", "drawer-shell");
    stream.innerHTML = "<template></template>";

    let rendered = false;
    stream.performAction = () => {
        rendered = true;
        root.innerHTML = "";
    };

    document.body.appendChild(stream);
    stream.dispatchEvent(new CustomEvent("turbo:before-stream-render", { bubbles: true }));

    expect(rendered).toBe(false);
    expect(root.innerHTML).toContain("Loaded drawer content");

    await wait(10);

    expect(rendered).toBe(true);
    expect(root.innerHTML).toBe("");
});

test.serial("refresh streams wait for the drawer close animation", async () => {
    await mount(frameHtml());
    const frame = document.getElementById("drawer-frame");

    frame.innerHTML = "<form>Loaded drawer form</form>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);

    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "refresh");

    let rendered = false;
    stream.performAction = () => {
        rendered = true;
    };

    document.body.appendChild(stream);
    stream.dispatchEvent(new CustomEvent("turbo:before-stream-render", { bubbles: true }));

    expect(rendered).toBe(false);
    expect(mounted.controller.isOpen).toBe(false);
    expect(modal().hidden).toBe(false);

    await wait(10);

    expect(rendered).toBe(true);
    expect(modal().hidden).toBe(true);
});

test.serial("frame fetches use default and per-link loading templates", async () => {
    await mount(frameHtml());
    const frame = document.getElementById("drawer-frame");

    click(document.querySelector('a[href="/items/1/edit"]'));
    frame.dispatchEvent(new CustomEvent("turbo:before-fetch-request", { bubbles: true }));
    expect(frame.innerHTML).toContain("Loading...");

    frame.innerHTML = "";
    click(document.querySelector('a[href="/items/1/comments"]'));
    frame.dispatchEvent(new CustomEvent("turbo:before-fetch-request", { bubbles: true }));
    expect(frame.innerHTML).toContain("Loading comments...");
    expect(frame.innerHTML).not.toContain("loading-state");
});

test.serial("transient empty frame content during Turbo replacement does not close the drawer", async () => {
    await mount(frameHtml());
    const frame = document.getElementById("drawer-frame");

    frame.innerHTML = "<p>Loading...</p>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);

    frame.innerHTML = "";
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);

    frame.innerHTML = "<p>Final drawer content</p>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);
    expect(frame.innerHTML).toContain("Final drawer content");
});

test.serial("frame replacement keeps the dynamic content target and opens with loaded content", async () => {
    await mount(frameHtml());
    const frame = document.getElementById("drawer-frame");
    const replacement = document.createElement("turbo-frame");

    replacement.id = "drawer-frame";
    replacement.innerHTML = "<p>Replaced drawer content</p>";
    frame.replaceWith(replacement);

    replacement.dispatchEvent(new CustomEvent("turbo:frame-render", { bubbles: true }));
    replacement.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    expect(replacement.getAttribute("data-drawer-target")).toContain("dynamicContent");
    expect(mounted.controller.isOpen).toBe(true);
    expect(replacement.innerHTML).toContain("Replaced drawer content");
});
