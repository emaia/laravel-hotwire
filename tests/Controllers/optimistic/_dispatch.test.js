import { afterEach, beforeEach, expect, test } from "bun:test";

import { Window } from "happy-dom";
import { dispatchOptimistic } from "../../../resources/js/controllers/optimistic/_dispatch.js";

let testWindow;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.HTMLElement = testWindow.HTMLElement;
    globalThis.FormData = testWindow.FormData;
});

afterEach(() => {
    testWindow.close();
});

function mount(html) {
    document.body.innerHTML = html;
    return document.body.firstElementChild;
}

function streams() {
    return [...document.body.querySelectorAll("turbo-stream")];
}

function payload(stream) {
    return stream.querySelector("template").content;
}

// --- no template / multiple templates ---

test.serial("no template: no stream appended", () => {
    const root = mount(`<div><p>hello</p></div>`);

    dispatchOptimistic(root);

    expect(streams()).toHaveLength(0);
});

test.serial("multiple templates: one stream appended per template", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-target-id="a">
                <div>A</div>
            </template>
            <template data-optimistic-stream data-optimistic-target-id="b">
                <div>B</div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    expect(streams()).toHaveLength(2);
});

// --- targeting ---

test.serial("data-optimistic-target-id renders target attribute", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-target-id="post_42">
                <div>card</div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    const stream = streams()[0];
    expect(stream.getAttribute("target")).toBe("post_42");
    expect(stream.hasAttribute("targets")).toBe(false);
});

test.serial("data-optimistic-targets renders targets attribute", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-targets=".post">
                <div>card</div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    const stream = streams()[0];
    expect(stream.getAttribute("targets")).toBe(".post");
    expect(stream.hasAttribute("target")).toBe(false);
});

test.serial("targets wins when both target-id and targets are present", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream
                      data-optimistic-target-id="single"
                      data-optimistic-targets=".many">
                <div>card</div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    const stream = streams()[0];
    expect(stream.getAttribute("targets")).toBe(".many");
    expect(stream.hasAttribute("target")).toBe(false);
});

// --- action default and skip behaviour ---

test.serial("action defaults to 'replace'", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-target-id="x">
                <div>card</div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    expect(streams()[0].getAttribute("action")).toBe("replace");
});

test.serial("template without target/targets is skipped when action != 'refresh'", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-action="append">
                <div>card</div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    expect(streams()).toHaveLength(0);
});

test.serial("template without target/targets is kept when action == 'refresh'", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-action="refresh">
                <div>card</div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    expect(streams()).toHaveLength(1);
    expect(streams()[0].getAttribute("action")).toBe("refresh");
});

// --- formData population ---

test.serial("populateFields: no formData leaves [data-field] untouched", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-target-id="t">
                <div><span data-field="title">placeholder</span></div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    const span = payload(streams()[0]).querySelector("[data-field]");
    expect(span.textContent).toBe("placeholder");
});

test.serial("populateFields: formData entries overwrite matching data-field textContent", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-target-id="t">
                <div>
                    <span data-field="title">placeholder</span>
                    <span data-field="missing">keep</span>
                </div>
            </template>
        </div>
    `);
    const formData = new FormData();
    formData.append("title", "Hello world");

    dispatchOptimistic(root, { formData });

    const fields = payload(streams()[0]).querySelectorAll("[data-field]");
    expect(fields[0].textContent).toBe("Hello world");
    expect(fields[1].textContent).toBe("keep");
});

// --- markOptimistic ---

test.serial("markOptimistic adds data-optimistic to direct children of the payload", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-target-id="t">
                <div class="card"><span>inner</span></div>
                <div class="meta"></div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    const content = payload(streams()[0]);
    const directs = [...content.children];
    expect(directs).toHaveLength(2);
    directs.forEach((el) => expect(el.hasAttribute("data-optimistic")).toBe(true));

    const nested = content.querySelector("span");
    expect(nested.hasAttribute("data-optimistic")).toBe(false);
});

test.serial("markOptimistic preserves existing data-optimistic value", () => {
    const root = mount(`
        <div>
            <template data-optimistic-stream data-optimistic-target-id="t">
                <div data-optimistic="from-server">already</div>
            </template>
        </div>
    `);

    dispatchOptimistic(root);

    const div = payload(streams()[0]).querySelector("div");
    expect(div.getAttribute("data-optimistic")).toBe("from-server");
});
