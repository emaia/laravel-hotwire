import { afterEach, expect, mock, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import PaginationController from "../../resources/js/controllers/pagination_controller.js";

let mounted;
let ioInstances = [];

class FakeIntersectionObserver {
    constructor(callback, options) {
        this.callback = callback;
        this.options = options;
        this.observed = [];
        this.disconnected = false;
        ioInstances.push(this);
    }

    observe(element) { this.observed.push(element); }
    unobserve(element) { this.observed = this.observed.filter((observed) => observed !== element); }
    disconnect() { this.disconnected = true; this.observed = []; }
    trigger(entries) { this.callback(entries, this); }
}

class ImmediateIntersectionObserver extends FakeIntersectionObserver {
    observe(element) {
        super.observe(element);
        queueMicrotask(() => this.trigger([{ isIntersecting: true, target: element }]));
    }
}

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    ioInstances = [];
    delete globalThis.IntersectionObserver;
    delete window.IntersectionObserver;
    delete globalThis.fetch;
    delete window.fetch;
});

test.serial("load appends next page content into the configured target and replaces the pagination control", async () => {
    await mount(pageHtml());
    installFetch(nextPageHtml());

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(document.querySelector("#page-1")).not.toBeNull();
    expect(document.querySelector("#page-2")).not.toBeNull();
    expect(document.querySelector("#page-3-link")).not.toBeNull();
    expect(document.querySelector("#page-2-link")).toBeNull();
    expect(mounted.root.isConnected).toBe(false);
});

test.serial("terminal response appends content and removes the pagination control", async () => {
    await mount(pageHtml());
    installFetch(`
        <turbo-frame id="users">
            <h2 id="heading">Users</h2>
            <div id="users-list"><div id="page-2">Page 2</div></div>
        </turbo-frame>
    `);

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(document.querySelector("#page-1")).not.toBeNull();
    expect(document.querySelector("#page-2")).not.toBeNull();
    expect(document.querySelector("[data-slot='pagination']")).toBeNull();
});

test.serial("load does not duplicate response siblings that are outside the append target", async () => {
    await mount(pageHtml());
    installFetch(`
        <turbo-frame id="users">
            <h2 id="heading">Users</h2>
            <div id="users-list"><div id="page-2">Page 2</div></div>
            <nav data-controller="pagination" data-pagination-append-to-value="#users-list" data-slot="pagination">
                <a id="page-3-link" href="/users?page=3" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
            </nav>
        </turbo-frame>
    `);

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(document.querySelectorAll("#heading")).toHaveLength(1);
    expect(document.querySelectorAll("#users-list")).toHaveLength(1);
    expect(document.querySelector("#page-2")).not.toBeNull();
});

test.serial("load sends an HTML accept header and marks busy while the request is pending", async () => {
    await mount(pageHtml());

    let resolveFetch;
    const fetch = mock(() => new Promise((resolve) => { resolveFetch = resolve; }));
    window.fetch = fetch;
    globalThis.fetch = fetch;

    const link = mounted.root.querySelector("a");
    link.click();

    expect(fetch.mock.calls[0][0]).toBe("http://localhost/users?page=2");
    expect(fetch.mock.calls[0][1].headers.Accept).toContain("text/html");
    expect(mounted.root.getAttribute("aria-busy")).toBe("true");
    expect(link.getAttribute("aria-busy")).toBe("true");
    expect(mounted.root.querySelector("[role='status']").textContent).toBe("Loading more");
    expect(mounted.root.getAttribute("data-state")).toBe("loading");
    expect(mounted.root.querySelector("[data-slot='pagination-next-loading-label']").textContent).toBe("Loading more");

    resolveFetch(htmlResponse(nextPageHtml()));
    await wait(0);
    await wait(0);

    expect(document.querySelector("[aria-busy='true']")).toBeNull();
    await wait(0);
    expect(document.querySelector("[role='status']").textContent).toBe("More results loaded");
});

