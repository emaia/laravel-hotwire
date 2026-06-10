import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ConditionalFieldsController from "../../resources/js/controllers/conditional_fields_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- select trigger ---

test.serial("select trigger — hides dependent that does not match initial value", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <select name="reason">
                <option value="bug" selected>Bug</option>
                <option value="other">Other</option>
            </select>
            <fieldset data-conditional-fields-target="dependent" data-when-reason="other">
                <input name="other_reason" />
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    expect(dep.hidden).toBe(true);
    expect(dep.disabled).toBe(true);
});

test.serial("select trigger — shows dependent that matches initial value", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <select name="reason">
                <option value="bug">Bug</option>
                <option value="other" selected>Other</option>
            </select>
            <fieldset data-conditional-fields-target="dependent" data-when-reason="other">
                <input name="other_reason" />
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    expect(dep.hidden).toBe(false);
    expect(dep.disabled).toBe(false);
});

test.serial("select trigger — flips dependent on change", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <select name="reason">
                <option value="bug" selected>Bug</option>
                <option value="other">Other</option>
            </select>
            <fieldset data-conditional-fields-target="dependent" data-when-reason="other">
                <input name="other_reason" />
            </fieldset>
        </form>
    `);

    const select = document.querySelector("select");
    const dep = document.querySelector("fieldset");

    select.value = "other";
    select.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(false);
    expect(dep.disabled).toBe(false);

    select.value = "bug";
    select.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(true);
    expect(dep.disabled).toBe(true);
});

// --- OR within field ---

test.serial("pipe-separated values are OR-matched within a field", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <select name="reason">
                <option value="bug" selected>Bug</option>
                <option value="feature">Feature</option>
                <option value="other">Other</option>
            </select>
            <fieldset data-conditional-fields-target="dependent" data-when-reason="bug|feature">
                <textarea name="details"></textarea>
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    const select = document.querySelector("select");

    expect(dep.hidden).toBe(false); // bug matches

    select.value = "feature";
    select.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(false); // feature matches

    select.value = "other";
    select.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(true); // neither
});

test.serial("trigger values containing spaces match literally (pipe separator)", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <select name="user">
                <option value="Kris Jhonson" selected>Kris Jhonson</option>
                <option value="John Doe">John Doe</option>
                <option value="Jane Doe">Jane Doe</option>
            </select>
            <fieldset data-conditional-fields-target="dependent" data-when-user="Kris Jhonson|John Doe">
                <input name="notes" />
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    const select = document.querySelector("select");

    expect(dep.hidden).toBe(false); // Kris Jhonson matches as a whole token

    select.value = "John Doe";
    select.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(false); // John Doe matches

    select.value = "Jane Doe";
    select.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(true); // neither
});

// --- AND across fields ---

test.serial("multiple data-when-* on one dependent AND-match", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <input type="radio" name="authorized" value="yes" />
            <input type="radio" name="authorized" value="no" checked />
            <input type="radio" name="needs_visa" value="yes" />
            <input type="radio" name="needs_visa" value="no" checked />
            <fieldset data-conditional-fields-target="dependent"
                      data-when-authorized="no"
                      data-when-needs-visa="yes">
                <input name="sponsorship_country" />
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    const needsVisaYes = document.querySelector('[name="needs_visa"][value="yes"]');

    expect(dep.hidden).toBe(true); // authorized=no matches, needs_visa=no does not → AND fails

    needsVisaYes.checked = true;
    needsVisaYes.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(false); // both match
});

// --- radio group ---

test.serial("radio group — effective value is the checked radio", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <input type="radio" name="plan" value="starter" checked />
            <input type="radio" name="plan" value="pro" />
            <input type="radio" name="plan" value="enterprise" />
            <fieldset data-conditional-fields-target="dependent" data-when-plan="pro|enterprise">
                <input name="team_size" />
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    expect(dep.hidden).toBe(true);

    const pro = document.querySelector('[name="plan"][value="pro"]');
    pro.checked = true;
    pro.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(false);
});

// --- checkbox tokens ---

test.serial(":checked token matches when checkbox is checked", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <input type="checkbox" name="ship_different" value="1" />
            <fieldset data-conditional-fields-target="dependent" data-when-ship-different=":checked">
                <input name="shipping_address" />
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    const cb = document.querySelector('[name="ship_different"]');

    expect(dep.hidden).toBe(true);

    cb.checked = true;
    cb.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(false);
});

test.serial(":unchecked token matches when checkbox is not checked", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <input type="checkbox" name="agree" value="1" />
            <fieldset data-conditional-fields-target="dependent" data-when-agree=":unchecked">
                <p>Please accept the terms</p>
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    const cb = document.querySelector('[name="agree"]');

    expect(dep.hidden).toBe(false);

    cb.checked = true;
    cb.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(true);
});

// --- checkbox group with name[] ---

test.serial("checkbox group name[] — matches when any value is checked", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <input type="checkbox" name="interests[]" value="news" />
            <input type="checkbox" name="interests[]" value="tips" />
            <input type="checkbox" name="interests[]" value="events" />
            <fieldset data-conditional-fields-target="dependent" data-when-interests="events">
                <input name="webinar_reminders" type="checkbox" />
            </fieldset>
        </form>
    `);

    const dep = document.querySelector("fieldset");
    expect(dep.hidden).toBe(true);

    const events = document.querySelector('[value="events"]');
    events.checked = true;
    events.dispatchEvent(new Event("change", { bubbles: true }));
    expect(dep.hidden).toBe(false);
});

