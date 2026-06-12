import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Window } from "happy-dom";

// --- Tiptap mocks ---
// The wrapper imports Editor from @tiptap/core plus the StarterKit, Placeholder,
// Link and Underline default exports. Replace them with controllable fakes so
// we can inspect the constructor options and trigger the callbacks Tiptap would
// normally fire from ProseMirror.

const editorState = {
    constructed: 0,
    lastOptions: null,
    lastInstance: null,
    setContentCalls: [],
    clearContentCalls: [],
    focusCalls: [],
    destroyCalls: [],
    setEditableCalls: [],
};

function createInstance(options) {
    const instance = {
        _options: options,
        _html: typeof options.content === "string" ? options.content : "",
        _json: { type: "doc", content: [] },
        getHTML: mock(function () {
            return instance._html;
        }),
        getJSON: mock(function () {
            return instance._json;
        }),
        commands: {
            setContent: mock((content, emitUpdate) => {
                if (typeof content === "string") instance._html = content;
                if (content && typeof content === "object") instance._json = content;
                editorState.setContentCalls.push({ instance, content, emitUpdate });
                if (emitUpdate) options.onUpdate?.({ editor: instance });
                return true;
            }),
            clearContent: mock((emitUpdate) => {
                instance._html = "";
                editorState.clearContentCalls.push({ instance, emitUpdate });
                if (emitUpdate) options.onUpdate?.({ editor: instance });
                return true;
            }),
            focus: mock(() => {
                editorState.focusCalls.push({ instance });
                return true;
            }),
        },
        destroy: mock(() => {
            editorState.destroyCalls.push({ instance });
        }),
        setEditable: mock((editable) => {
            instance.isEditable = !!editable;
            editorState.setEditableCalls.push({ instance, editable });
        }),
        isActive: mock(() => false),
        isEditable: options.editable !== false,
        // Helper for tests to trigger lifecycle callbacks the real editor fires.
        _trigger: (name, extra = {}) => options[name]?.({ editor: instance, ...extra }),
    };
    return instance;
}

class EditorMock {
    constructor(options) {
        editorState.constructed++;
        editorState.lastOptions = options;
        const instance = createInstance(options);
        editorState.lastInstance = instance;
        return instance;
    }
}

mock.module("@tiptap/core", () => ({ Editor: EditorMock }));
mock.module("@tiptap/starter-kit", () => ({ default: "StarterKit" }));
mock.module("@tiptap/extension-placeholder", () => ({
    default: {
        configure: mock((opts) => ({ name: "Placeholder", options: opts })),
    },
}));
mock.module("@tiptap/extension-link", () => ({ default: "Link" }));
mock.module("@tiptap/extension-underline", () => ({ default: "Underline" }));

const { RichTextEditor, defaultExtensions } = await import(
    "../../resources/js/controllers/_rich_text_editor.js"
);

let testWindow;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.File = testWindow.File;
    globalThis.Blob = testWindow.Blob;

    editorState.constructed = 0;
    editorState.lastOptions = null;
    editorState.lastInstance = null;
    editorState.setContentCalls = [];
    editorState.clearContentCalls = [];
    editorState.focusCalls = [];
    editorState.destroyCalls = [];
    editorState.setEditableCalls = [];
});

afterEach(() => {
    testWindow.close();
});

function elem() {
    const el = document.createElement("div");
    document.body.appendChild(el);
    return el;
}

// --- construction ---

test("constructor passes element, content and editable to Tiptap Editor", () => {
    const el = elem();
    new RichTextEditor(el, { content: "<p>Hi</p>", editable: false });

    expect(editorState.constructed).toBe(1);
    expect(editorState.lastOptions.element).toBe(el);
    expect(editorState.lastOptions.content).toBe("<p>Hi</p>");
    expect(editorState.lastOptions.editable).toBe(false);
});

test("constructor defaults content to empty string and editable to true", () => {
    new RichTextEditor(elem());

    expect(editorState.lastOptions.content).toBe("");
    expect(editorState.lastOptions.editable).toBe(true);
});

// --- extensions ---

test("default extension list includes StarterKit, Link and Underline", () => {
    new RichTextEditor(elem());

    const exts = editorState.lastOptions.extensions;
    expect(exts).toContain("StarterKit");
    expect(exts).toContain("Link");
    expect(exts).toContain("Underline");
});

test("Placeholder is configured and added when placeholder option is provided", () => {
    new RichTextEditor(elem(), { placeholder: "Type here…" });

    const placeholder = editorState.lastOptions.extensions.find((e) => e?.name === "Placeholder");
    expect(placeholder).toBeDefined();
    expect(placeholder.options).toEqual({ placeholder: "Type here…" });
});

test("Placeholder is omitted when no placeholder option", () => {
    new RichTextEditor(elem());

    const exts = editorState.lastOptions.extensions;
    expect(exts.some((e) => e?.name === "Placeholder")).toBe(false);
});

test("explicit `extensions` option replaces the default list", () => {
    new RichTextEditor(elem(), { extensions: ["CustomA", "CustomB"] });

    expect(editorState.lastOptions.extensions).toEqual(["CustomA", "CustomB"]);
});

// --- defaultExtensions helper ---

test("defaultExtensions() returns the unmodified default list", () => {
    expect(defaultExtensions()).toEqual(["StarterKit", "Link", "Underline"]);
});

