import { afterEach, beforeEach, expect, mock, test } from "bun:test";

// --- Tiptap mocks ---
// Identical pattern to the other rich-text tests: mock the Tiptap modules so
// the rich-text controller can construct an editor without the real Tiptap
// dependency. We also expose chain()/isActive() that the toolbar uses.

const editorState = {
    lastInstance: null,
    chainCalls: [],
    isActiveOverrides: {},
};

function createInstance(options) {
    const chainFactory = () => {
        const c = {
            _calls: [],
            focus() { c._calls.push("focus"); return c; },
            toggleBold() { c._calls.push("toggleBold"); return c; },
            toggleItalic() { c._calls.push("toggleItalic"); return c; },
            toggleUnderline() { c._calls.push("toggleUnderline"); return c; },
            toggleBulletList() { c._calls.push("toggleBulletList"); return c; },
            toggleOrderedList() { c._calls.push("toggleOrderedList"); return c; },
            toggleBlockquote() { c._calls.push("toggleBlockquote"); return c; },
            toggleCodeBlock() { c._calls.push("toggleCodeBlock"); return c; },
            toggleHeading(attrs) { c._calls.push({ toggleHeading: attrs }); return c; },
            undo() { c._calls.push("undo"); return c; },
            redo() { c._calls.push("redo"); return c; },
            setLink(attrs) { c._calls.push({ setLink: attrs }); return c; },
            unsetLink() { c._calls.push("unsetLink"); return c; },
            run() {
                c._calls.push("run");
                editorState.chainCalls.push(c._calls);
                return true;
            },
        };
        return c;
    };

    const instance = {
        _options: options,
        _html: typeof options.content === "string" ? options.content : "",
        _json: { type: "doc", content: [] },
        getHTML: mock(() => instance._html),
        getJSON: mock(() => instance._json),
        getAttributes: mock(() => ({})),
        commands: {
            setContent: mock(() => true),
            clearContent: mock(() => true),
            focus: mock(() => true),
        },
        destroy: mock(() => {}),
        setEditable: mock(() => {}),
        isActive: mock((name, attrs) => {
            if (typeof name === "string") {
                if (attrs?.level) return editorState.isActiveOverrides[`${name}:${attrs.level}`] === true;
                return editorState.isActiveOverrides[name] === true;
            }
            return false;
        }),
        chain: chainFactory,
        isEditable: options.editable !== false,
        _trigger: (name, extra = {}) => options[name]?.({ editor: instance, ...extra }),
    };
    return instance;
}

class EditorMock {
    constructor(options) {
        const instance = createInstance(options);
        editorState.lastInstance = instance;
        return instance;
    }
}

mock.module("@tiptap/core", () => ({ Editor: EditorMock }));
mock.module("@tiptap/starter-kit", () => ({ default: "StarterKit" }));
mock.module("@tiptap/extension-placeholder", () => ({
    default: { configure: mock((opts) => ({ name: "Placeholder", options: opts })) },
}));
mock.module("@tiptap/extension-link", () => ({ default: "Link" }));
mock.module("@tiptap/extension-underline", () => ({ default: "Underline" }));

const { mountMultipleControllers, wait } = await import(
    "../../resources/js/helpers/test_stimulus.js"
);
const { default: RichTextController } = await import(
    "../../resources/js/controllers/rich_text_controller.js"
);
const { default: RichTextToolbarController } = await import(
    "../../resources/js/controllers/rich_text_toolbar_controller.js"
);

let mounted;
let originalPrompt;

beforeEach(() => {
    editorState.lastInstance = null;
    editorState.chainCalls = [];
    editorState.isActiveOverrides = {};
    originalPrompt = globalThis.prompt;
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    globalThis.prompt = originalPrompt;
});

