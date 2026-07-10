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
