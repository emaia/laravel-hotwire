// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";
import { createPresence } from "./_presence.js";
import { createTopLayer } from "./_top_layer.js";

export default class extends Controller {
    static targets = ["trigger", "content", "select", "list", "option", "value", "search", "selectAll", "empty", "validation"];
    static values = {
        align: { type: String, default: "start" },
        alignOffset: { type: Number, default: 0 },
        closeListOnItemSelect: { type: Boolean, default: false },
        deselectAllText: { type: String, default: "Clear all" },
        flip: { type: Boolean, default: true },
        listAll: { type: Boolean, default: false },
        listAllLimit: { type: Number, default: 3 },
        listAllMoreText: { type: String, default: '+:count more' },
        max: Number,
        open: { type: Boolean, default: false },
        placeholder: { type: String, default: "Select options" },
        required: { type: Boolean, default: false },
        search: { type: Boolean, default: true },
        selectAll: { type: Boolean, default: false },
        selectAllText: { type: String, default: "Select all" },
        shift: { type: Boolean, default: true },
        side: { type: String, default: "bottom" },
        sideOffset: { type: Number, default: 4 },
        sortSelected: { type: Boolean, default: false },
        strategy: { type: String, default: "fixed" },
    };

    initialize() {
        this.onOutsideClick = this.onOutsideClick.bind(this);
        this.onDocumentKeydown = this.onDocumentKeydown.bind(this);
        this.onContentClick = this.onContentClick.bind(this);
        this.onContentKeydown = this.onContentKeydown.bind(this);
        this.onFocusIn = this.onFocusIn.bind(this);
        this.onFocusOut = this.onFocusOut.bind(this);
        this.onSearchInput = this.onSearchInput.bind(this);
        this.onSearchKeydown = this.onSearchKeydown.bind(this);
        this.closeForCache = this.closeForCache.bind(this);
        this.handleFormReset = this.handleFormReset.bind(this);
        this.handleSubmitEnd = this.handleSubmitEnd.bind(this);
        this.focusOutFrame = null;
        this.form = null;
        this.floating = null;
        this.floatingAnchor = null;
        this.floatingElement = null;
        this.positioningGeneration = 0;
        this.focusOnOpen = false;
        this.contentHasFocus = false;
        this.pendingContentReplacement = null;
        this.presence = null;
        this.presenceElement = null;
        this.topLayer = null;
        this.nativeOptionsByValue = new Map();
        this.sortingOptions = false;
    }

    connect() {
        this.form = this.element.closest("form");
        document.addEventListener("click", this.onOutsideClick);
        document.addEventListener("keydown", this.onDocumentKeydown);
        document.addEventListener("turbo:before-cache", this.closeForCache);
        this.form?.addEventListener("reset", this.handleFormReset);
        this.form?.addEventListener("turbo:submit-end", this.handleSubmitEnd);
        this.element.addEventListener("focusout", this.onFocusOut);
        this.element.addEventListener("focusin", this.onFocusIn);
        if (this.hasContentTarget && this.presenceElement !== this.contentTarget) this.setupContent(this.contentTarget);

        this.cacheOptions();
        this.syncOptionsFromSelect();
        this.syncState();
        this.updateSummary();
        this.updateEmptyState();
        this.updateSelectAllState();
        this.updateMaxState();
        this.updateValidation();
    }

    disconnect() {
        document.removeEventListener("click", this.onOutsideClick);
        document.removeEventListener("keydown", this.onDocumentKeydown);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        this.form?.removeEventListener("reset", this.handleFormReset);
        this.form?.removeEventListener("turbo:submit-end", this.handleSubmitEnd);
        this.form = null;
        this.element.removeEventListener("focusout", this.onFocusOut);
        this.element.removeEventListener("focusin", this.onFocusIn);
        if (this.hasContentTarget) {
            this.contentTarget.removeEventListener("click", this.onContentClick);
            this.contentTarget.removeEventListener("keydown", this.onContentKeydown);
        }
        if (this.hasSearchTarget) {
            this.searchTarget.removeEventListener("input", this.onSearchInput);
            this.searchTarget.removeEventListener("inputCleared", this.onSearchInput);
            this.searchTarget.removeEventListener("keydown", this.onSearchKeydown);
        }
        this.element.removeAttribute("data-hotwire-escape-scope");
        this.clearFocusOutFrame();
        this.focusOnOpen = false;
        this.contentHasFocus = false;
        this.pendingContentReplacement = null;
        this.teardownContent();
    }

