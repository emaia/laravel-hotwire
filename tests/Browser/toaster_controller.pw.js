import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

// Reduced motion is on by default in headless Chromium, and createPresence honours it by skipping
// the animation entirely — which would make every motion assertion here vacuous.
test.use({ reducedMotion: "no-preference" });

// The unit suite runs on happy-dom, which has no layout and no CSS, so it stays green while the
// stack is clipped, the entry animation is missing or the transform silently collapses to identity.
// Everything here is about motion and geometry the browser actually computes.

test("enters from off screen, not from its resting position", async ({ page }) => {
    await setup(page);

    const path = await trackTransform(page, () => window.toaster.success("Saved", { duration: 0 }));

    // A card that starts at rest has nothing to travel and reads as a bare fade. The offset comes
    // from --toast-exit-y, so this also fails if any var in the transform loses its fallback.
    expect(Math.abs(path.at(0))).toBeGreaterThan(40);
    expect(path.at(-1)).toBe(0);
});

test("keeps the transform valid before the height has been measured", async ({ page }) => {
    await setup(page);

    // --toast-height is written after the element is attached. If any var in the transform lacked a
    // fallback the whole calc would be invalid, the card would sit at identity, and the entry would
    // degrade to a fade the moment the value landed.
    const atBirth = await page.evaluate(() => {
        const seen = [];
        const observer = new MutationObserver((records) => {
            records.forEach((record) => record.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.dataset?.slot === "toast") {
                    seen.push(getComputedStyle(node).transform);
                }
            }));
        });
        observer.observe(document.body, { childList: true, subtree: true });
        window.toaster.success("Saved", { duration: 0 });
        observer.disconnect();

        return seen[0];
    });

    expect(atBirth).not.toBe("none");
    expect(atBirth).not.toBe("matrix(1, 0, 0, 1, 0, 0)");
});

test("pushes the card behind back with a transition", async ({ page }) => {
    await setup(page);
    await page.evaluate(() => window.toaster.success("First", { duration: 0 }));
    await page.waitForTimeout(700);

    const path = await trackTransform(
        page,
        () => window.toaster.info("Second", { duration: 0 }),
        '[data-slot="toast"][data-behind]',
    );

    // Snapping into the new slot reports one value; gliding reports many. The stack grows upwards
    // from a bottom position, so the card behind travels to a negative offset.
    expect(path.length).toBeGreaterThan(3);
    expect(Math.abs(path.at(-1) - path.at(0))).toBeGreaterThan(10);
});

test("leaves from where the card sits instead of jumping to the front", async ({ page }) => {
    await setup(page);
    await page.evaluate(() => {
        window.toaster.success("First", { duration: 0 });
        window.toaster.info("Second", { duration: 0 });
    });
    await page.waitForTimeout(700);

    const restingY = await page.evaluate(
        () => new DOMMatrix(getComputedStyle(document.querySelector('[data-slot="toast"][data-behind]')).transform).m42,
    );
    const path = await trackTransform(
        page,
        () => window.toaster.dismiss(document.querySelector('[data-slot="toast"][data-behind]').dataset.toastId),
        '[data-slot="toast"][data-behind]',
    );

    // The first sample has to start at the card's resting offset. Replacing the transform on exit —
    // as the shadcn Toast does — teleports it to the front before it slides away.
    expect(Math.abs(path.at(0) - restingY)).toBeLessThan(12);
});

test("measures its height so the card is never clipped", async ({ page }) => {
    await setup(page);
    await page.evaluate(() => window.toaster.success("Saved", {
        description: "A description long enough to wrap onto a second line inside the card.",
        duration: 0,
    }));
    await page.waitForTimeout(700);

    const box = await page.evaluate(() => {
        const toast = document.querySelector('[data-slot="toast"]');
        const rendered = toast.offsetHeight;
        // data-measuring lifts the clamp, so this is the height the card wants.
        toast.setAttribute("data-measuring", "");
        const natural = toast.offsetHeight;
        toast.removeAttribute("data-measuring");

        return { rendered, natural, variable: toast.style.getPropertyValue("--toast-height") };
    });

    // The Turbo failure mode wrote a zero here and left the card at its two borders.
    expect(box.variable).toMatch(/^\d+(\.\d+)?px$/);
    expect(box.natural).toBeGreaterThan(40);
    expect(box.rendered).toBe(box.natural);
});

test("F6 moves focus to the viewport when a toast is on screen", async ({ page }) => {
    await setup(page);

    await page.keyboard.press("F6");
    await expect(page.locator('[data-slot="toaster"]')).not.toBeFocused();

    await page.evaluate(() => window.toaster.success("Saved", { duration: 0 }));
    await page.waitForTimeout(300);
    await page.keyboard.press("F6");

    await expect(page.locator('[data-slot="toaster"]')).toBeFocused();
});

