import { afterEach, beforeEach, expect, mock, test } from "bun:test";

// --- Tiptap mocks ---
// Same pattern as _rich_text_editor.test.js: replace the Tiptap modules with
// controllable fakes so we can trigger the editor lifecycle from the test and
// verify the controller's bridging (input sync, dispatch events, public API).

const editorState = {
    constructed: 0,
    lastOptions: null,
    lastInstance: null,
    setContentCalls: [],
    clearContentCalls: [],
    focusCalls: [],
    destroyCalls: [],
    // Hook for tests to control isEmpty at construction (init runs before the
    // test can mutate the instance directly). Tests that need to flip isEmpty
    // after mount mutate `lastInstance.isEmpty` instead.
    nextIsEmpty: false,
};

function createInstance(options) {
    const instance = {
        _options: options,
        _html: typeof options.content === "string" ? options.content : "",
        _json: { type: "doc", content: [] },
        getHTML: mock(() => instance._html),
        getJSON: mock(() => instance._json),
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
        setEditable: mock(() => {}),
        isActive: mock(() => false),
        isEditable: options.editable !== false,
        isEmpty: editorState.nextIsEmpty,
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
    default: { configure: mock((opts) => ({ name: "Placeholder", options: opts })) },
}));
mock.module("@tiptap/extension-link", () => ({
    default: {
        configure: mock((opts) => ({ name: "Link", options: opts })),
    },
}));
mock.module("@tiptap/extension-underline", () => ({ default: "Underline" }));

const { mountController } = await import("../../resources/js/helpers/test_stimulus.js");
const { default: RichTextController } = await import(
    "../../resources/js/controllers/rich_text_controller.js"
);

let mounted;

