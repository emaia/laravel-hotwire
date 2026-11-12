import { afterEach, expect, spyOn, test } from "bun:test";
import InputOtpController from "../../resources/js/controllers/input_otp_controller.js";
import { mountController } from "../../resources/js/helpers/test_stimulus.js";

let mounted;
afterEach(async () => mounted?.cleanup());

async function mount(value = "") {
    mounted = await mountController("input-otp", InputOtpController, `
        <div data-controller="input-otp">
            <input name="code" value="${value}" maxlength="6" data-input-otp-target="input">
            <span hidden data-input-otp-target="slots" aria-hidden="true">
                ${Array.from({ length: 6 }, () => '<span data-input-otp-target="slot"></span>').join("")}
            </span>
        </div>
    `);
    return mounted.root.querySelector("input");
}

test("enhances existing native values without emitting completion", async () => {
    const input = await mount("001234");
    let completions = 0;
    mounted.root.addEventListener("input-otp:complete", () => completions++);
    mounted.controller.disconnect();
    mounted.controller.connect();

    expect(input.value).toBe("001234");
    expect(mounted.controller.slotTargets.map(slot => slot.textContent).join("")).toBe("001234");
    expect(mounted.root.dataset.enhanced).toBe("true");
    expect(mounted.controller.slotsTarget.hidden).toBe(false);
    expect(completions).toBe(0);
});

test("filters non-digits while keeping the native caret position", async () => {
    const input = await mount();
    input.value = "0a1 2";
    input.setSelectionRange(3, 3);
    mounted.controller.input({ target: input, isComposing: false });

    expect(input.value).toBe("012");
    expect(input.selectionStart).toBe(2);
    expect(mounted.controller.slotTargets.map(slot => slot.textContent).join("")).toBe("012");
});

test("emits one value-free completion per changed complete value", async () => {
    const input = await mount();
    const events = [];
    mounted.root.addEventListener("input-otp:complete", event => events.push(event.detail));
    for (const value of ["00123", "001234", "001234", "001235", "00123", "001235"]) {
        input.value = value;
        mounted.controller.input({ target: input });
    }

    expect(events).toHaveLength(3);
    expect(events).toEqual([{}, {}, {}]);
});

test("does not modify input or dispatch completion during composition", async () => {
    const input = await mount();
    input.value = "12345a";
    mounted.controller.input({ target: input, isComposing: true });
    expect(input.value).toBe("12345a");
    mounted.controller.input({ target: input, isComposing: false });
    expect(input.value).toBe("12345");
});

test("ignores events from other composed controls and disabled or readonly fields", async () => {
    const input = await mount();
    let completions = 0;
    mounted.root.addEventListener("input-otp:complete", () => completions++);
    input.value = "001234";
    mounted.controller.input({ target: document.createElement("input") });
    input.readOnly = true;
    mounted.controller.input({ target: input });
    input.readOnly = false;
    input.disabled = true;
    mounted.controller.input({ target: input });
    expect(completions).toBe(0);
});

test("restores the usable native presentation on disconnect", async () => {
    await mount("012");
    mounted.controller.disconnect();
    expect(mounted.root.hasAttribute("data-enhanced")).toBe(false);
    expect(mounted.controller.slotsTarget.hidden).toBe(true);
    expect(mounted.controller.slotTargets.every(slot => slot.textContent === "")).toBe(true);
});

test("uses the slot count when maxlength is missing", async () => {
    const input = await mount();
    input.removeAttribute("maxlength");
    let completions = 0;
    mounted.root.addEventListener("input-otp:complete", () => completions++);
    input.value = "01234567";
    mounted.controller.input({ target: input });
    expect(input.value).toBe("012345");
    expect(completions).toBe(1);
});

test.each(["٠١٢٣٤٥", "۰۱۲۳۴۵", "０１２３４５", "𝟢𝟣𝟤𝟥𝟦𝟧", "𞥐𞥑𞥒𞥓𞥔𞥕"])("normalizes decimal digits %s to ASCII", async value => {
    const input = await mount();
    input.value = value;
    mounted.controller.input({ target: input });
    expect(input.value).toBe("012345");
});

test("keeps selection offsets when normalizing supplementary-plane digits", async () => {
    const input = await mount();
    input.value = "a𝟢 𝟣𝟤";
    input.setSelectionRange(3, 6);
    mounted.controller.input({ target: input });
    expect(input.value).toBe("012");
    expect([input.selectionStart, input.selectionEnd]).toEqual([1, 2]);
});

test("uses the shared composition guard for legacy IME events", async () => {
    const input = await mount();
    input.value = "12345a";
    mounted.controller.input({ target: input, keyCode: 229 });
    expect(input.value).toBe("12345a");
});

test("ignores morph events bubbling from descendants", async () => {
    const input = await mount("123");
    input.value = "456";
    mounted.controller.refresh({ target: input });
    expect(mounted.controller.slotTargets.map(slot => slot.textContent).join("")).toBe("123");
    mounted.controller.refresh({ target: mounted.root });
    expect(mounted.controller.slotTargets.map(slot => slot.textContent).join("")).toBe("456");
});

test("does not render unrelated document selection changes while inactive", async () => {
    const input = await mount();
    const render = spyOn(mounted.controller, "render");
    try {
        mounted.controller.onSelectionChange();
        expect(render).not.toHaveBeenCalled();
        input.focus();
        mounted.controller.onSelectionChange();
        expect(render).toHaveBeenCalledTimes(1);
        input.blur();
        mounted.controller.onSelectionChange();
        expect(render).toHaveBeenCalledTimes(2);
        mounted.controller.onSelectionChange();
        expect(render).toHaveBeenCalledTimes(2);
        expect(mounted.controller.slotTargets.every(slot => !slot.hasAttribute("data-active") && !slot.hasAttribute("data-selected"))).toBe(true);
    } finally {
        render.mockRestore();
    }
});