    contentTargetConnected(content) {
        content.addEventListener("click", this.onContentClick);
        content.addEventListener("keydown", this.onContentKeydown);
        this.setupContent(content);
        this.syncState();
    }

    contentTargetDisconnected(content) {
        content.removeEventListener("click", this.onContentClick);
        content.removeEventListener("keydown", this.onContentKeydown);
        if (content !== this.presenceElement) return;

        if (this.openValue) {
            const replacement = { focus: this.contentHasFocus || this.focusOnOpen };
            this.pendingContentReplacement = replacement;
            queueMicrotask(() => {
                if (this.pendingContentReplacement === replacement) this.pendingContentReplacement = null;
            });
        }
        this.contentHasFocus = false;
        this.teardownContent();
        if (this.openValue && !this.hasContentTarget) this.close();
    }

    triggerTargetConnected(trigger) {
        this.syncTrigger(trigger);
        this.refreshTriggerAnchor();
    }

    triggerTargetDisconnected(trigger) {
        if (!this.openValue) return;

        this.hasTriggerTarget ? this.refreshTriggerAnchor() : this.close();
    }

    searchTargetConnected(search) {
        search.addEventListener("input", this.onSearchInput);
        search.addEventListener("inputCleared", this.onSearchInput);
        search.addEventListener("keydown", this.onSearchKeydown);
    }

    searchTargetDisconnected(search) {
        search.removeEventListener("input", this.onSearchInput);
        search.removeEventListener("inputCleared", this.onSearchInput);
        search.removeEventListener("keydown", this.onSearchKeydown);
    }

    toggle() {
        this.openValue ? this.close() : this.open();
    }

    open() {
        if (this.openValue || !this.hasTriggerTarget || !this.hasContentTarget || !this.presence) return;

        this.openValue = true;
        this.syncState();
        this.present({ focus: true });
    }

    close({ focusTrigger = false } = {}) {
        if (!this.openValue) return;

        this.invalidatePositioning();
        this.clearFocusOutFrame();
        this.focusOnOpen = false;
        this.contentHasFocus = false;
        this.pendingContentReplacement = null;
        this.openValue = false;
        this.syncState();
        this.dismiss();
        if (focusTrigger && this.hasTriggerTarget) this.triggerTarget.focus();
    }

    onTriggerKeydown(event) {
        if (["Enter", " "].includes(event.key) || (event.key === "ArrowDown" && !this.openValue)) {
            event.preventDefault();
            this.openValue ? this.close() : this.open();
        }
    }

    onOutsideClick(event) {
        if (this.openValue && !this.element.contains(event.target)) this.close();
    }

    onDocumentKeydown(event) {
        if (this.openValue && event.key === "Escape") {
            event.preventDefault();
            event.stopImmediatePropagation();
            this.close({ focusTrigger: true });
        }
    }

    onContentClick(event) {
        const selectAll = event.target.closest('[data-multi-select-target~="selectAll"]');
        if (selectAll) {
            selectAll.focus();
            if (selectAll.getAttribute("aria-disabled") === "true") return;

            this.toggleSelectAll();
            return;
        }

        const option = event.target.closest('[data-multi-select-target~="option"]');
        if (option) {
            option.focus();
            this.toggleOption(option);
        }
    }

    onContentKeydown(event) {
        const option = event.target.closest('[data-multi-select-target~="option"], [data-multi-select-target~="selectAll"]');
        if (!option) return;

        if (["Enter", " "].includes(event.key)) {
            event.preventDefault();
            if (option.getAttribute("aria-disabled") === "true") return;

            this.isSelectAll(option) ? this.toggleSelectAll() : this.toggleOption(option);
        } else if (event.key === "ArrowDown") {
            event.preventDefault();
            this.moveOptionFocus(option, 1);
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            this.moveOptionFocus(option, -1);
        }
    }