function tmpl({ targets = [], outletSelector = "[data-rich-text-id-value='content']" } = {}) {
    const defaultTargets = [
        ["bold", "B"],
        ["italic", "I"],
        ["underline", "U"],
        ["bulletList", "•"],
        ["orderedList", "1."],
        ["blockquote", "\""],
        ["codeBlock", "</>"],
        ["link", "🔗"],
        ["undo", "↶"],
        ["redo", "↷"],
    ];
    const list = targets.length ? targets : defaultTargets;
    const buttons = list
        .map(
            ([name, label]) =>
                `<button type="button" data-action="click->rich-text-toolbar#${name}" data-rich-text-toolbar-target="${name}">${label}</button>`,
        )
        .join("\n");

    return `
        <div data-controller="rich-text" data-rich-text-id-value="content">
            <input type="hidden" name="content" data-rich-text-target="input" value="">
            <div data-rich-text-target="editor"></div>
        </div>
        <div data-controller="rich-text-toolbar"
             data-rich-text-toolbar-rich-text-outlet="${outletSelector}">
            ${buttons}
        </div>
    `;
}

async function mount(html = tmpl()) {
    mounted = await mountMultipleControllers(
        {
            "rich-text": RichTextController,
            "rich-text-toolbar": RichTextToolbarController,
        },
        html,
    );
    await wait(0);
}

function toolbar() {
    const el = document.querySelector("[data-controller~='rich-text-toolbar']");
    return mounted.getController("rich-text-toolbar", el);
}

// --- outlet wiring ---

test("connects to the rich-text outlet by id selector", async () => {
    await mount();

    expect(toolbar().hasRichTextOutlet).toBe(true);
    expect(toolbar().richTextOutlet.editor).toBe(editorState.lastInstance);
});

// --- syncButtons ---

test("syncButtons reflects editor.isActive on connected targets", async () => {
    editorState.isActiveOverrides = { bold: true, italic: false };
    await mount();

    const boldBtn = document.querySelector("[data-rich-text-toolbar-target='bold']");
    const italicBtn = document.querySelector("[data-rich-text-toolbar-target='italic']");

    expect(boldBtn.classList.contains("is-active")).toBe(true);
    expect(boldBtn.getAttribute("aria-pressed")).toBe("true");
    expect(italicBtn.classList.contains("is-active")).toBe(false);
    expect(italicBtn.getAttribute("aria-pressed")).toBe("false");
});

test("rich-text:state event from the editor element re-runs syncButtons", async () => {
    await mount();
    const boldBtn = document.querySelector("[data-rich-text-toolbar-target='bold']");

    // Start inactive.
    expect(boldBtn.classList.contains("is-active")).toBe(false);

    // Flip the editor state and re-dispatch state.
    editorState.isActiveOverrides = { bold: true };
    const editorEl = document.querySelector("[data-controller~='rich-text']");
    editorEl.dispatchEvent(new CustomEvent("rich-text:state", { bubbles: false }));

    expect(boldBtn.classList.contains("is-active")).toBe(true);
});

test("heading targets reflect isActive per level", async () => {
    editorState.isActiveOverrides = { "heading:2": true };

    const customHtml = tmpl({ targets: [["heading", "H2"]] }).replace(
        `data-rich-text-toolbar-target="heading"`,
        `data-rich-text-toolbar-target="heading" data-level="2"`,
    );
    await mount(customHtml);

    const headingBtn = document.querySelector("[data-rich-text-toolbar-target='heading']");
    expect(headingBtn.classList.contains("is-active")).toBe(true);
});

// --- action delegation ---

test("bold action runs editor.chain().focus().toggleBold().run()", async () => {
    await mount();

    toolbar().bold();

    const last = editorState.chainCalls.at(-1);
    expect(last).toEqual(["focus", "toggleBold", "run"]);
});

test("italic, underline, bulletList, orderedList, blockquote and codeBlock actions all delegate via chain().focus()", async () => {
    await mount();
    const t = toolbar();

    t.italic();
    t.underline();
    t.bulletList();
    t.orderedList();
    t.blockquote();
    t.codeBlock();

    expect(editorState.chainCalls.at(-6)).toEqual(["focus", "toggleItalic", "run"]);
    expect(editorState.chainCalls.at(-5)).toEqual(["focus", "toggleUnderline", "run"]);
    expect(editorState.chainCalls.at(-4)).toEqual(["focus", "toggleBulletList", "run"]);
    expect(editorState.chainCalls.at(-3)).toEqual(["focus", "toggleOrderedList", "run"]);
    expect(editorState.chainCalls.at(-2)).toEqual(["focus", "toggleBlockquote", "run"]);
    expect(editorState.chainCalls.at(-1)).toEqual(["focus", "toggleCodeBlock", "run"]);
});

