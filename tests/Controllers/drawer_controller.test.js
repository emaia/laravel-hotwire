import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import DrawerController from "../../resources/js/controllers/drawer_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.body.className = "";
});

function html() {
    return `
        <div data-controller="drawer"
             data-drawer-lock-scroll-class="overflow-hidden">
            <button id="trigger" data-drawer-target="trigger" data-action="drawer#toggle">Open</button>
            <div data-drawer-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-drawer-target="backdrop" data-action="click->drawer#clickOutside"></div>
                <div data-drawer-target="dialog">
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
             data-${identifier}-lock-scroll-class="overflow-hidden">
            <a href="/items/1/edit" data-turbo-frame="${identifier}-frame">Edit</a>
            <a href="/items/1/comments" data-turbo-frame="${identifier}-frame" data-loading-template="#${identifier}-comments-skeleton">Comments</a>
            <template id="${identifier}-comments-skeleton"><div class="comments-skeleton">Loading comments...</div></template>
            <div data-${identifier}-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-${identifier}-target="backdrop" data-action="click->${identifier}#clickOutside"></div>
                <div data-${identifier}-target="dialog">
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
    expect(modal().dataset.state).toBe("open");
    expect(modal().hasAttribute("inert")).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);

    click(document.getElementById("trigger"));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);
    expect(modal().hidden).toBe(true);
    expect(modal().dataset.state).toBe("closed");
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
});

test.serial("connect applies visible state when the drawer is pre-rendered open", async () => {
    await mount(`
        <div data-controller="drawer"
             data-drawer-lock-scroll-class="overflow-hidden">
            <div data-drawer-target="modal" data-state="open" data-motion="none">
                <div data-drawer-target="backdrop"></div>
                <div data-drawer-target="dialog">
                    <p>Drawer content</p>
                </div>
            </div>
        </div>
    `);

    expect(mounted.controller.isOpen).toBe(true);
    expect(modal().hidden).toBe(false);
    expect(modal().dataset.state).toBe("open");
    expect(modal().hasAttribute("inert")).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
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

test.serial("preserves directional styling hooks on the dialog", async () => {
    await mount(html().replace('data-drawer-target="dialog"', 'data-drawer-target="dialog" data-direction="up"'));

    click(document.getElementById("trigger"));
    await wait(10);

    expect(panel().dataset.direction).toBe("up");
    expect(modal().dataset.state).toBe("open");
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

test.serial("Tab enters a dynamically loaded form after the drawer opened with loading content", async () => {
    await mount(frameHtml());
    const link = document.querySelector('a[href="/items/1/edit"]');
    const frame = document.getElementById("drawer-frame");

    link.focus();
    frame.innerHTML = "<p>Loading...</p>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);
    expect(document.activeElement).toBe(link);

    frame.innerHTML = `
        <form>
            <input id="drawer-name" type="text" />
            <button id="drawer-save" type="submit">Save</button>
        </form>
    `;
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(0);

    const event = new KeyboardEvent("keydown", { key: "Tab", bubbles: true, cancelable: true });
    document.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(document.getElementById("drawer-name"));
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
    expect(modal().hidden).toBe(true);

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