    onFocusIn(event) {
        this.contentHasFocus = Boolean(this.presenceElement?.contains(event.target));
    }

    onFocusOut(event) {
        this.clearFocusOutFrame();
        if (!this.openValue) return;
        if (this.sortingOptions) return;

        if (event.relatedTarget) {
            this.contentHasFocus = Boolean(this.presenceElement?.contains(event.relatedTarget));
            if (!this.element.contains(event.relatedTarget)) this.close();

            return;
        }

        this.focusOutFrame = requestAnimationFrame(() => {
            this.focusOutFrame = null;
            if (this.openValue && !this.element.contains(document.activeElement)) this.close();
        });
    }

    onSearchInput() {
        const term = normalize(this.searchTarget.value);
        this.optionTargets.forEach((option) => {
            option.hidden = !option.dataset.search.includes(term);
        });
        this.updateEmptyState();
        this.updateSelectAllState();
        this.updateMaxState();
    }

    onSearchKeydown(event) {
        if (event.key === "ArrowDown") {
            event.preventDefault();
            this.firstEnabledOption()?.focus();
        } else if (event.key === "Escape") {
            event.preventDefault();
            event.stopImmediatePropagation();
            this.close({ focusTrigger: true });
        }
    }

    toggleOption(eventOrOption) {
        const option = eventOrOption?.currentTarget ?? eventOrOption;
        if (option.getAttribute("aria-disabled") === "true") return;

        const selected = option.dataset.selected === "true";
        selected ? this.setSelected(option, false) : this.setSelected(option, true);
        this.afterSelectionChange();
        this.dispatch(selected ? "unselect" : "select", { detail: { value: option.dataset.value, label: labelFor(option), option } });
        this.dispatch("change", { detail: { values: this.selectedValues() } });

        if (this.closeListOnItemSelectValue) this.close();
    }

    toggleSelectAll() {
        const options = this.optionTargets;
        const selectable = options.filter((option) => !option.hidden);
        const allSelected = selectable.length > 0 && selectable.every((option) => option.dataset.selected === "true");
        const changes = [];

        for (const option of selectable) {
            if (allSelected) {
                if (option.dataset.selected === "true") {
                    this.setSelected(option, false);
                    changes.push({ value: option.dataset.value, selected: false });
                }
                continue;
            }

            if (option.dataset.selected !== "true" && this.maxReached()) break;
            if (option.dataset.selected !== "true") {
                this.setSelected(option, true);
                changes.push({ value: option.dataset.value, selected: true });
            }
        }

        this.afterSelectionChange();
        this.dispatch(allSelected ? "deselect-all" : "select-all", { detail: { changes, count: changes.length } });
        this.dispatch("change", { detail: { values: this.selectedValues() } });
    }

    setSelected(option, selected) {
        option.dataset.selected = String(selected);
        option.setAttribute("aria-selected", String(selected));

        const native = this.nativeOptionsByValue.get(option.dataset.value);
        if (native) {
            native.selected = selected;
        }
    }

    afterSelectionChange() {
        if (this.hasSearchTarget) {
            this.searchTarget.value = "";
            this.optionTargets.forEach((option) => (option.hidden = false));
        }

        this.updateSummary();
        this.sortOptions();
        this.updateEmptyState();
        this.updateSelectAllState();
        this.updateMaxState();
        this.updateValidation();
    }

    cacheOptions() {
        this.nativeOptionsByValue = new Map([...this.selectTarget.options].map((option) => [option.value, option]));
        this.optionTargets.forEach((option, index) => {
            option.dataset.originalIndex ??= String(index);
            option.dataset.search = normalize(labelFor(option));
        });
    }

    syncOptionsFromSelect() {
        const selected = new Set([...this.selectTarget.options].filter((option) => option.selected).map((option) => option.value));

        this.optionTargets.forEach((option) => {
            this.setSelected(option, selected.has(option.dataset.value));
        });

        this.sortOptions();
    }