// Nothing is on screen while a view transition runs: the browser paints its snapshot, not the live
// DOM. The two cases below are the orders the viewport can be born in, and each needs its own signal.

test("holds the entry back until a render under a view transition has finished", async ({ page }) => {
    // A preloaded chunk is already listening when the render starts.
    const seen = await sampleAcrossTransition(page, async (swap, capture) => {
        document.dispatchEvent(new CustomEvent("turbo:before-render"));
        const transition = document.startViewTransition(swap);
        await transition.finished;
        capture();
    }, () => document.dispatchEvent(new CustomEvent("turbo:load")));

    expect(seen.held.hidden).toBe(true);
    expect(seen.held.state).toBe("closed");
    expect(seen.released.state).toBe("open");
    expect(seen.released.opacity).toBeGreaterThan(0.9);
    expect(seen.path.length).toBeGreaterThan(3);
    expect(seen.released.y).toBe(0);
});

test("holds the entry back when the view transition is already running", async ({ page }) => {
    // A chunk imported on demand misses Turbo's events, but lands after the snapshot is taken —
    // the first point at which the transition's own animations can be observed.
    const seen = await sampleAcrossTransition(page, async (swap, capture) => {
        const transition = document.startViewTransition(() => {
            document.body.appendChild(document.createElement("hr"));
        });
        await transition.ready;
        swap();
        // Here the release is the transition's own animations, so the card has to be read while
        // they are still running — reading after they settle races the entry it just unblocked.
        await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
        capture();
        await transition.finished;
    });

    expect(seen.held.hidden).toBe(true);
    expect(seen.held.state).toBe("closed");
    expect(seen.released.state).toBe("open");
    expect(seen.released.opacity).toBeGreaterThan(0.9);
    expect(seen.path.length).toBeGreaterThan(3);
    expect(seen.released.y).toBe(0);
});