test("undo and redo actions delegate via chain()", async () => {
    await mount();
    const t = toolbar();

    t.undo();
    t.redo();

    expect(editorState.chainCalls.at(-2)).toEqual(["focus", "undo", "run"]);
    expect(editorState.chainCalls.at(-1)).toEqual(["focus", "redo", "run"]);
});

test("heading action toggles the level read from the params or dataset", async () => {
    await mount();
    const t = toolbar();

    const target = document.createElement("button");
    target.dataset.level = "3";
    t.heading({ currentTarget: target, params: {} });

    const last = editorState.chainCalls.at(-1);
    expect(last[0]).toBe("focus");
    expect(last[1]).toEqual({ toggleHeading: { level: 3 } });
    expect(last[2]).toBe("run");
});

// --- link prompt ---

test("link action with a URL prompt runs setLink", async () => {
    globalThis.prompt = mock(() => "https://example.com");
    await mount();

    toolbar().link({ params: {} });

    const last = editorState.chainCalls.at(-1);
    expect(last[1]).toEqual({ setLink: { href: "https://example.com" } });
});

test("link action with an empty prompt result runs unsetLink", async () => {
    globalThis.prompt = mock(() => "");
    await mount();

    toolbar().link({ params: {} });

    const last = editorState.chainCalls.at(-1);
    expect(last[1]).toBe("unsetLink");
});

test("link action with a cancelled prompt does nothing", async () => {
    globalThis.prompt = mock(() => null);
    await mount();

    const before = editorState.chainCalls.length;
    toolbar().link({ params: {} });

    expect(editorState.chainCalls.length).toBe(before);
});

// --- subclass extensibility (activeStates spread) ---

test("subclass extends activeStates via spread and reflects new targets on syncButtons", async () => {
    class TableToolbar extends RichTextToolbarController {
        static targets = [...RichTextToolbarController.targets, "table"];
        static activeStates = {
            ...RichTextToolbarController.activeStates,
            table: "table",
        };
    }

    editorState.isActiveOverrides = { table: true, bold: true };

    const html = tmpl({
        targets: [
            ["bold", "B"],
            ["table", "[Tbl]"],
        ],
    });
    mounted = await mountMultipleControllers(
        {
            "rich-text": RichTextController,
            "rich-text-toolbar": TableToolbar,
        },
        html,
    );
    await wait(0);

    const tableBtn = document.querySelector("[data-rich-text-toolbar-target='table']");
    const boldBtn = document.querySelector("[data-rich-text-toolbar-target='bold']");

    expect(tableBtn.classList.contains("is-active")).toBe(true);
    expect(tableBtn.getAttribute("aria-pressed")).toBe("true");
    // parent class targets still reflect — inheritance via spread did not drop them
    expect(boldBtn.classList.contains("is-active")).toBe(true);
});

test("targets without an activeStates entry are skipped by syncButtons (undo/redo)", async () => {
    editorState.isActiveOverrides = { undo: true, redo: true };
    await mount();

    const undoBtn = document.querySelector("[data-rich-text-toolbar-target='undo']");
    const redoBtn = document.querySelector("[data-rich-text-toolbar-target='redo']");

    // undo/redo do not have an isActive state in Tiptap, so they stay inert
    expect(undoBtn.classList.contains("is-active")).toBe(false);
    expect(undoBtn.getAttribute("aria-pressed")).toBe(null);
    expect(redoBtn.classList.contains("is-active")).toBe(false);
});

// --- disconnect ---

test("disconnect detaches the state listener so syncButtons no longer re-runs", async () => {
    await mount();
    const t = toolbar();
    const boldBtn = document.querySelector("[data-rich-text-toolbar-target='bold']");

    t.disconnect();
    editorState.isActiveOverrides = { bold: true };

    const editorEl = document.querySelector("[data-controller~='rich-text']");
    editorEl.dispatchEvent(new CustomEvent("rich-text:state", { bubbles: false }));

    expect(boldBtn.classList.contains("is-active")).toBe(false);
});
