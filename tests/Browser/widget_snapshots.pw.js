import { expect, test } from "@playwright/test";
import { execFileSync } from "node:child_process";
import { readFileSync } from "node:fs";

let bundle;
test.beforeAll(() => {
    bundle = execFileSync("bun", ["tests/Browser/fixtures/build_widget_snapshots.js"], { maxBuffer: 15 * 1024 * 1024 }).toString();
});

for (const output of ["html", "json"]) for (const upstream of [false, true]) for (const inside of [false, true]) {
    test(`restores ${output} draft ${inside ? "inside" : "outside"} frame (${upstream ? "1555" : "published"})`, async ({ page }) => {
        const requests = await start(page, { output, upstream, inside });
        await expect(page.locator(".ProseMirror")).toHaveCount(1);
        await page.locator(".ProseMirror").fill("Local draft");
        await expect(page.locator("textarea")).toHaveValue(/Local draft/);
        const draft = await page.locator("textarea").inputValue();
        await promote(page);
        await page.goBack();
        await restored(page);
        await expect(page).toHaveURL(/\/one$/);
        await expect(page.locator("#client-marker")).toHaveCount(1);
        await expect(page.locator(".ProseMirror")).toHaveCount(1);
        await expect(page.locator("textarea")).toHaveValue(draft);
        await expect(page.locator(".ProseMirror")).toContainText("Local draft");
        await page.locator(".ProseMirror").fill("Still editable");
        await expect(page.locator("#editor textarea")).toHaveValue(/Still editable/);
        for (let round = 0; round < 2; round++) {
            await page.goForward();
            await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/two");
            await page.goBack();
            await restored(page);
            await expect(page.locator(".ProseMirror")).toHaveCount(1);
            await expect(page.locator("#editor textarea")).toHaveValue(/Still editable/);
        }
        expect(requests()).toBe(2);
    });
}

test("restores one Leaflet structure without consuming application children", async ({ page }) => {
    const requests = await start(page);
    await expect(page.locator("#map > .leaflet-pane")).toHaveCount(1);
    await promote(page);
    await page.goBack();
    await restored(page);
    await expect(page.locator("#client-marker")).toHaveCount(1);
    await expect(page.locator("#map > .leaflet-pane")).toHaveCount(1);
    await expect(page.locator("#map > .leaflet-control-container")).toHaveCount(1);
    await expect(page.locator("#app-map-content")).toHaveText("Application content");
    await page.evaluate(() => window.widget("map", "custom-map").map.setZoom(9, { animate: false }));
    expect(await page.evaluate(() => window.widget("map", "custom-map").map.getZoom())).toBe(9);
    expect(requests()).toBe(2);
});

test("discards orphan tooltips and only their ARIA tokens from detached snapshots", async ({ page }) => {
    await start(page);
    await page.locator("#tip").focus();
    await expect(page.locator('[role="tooltip"]')).toHaveCount(1);
    // Keep the tooltip open while the sibling frame navigates (without clicking elsewhere).
    await page.evaluate(() => document.querySelector("#next").click());
    await expect(page).toHaveURL(/\/two$/);
    await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/two");
    await cached(page);
    await page.goBack();
    await restored(page);
    await expect(page.locator("#client-marker")).toHaveCount(1);
    await expect(page.locator('[role="tooltip"]')).toHaveCount(0);
    await expect(page.locator("#tip")).toHaveAttribute("aria-describedby", "app-help");
    await page.locator("#tip").evaluate((element) => element.blur());
    await page.locator("#tip").focus();
    await expect(page.locator('[role="tooltip"]')).toHaveCount(1);
    await page.keyboard.press("Escape");
    await expect(page.locator('[role="tooltip"]')).toHaveCount(0);
});