// --- non-fieldset dependent ---

test.serial("non-fieldset dependent — disables descendant inputs on hide", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <select name="mode">
                <option value="simple" selected>Simple</option>
                <option value="advanced">Advanced</option>
            </select>
            <div data-conditional-fields-target="dependent" data-when-mode="advanced">
                <input name="threshold" />
                <input name="window_ms" disabled />
            </div>
        </form>
    `);

    const dep = document.querySelector("div[data-conditional-fields-target='dependent']");
    const threshold = document.querySelector('[name="threshold"]');
    const windowMs = document.querySelector('[name="window_ms"]');

    expect(dep.hidden).toBe(true);
    expect(threshold.disabled).toBe(true);
    expect(windowMs.disabled).toBe(true); // was already disabled
});

test.serial("non-fieldset dependent — restores original disabled state on show", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <select name="mode">
                <option value="simple" selected>Simple</option>
                <option value="advanced">Advanced</option>
            </select>
            <div data-conditional-fields-target="dependent" data-when-mode="advanced">
                <input name="threshold" />
                <input name="window_ms" disabled />
            </div>
        </form>
    `);

    const select = document.querySelector("select");
    const threshold = document.querySelector('[name="threshold"]');
    const windowMs = document.querySelector('[name="window_ms"]');

    select.value = "advanced";
    select.dispatchEvent(new Event("change", { bubbles: true }));

    expect(threshold.disabled).toBe(false);
    expect(windowMs.disabled).toBe(true); // original state preserved
});

// --- cleanup ---

test.serial("disconnect removes the delegated change listener", async () => {
    await mount(`
        <form data-controller="conditional-fields">
            <select name="reason">
                <option value="bug" selected>Bug</option>
                <option value="other">Other</option>
            </select>
            <fieldset data-conditional-fields-target="dependent" data-when-reason="other">
                <input name="other_reason" />
            </fieldset>
        </form>
    `);

    const select = document.querySelector("select");
    const dep = document.querySelector("fieldset");

    // Call disconnect directly — happy-dom's application.stop() doesn't reliably
    // flush controller disconnects, but we own the controller method so test it
    // directly. Subsequent change events should not flip dep state.
    mounted.controller.disconnect();

    select.value = "other";
    select.dispatchEvent(new Event("change", { bubbles: true }));

    expect(dep.hidden).toBe(true);
});

async function mount(html) {
    mounted = await mountController("conditional-fields", ConditionalFieldsController, html);
}
