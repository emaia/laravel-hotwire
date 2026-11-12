import { afterEach, expect, test } from "bun:test";
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