test("restores transient overlays closed and releases only package-owned body effects", async ({ page }) => {
    await start(page, { upstream: true });
    await page.evaluate(async () => {
        await window.widget("modal", "modal").overlay.open();
        window.widget("dropdown", "dropdown").open();
    });
    await expect(page.locator("#modal-surface")).toHaveAttribute("data-state", "open");
    expect(await page.evaluate(() => {
        const clone = document.body.cloneNode(true);
        window.normalizeSnapshot(clone);
        return clone.querySelector("#menu-trigger").getAttribute("aria-expanded");
    })).toBe("false");
    await page.evaluate(() => document.querySelector("#next").click());
    await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/two");
    await cached(page);
    await page.goBack();
    await restored(page);
    await expect(page.locator("#client-marker")).toHaveCount(1);
    await expect(page.locator("#modal-surface")).toHaveAttribute("data-state", "closed");
    await expect(page.locator("#modal-surface")).toHaveAttribute("hidden", "");
    await expect(page.locator("#menu")).toHaveAttribute("hidden", "");
    await expect(page.locator("#modal-surface")).toBeHidden();
    await expect(page.locator("#menu")).toBeHidden();
    await expect(page.locator("#menu-trigger")).toHaveAttribute("aria-expanded", "false");
    await expect(page.locator("body")).not.toHaveClass(/package-lock/);
    await expect(page.locator("body")).toHaveClass(/app-lock/);
    expect(await page.locator("body").evaluate((body) => body.style.color)).toBe("red");
    await page.evaluate(() => window.widget("modal", "modal").overlay.open());
    await page.keyboard.press("Escape");
    await expect(page.locator("#modal-surface")).toBeHidden();
});

for (const [direction, side, priority] of [["ltr", "right", ""], ["rtl", "left", "important"]]) {
    test(`restores ${direction} body padding precedence after caching an open overlay`, async ({ page }) => {
        await start(page, { upstream: true });
        await page.evaluate(({ direction, side, priority }) => {
            document.documentElement.dir = direction;
            Object.defineProperty(document.documentElement, "clientWidth", { configurable: true, value: innerWidth - 20 });
            document.body.style.setProperty("padding-inline-end", "10px", priority);
            document.body.style.setProperty(`padding-${side}`, "30px", priority);
            document.body.style.setProperty("--application-state", "initial");
        }, { direction, side, priority });
        await expect(page.locator("body")).toHaveCSS(`padding-${side}`, "30px");
        await page.evaluate(async () => {
            await window.widget("modal", "modal").overlay.open();
            document.body.style.setProperty("--application-state", "preserved");
        });
        await page.evaluate(() => document.querySelector("#next").click());
        await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/two");
        await cached(page);

        await page.goBack();
        await restored(page);

        await expect(page.locator("body")).toHaveCSS(`padding-${side}`, "30px");
        expect(await page.locator("body").evaluate(body => body.style.getPropertyPriority("padding-inline-end"))).toBe(priority);
        expect(await page.locator("body").evaluate(body => body.style.getPropertyValue("--application-state"))).toBe("preserved");
    });
}

test("reconstructs ECharts and Embla without duplicate generated UI", async ({ page }) => {
    await start(page);
    await promote(page);
    await page.goBack();
    await restored(page);
    await expect(page.locator("#chart canvas")).toHaveCount(1);
    await expect(page.locator("#dots > button")).toHaveCount(2);
    await page.evaluate(() => window.widget("carousel", "carousel").next());
    await expect.poll(() => page.evaluate(() => window.widget("carousel", "carousel").embla.selectedScrollSnap())).toBe(1);
    await page.evaluate(() => window.widget("chart", "chart").chart.setOption({ series: [{ data: [7] }] }));
    expect(await page.evaluate(() => window.widget("chart", "chart").chart.getOption().series[0].data[0])).toBe(7);
});

test("normalizes morph-guard claims without removing application permanent", async ({ page }) => {
    await start(page, { upstream: true });
    await expect(page.locator("#guard-frame")).toHaveAttribute("data-turbo-permanent", "");
    const state = await page.evaluate(() => {
        const clone = document.body.cloneNode(true);
        window.normalizeSnapshot(clone);
        return {
            transient: clone.querySelector("#guard-frame").hasAttribute("data-turbo-permanent"),
            application: clone.querySelector("#app-permanent").hasAttribute("data-turbo-permanent"),
            live: document.querySelector("#guard-frame").hasAttribute("data-turbo-permanent"),
        };
    });
    expect(state).toEqual({ transient: false, application: true, live: true });
});

