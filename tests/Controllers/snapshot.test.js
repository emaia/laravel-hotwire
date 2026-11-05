import { afterEach, beforeEach, expect, test } from "bun:test";
import { Window } from "happy-dom";
import { installSnapshotNormalization, markSnapshotGenerated, mountSnapshotWidget, normalizeSnapshot, normalizeWidget, rememberSnapshotState, rememberSnapshotToken } from "../../resources/js/controllers/_snapshot.js";

let window;
let document;
beforeEach(() => {
    window = new Window();
    document = window.document;
});
afterEach(() => window.close());

test("normalization is detached-only, idempotent and does not enter application templates", () => {
    document.body.innerHTML = '<section id="host"><p>Application</p><template><i data-hotwire-generated="map">Template</i></template></section>';
    const host = document.querySelector("#host");
    mountSnapshotWidget(host, "map", () => {
        host.classList.add("library");
        host.style.position = "relative";
        host.tabIndex = 0;
        host.append(document.createElement("canvas"));
    });
    host.classList.add("later-application-class");
    host.style.color = "red";
    normalizeSnapshot(document.body);
    expect(host.querySelector("canvas")).not.toBeNull();
    const clone = document.body.cloneNode(true);
    normalizeSnapshot(clone);
    const normalized = clone.querySelector("#host");
    expect(normalized.querySelector("canvas")).toBeNull();
    expect(normalized.querySelector("p").textContent).toBe("Application");
    expect(normalized.querySelector("template").content.querySelector("i")).not.toBeNull();
    expect(normalized.classList.contains("library")).toBe(false);
    expect(normalized.classList.contains("later-application-class")).toBe(true);
    expect(normalized.style.position).toBe("");
    expect(normalized.style.color).toBe("red");
    expect(normalized.hasAttribute("tabindex")).toBe(false);
    const html = clone.outerHTML;
    normalizeSnapshot(clone);
    expect(clone.outerHTML).toBe(html);
});

test("only recorded ARIA tokens and generated roots are removed", () => {
    const body = document.createElement("body");
    body.innerHTML = '<button aria-describedby="app-help tooltip-id later-help"></button><aside id="tooltip-id"></aside>';
    rememberSnapshotToken(body.firstElementChild, "aria-describedby", "tooltip-id");
    markSnapshotGenerated(body.lastElementChild, "tooltip");
    normalizeSnapshot(body);
    expect(body.querySelector("aside")).toBeNull();
    expect(body.querySelector("button").getAttribute("aria-describedby")).toBe("app-help later-help");
});

test("local wrapper normalization removes every owned root and leaves other controllers alone", () => {
    const host = document.createElement("div");
    host.innerHTML = '<div data-hotwire-generated="map"></div><div data-hotwire-generated="map"></div><div data-hotwire-generated="other"></div><span>Keep</span>';
    normalizeWidget(host, "map");
    expect(host.children.length).toBe(2);
    expect(host.querySelector('[data-hotwire-generated="other"]')).not.toBeNull();
});

test("the hook registers once, prepares replace renders and skips morph", () => {
    let registrations = 0;
    const add = document.addEventListener.bind(document);
    document.addEventListener = (...args) => { registrations++; add(...args); };
    installSnapshotNormalization(document);
    installSnapshotNormalization(document);
    expect(registrations).toBe(2);
    const body = document.createElement("body");
    body.innerHTML = '<div data-hotwire-generated="map"></div>';
    document.dispatchEvent(new window.CustomEvent("turbo:before-render", { detail: { newBody: body, renderMethod: "morph" } }));
    expect(body.children.length).toBe(1);
    document.dispatchEvent(new window.CustomEvent("turbo:before-render", { detail: { newBody: body, renderMethod: "replace" } }));
    expect(body.children.length).toBe(0);
});

test("explicit initial open state and application permanent attributes survive normalization", () => {
    const body = document.createElement("body");
    body.innerHTML = '<div id="permanent" data-turbo-permanent data-state="open"></div>';
    rememberSnapshotState(body.firstElementChild, { attributes: ["data-state", "hidden", "inert"] });
    body.firstElementChild.dataset.state = "closed";
    body.firstElementChild.hidden = true;
    normalizeSnapshot(body);
    expect(body.firstElementChild.dataset.state).toBe("open");
    expect(body.firstElementChild.hidden).toBe(false);
    expect(body.firstElementChild.hasAttribute("data-turbo-permanent")).toBe(true);
});