beforeEach(() => {
    editorState.constructed = 0;
    editorState.lastOptions = null;
    editorState.lastInstance = null;
    editorState.setContentCalls = [];
    editorState.clearContentCalls = [];
    editorState.focusCalls = [];
    editorState.destroyCalls = [];
    editorState.nextIsEmpty = false;
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// Blade escapes `{{ $resolvedValue }}` inside the textarea, so a stored
// `<p>Hello</p>` lands on the page as `&lt;p&gt;Hello&lt;/p&gt;`. Mirror that
// here so the template behaves like the real component output.
function escapeHtml(raw) {
    return String(raw)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;");
}

function tmpl({
    value = "",
    placeholder = "",
    editable = true,
    output = "html",
    editorClass = "",
    imageUpload = false,
} = {}) {
    return `
        <div data-controller="rich-text"
             data-rich-text-id-value="content"
             ${placeholder ? `data-rich-text-placeholder-value="${placeholder}"` : ""}
             ${editable === false ? `data-rich-text-editable-value="false"` : ""}
             ${output !== "html" ? `data-rich-text-output-value="${output}"` : ""}
             ${editorClass ? `data-rich-text-editor-class-value="${editorClass}"` : ""}
             ${imageUpload ? `data-rich-text-image-upload-value="true"` : ""}>
            <textarea hidden name="content" data-rich-text-target="input">${escapeHtml(value)}</textarea>
            <div data-rich-text-target="editor"></div>
        </div>
    `;
}

async function mount(html, ControllerClass = RichTextController) {
    mounted = await mountController("rich-text", ControllerClass, html);
}

// --- connect / initialisation ---

test("connect constructs a Tiptap editor mounted on the editor target", async () => {
    await mount(tmpl());

    const editorTarget = mounted.root.querySelector("[data-rich-text-target='editor']");
    expect(editorState.constructed).toBe(1);
    expect(editorState.lastOptions.element).toBe(editorTarget);
});

test("initial content is read from the hidden input target", async () => {
    await mount(tmpl({ value: "<p>initial</p>" }));

    expect(editorState.lastOptions.content).toBe("<p>initial</p>");
});

test("placeholderValue is forwarded into the Placeholder extension", async () => {
    await mount(tmpl({ placeholder: "Type here…" }));

    const placeholder = editorState.lastOptions.extensions.find((e) => e?.name === "Placeholder");
    expect(placeholder).toBeDefined();
    expect(placeholder.options).toEqual({ placeholder: "Type here…" });
});

test("editableValue=false makes the editor read-only", async () => {
    await mount(tmpl({ editable: false }));

    expect(editorState.lastOptions.editable).toBe(false);
});

// --- update sync ---

test("editor update syncs the hidden input with HTML by default", async () => {
    await mount(tmpl());
    const input = mounted.root.querySelector("[data-rich-text-target='input']");

    editorState.lastInstance._html = "<p>typed</p>";
    editorState.lastInstance._trigger("onUpdate");

    expect(input.value).toBe("<p>typed</p>");
});

test("editor update syncs the hidden input with JSON when outputValue is 'json'", async () => {
    await mount(tmpl({ output: "json" }));
    const input = mounted.root.querySelector("[data-rich-text-target='input']");

    editorState.lastInstance._json = { type: "doc", content: [{ type: "paragraph" }] };
    editorState.lastInstance._trigger("onUpdate");

    expect(JSON.parse(input.value)).toEqual({ type: "doc", content: [{ type: "paragraph" }] });
});

// --- empty-state sync ---

test("update clears the textarea when the editor reports isEmpty", async () => {
    await mount(tmpl({ value: "<p>Hello</p>" }));
    const input = mounted.root.querySelector("[data-rich-text-target='input']");

    // User typed and then deleted everything. Tiptap returns `<p></p>` (its
    // default empty doc) and flips `isEmpty` to `true`.
    editorState.lastInstance.isEmpty = true;
    editorState.lastInstance._html = "<p></p>";
    editorState.lastInstance._trigger("onUpdate");

    expect(input.value).toBe("");
});

test("update clears the textarea on isEmpty even when outputValue is 'json'", async () => {
    await mount(tmpl({ output: "json" }));
    const input = mounted.root.querySelector("[data-rich-text-target='input']");

    editorState.lastInstance.isEmpty = true;
    editorState.lastInstance._json = { type: "doc", content: [{ type: "paragraph" }] };
    editorState.lastInstance._trigger("onUpdate");

    expect(input.value).toBe("");
});

test("connect clears a leftover <p></p> from old() when the mounted doc is empty", async () => {
    editorState.nextIsEmpty = true;

    await mount(tmpl({ value: "<p></p>" }));
    const input = mounted.root.querySelector("[data-rich-text-target='input']");

    expect(input.value).toBe("");
});

test("connect leaves a non-empty textarea untouched (no normalization)", async () => {
    await mount(tmpl({ value: "<p>Initial</p>" }));
    const input = mounted.root.querySelector("[data-rich-text-target='input']");

    expect(input.value).toBe("<p>Initial</p>");
});

// --- events ---

test("dispatches ready and state after connect", async () => {
    const events = [];
    class Spy extends RichTextController {
        dispatch(name, opts) {
            events.push({ name, detail: opts?.detail });
            return super.dispatch(name, opts);
        }
    }
    await mount(tmpl(), Spy);

    const names = events.map((e) => e.name);
    expect(names).toContain("ready");
    expect(names).toContain("state");
});

test("dispatches change with html and json detail on editor update", async () => {
    const events = [];
    class Spy extends RichTextController {
        dispatch(name, opts) {
            events.push({ name, detail: opts?.detail });
            return super.dispatch(name, opts);
        }
    }
    await mount(tmpl(), Spy);
    events.length = 0;

    editorState.lastInstance._html = "<p>hi</p>";
    editorState.lastInstance._trigger("onUpdate");

    const change = events.find((e) => e.name === "change");
    expect(change).toBeDefined();
    expect(change.detail.html).toBe("<p>hi</p>");
    expect(change.detail.json).toBe(editorState.lastInstance._json);
});

test("dispatches focus and blur when editor focus/blur fires", async () => {
    const events = [];
    class Spy extends RichTextController {
        dispatch(name, opts) {
            events.push(name);
            return super.dispatch(name, opts);
        }
    }
    await mount(tmpl(), Spy);

    editorState.lastInstance._trigger("onFocus");
    editorState.lastInstance._trigger("onBlur");

    expect(events).toContain("focus");
    expect(events).toContain("blur");
});

test("emits events under the fixed 'rich-text:' prefix even when registered under a swapped identifier", async () => {
    // Subclass registered as "rich-text-full" should still fire rich-text:state
    // (so a toolbar that listens for rich-text:state works against any swap).
    const events = [];

    const html = `
        <div data-controller="rich-text-full" data-rich-text-full-id-value="content">
            <textarea data-rich-text-full-target="input" hidden></textarea>
            <div data-rich-text-full-target="editor"></div>
        </div>
    `;
    // Reuse mounted-cleanup pattern: tear down whatever previous test left.
    await mounted?.cleanup();
    mounted = await mountController("rich-text-full", RichTextController, html);

    const root = document.querySelector("[data-controller~='rich-text-full']");
    root.addEventListener("rich-text:state", () => events.push("rich-text:state"));
    root.addEventListener("rich-text-full:state", () => events.push("rich-text-full:state"));

    editorState.lastInstance._trigger("onSelectionUpdate");

    expect(events).toContain("rich-text:state");
    expect(events).not.toContain("rich-text-full:state");
});

test("dispatches state on selectionUpdate so toolbars can resync", async () => {
    const events = [];
    class Spy extends RichTextController {
        dispatch(name, opts) {
            events.push(name);
            return super.dispatch(name, opts);
        }
    }
    await mount(tmpl(), Spy);
    events.length = 0;

    editorState.lastInstance._trigger("onSelectionUpdate");

    expect(events).toContain("state");
});

// --- public API ---

test("html and json getters return the editor's current html / json", async () => {
    await mount(tmpl());
    editorState.lastInstance._html = "<p>x</p>";

    expect(mounted.controller.html).toBe("<p>x</p>");
    expect(mounted.controller.json).toBe(editorState.lastInstance._json);
});

test("setContent delegates to the editor wrapper and emits the update", async () => {
    const events = [];
    class Spy extends RichTextController {
        dispatch(name, opts) {
            events.push({ name, detail: opts?.detail });
            return super.dispatch(name, opts);
        }
    }
    await mount(tmpl(), Spy);
    events.length = 0;

    mounted.controller.setContent("<p>set</p>");

    expect(editorState.lastInstance._html).toBe("<p>set</p>");
    expect(events.some((e) => e.name === "change")).toBe(true);
});

test("clear empties the editor", async () => {
    await mount(tmpl({ value: "<p>x</p>" }));

    mounted.controller.clear();

    expect(editorState.lastInstance._html).toBe("");
});

test("focus delegates to the editor wrapper", async () => {
    await mount(tmpl());

    mounted.controller.focus();

    expect(editorState.focusCalls).toHaveLength(1);
});

test("editor getter exposes the Tiptap instance", async () => {
    await mount(tmpl());

    expect(mounted.controller.editor).toBe(editorState.lastInstance);
});

// --- disconnect ---

test("disconnect destroys the editor", async () => {
    await mount(tmpl());
    const instance = editorState.lastInstance;

    mounted.controller.disconnect();

    expect(editorState.destroyCalls.some((c) => c.instance === instance)).toBe(true);
});

// --- extensions hook ---

test("extensions() hook lets a subclass replace the extension list", async () => {
    class WithExt extends RichTextController {
        extensions() {
            return ["Only"];
        }
    }
    await mount(tmpl(), WithExt);

    expect(editorState.lastOptions.extensions).toEqual(["Only"]);
});

test("default extensions() returns null and the wrapper builds defaults", async () => {
    await mount(tmpl());

    expect(editorState.lastOptions.extensions).toContain("StarterKit");
});

// --- image upload ---

test("imageUploadValue=true dispatches image-upload with the file detail", async () => {
    const events = [];
    class Spy extends RichTextController {
        dispatch(name, opts) {
            events.push({ name, detail: opts?.detail });
            return super.dispatch(name, opts);
        }
    }
    await mount(tmpl({ imageUpload: true }), Spy);

    const file = new File(["x"], "pic.png", { type: "image/png" });
    editorState.lastOptions.editorProps.handleDrop(null, {
        dataTransfer: { files: [file] },
        preventDefault: () => {},
    });

    const upload = events.find((e) => e.name === "image-upload");
    expect(upload).toBeDefined();
    expect(upload.detail.file).toBe(file);
    expect(upload.detail.editor).toBe(editorState.lastInstance);
});

test("imageUploadValue=false leaves editorProps undefined (no interception)", async () => {
    await mount(tmpl({ imageUpload: false }));

    expect(editorState.lastOptions.editorProps).toBeUndefined();
});

// --- editorClass ---

test("editorClassValue forwards to editorProps.attributes.class", async () => {
    await mount(tmpl({ editorClass: "prose prose-sm focus:outline-none" }));

    expect(editorState.lastOptions.editorProps.attributes).toEqual({
        class: "prose prose-sm focus:outline-none",
    });
});

test("editorClass is omitted from editorProps when value is empty", async () => {
    await mount(tmpl());

    expect(editorState.lastOptions.editorProps).toBeUndefined();
});

// --- morph recovery ---

test("re-initialises the editor when turbo:morph-element fires and the ProseMirror DOM is gone", async () => {
    await mount(tmpl());

    const constructedBefore = editorState.constructed;
    const editorTarget = mounted.root.querySelector("[data-rich-text-target='editor']");

    // Simulate morph: a Turbo morph wiped the embedded ProseMirror DOM while
    // preserving the host element and the editor target.
    editorTarget.innerHTML = "";
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    expect(editorState.constructed).toBe(constructedBefore + 1);
});

test("does NOT re-initialise on morph when a ProseMirror node is still present", async () => {
    await mount(tmpl());

    const constructedBefore = editorState.constructed;
    const editorTarget = mounted.root.querySelector("[data-rich-text-target='editor']");

    // The mock doesn't actually mount ProseMirror; add a stand-in so isStale() returns false.
    const pm = document.createElement("div");
    pm.className = "ProseMirror";
    editorTarget.appendChild(pm);
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    expect(editorState.constructed).toBe(constructedBefore);
});

test("disconnect detaches the morph recovery listener", async () => {
    await mount(tmpl());

    mounted.controller.disconnect();

    const constructedBefore = editorState.constructed;
    const editorTarget = mounted.root.querySelector("[data-rich-text-target='editor']");
    editorTarget.innerHTML = "";
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    expect(editorState.constructed).toBe(constructedBefore);
});