test("does not restore an orphan opening cloned from a reusable overlay host", async ({ page }) => {
    await start(page);
    await page.locator("#open-child").click();
    await expect(page.locator('[data-hotwire-overlay-instance]')).toHaveCount(1);
    await expect(page.locator('[data-hotwire-overlay-instance] [data-modal-target="modal"]')).toHaveAttribute("data-state", "open");
    await page.evaluate(() => document.querySelector("#next").click());
    await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/two");
    await cached(page);
    await page.goBack();
    await restored(page);
    await expect(page.locator("#client-marker")).toHaveCount(1);
    await expect(page.locator('[data-hotwire-overlay-instance]')).toHaveCount(0);
    await expect(page.locator("#modal-host")).toHaveCount(1);
});

test("keeps a sibling upload running and hydrates only completed attachments from cache", async ({ page }) => {
    await start(page, { upstream: true });
    let pending;
    await page.route("http://snapshots.test/upload", (route) => { pending = route; });
    await page.locator('#upload input[type="file"]').setInputFiles({ name: "new.txt", mimeType: "text/plain", buffer: Buffer.from("draft") });
    await expect.poll(() => Boolean(pending)).toBe(true);
    await promote(page);
    await expect(page.locator('#upload [data-state="uploading"]')).toHaveCount(1);
    await pending.fulfill({ status: 201, contentType: "application/json", body: '{"token":"new-token"}' });
    await expect(page.locator('#upload input[value="new-token"]')).toHaveCount(1);
    await page.goBack();
    await restored(page);
    await expect(page.locator('#upload [data-file-upload-attachment]')).toHaveCount(1);
    await expect(page.locator('#upload input[data-hw-upload]')).toHaveCount(1);
    await expect(page.locator('#upload input[data-hw-upload]')).toHaveValue("existing-token");
    expect(await page.evaluate(() => window.widget("upload", "file-upload").activeUploads)).toBe(0);
});

test("retains snapshot ownership through morph without replacing a live editor", async ({ page }) => {
    await start(page, { upstream: true });
    const state = await page.evaluate(() => {
        const controller = window.widget("editor", "custom-editor");
        const instance = controller.instance;
        const replacement = controller.element.cloneNode(true);
        for (const node of replacement.querySelectorAll("[data-hotwire-snapshot], [data-hotwire-generated]")) {
            node.removeAttribute("data-hotwire-snapshot");
            node.removeAttribute("data-hotwire-generated");
        }
        window.Turbo.morphElements(controller.element, replacement);
        const clone = document.body.cloneNode(true);
        window.normalizeSnapshot(clone);
        return { same: controller.instance === instance, clonedEditors: clone.querySelectorAll(".ProseMirror").length };
    });
    expect(state).toEqual({ same: true, clonedEditors: 0 });
});

test("leaves live sibling widgets untouched for frames without an action", async ({ page }) => {
    const requests = await start(page, { action: "" });
    await page.evaluate(() => {
        window.liveEditor = window.widget("editor", "custom-editor").instance;
        window.liveMap = window.widget("map", "custom-map").map;
    });
    await page.locator("#next").click();
    await expect(page.locator("#results")).toContainText("Two");
    await expect(page).toHaveURL(/\/one$/);
    expect(await page.evaluate(() => window.liveEditor === window.widget("editor", "custom-editor").instance && window.liveMap === window.widget("map", "custom-map").map)).toBe(true);
    expect(requests()).toBe(2);
});

test("normal Drive restores the cached draft with a single editable instance", async ({ page }) => {
    const requests = await start(page);
    await page.locator(".ProseMirror").fill("Drive draft");
    await expect(page.locator("textarea")).toHaveValue(/Drive draft/);
    await page.evaluate(() => window.Turbo.visit("/two"));
    await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/two");
    await cached(page);
    await page.goBack();
    await restored(page);
    await expect(page.locator(".ProseMirror")).toHaveCount(1);
    await expect(page.locator(".ProseMirror")).toContainText("Drive draft");
    expect(requests()).toBe(2);
});