test.serial("load restores focus to the replacement next link", async () => {
    await mount(pageHtml());
    installFetch(nextPageHtml());

    const link = mounted.root.querySelector("a");
    link.focus();
    link.click();
    await wait(0);
    await wait(0);

    expect(document.activeElement.id).toBe("page-3-link");
});

test.serial("manual load scrolls to the configured target", async () => {
    await mount(pageHtml({ scrollTo: "#heading" }));
    installFetch(nextPageHtml());
    const scrollIntoView = mock(() => {});
    document.querySelector("#heading").scrollIntoView = scrollIntoView;

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(scrollIntoView).toHaveBeenCalledTimes(1);
});

test.serial("load restores the scroll position of the nearest scrollable ancestor", async () => {
    await mount(`
        <turbo-frame id="users">
            <div id="scroller" style="height: 100px; overflow: auto;">
                <div id="anchor">Anchor</div>
            </div>
            <nav data-controller="pagination" data-slot="pagination"></nav>
        </turbo-frame>
    `);

    const scroller = document.querySelector("#scroller");
    const anchor = document.querySelector("#anchor");

    Object.defineProperty(scroller, "scrollHeight", { configurable: true, value: 300 });
    Object.defineProperty(scroller, "clientHeight", { configurable: true, value: 100 });
    Object.defineProperty(scroller, "scrollWidth", { configurable: true, value: 100 });
    Object.defineProperty(scroller, "clientWidth", { configurable: true, value: 100 });
    anchor.getBoundingClientRect = () => ({ top: 130, left: 0 });
    scroller.scrollTop = 40;

    const originalGetComputedStyle = window.getComputedStyle;
    const getComputedStyle = (element) => element === scroller
        ? { overflowY: "auto", overflowX: "visible" }
        : originalGetComputedStyle(element);
    window.getComputedStyle = getComputedStyle;
    globalThis.getComputedStyle = getComputedStyle;

    mounted.controller.restorePosition(anchor, { top: 100, left: 0 });

    window.getComputedStyle = originalGetComputedStyle;
    globalThis.getComputedStyle = originalGetComputedStyle;

    expect(scroller.scrollTop).toBe(70);
    expect(document.documentElement.scrollTop).not.toBe(70);
});

test.serial("intersection load does not scroll to the configured target", async () => {
    installIntersectionObserver();
    await mount(pageHtml({ infinite: true, scrollTo: "#heading" }));
    installFetch(nextPageHtml());
    const scrollIntoView = mock(() => {});
    document.querySelector("#heading").scrollIntoView = scrollIntoView;

    ioInstances[0].trigger([{ isIntersecting: true, target: mounted.root.querySelector("a") }]);
    await wait(0);
    await wait(0);

    expect(scrollIntoView).not.toHaveBeenCalled();
});

test.serial("load appends into the previous keyed container and replaces the pagination wrapper", async () => {
    await mount(`
        <turbo-frame id="results">
            <div id="tasks"><article id="task-1">Task 1</article></div>
            <div id="tasks-pagination">
                <nav data-controller="pagination" data-slot="pagination">
                    <a id="page-2-link" href="/tasks?page=2" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
                </nav>
            </div>
        </turbo-frame>
    `);
    installFetch(`
        <turbo-frame id="results">
            <div id="tasks"><article id="task-2">Task 2</article></div>
            <div id="tasks-pagination" data-page="2">
                <nav data-controller="pagination" data-slot="pagination">
                    <a id="page-3-link" href="/tasks?page=3" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
                </nav>
            </div>
        </turbo-frame>
    `);

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(document.querySelectorAll("#tasks")).toHaveLength(1);
    expect(document.querySelector("#task-1")).not.toBeNull();
    expect(document.querySelector("#task-2")).not.toBeNull();
    expect(document.querySelector("#tasks-pagination").dataset.page).toBe("2");
    expect(document.querySelector("#page-2-link")).toBeNull();
    expect(document.querySelector("#page-3-link")).not.toBeNull();
});

