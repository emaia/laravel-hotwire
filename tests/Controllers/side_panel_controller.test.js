import { afterEach, expect, test } from "bun:test";

import {
    mountController,
    mountControllers,
    mountMultipleControllers,
    wait,
} from "../../resources/js/helpers/test_stimulus.js";
import SidebarController from "../../resources/js/controllers/sidebar_controller.js";
import SidePanelController from "../../resources/js/controllers/side_panel_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.cookie = "side_panel_project-nav_state=; path=/; max-age=0";
    document.cookie = "side_panel_inner_state=; path=/; max-age=0";
});

function template(open = true, name = "project-nav") {
    return `
        <div data-controller="side-panel"
             data-side-panel-name-value="${name}"
             data-side-panel-open-value="${open}"
             data-side-panel-cookie-name-value="side_panel_${name}_state"
             data-state="${open ? "expanded" : "collapsed"}">
            <aside id="${name}-panel" data-side-panel-target="panel" ${open ? "" : "inert"}>
                <a href="/tasks">Tasks</a>
            </aside>
            <button data-side-panel-target="trigger"
                    data-action="click->side-panel#toggle"
                    aria-controls="${name}-panel"
                    aria-expanded="${open}">Toggle</button>
        </div>
    `;
}

test("connect synchronizes state, trigger aria and panel inert", async () => {
    mounted = await mountController("side-panel", SidePanelController, template(false));

    expect(mounted.root.dataset.state).toBe("collapsed");
    expect(mounted.root.querySelector("aside").hasAttribute("inert")).toBe(true);
    expect(mounted.root.querySelector("button").getAttribute("aria-expanded")).toBe("false");
});

test("toggle updates state, accessibility and persistence", async () => {
    mounted = await mountController("side-panel", SidePanelController, template(true));
    let detail;
    mounted.root.addEventListener("side-panel:change", (event) => (detail = event.detail));

    mounted.root.querySelector("button").click();
    await wait(0);

    expect(mounted.root.dataset.state).toBe("collapsed");
    expect(mounted.root.querySelector("aside").hasAttribute("inert")).toBe(true);
    expect(mounted.root.querySelector("button").getAttribute("aria-expanded")).toBe("false");
    expect(document.cookie).toContain("side_panel_project-nav_state=false");
    expect(detail).toEqual({ open: false, state: "collapsed" });

    mounted.controller.open();

    expect(mounted.root.dataset.state).toBe("expanded");
    expect(mounted.root.querySelector("aside").hasAttribute("inert")).toBe(false);
    expect(mounted.root.querySelector("button").getAttribute("aria-expanded")).toBe("true");
});

test("nested side panels operate independently", async () => {
    mounted = await mountControllers(
        "side-panel",
        SidePanelController,
        `
        <div id="outer" data-controller="side-panel" data-side-panel-open-value="true" data-state="expanded">
            <aside data-side-panel-target="panel">
                <div id="inner" data-controller="side-panel"
                     data-side-panel-open-value="true"
                     data-side-panel-cookie-name-value="side_panel_inner_state"
                     data-state="expanded">
                    <aside data-side-panel-target="panel"><a href="/nested">Nested</a></aside>
                    <button id="inner-trigger" data-side-panel-target="trigger" data-action="side-panel#toggle">Inner</button>
                </div>
            </aside>
            <button id="outer-trigger" data-side-panel-target="trigger" data-action="side-panel#toggle">Outer</button>
        </div>
    `,
    );

    document.querySelector("#outer-trigger").click();
    await wait(0);

    expect(document.querySelector("#outer").dataset.state).toBe("collapsed");
    expect(document.querySelector("#outer > aside").hasAttribute("inert")).toBe(true);
    expect(document.querySelector("#inner").dataset.state).toBe("expanded");
    expect(document.querySelector("#inner > aside").hasAttribute("inert")).toBe(false);

    document.querySelector("#inner-trigger").click();
    await wait(0);

    expect(document.querySelector("#outer").dataset.state).toBe("collapsed");
    expect(document.querySelector("#inner").dataset.state).toBe("collapsed");
});

test("closing moves focus from panel content to its trigger", async () => {
    mounted = await mountController("side-panel", SidePanelController, template(true));
    const trigger = mounted.root.querySelector("button");
    mounted.root.querySelector("a").focus();

    mounted.controller.close();

    expect(document.activeElement).toBe(trigger);
    expect(mounted.root.querySelector("aside").hasAttribute("inert")).toBe(true);
});

test("Turbo renders keep the current state", async () => {
    mounted = await mountController("side-panel", SidePanelController, template(false));
    const newBody = document.createElement("body");
    newBody.innerHTML = template(true);

    mounted.controller.preserveStateForRender({ detail: { newBody } });

    const nextRoot = newBody.querySelector('[data-controller~="side-panel"]');
    expect(nextRoot.dataset.state).toBe("collapsed");
    expect(nextRoot.dataset.sidePanelOpenValue).toBe("false");
    expect(nextRoot.querySelector("aside").hasAttribute("inert")).toBe(true);
    expect(nextRoot.querySelector("button").getAttribute("aria-expanded")).toBe("false");
});