test("replace keeps the draft when returning Forward to the replacement entry", async ({ page }) => {
    await start(page, { action: "replace", upstream: true, previous: true });
    await page.locator(".ProseMirror").fill("Replace draft");
    await expect(page.locator("textarea")).toHaveValue(/Replace draft/);
    await promote(page);
    await page.goBack();
    await expect(page).toHaveURL(/\/before$/);
    await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/before");
    await page.goForward();
    await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/two");
    await expect(page.locator("#client-marker")).toHaveCount(1);
    await expect(page.locator(".ProseMirror")).toHaveCount(1);
    await expect(page.locator(".ProseMirror")).toContainText("Replace draft");
});

async function start(page, { output = "html", upstream = false, inside = false, action = "advance", previous = false } = {}) {
    let requests = 0;
    await page.route("http://snapshots.test/**", (route) => {
        requests++;
        if (route.request().url().endsWith("/dialog")) return route.fulfill({ contentType: "text/html", body: `<turbo-frame id="${route.request().headers()["turbo-frame"]}"><button>Child dialog</button></turbo-frame>` });
        const editor = `<section id="editor" data-controller="custom-editor" data-custom-editor-output-value="${output}">
                <textarea data-custom-editor-target="input">${output === "html" ? "<p>Server</p>" : '{"type":"doc","content":[{"type":"paragraph"}]}'}</textarea>
                <div data-custom-editor-target="editor"></div><span id="app-editor-content">Keep me</span>
            </section>`;
        return route.fulfill({ contentType: "text/html", body: `<!doctype html><html><head>
            <meta name="turbo-prefetch" content="false"><title>Snapshots</title>
            <style>${readFileSync("node_modules/leaflet/dist/leaflet.css", "utf8")}</style>
            </head><body class="app-lock" style="color:red">
            ${inside ? "" : editor}
            <div id="map" data-controller="custom-map" style="width:300px;height:200px"><span id="app-map-content">Application content</span></div>
            <p id="app-help">Help</p><button id="tip" data-controller="tooltip" aria-describedby="app-help">Tip
                <template data-tooltip-target="template"><aside>Tooltip</aside></template>
            </button>
            <div id="modal" data-controller="modal" data-modal-lock-scroll-class="package-lock app-lock" data-action="turbo:before-cache@window->modal#closeForCache">
                <div id="modal-surface" data-modal-target="modal" data-state="closed" hidden inert>
                    <div data-modal-target="backdrop"></div><div data-modal-target="dialog"><button>Dialog button</button></div>
                </div>
            </div>
            <div id="dropdown" data-controller="dropdown">
                <button id="menu-trigger" data-dropdown-target="trigger">Menu</button>
                <div id="menu" data-dropdown-target="menu" data-state="closed" hidden inert><button role="menuitem">Item</button></div>
            </div>
            <div id="chart" data-controller="chart" style="width:300px;height:200px" data-chart-option-value='{"xAxis":{"data":["a"]},"yAxis":{},"series":[{"type":"bar","data":[1]}]}'></div>
            <div id="carousel" data-controller="carousel">
                <div data-carousel-viewport style="width:200px;overflow:hidden"><div data-carousel-container style="display:flex">
                    <div style="flex:0 0 100%">One</div><div style="flex:0 0 100%">Two</div>
                </div></div>
                <div id="dots" data-carousel-target="dotList"></div><template data-carousel-target="dotTemplate"><button>Dot</button></template>
            </div>
            <turbo-frame id="guard-frame"><form data-controller="turbo--morph-guard"><input value="Local"></form></turbo-frame>
            <div id="app-permanent" data-turbo-permanent>Application permanent</div>
            <a id="open-child" href="/dialog" data-turbo-frame="dialogs">Open child</a>
            <div id="modal-host" data-controller="modal" data-hotwire-overlay-host="dialogs" data-modal-lock-scroll-class="package-lock">
                <div data-modal-target="modal" data-state="closed" hidden inert><div data-modal-target="backdrop"></div>
                    <div data-modal-target="dialog"><turbo-frame id="dialogs" data-modal-target="dynamicContent"></turbo-frame></div>
                </div>
            </div>
            <div id="upload" data-controller="file-upload" data-file-upload-url-value="/upload" data-file-upload-hidden-name-value="files[]" data-file-upload-multiple-value="true">
                <input type="file" data-file-upload-target="input" data-action="change->file-upload#select">
                <input type="hidden" data-hw-upload data-file-upload-id="1" name="files[]" value="existing-token">
                <div data-file-upload-target="list"><div data-file-upload-attachment data-file-upload-id="1" data-state="done"><span data-file-upload-name>Existing</span></div></div>
                <template data-file-upload-target="template"><div data-file-upload-attachment data-state="idle"><span data-file-upload-name></span><span data-file-upload-description></span></div></template>
            </div>
            <turbo-frame id="results"><a id="next" href="/two" ${action ? `data-turbo-action="${action}"` : ""}>Next</a><span>${route.request().url().endsWith("/two") ? "Two" : "One"}</span>${inside ? editor : ""}</turbo-frame>
            </body></html>` });
    });
    await page.goto(`http://snapshots.test/${previous ? "before" : "one"}`);
    await page.evaluate(() => {
        window.loadedPaths = [];
        document.addEventListener("turbo:load", () => window.loadedPaths.push(location.pathname));
    });
    await page.addScriptTag({ content: turboSource(upstream) });
    await page.addScriptTag({ content: bundle });
    await page.evaluate(() => {
        // Match the response head: runtime scripts must not turn the cached restore into a head reload.
        for (const script of document.head.querySelectorAll("script")) {
            script.dataset.turboEval = "false";
            document.body.append(script);
        }
    });
    if (previous) {
        await page.evaluate(() => window.Turbo.visit("/one"));
        await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/one");
    }
    await page.evaluate(() => {
        const marker = document.createElement("span");
        marker.id = "client-marker";
        document.body.append(marker);
    });
    await expect(page.locator(".ProseMirror")).toHaveCount(1);
    await expect(page.locator("#map > .leaflet-pane")).toHaveCount(1);
    return () => requests;
}