test("defaultExtensions({ placeholder }) appends configured Placeholder", () => {
    const exts = defaultExtensions({ placeholder: "Hello" });

    expect(exts).toContain("StarterKit");
    const placeholder = exts.find((e) => e?.name === "Placeholder");
    expect(placeholder).toBeDefined();
    expect(placeholder.options).toEqual({ placeholder: "Hello" });
});

// --- callbacks ---

test("onUpdate callback receives html, json and editor", () => {
    const received = [];
    new RichTextEditor(elem(), { onUpdate: (payload) => received.push(payload) });

    editorState.lastInstance._html = "<p>x</p>";
    editorState.lastInstance._trigger("onUpdate");

    expect(received).toHaveLength(1);
    expect(received[0].html).toBe("<p>x</p>");
    expect(received[0].json).toBe(editorState.lastInstance._json);
    expect(received[0].editor).toBe(editorState.lastInstance);
});

test("onFocus and onBlur callbacks each receive editor", () => {
    const focusCalls = [];
    const blurCalls = [];
    new RichTextEditor(elem(), {
        onFocus: (p) => focusCalls.push(p),
        onBlur: (p) => blurCalls.push(p),
    });

    editorState.lastInstance._trigger("onFocus");
    editorState.lastInstance._trigger("onBlur");

    expect(focusCalls).toHaveLength(1);
    expect(focusCalls[0].editor).toBe(editorState.lastInstance);
    expect(blurCalls).toHaveLength(1);
    expect(blurCalls[0].editor).toBe(editorState.lastInstance);
});

test("onSelectionUpdate callback receives editor", () => {
    const calls = [];
    new RichTextEditor(elem(), { onSelectionUpdate: (p) => calls.push(p) });

    editorState.lastInstance._trigger("onSelectionUpdate");

    expect(calls).toHaveLength(1);
    expect(calls[0].editor).toBe(editorState.lastInstance);
});

// --- accessors and commands ---

test("html and json getters delegate to editor.getHTML / getJSON", () => {
    const wrapper = new RichTextEditor(elem(), { content: "<p>Hello</p>" });

    expect(wrapper.html).toBe("<p>Hello</p>");
    expect(wrapper.json).toBe(editorState.lastInstance._json);
});

test("setContent forwards to editor.commands.setContent with emitUpdate=true", () => {
    const wrapper = new RichTextEditor(elem());

    wrapper.setContent("<p>New</p>");

    expect(editorState.setContentCalls).toHaveLength(1);
    expect(editorState.setContentCalls[0].content).toBe("<p>New</p>");
    expect(editorState.setContentCalls[0].emitUpdate).toBe(true);
});

test("clear forwards to editor.commands.clearContent with emitUpdate=true", () => {
    const wrapper = new RichTextEditor(elem());

    wrapper.clear();

    expect(editorState.clearContentCalls).toHaveLength(1);
    expect(editorState.clearContentCalls[0].emitUpdate).toBe(true);
});

test("focus forwards to editor.commands.focus", () => {
    const wrapper = new RichTextEditor(elem());

    wrapper.focus();

    expect(editorState.focusCalls).toHaveLength(1);
});

test("setEditable forwards to editor.setEditable", () => {
    const wrapper = new RichTextEditor(elem(), { editable: true });

    wrapper.setEditable(false);

    expect(editorState.setEditableCalls).toHaveLength(1);
    expect(editorState.setEditableCalls[0].editable).toBe(false);
});

test("destroy forwards to editor.destroy", () => {
    const wrapper = new RichTextEditor(elem());

    wrapper.destroy();

    expect(editorState.destroyCalls).toHaveLength(1);
});

// --- image drop / paste ---

test("editorProps is undefined when onImageDrop is not provided", () => {
    new RichTextEditor(elem());

    expect(editorState.lastOptions.editorProps).toBeUndefined();
});

test("handlePaste delegates an image file to onImageDrop and returns true", () => {
    const calls = [];
    new RichTextEditor(elem(), { onImageDrop: (file) => calls.push(file) });

    const file = new File(["x"], "pic.png", { type: "image/png" });
    const event = {
        clipboardData: { files: [file] },
        preventDefault: mock(() => {}),
    };

    const handled = editorState.lastOptions.editorProps.handlePaste(null, event);

    expect(handled).toBe(true);
    expect(event.preventDefault).toHaveBeenCalledTimes(1);
    expect(calls).toHaveLength(1);
    expect(calls[0]).toBe(file);
});

test("handlePaste ignores non-image clipboard content and returns false", () => {
    const calls = [];
    new RichTextEditor(elem(), { onImageDrop: (file) => calls.push(file) });

    const event = {
        clipboardData: { files: [new File(["x"], "doc.pdf", { type: "application/pdf" })] },
        preventDefault: mock(() => {}),
    };

    const handled = editorState.lastOptions.editorProps.handlePaste(null, event);

    expect(handled).toBe(false);
    expect(event.preventDefault).not.toHaveBeenCalled();
    expect(calls).toHaveLength(0);
});

test("handleDrop fires for dataTransfer image files", () => {
    const calls = [];
    new RichTextEditor(elem(), { onImageDrop: (file) => calls.push(file) });

    const file = new File(["x"], "pic.jpg", { type: "image/jpeg" });
    const event = {
        dataTransfer: { files: [file] },
        preventDefault: mock(() => {}),
    };

    const handled = editorState.lastOptions.editorProps.handleDrop(null, event);

    expect(handled).toBe(true);
    expect(event.preventDefault).toHaveBeenCalledTimes(1);
    expect(calls).toHaveLength(1);
    expect(calls[0]).toBe(file);
});