test.serial("load appends into append-to when the pagination nav has footer siblings", async () => {
    await mount(`
        <turbo-frame id="results">
            <div id="rows"><article id="row-1">Row 1</article></div>
            <div id="footer">
                <span id="summary">Showing 10 of 200</span>
                <nav data-controller="pagination" data-pagination-append-to-value="#rows" data-slot="pagination">
                    <a id="page-2-link" href="/tasks?page=2" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
                </nav>
            </div>
        </turbo-frame>
    `);
    installFetch(`
        <turbo-frame id="results">
            <div id="rows"><article id="row-2">Row 2</article></div>
            <div id="footer">
                <span id="summary">Showing 20 of 200</span>
                <nav data-controller="pagination" data-pagination-append-to-value="#rows" data-slot="pagination">
                    <a id="page-3-link" href="/tasks?page=3" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
                </nav>
            </div>
        </turbo-frame>
    `);

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(document.querySelector("#row-1")).not.toBeNull();
    expect(document.querySelector("#row-2")).not.toBeNull();
    expect(document.querySelectorAll("#summary")).toHaveLength(1);
    expect(document.querySelector("#summary").textContent).toBe("Showing 20 of 200");
    expect(document.querySelector("#page-2-link")).toBeNull();
    expect(document.querySelector("#page-3-link")).not.toBeNull();
});

test.serial("append-to may contain the pagination nav without replacing accumulated content", async () => {
    await mount(`
        <turbo-frame id="frame">
            <div id="results">
                <div id="row-1" class="row">Row 1</div>
                <nav data-controller="pagination" data-pagination-append-to-value="#results" data-slot="pagination">
                    <a id="page-2-link" href="/rows?page=2" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
                </nav>
            </div>
        </turbo-frame>
    `);
    installFetch(`
        <turbo-frame id="frame">
            <div id="results">
                <div id="row-2" class="row">Row 2</div>
                <nav data-controller="pagination" data-pagination-append-to-value="#results" data-slot="pagination">
                    <a id="page-3-link" href="/rows?page=3" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
                </nav>
            </div>
        </turbo-frame>
    `);

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(document.querySelector("#row-1")).not.toBeNull();
    expect(document.querySelector("#row-2")).not.toBeNull();
    expect([...document.querySelector("#results").children].map((child) => child.id)).toEqual(["row-1", "row-2", ""]);
    expect(document.querySelectorAll("[data-slot='pagination']")).toHaveLength(1);
    expect(document.querySelector("#page-3-link")).not.toBeNull();
});

test.serial("focus is restored to the replacement from the same pagination when multiple paginations exist", async () => {
    await mount(`
        <turbo-frame id="users">
            <div id="users-list"><div id="page-1">Page 1</div></div>
            <nav data-controller="pagination" data-pagination-append-to-value="#users-list" data-slot="pagination">
                <a id="page-2-link" href="/users?page=2" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
            </nav>
            <nav data-controller="other-pagination" data-slot="pagination">
                <a id="other-next" href="/other?page=2">Other</a>
            </nav>
        </turbo-frame>
    `);
    installFetch(nextPageHtml());

    const link = mounted.root.querySelector("#page-2-link");
    link.focus();
    link.click();
    await wait(0);
    await wait(0);

    expect(document.activeElement.id).toBe("page-3-link");
});

test.serial("load fails instead of silently succeeding when no append target can be resolved", async () => {
    await mount(`
        <turbo-frame id="users">
            <h2 id="heading">Users</h2>
            <nav data-controller="pagination" data-slot="pagination">
                <a id="page-2-link" href="/users?page=2" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
            </nav>
        </turbo-frame>
    `);
    installFetch(nextPageHtml());

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(mounted.root.getAttribute("data-state")).toBe("error");
    expect(document.querySelector("#page-2")).toBeNull();
});