    syncState() {
        this.element.toggleAttribute("data-hotwire-escape-scope", this.openValue);
        this.triggerTargets.forEach((trigger) => this.syncTrigger(trigger));
    }

    updateSummary() {
        const selected = this.selectedOptions();
        const summaryText = summary(selected, this.placeholderValue, this.listAllValue, this.listAllLimitValue, this.listAllMoreTextValue);
        const fullSummaryText = fullSummary(selected, this.placeholderValue);

        this.valueTarget.textContent = summaryText;
        this.valueTarget.toggleAttribute("title", summaryText !== fullSummaryText);
        if (summaryText !== fullSummaryText) this.valueTarget.title = fullSummaryText;
    }

    updateSelectAllState() {
        if (!this.hasSelectAllTarget) return;

        const options = this.visibleOptions();
        this.selectAllTarget.hidden = options.length === 0;

        const allSelected = options.length > 0 && options.every((option) => option.dataset.selected === "true");
        const someSelected = !allSelected && options.some((option) => option.dataset.selected === "true");
        this.selectAllTarget.dataset.selected = String(allSelected);
        this.selectAllTarget.dataset.indeterminate = String(someSelected);
        this.selectAllTarget.removeAttribute("aria-selected");
        this.selectAllTarget.setAttribute("aria-pressed", someSelected ? "mixed" : String(allSelected));
        const text = this.selectAllTarget.querySelector('[data-slot="multi-select-option-text"]');
        if (text) text.textContent = allSelected ? this.deselectAllTextValue : this.selectAllTextValue;
    }

    updateMaxState() {
        const reached = this.maxReached();
        this.optionTargets.forEach((option) => {
            const disabled = reached && option.dataset.selected !== "true";
            option.dataset.disabled = String(disabled);
            option.setAttribute("aria-disabled", String(disabled));
        });

        if (this.hasSelectAllTarget) {
            const visible = this.visibleOptions();
            const allVisibleSelected = visible.length > 0 && visible.every((option) => option.dataset.selected === "true");
            this.selectAllTarget.setAttribute("aria-disabled", String(reached && !allVisibleSelected));
        }
    }

    updateEmptyState() {
        if (!this.hasEmptyTarget) return;

        this.emptyTarget.hidden = this.visibleOptions().length > 0;
    }

    updateValidation() {
        if (!this.hasValidationTarget) return;

        const hasSelection = this.selectedValues().length > 0;
        this.validationTarget.value = hasSelection ? "1" : "";
        this.validationTarget.setCustomValidity(hasSelection ? "" : "Select at least one option.");
    }

    closeForCache() {
        this.invalidatePositioning();
        this.clearFocusOutFrame();
        this.focusOnOpen = false;
        this.contentHasFocus = false;
        this.pendingContentReplacement = null;
        this.openValue = false;
        this.syncState();
        this.presence?.sync(false);
        this.cleanupFloating();
        this.topLayer?.hide();
    }

    handleFormReset(event) {
        const form = this.form;

        queueMicrotask(() => {
            if (event.defaultPrevented || !form || this.form !== form || !this.element.isConnected) return;

            this.syncOptionsFromSelect();
            if (this.hasSearchTarget) {
                this.onSearchInput();
            } else {
                this.updateEmptyState();
                this.updateSelectAllState();
                this.updateMaxState();
            }
            this.updateSummary();
            this.updateValidation();
        });
    }

    handleSubmitEnd(event) {
        if (!event.detail?.success || event.target !== this.form) return;

        [...this.selectTarget.options].forEach((option) => {
            option.defaultSelected = option.selected;
        });
    }

    startFloating() {
        if (!this.openValue || !this.hasTriggerTarget || !this.hasContentTarget || !this.topLayer) {
            return Promise.resolve(false);
        }

        if (this.floating && (this.floatingAnchor !== this.triggerTarget || this.floatingElement !== this.contentTarget)) {
            this.cleanupFloating();
        }

        this.topLayer.show();
        if (!this.floating) {
            this.floatingAnchor = this.triggerTarget;
            this.floatingElement = this.contentTarget;
            this.floating = createFloating(this.triggerTarget, this.contentTarget, {
                side: this.sideValue,
                align: this.alignValue,
                sideOffset: this.sideOffsetValue,
                alignOffset: this.alignOffsetValue,
                strategy: this.strategyValue,
                flip: this.flipValue,
                shift: this.shiftValue,
            });
        }

        return this.floating.start();
    }

