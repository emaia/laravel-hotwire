import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";

const start = mock(() => Promise.resolve(true));
const stop = mock(() => {});
mock.module("../../resources/js/controllers/_floating.js", () => ({
    createFloating: () => ({ start, stop }),
}));

let mounted;
let products;
let company;
let first;
let second;
let viewport;

beforeEach(async () => {
    const { default: Controller } = await import("../../resources/js/controllers/navigation_menu_controller.js");
    start.mockClear();
    stop.mockClear();
    mounted = await mountController("navigation-menu", Controller, `
        <nav data-controller="navigation-menu" data-navigation-menu-close-delay-value="10" data-navigation-menu-open-delay-value="0"
             data-action="pointerenter->navigation-menu#cancelClose pointerleave->navigation-menu#leave
                 focusout->navigation-menu#focusOut keydown@window->navigation-menu#dismissPreviewOnTab:capture
                 keydown->navigation-menu#keydown keydown.esc@window->navigation-menu#escapeOutside:capture click@document->navigation-menu#outside
                 turbo:before-cache@document->navigation-menu#close turbo:morph@document->navigation-menu#close">
            <ul data-navigation-menu-target="list">
                <li><button aria-controls="first" data-navigation-menu-target="trigger"
                            data-action="pointerenter->navigation-menu#hoverTrigger pointerleave->navigation-menu#cancelOpen navigation-menu#toggle">Products</button></li>
                <li><button aria-controls="second" data-navigation-menu-target="trigger"
                            data-action="pointerenter->navigation-menu#hoverTrigger pointerleave->navigation-menu#cancelOpen navigation-menu#toggle">Company</button></li>
                <li><a href="/about" data-navigation-menu-target="link"
                       data-action="pointerenter->navigation-menu#hoverLink">About</a></li>
            </ul>
            <div data-navigation-menu-target="viewport" hidden inert>
                <div id="first" data-navigation-menu-target="content" hidden inert>
                    <a href="/products">All products</a>
                </div>
                <div id="second" data-navigation-menu-target="content" hidden inert>
                    <turbo-frame id="company-frame" data-navigation-menu-src="/menu/company"></turbo-frame>
                </div>
            </div>
        </nav>
        <button id="outside">Outside</button>
    `);
    [products, company] = mounted.root.querySelectorAll("button");
    first = document.getElementById("first");
    second = document.getElementById("second");
    viewport = first.parentElement;
});

afterEach(async () => {
    await mounted?.cleanup();
});

function pointer(element, type, pointerType = "mouse") {
    element.dispatchEvent(new mounted.window.PointerEvent(type, { pointerType }));
}

test("toggles on click without treating touch entry as hover", () => {
    pointer(products, "pointerenter", "touch");
    expect(first.hidden).toBe(true);
    products.click();
    expect(first.hidden).toBe(false);
    expect(products.getAttribute("aria-expanded")).toBe("true");
    expect(viewport.hidden).toBe(false);
    products.click();
    expect(products.dataset.state).toBe("closed");
    expect(first.hasAttribute("inert")).toBe(true);
    expect(viewport.hidden).toBe(true);
});

test("cancels delayed hover when a quick click activates the trigger", async () => {
    mounted.controller.openDelayValue = 15;
    pointer(products, "pointerenter");
    expect(viewport.hidden).toBe(true);
    products.click();
    expect(viewport.hidden).toBe(false);
    products.click();
    await wait(35);
    expect(viewport.hidden).toBe(true);

    pointer(products, "pointerenter");
    pointer(products, "pointerleave");
    await wait(35);
    expect(viewport.hidden).toBe(true);
});

test("a click on a hover preview activates it before another click dismisses it", () => {
    pointer(products, "pointerenter");
    expect(first.hidden).toBe(false);
    products.click();
    expect(first.hidden).toBe(false);
    products.click();
    expect(first.hidden).toBe(true);
});