test("recovers when a render never lands", async ({ page }) => {
    await setup(page);

    const waited = await page.evaluate(async () => {
        // A visit that is aborted or superseded never reaches turbo:load.
        document.dispatchEvent(new CustomEvent("turbo:before-render"));
        await new Promise((resolve) => setTimeout(resolve, 2100));

        const started = performance.now();
        window.toaster.success("Saved", { duration: 0 });

        await new Promise((resolve) => {
            const tick = () => {
                const toast = document.querySelector('[data-slot="toast"]');
                if ((toast && !toast.hidden) || performance.now() - started > 3000) resolve();
                else requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });

        return Math.round(performance.now() - started);
    });

    // Without bounding the flag every later toast pays the full wait for a render that is gone.
    expect(waited).toBeLessThan(300);
});

test("survives a webfont landing mid-entry", async ({ page }) => {
    await setup(page);

    const path = await page.evaluate(async () => {
        const samples = [];
        window.toaster.success("Saved", {
            description: "A description long enough to wrap onto a second line inside the card.",
            duration: 0,
        });

        const started = performance.now();
        let swapped = false;

        await new Promise((resolve) => {
            const tick = () => {
                const toast = document.querySelector('[data-slot="toast"]');
                if (toast) samples.push(Math.round(new DOMMatrix(getComputedStyle(toast).transform).m42));

                // Stand-in for the font swap on a cold load: the text metrics change under the card
                // while it is still on its way in.
                if (!swapped && performance.now() - started > 120) {
                    swapped = true;
                    document.body.style.fontSize = "19px";
                }

                if (performance.now() - started < 800) requestAnimationFrame(tick);
                else resolve();
            };
            requestAnimationFrame(tick);
        });

        return [...new Set(samples)];
    });

    // Re-measuring lifts the clamp with transitions off. Doing that mid-entry cancels the animation
    // outright and the card drops into place — which is what a first, uncached load looks like.
    expect(path.length).toBeGreaterThan(3);
    expect(path.at(-1)).toBe(0);
});

test("leaves the cards already on screen alone while a newcomer is held", async ({ page }) => {
    await setup(page);
    await page.evaluate(() => window.toaster.success("First", { duration: 0 }));
    await page.waitForTimeout(700);

    const seen = await page.evaluate(async () => {
        const resting = document.querySelector('[data-slot="toast"]');
        const restingY = () => Math.round(new DOMMatrix(getComputedStyle(resting).transform).m42);
        const before = restingY();

        document.dispatchEvent(new CustomEvent("turbo:before-render"));
        window.toaster.info("Second", { duration: 0 });
        await new Promise((resolve) => setTimeout(resolve, 300));
        const held = restingY();

        document.dispatchEvent(new CustomEvent("turbo:load"));
        await new Promise((resolve) => setTimeout(resolve, 800));

        return { before, held, after: restingY() };
    });

    // Restacking on arrival rather than on entry opens a gap for a card that is not there yet, and
    // one arrival is seen as two separate movements.
    expect(seen.held).toBe(seen.before);
    expect(Math.abs(seen.after - seen.before)).toBeGreaterThan(10);
});

/**
 * Buffer a toast, run `transition` around the birth of the viewport, and report the card twice:
 * the moment the snapshot is dropped, and once the entry has had time to play out.
 */
async function sampleAcrossTransition(page, transition, release = () => {}) {
    await setup(page, { createToaster: false });

    return page.evaluate(async ([transitionSource, releaseSource]) => {
        const read = (toast) => ({
            hidden: toast.hidden,
            state: toast.dataset.state,
            opacity: Number(getComputedStyle(toast).opacity),
            y: new DOMMatrix(getComputedStyle(toast).transform).m42,
        });

        window.emitToast({ message: "Saved", duration: 0 });

        const swap = () => {
            window.toaster = window.createToaster(
                document.querySelector('[data-slot="toaster"]'),
                { position: "bottom-end" },
            );
        };

        let held = null;
        const capture = () => {
            held = read(document.querySelector('[data-slot="toast"]'));
        };

        // eslint-disable-next-line no-new-func
        await new Function(`return (${transitionSource})`)()(swap, capture);

        const toast = document.querySelector('[data-slot="toast"]');

        // eslint-disable-next-line no-new-func
        new Function(`return (${releaseSource})`)()();

        // Sample the way out as well. Ending up open proves nothing on its own: a card whose
        // transition was cancelled lands there too, in a single frame, which is the pop-in this
        // whole hold exists to prevent.
        const path = [];
        const started = performance.now();
        await new Promise((resolve) => {
            const tick = () => {
                path.push(Math.round(new DOMMatrix(getComputedStyle(toast).transform).m42));
                if (performance.now() - started < 800) requestAnimationFrame(tick);
                else resolve();
            };
            requestAnimationFrame(tick);
        });

        return { held, released: read(toast), path: [...new Set(path)] };
    }, [transition.toString(), release.toString()]);
}

/** Sample the vertical translate of a toast every frame while `action` plays out. */
async function trackTransform(page, action, selector = '[data-slot="toast"]:not([data-behind])') {
    const samples = await page.evaluate(async ([fn, sel]) => {
        const collected = [];
        const started = performance.now();
        // eslint-disable-next-line no-new-func
        new Function(`return (${fn})`)()();

        await new Promise((resolve) => {
            const tick = () => {
                const element = document.querySelector(sel);
                if (element) {
                    collected.push(Math.round(new DOMMatrix(getComputedStyle(element).transform).m42));
                }
                if (performance.now() - started < 700) requestAnimationFrame(tick);
                else resolve();
            };
            requestAnimationFrame(tick);
        });

        return collected;
    }, [action.toString(), selector]);

    return [...new Set(samples)];
}

async function setup(page, { createToaster = true } = {}) {
    await page.setContent(`
        <style>${await readFile("resources/css/structural.css", "utf8")}</style>
        <style>
            /* Stand-in for the preset: the shipped one is written with @apply and cannot be
               injected raw. Only the parts the geometry depends on are reproduced. */
            body { margin: 0; font: 14px/1.4 system-ui, sans-serif; }
            [data-slot="toast"] {
                width: 22rem;
                border: 1px solid #e5e5e5;
                border-radius: 16px;
                background: #fff;
                transition: transform 500ms cubic-bezier(0.22, 1, 0.36, 1), opacity 500ms, block-size 150ms;
            }
            [data-slot="toast"][data-state="closed"] {
                transition: transform 260ms cubic-bezier(0.32, 0, 0.67, 0), opacity 200ms ease-in;
            }
            [data-slot="toast-content"] { display: flex; align-items: center; gap: 12px; padding: 16px; height: 100%; overflow: hidden; }
            [data-slot="toast-body"] { display: flex; min-width: 0; flex: 1; flex-direction: column; gap: 4px; }
            [data-slot="toast-icon"] { width: 16px; height: 16px; flex-shrink: 0; }
            [data-slot="toast-close"] { width: 32px; height: 32px; flex-shrink: 0; }
        </style>
        <div data-slot="toaster"></div>
    `);

    await page.addScriptTag({ content: await bundle() });

    if (!createToaster) return;

    await page.evaluate(() => {
        window.toaster = window.createToaster(document.querySelector('[data-slot="toaster"]'), {
            position: "bottom-end",
        });
    });
}

async function bundle() {
    // The modules are inlined as a plain script, so every export keyword has to go. Stripping them
    // by name meant a newly exported helper became a SyntaxError that took the whole bundle with it.
    const presence = (await readFile("resources/js/controllers/_presence.js", "utf8"))
        .replace(/export function /g, "function ");

    const toaster = (await readFile("resources/js/controllers/_toaster.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/export function /g, "function ");

    return `${presence}\n${toaster}\nwindow.createToaster = createToaster;\nwindow.emitToast = emitToast;`;
}