    cleanupFloating() {
        this.floating?.cleanup();
        this.floating = null;
        this.floatingAnchor = null;
        this.floatingElement = null;
    }

    selectedValues() {
        return [...this.selectTarget.options].filter((option) => option.selected).map((option) => option.value);
    }

    selectedOptions() {
        return this.optionTargets.filter((option) => option.dataset.selected === "true");
    }

    visibleOptions() {
        return this.optionTargets.filter((option) => !option.hidden);
    }

    sortOptions() {
        if (!this.sortSelectedValue || !this.hasListTarget) return;

        const active = document.activeElement;
        const shouldRestoreFocus = this.openValue && active instanceof HTMLElement && this.element.contains(active);
        if (this.openValue) this.sortingOptions = true;

        [...this.optionTargets]
            .sort((a, b) => {
                const selected = Number(b.dataset.selected === "true") - Number(a.dataset.selected === "true");
                if (selected !== 0) return selected;

                return Number(a.dataset.originalIndex) - Number(b.dataset.originalIndex);
            })
            .forEach((option) => this.listTarget.append(option));

        if (shouldRestoreFocus && active.isConnected) active.focus({ preventScroll: true });
        if (this.openValue) requestAnimationFrame(() => (this.sortingOptions = false));
    }

    maxReached() {
        return this.hasMaxValue && this.maxValue > 0 && this.selectedValues().length >= this.maxValue;
    }

    firstEnabledOption() {
        return [this.hasSelectAllTarget ? this.selectAllTarget : null, ...this.optionTargets]
            .filter(Boolean)
            .find((option) => !option.hidden && option.getAttribute("aria-disabled") !== "true");
    }

    moveOptionFocus(current, direction) {
        const options = [this.hasSelectAllTarget ? this.selectAllTarget : null, ...this.optionTargets]
            .filter(Boolean)
            .filter((option) => !option.hidden && option.getAttribute("aria-disabled") !== "true");
        const index = options.indexOf(current);
        const next = options[index + direction];

        if (next) {
            next.focus();
        } else if (direction < 0 && index === 0 && this.hasSearchTarget) {
            this.searchTarget.focus();
        }
    }

    isSelectAll(option) {
        return this.hasSelectAllTarget && option === this.selectAllTarget;
    }

    setupContent(content) {
        if (this.presenceElement === content) return;

        const restoreFocus = this.openValue && (
            this.pendingContentReplacement?.focus === true ||
            (Boolean(this.presenceElement) && (this.contentHasFocus || this.focusOnOpen))
        );
        this.pendingContentReplacement = null;
        this.contentHasFocus = false;
        this.teardownContent();
        this.presenceElement = content;
        this.topLayer = createTopLayer(content);
        this.presence = createPresence(content);

        if (this.openValue) {
            this.presence.sync(false);
            this.present({ animate: false, focus: restoreFocus });
        } else {
            this.presence.sync(false);
        }
    }

    teardownContent() {
        this.invalidatePositioning();
        this.clearFocusOutFrame();
        this.presence?.cleanup();
        this.cleanupFloating();
        this.topLayer?.cleanup();
        this.presence = null;
        this.presenceElement = null;
        this.topLayer = null;
    }

    present({ animate = true, focus = false } = {}) {
        const presence = this.presence;
        if (!presence) return;
        if (focus) this.focusOnOpen = true;
        const generation = ++this.positioningGeneration;

        void presence.open({
            beforeEnter: () => this.isPositioningCurrent(generation, presence) ? this.startFloating() : false,
            onEnter: () => this.finishEnter(presence, generation),
            immediate: !animate,
        }).then((opened) => {
            if (!this.isPositioningCurrent(generation, presence)) return;
            if (!opened) {
                this.finishPresent(presence, false, null, generation);

                return;
            }
        }).catch((error) => {
            if (this.isPositioningCurrent(generation, presence)) {
                this.finishPresent(presence, false, error, generation);
            }
        });
    }