test("switches panels in both directions without hiding the shared viewport", () => {
    pointer(products, "pointerenter");
    pointer(company, "pointerenter");
    expect(products.getAttribute("aria-expanded")).toBe("false");
    expect(company.getAttribute("aria-expanded")).toBe("true");
    expect(first.hidden).toBe(true);
    expect(second.dataset.motion).toBe("from-end");
    expect(viewport.hidden).toBe(false);

    pointer(products, "pointerenter");
    expect(first.dataset.motion).toBe("from-start");
    expect(second.hidden).toBe(true);
    expect(viewport.hidden).toBe(false);
});

test("rounds fractional panel sizes up so the viewport does not clip them", () => {
    first.getBoundingClientRect = () => ({ width: 185.2, height: 95.4 });
    second.getBoundingClientRect = () => ({ width: 320.1, height: 110.3 });

    products.click();
    expect(viewport.style.getPropertyValue("--navigation-menu-viewport-width")).toBe("186px");
    expect(viewport.style.getPropertyValue("--navigation-menu-viewport-height")).toBe("96px");
    company.click();
    expect(viewport.style.getPropertyValue("--navigation-menu-viewport-width")).toBe("321px");
    expect(viewport.style.getPropertyValue("--navigation-menu-viewport-height")).toBe("111px");
});

test("loads a submenu frame once and retains the returned markup", () => {
    const frame = second.firstElementChild;
    expect(frame.hasAttribute("src")).toBe(false);
    company.click();
    expect(frame.getAttribute("src")).toBe("/menu/company");
    frame.setAttribute("src", "/menu/company/redirected");
    frame.innerHTML = `<a href="/company">Loaded</a>`;
    company.click();
    company.click();
    expect(frame.getAttribute("src")).toBe("/menu/company/redirected");
    expect(frame.textContent).toBe("Loaded");
});

test("delays pointer dismissal but cancels it on entry into the shared viewport", async () => {
    pointer(products, "pointerenter");
    pointer(mounted.root, "pointerleave");
    expect(viewport.hidden).toBe(false);
    pointer(mounted.root, "pointerenter");
    await wait(30);
    expect(viewport.hidden).toBe(false);
    pointer(mounted.root, "pointerleave");
    await wait(30);
    expect(viewport.hidden).toBe(true);
});

test("dismisses on Escape without propagating to enclosing listeners", () => {
    const parent = mock(() => {});
    document.body.addEventListener("keydown", parent);
    products.click();
    first.querySelector("a").focus();
    const event = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    first.querySelector("a").dispatchEvent(event);
    expect(parent).not.toHaveBeenCalled();
    expect(event.defaultPrevented).toBe(true);
    expect(viewport.hidden).toBe(true);
    expect(document.activeElement).toBe(products);
});

test("Escape dismisses a hover preview without taking focus from another entry", () => {
    company.focus();
    pointer(products, "pointerenter");
    company.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
    expect(viewport.hidden).toBe(true);
    expect(document.activeElement === company).toBe(true);
});

test("Escape over a hover-open submenu does not move focus from outside the navigation", () => {
    const outside = document.getElementById("outside");
    outside.focus();
    pointer(products, "pointerenter");
    outside.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
    expect(viewport.hidden).toBe(true);
    expect(document.activeElement).toBe(outside);
});

test("closes for outside clicks and before Turbo caches, without clearing loaded frames", () => {
    products.click();
    document.getElementById("outside").click();
    expect(viewport.hidden).toBe(true);
    company.click();
    document.dispatchEvent(new Event("turbo:before-cache", { bubbles: true }));
    expect(mounted.root.hasAttribute("data-hotwire-escape-scope")).toBe(false);
    expect(viewport.hidden).toBe(true);
    expect(viewport.style.getPropertyValue("--navigation-menu-viewport-width")).toBe("");
    expect(second.firstElementChild.getAttribute("src")).toBe("/menu/company");
});

test("disconnect cancels pending closes and tears down positioning and event actions", async () => {
    products.click();
    pointer(mounted.root, "pointerleave");
    mounted.application.unload("navigation-menu");
    expect(stop).toHaveBeenCalledTimes(1);
    expect(viewport.hidden).toBe(true);
    products.click();
    await wait(30);
    expect(start).toHaveBeenCalledTimes(1);
    expect(stop).toHaveBeenCalledTimes(1);
});