test.serial("load dispatches an error event and restores a missing original aria-label", async () => {
    await mount(`
        <turbo-frame id="users">
            <nav data-controller="pagination" data-slot="pagination">
                <a id="page-2-link" href="/users?page=2" data-pagination-target="next" data-action="click->pagination#load">Load more</a>
            </nav>
        </turbo-frame>
    `);
    installFetch(nextPageHtml());
    const error = mock(() => {});
    mounted.root.addEventListener("pagination:error", error);

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(error).toHaveBeenCalledTimes(1);
    expect(mounted.root.querySelector("a").hasAttribute("aria-label")).toBe(false);
});

test.serial("empty loading label keeps the original aria-label while loading", async () => {
    await mount(pageHtml({ loadingLabel: "", ariaLabel: "Load more users" }));

    let resolveFetch;
    const fetch = mock(() => new Promise((resolve) => { resolveFetch = resolve; }));
    window.fetch = fetch;
    globalThis.fetch = fetch;

    const link = mounted.root.querySelector("a");
    link.click();

    expect(link.getAttribute("aria-label")).toBe("Load more users");
    expect(mounted.root.querySelector("[role='status']").textContent).toBe("");

    resolveFetch(htmlResponse(nextPageHtml()));
    await wait(0);
    await wait(0);
});


test.serial("rapid intersections only start one request", async () => {
    installIntersectionObserver();
    await mount(pageHtml({ infinite: true }));
    installFetch(nextPageHtml());

    const link = mounted.root.querySelector("a");
    ioInstances[0].trigger([{ isIntersecting: true, target: link }]);
    ioInstances[0].trigger([{ isIntersecting: true, target: link }]);
    await wait(0);

    expect(fetch).toHaveBeenCalledTimes(1);
});

test.serial("infinite mode observes the next link when IntersectionObserver exists", async () => {
    installIntersectionObserver();
    await mount(pageHtml({ infinite: true, rootMargin: "200px", threshold: "0.5" }));

    expect(ioInstances).toHaveLength(1);
    expect(ioInstances[0].options.rootMargin).toBe("200px");
    expect(ioInstances[0].options.threshold).toBe(0.5);
    expect(ioInstances[0].observed).toContain(mounted.root.querySelector("a"));
});

test.serial("next target reconnect re-observes after the old link was unobserved", async () => {
    installIntersectionObserver();
    await mount(pageHtml({ infinite: true }));

    const firstObserver = ioInstances[0];
    mounted.controller.stopObserver();

    mounted.controller.nextTargetConnected();

    expect(firstObserver.disconnected).toBe(true);
    expect(ioInstances).toHaveLength(2);
    expect(ioInstances[1].observed).toContain(mounted.root.querySelector("a"));
});

test.serial("infinite mode keeps the manual link when IntersectionObserver is missing", async () => {
    await mount(pageHtml({ infinite: true }));

    expect(ioInstances).toHaveLength(0);
    expect(mounted.root.querySelector("a").getAttribute("href")).toBe("/users?page=2");
});

test.serial("fetch failure leaves the retry link in place and sets error state", async () => {
    await mount(pageHtml());
    const fetch = mock(() => Promise.reject(new Error("Network error")));
    window.fetch = fetch;
    globalThis.fetch = fetch;

    mounted.root.querySelector("a").click();
    await wait(0);
    await wait(0);

    expect(mounted.root.getAttribute("data-state")).toBe("error");
    expect(mounted.root.querySelector("a").getAttribute("href")).toBe("/users?page=2");
    expect(mounted.root.hasAttribute("aria-busy")).toBe(false);
    expect(mounted.root.querySelector("[role='status']").textContent).toBe("Loading failed");
});

test.serial("infinite mode leaves failed requests to manual retry instead of immediately observing again", async () => {
    installIntersectionObserver(ImmediateIntersectionObserver);
    const fetch = mock(() => Promise.reject(new Error("Network error")));
    window.fetch = fetch;
    globalThis.fetch = fetch;
    await mount(pageHtml({ infinite: true }));

    await wait(0);
    await wait(0);
    const firstObserver = ioInstances[0];

    expect(firstObserver.disconnected).toBe(true);
    expect(mounted.controller.observer).toBeNull();
    expect(ioInstances).toHaveLength(1);
    expect(fetch).toHaveBeenCalledTimes(1);
});