    dismiss() {
        const presence = this.presence;
        if (!presence) return;

        const closing = presence.close();
        if (!presence.isPresent) {
            this.finishDismiss(presence);

            return;
        }

        void closing.then((closed) => {
            if (closed) this.finishDismiss(presence);
        });
    }

    finishDismiss(presence) {
        if (presence !== this.presence || this.openValue || presence.isPresent) return;

        this.invalidatePositioning();
        this.cleanupFloating();
        this.topLayer?.hide();
    }

    finishEnter(presence, generation) {
        if (!this.isPositioningCurrent(generation, presence)) return;

        const shouldFocus = this.focusOnOpen;
        this.focusOnOpen = false;
        if (shouldFocus) (this.hasSearchTarget ? this.searchTarget : this.firstEnabledOption())?.focus();
    }

    syncTrigger(trigger) {
        trigger.setAttribute("aria-expanded", String(this.openValue));
        trigger.dataset.multiSelectState = this.openValue ? "open" : "closed";
    }

    refreshTriggerAnchor() {
        if (!this.openValue || !this.presence?.isPresent || !this.hasTriggerTarget) return;
        if (this.floating && this.floatingAnchor === this.triggerTarget) return;

        this.invalidatePositioning();
        this.cleanupFloating();
        if (this.presence.phase === "opening") {
            this.present();
        } else {
            this.restartPositioning(this.presence);
        }
    }

    restartPositioning(presence) {
        if (!presence) return;
        const generation = ++this.positioningGeneration;

        void Promise.resolve().then(() => {
            return this.isPositioningCurrent(generation, presence) ? this.startFloating() : false;
        }).then((started) => {
            if (!this.isPositioningCurrent(generation, presence)) return;
            if (!started) this.rollbackOpen(presence, null, generation);
        }).catch((error) => {
            if (this.isPositioningCurrent(generation, presence)) {
                this.rollbackOpen(presence, error, generation);
            }
        });
    }

    finishPresent(presence, opened, error, generation) {
        if (opened || presence !== this.presence || !this.openValue) return;
        if (presence.phase !== "closed" || presence.isPresent) return;

        this.rollbackOpen(presence, error, generation);
    }

    rollbackOpen(presence, error, generation) {
        if (!this.isPositioningCurrent(generation, presence)) return;

        if (error) {
            this.application.handleError(error, "Error opening multi-select", {
                controller: this,
                element: this.element,
            });
        }

        this.invalidatePositioning();
        this.focusOnOpen = false;
        this.openValue = false;
        this.syncState();
        presence.sync(false);
        this.cleanupFloating();
        this.topLayer?.hide();
    }

    isPositioningCurrent(generation, presence) {
        return generation === this.positioningGeneration && presence === this.presence && this.openValue;
    }

    invalidatePositioning() {
        this.positioningGeneration++;
    }

    clearFocusOutFrame() {
        cancelAnimationFrame(this.focusOutFrame);
        this.focusOutFrame = null;
    }
}

function normalize(value) {
    return String(value ?? "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

function labelFor(option) {
    return option.querySelector('[data-slot="multi-select-option-text"]')?.textContent?.trim() || option.textContent.trim();
}

function summary(selected, placeholder, listAll, listAllLimit, listAllMoreText) {
    if (selected.length === 0) return placeholder;
    if (listAll) {
        const labels = selected.map(labelFor);
        const limit = Number(listAllLimit);

        if (limit > 0 && labels.length > limit) {
            return `${labels.slice(0, limit).join(", ")}, ${formatMoreText(listAllMoreText, labels.length - limit)}`;
        }

        return labels.join(", ");
    }

    return `${selected.length} selected`;
}

function formatMoreText(template, count) {
    return String(template).replaceAll(":count", String(count));
}

function fullSummary(selected, placeholder) {
    if (selected.length === 0) return placeholder;

    return selected.map(labelFor).join(", ");
}
