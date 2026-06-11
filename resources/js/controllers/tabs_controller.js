// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["tab", "panel"];
    static values = { selectedIndex: { type: Number, default: 0 } };

    connect() {
        this.activate(this.tabTargets[this.initialIndex], { focus: false, notify: false });
    }

    select(event) {
        const tab = this.tabFromEvent(event);
        if (tab) this.activate(tab, { focus: false });
    }

    navigate(event) {
        const current = this.tabFromEvent(event);
        const index = this.tabTargets.indexOf(current);
        if (index === -1) return;

        const last = this.tabTargets.length - 1;
        let next;

        switch (event.key) {
            case this.nextKey:
                next = index === last ? 0 : index + 1;
                break;
            case this.prevKey:
                next = index === 0 ? last : index - 1;
                break;
            case "Home":
                next = 0;
                break;
            case "End":
                next = last;
                break;
            default:
                return;
        }

        event.preventDefault();
        this.activate(this.tabTargets[next], { focus: true });
    }

    activate(tab, { focus = false, notify = true } = {}) {
        if (!tab) return;

        this.tabTargets.forEach((current) => {
            const selected = current === tab;
            current.setAttribute("aria-selected", selected ? "true" : "false");
            current.setAttribute("tabindex", selected ? "0" : "-1");

            const panel = this.panelFor(current);
            if (panel) panel.hidden = !selected;
        });

        const index = this.tabTargets.indexOf(tab);
        this.selectedIndexValue = index;

        if (focus) tab.focus();
        if (notify) this.dispatch("change", { detail: { index, tab, panel: this.panelFor(tab) } });
    }

    tabFromEvent(event) {
        const tab = event.target.closest('[role="tab"]');
        return tab && this.tabTargets.includes(tab) ? tab : null;
    }

    panelFor(tab) {
        const id = tab.getAttribute("aria-controls");
        return id ? document.getElementById(id) : null;
    }

    get initialIndex() {
        const preselected = this.tabTargets.findIndex((tab) => tab.getAttribute("aria-selected") === "true");
        if (preselected !== -1) return preselected;

        const fromValue = this.selectedIndexValue;
        return fromValue >= 0 && fromValue < this.tabTargets.length ? fromValue : 0;
    }

    get vertical() {
        return this.element.querySelector('[role="tablist"]')?.getAttribute("aria-orientation") === "vertical";
    }

    get nextKey() {
        return this.vertical ? "ArrowDown" : "ArrowRight";
    }

    get prevKey() {
        return this.vertical ? "ArrowUp" : "ArrowLeft";
    }
}
