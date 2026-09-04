import { afterEach, expect, test } from "bun:test";

import RichTextController from "../../resources/js/controllers/rich_text_controller.js";
import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test("real Tiptap v3 packages preserve the default editor contract", async () => {
    mounted = await mountController(
        "rich-text",
        RichTextController,
        `
        <div data-controller="rich-text"
             data-rich-text-id-value="content"
             data-rich-text-placeholder-value="Write something">
            <textarea hidden data-rich-text-target="input">&lt;h2&gt;Heading&lt;/h2&gt;</textarea>
            <div data-rich-text-target="editor"></div>
        </div>
    `,
    );

    const { controller } = mounted;
    const extensionNames = controller.editor.extensionManager.extensions.map((extension) => extension.name);

    expect(extensionNames).toContain("placeholder");
    expect(extensionNames.filter((name) => name === "link")).toHaveLength(1);
    expect(extensionNames.filter((name) => name === "underline")).toHaveLength(1);
    expect(new Set(extensionNames).size).toBe(extensionNames.length);
    expect(controller.html).toBe("<h2>Heading</h2>");

    expect(controller.editor.chain().focus().selectAll().toggleBold().run()).toBe(true);
    await wait(0);

    expect(controller.html).toBe("<h2><strong>Heading</strong></h2>");
    expect(mounted.root.querySelector("[data-rich-text-target='input']").value).toBe(
        "<h2><strong>Heading</strong></h2>",
    );

    controller.setContent("<p>Updated</p>");
    await wait(0);

    expect(controller.html).toBe("<p>Updated</p>");
    expect(mounted.root.querySelector("[data-rich-text-target='input']").value).toBe("<p>Updated</p>");
});