test.serial("disconnect aborts an in-flight request", async () => {
    await mount(pageHtml());
    let signal;
    const fetch = mock((url, options) => {
        signal = options.signal;

        return new Promise(() => {});
    });
    window.fetch = fetch;
    globalThis.fetch = fetch;

    mounted.root.querySelector("a").click();
    await wait(0);

    await mounted.cleanup();
    mounted = null;

    expect(signal.aborted).toBe(true);
});

test.serial("disconnect removes the observer", async () => {
    installIntersectionObserver();
    await mount(pageHtml({ infinite: true }));

    const observer = ioInstances[0];
    await mounted.cleanup();
    mounted = null;

    expect(observer.disconnected).toBe(true);
});

async function mount(html) {
    mounted = await mountController("pagination", PaginationController, html);
}

function installFetch(body) {
    const fetch = mock(() => Promise.resolve(htmlResponse(body)));
    window.fetch = fetch;
    globalThis.fetch = fetch;
}

function installIntersectionObserver(observer = FakeIntersectionObserver) {
    window.IntersectionObserver = observer;
    globalThis.IntersectionObserver = observer;
}

function htmlResponse(body, ok = true) {
    return {
        ok,
        text: () => Promise.resolve(body),
    };
}

function pageHtml({ infinite = false, rootMargin = "300px", threshold = "1", scrollTo = null, loadingLabel = "Loading more", ariaLabel = null } = {}) {
    return `
        <turbo-frame id="users">
            <h2 id="heading">Users</h2>
            <div id="users-list">
            <div id="page-1">Page 1</div>
            </div>
            <nav
                data-controller="pagination"
                data-pagination-append-to-value="#users-list"
                data-pagination-infinite-value="${infinite}"
                data-pagination-loading-label-value="${loadingLabel}"
                data-pagination-root-margin-value="${rootMargin}"
                data-pagination-threshold-value="${threshold}"
                ${scrollTo ? `data-pagination-scroll-to-value="${scrollTo}"` : ""}
                data-slot="pagination"
            >
                <span data-slot="pagination-status" data-pagination-target="status" role="status" aria-live="polite" aria-atomic="true"></span>
                <a id="page-2-link" href="/users?page=2" ${ariaLabel ? `aria-label="${ariaLabel}"` : ""} data-pagination-target="next" data-action="click->pagination#load">
                    <span data-slot="pagination-next-content">
                        <span data-slot="pagination-next-label">Load more</span>
                        <svg data-slot="pagination-next-icon" aria-hidden="true"></svg>
                    </span>
                    <span data-slot="pagination-next-loading-content">
                        ${loadingLabel === "" ? "" : `<span data-slot="pagination-next-loading-label">${loadingLabel}</span>`}
                        <svg data-slot="pagination-next-spinner" aria-hidden="true"></svg>
                    </span>
                </a>
            </nav>
        </turbo-frame>
    `;
}

function nextPageHtml() {
    return `
        <turbo-frame id="users">
            <h2 id="heading">Users</h2>
            <div id="users-list">
            <div id="page-2">Page 2</div>
            </div>
            <nav data-controller="pagination" data-slot="pagination">
                <span data-slot="pagination-status" data-pagination-target="status" role="status" aria-live="polite" aria-atomic="true"></span>
                <a id="page-3-link" href="/users?page=3" data-pagination-target="next" data-action="click->pagination#load">
                    <span data-slot="pagination-next-content">
                        <span data-slot="pagination-next-label">Load more</span>
                        <svg data-slot="pagination-next-icon" aria-hidden="true"></svg>
                    </span>
                    <span data-slot="pagination-next-loading-content">
                        <span data-slot="pagination-next-loading-label">Loading more</span>
                        <svg data-slot="pagination-next-spinner" aria-hidden="true"></svg>
                    </span>
                </a>
            </nav>
        </turbo-frame>
    `;
}