test("Turbo renders match reordered panels by name", async () => {
    mounted = await mountController("side-panel", SidePanelController, template(false, "filters"));
    const newBody = document.createElement("body");
    newBody.innerHTML = `${template(true, "navigation")}${template(true, "filters")}`;

    mounted.controller.preserveStateForRender({ detail: { newBody } });

    const roots = newBody.querySelectorAll('[data-controller~="side-panel"]');
    expect(roots[0].dataset.state).toBe("expanded");
    expect(roots[1].dataset.state).toBe("collapsed");
});

test("closing without a trigger focuses the root before making the panel inert", async () => {
    mounted = await mountController(
        "side-panel",
        SidePanelController,
        '<div data-controller="side-panel"><aside data-side-panel-target="panel"><a href="/">Link</a></aside></div>',
    );
    mounted.root.querySelector("a").focus();

    mounted.controller.close();

    expect(document.activeElement).toBe(mounted.root);
    expect(mounted.root.getAttribute("tabindex")).toBe("-1");
});

test("external open value changes move focus before making the panel inert", async () => {
    mounted = await mountController("side-panel", SidePanelController, template(true));
    const trigger = mounted.root.querySelector("button");
    mounted.root.querySelector("a").focus();

    mounted.controller.openValue = false;
    await wait(0);

    expect(document.activeElement).toBe(trigger);
    expect(mounted.root.querySelector("aside").hasAttribute("inert")).toBe(true);
});

test("disconnect removes a root tabindex added for focus fallback", async () => {
    mounted = await mountController(
        "side-panel",
        SidePanelController,
        '<div data-controller="side-panel"><aside data-side-panel-target="panel"><a href="/">Link</a></aside></div>',
    );
    mounted.root.querySelector("a").focus();
    mounted.controller.close();

    expect(mounted.root.getAttribute("tabindex")).toBe("-1");

    await mounted.cleanup();

    expect(mounted.root.hasAttribute("tabindex")).toBe(false);
    mounted = null;
});

test("a toggle synchronizes the live root once", async () => {
    mounted = await mountController("side-panel", SidePanelController, template(true));
    let syncs = 0;
    const sync = mounted.controller.sync.bind(mounted.controller);
    mounted.controller.sync = () => {
        syncs++;
        sync();
    };

    mounted.controller.close();
    await wait(0);

    expect(syncs).toBe(1);
});

test("same-turn external reversal keeps DOM synchronized with the final value", async () => {
    mounted = await mountController("side-panel", SidePanelController, template(true));

    mounted.controller.close();
    mounted.controller.openValue = true;
    await wait(0);

    expect(mounted.controller.openValue).toBe(true);
    expect(mounted.root.dataset.state).toBe("expanded");
    expect(mounted.root.querySelector("aside").hasAttribute("inert")).toBe(false);
    expect(mounted.root.querySelector("button").getAttribute("aria-expanded")).toBe("true");
});

test("an open app sidebar and its side panel keep independent state", async () => {
    mounted = await mountMultipleControllers(
        { sidebar: SidebarController, "side-panel": SidePanelController },
        `
            <div id="app-shell" data-controller="sidebar" data-sidebar-open-value="true" data-state="expanded">
                <button id="sidebar-trigger" data-slot="sidebar-trigger" data-action="sidebar#toggle">Sidebar</button>
                <div data-slot="sidebar" data-sidebar-collapsible="offcanvas" data-state="expanded">
                    <div id="workspace" data-controller="side-panel" data-side-panel-open-value="true" data-state="expanded">
                        <aside data-side-panel-target="panel">Tools</aside>
                        <button id="panel-trigger" data-side-panel-target="trigger" data-action="side-panel#toggle">Panel</button>
                    </div>
                </div>
            </div>
        `,
    );

    document.querySelector("#panel-trigger").click();
    await wait(0);

    expect(document.querySelector("#workspace").dataset.state).toBe("collapsed");
    expect(document.querySelector("#app-shell").dataset.state).toBe("expanded");

    document.querySelector("#sidebar-trigger").click();
    await wait(0);

    expect(document.querySelector("#app-shell").dataset.state).toBe("collapsed");
    expect(document.querySelector("#workspace").dataset.state).toBe("collapsed");
});

test("missing optional targets remain safe", async () => {
    mounted = await mountController("side-panel", SidePanelController, '<div data-controller="side-panel"></div>');

    expect(() => mounted.controller.toggle()).not.toThrow();
    expect(mounted.root.dataset.state).toBe("collapsed");
});
