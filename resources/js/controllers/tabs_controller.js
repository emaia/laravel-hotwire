// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["tab", "panel"];
    static values = { selectedIndex: { type: Number, default: 0 } };

    connect() {
        this.activate(this.initialTab, { focus: false, notify: false });
    }

    select(event) {
        const tab = this.tabFromEvent(event);
        if (tab && !this.isDisabled(tab)) this.activate(tab, { focus: false });
    }

    navigate(event) {
        const current = this.tabFromEvent(event);
        const tabs = this.enabledTabs;
        const index = tabs.indexOf(current);
        if (index === -1) return;

        const last = tabs.length - 1;
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
        this.activate(tabs[next], { focus: true });
    }

    activate(tab, { focus = false, notify = true } = {}) {
        if (!tab || this.isDisabled(tab)) return;

        this.tabTargets.forEach((current) => {
            const selected = current === tab;
            current.dataset.state = selected ? "active" : "inactive";
            current.setAttribute("aria-selected", selected ? "true" : "false");
            current.setAttribute("tabindex", selected && !this.isDisabled(current) ? "0" : "-1");

            const panel = this.panelFor(current);
            if (panel) {
                panel.dataset.state = selected ? "active" : "inactive";
                panel.hidden = !selected;
            }
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

    isDisabled(tab) {
        return tab.disabled || tab.getAttribute("aria-disabled") === "true";
    }

    panelFor(tab) {
        const id = tab.getAttribute("aria-controls");
        return id ? document.getElementById(id) : null;
    }

    get enabledTabs() {
        return this.tabTargets.filter((tab) => !this.isDisabled(tab));
    }

    get initialTab() {
        const enabled = this.enabledTabs;
        const preselected = enabled.find((tab) => tab.getAttribute("aria-selected") === "true");
        if (preselected) return preselected;

        const fromValue = this.selectedIndexValue;
        const tab = this.tabTargets[fromValue];
        if (tab && !this.isDisabled(tab)) return tab;

        return enabled[0] ?? null;
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