async function promote(page) {
    await page.locator("#next").click();
    await expect(page).toHaveURL(/\/two$/);
    await expect(page.locator("#results")).toContainText("Two");
    await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/two");
    await cached(page);
}

async function cached(page) {
    // Read-only test instrumentation: synthetic load can precede the asynchronous cache write.
    await expect.poll(() => page.evaluate(() => Boolean(window.Turbo.session.view.getCachedSnapshotForLocation(new URL("/one", location.href))?.element.querySelector("#client-marker")))).toBe(true);
}

async function restored(page) {
    await expect.poll(() => page.evaluate(() => window.loadedPaths.at(-1))).toBe("/one");
    await expect.poll(() => page.evaluate(() => window.widget("map", "custom-map")?.map != null)).toBe(true);
}

function turboSource(upstream) {
    const source = readFileSync("node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js", "utf8");
    if (!upstream) return source;
    // Test-only application of PR #1555 (4d2876d); no production monkey-patch.
    const replacements = [
        ["async cacheSnapshot(snapshot = this.snapshot) {", "async cacheSnapshot(snapshot = this.snapshot, shouldNotifyApplication = true) {"],
        ["this.delegate.viewWillCacheSnapshot();", "if (shouldNotifyApplication) this.delegate.viewWillCacheSnapshot();"],
        ["this.view.cacheSnapshot(this.snapshot).then((snapshot) => snapshot && this.visitCachedSnapshot(snapshot));", `this.view.cacheSnapshot(this.snapshot, !this.snapshot).then((snapshot) => {
            if (snapshot) {
                this.visitCachedSnapshot(snapshot);
                snapshot.element.querySelectorAll('[data-turbo-temporary]').forEach((element) => element.remove());
            }
        });`],
    ];
    return replacements.reduce((result, [before, after]) => {
        expect(result.split(before)).toHaveLength(2);
        return result.replace(before, after);
    }, source);
}
