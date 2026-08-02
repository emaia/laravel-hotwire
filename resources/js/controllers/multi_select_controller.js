// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";
import { formHasErrors } from "./_form_errors.js";
import { frameEventAffects, submissionFrameId } from "./_frame_events.js";
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
        this.handleBeforeRender = this.handleBeforeRender.bind(this);
        this.handleBeforeStreamRender = this.handleBeforeStreamRender.bind(this);
        this.handleFormReset = this.handleFormReset.bind(this);
        this.handleRender = this.handleRender.bind(this);
        this.handleSubmitEnd = this.handleSubmitEnd.bind(this);
        this.handleSubmitStart = this.handleSubmitStart.bind(this);
        this.handleVisit = this.handleVisit.bind(this);
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
        this.baselinePromise = null;
        this.pendingSubmission = null;
        this.startedSubmission = null;
        this.submitGeneration = 0;
    }

    connect() {
        this.form = this.element.closest("form");
        document.addEventListener("click", this.onOutsideClick);
        document.addEventListener("keydown", this.onDocumentKeydown);
        document.addEventListener("turbo:before-cache", this.closeForCache);
        document.addEventListener("turbo:before-render", this.handleBeforeRender);
        document.addEventListener("turbo:before-stream-render", this.handleBeforeStreamRender, true);
        document.addEventListener("turbo:render", this.handleRender);
        document.addEventListener("turbo:frame-render", this.handleRender);
        document.addEventListener("turbo:visit", this.handleVisit);
        this.form?.addEventListener("reset", this.handleFormReset);
        this.form?.addEventListener("turbo:submit-end", this.handleSubmitEnd);
        this.form?.addEventListener("turbo:submit-start", this.handleSubmitStart);
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
        document.removeEventListener("turbo:before-render", this.handleBeforeRender);
        document.removeEventListener("turbo:before-stream-render", this.handleBeforeStreamRender, true);
        document.removeEventListener("turbo:render", this.handleRender);
        document.removeEventListener("turbo:frame-render", this.handleRender);
        document.removeEventListener("turbo:visit", this.handleVisit);
        this.form?.removeEventListener("reset", this.handleFormReset);
        this.form?.removeEventListener("turbo:submit-end", this.handleSubmitEnd);
        this.form?.removeEventListener("turbo:submit-start", this.handleSubmitStart);
        this.form = null;
        this.baselinePromise = null;
        this.pendingSubmission = null;
        this.startedSubmission = null;
        this.submitGeneration++;
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
        if (event.target !== this.form) return;

        const formSubmission = event.detail?.formSubmission ?? null;
        if (this.startedSubmission?.formSubmission && formSubmission && this.startedSubmission.formSubmission !== formSubmission) return;

        const generation = ++this.submitGeneration;
        const started = this.startedSubmission ?? this.captureSubmission(formSubmission);
        this.startedSubmission = null;
        this.pendingSubmission = null;

        const succeeded = event.detail?.success === true;
        const fetchResponse = event.detail?.fetchResponse ?? null;
        if (!succeeded && !fetchResponse) return;

        const responseHTML = fetchResponse ? readResponseHTML(fetchResponse) : null;
        let resolveBaseline;
        const baseline = new Promise((resolve) => (resolveBaseline = resolve));
        const pending = {
            baseline,
            documentBody: isDocumentResponse(fetchResponse) ? responseBodySignature(responseHTML) : null,
            fetchResponse,
            frameId: submissionFrameId(this.form, event),
            hasRefresh: false,
            generation,
            previousBaseline: started.previousBaseline,
            refreshStarted: false,
            resolveBaseline,
            responseHTML,
            streamCompleted: new Map(),
            streamExpected: null,
            submitted: started.selected,
            succeeded,
            subject: started.subject,
            superseded: false,
            waitingForRefresh: false,
        };
        this.baselinePromise = baseline;
        this.pendingSubmission = pending;

        if (fetchResponse) void this.resolveSubmissionBaseline(pending);

        if (fetchResponse && isDocumentResponse(fetchResponse)) {
            void this.settleDocumentSubmission(pending);
        } else if (fetchResponse) {
            void this.settleNonDocumentSubmission(pending);
        }
    }

    handleSubmitStart(event) {
        if (event.target !== this.form) return;

        const pending = this.pendingSubmission;
        if (pending) {
            pending.superseded = true;
            if (pending.hasRefresh) void pending.previousBaseline.then(pending.resolveBaseline);
        }

        this.startedSubmission = this.captureSubmission(event.detail?.formSubmission ?? null);
    }

    captureSubmission(formSubmission) {
        const options = [...this.selectTarget.options];

        const defaults = new Map(options.map((option) => [option.value, option.defaultSelected]));

        return {
            defaults,
            formSubmission,
            previousBaseline: this.baselinePromise ?? Promise.resolve(defaults),
            selected: new Map(options.map((option) => [option.value, option.selected])),
            subject: {
                element: this.element,
                form: this.form,
                selectName: this.selectTarget.name,
                triggerId: this.hasTriggerTarget ? this.triggerTarget.id : null,
            },
        };
    }

    handleRender(event) {
        const pending = this.pendingSubmission;
        if (!pending) return;

        if (event.type === "turbo:frame-render") {
            if (!pending.frameId || !frameEventAffects(this.element, event, pending.frameId)) return;
        } else if (!pending.waitingForRefresh && (pending.fetchResponse || pending.frameId || pending.documentBody)) {
            return;
        }

        if (event.type === "turbo:frame-render" && pending.fetchResponse !== event.detail?.fetchResponse) return;

        void this.settleSubmission(pending.generation);
    }

    handleBeforeStreamRender(event) {
        const pending = this.pendingSubmission;
        const stream = event.detail?.newStream ?? event.target;
        if (!pending?.fetchResponse || isDocumentResponse(pending.fetchResponse) || !stream?.matches?.("turbo-stream")) return;

        const generation = pending.generation;
        const key = streamKey(stream);
        if (stream.getAttribute("action") === "refresh") pending.hasRefresh = true;
        let completed = false;
        const wrap = (render) => async (...args) => {
            try {
                return await render(...args);
            } finally {
                const current = this.pendingSubmission;
                if (completed || !current || current.generation !== generation) return;

                completed = true;
                incrementCount(current.streamCompleted, key);
                this.settleStreamSubmission(current);
            }
        };

        if (typeof event.detail?.render !== "function") return;

        let wrapped = wrap(event.detail.render);
        event.detail.render = wrapped;
        queueMicrotask(() => {
            const render = event.detail?.render;
            if (typeof render !== "function" || render === wrapped) return;

            wrapped = wrap(render);
            event.detail.render = wrapped;
        });
    }

    handleVisit() {
        const pending = this.pendingSubmission;
        if (pending?.hasRefresh) pending.refreshStarted = true;
    }

    handleBeforeRender(event) {
        const pending = this.pendingSubmission;
        const newBody = event.detail?.newBody;
        const render = event.detail?.render;
        if (!pending?.documentBody || pending.frameId || !newBody || typeof render !== "function") return;

        const generation = pending.generation;
        const candidateBody = newBody.innerHTML;
        event.detail.render = async (...args) => {
            const result = await render(...args);
            const current = this.pendingSubmission;
            if (!current || current.generation !== generation) return result;

            const expectedBody = await current.documentBody;
            if (expectedBody !== null && expectedBody === candidateBody) await this.settleSubmission(generation);

            return result;
        };
    }

    async resolveSubmissionBaseline(pending) {
        if (!pending.succeeded) {
            pending.resolveBaseline(await pending.previousBaseline);

            return;
        }

        if (!isDocumentResponse(pending.fetchResponse) && await responseHasRefresh(pending.responseHTML, pending.subject)) {
            if (pending.superseded) pending.resolveBaseline(await pending.previousBaseline);

            return;
        }

        const hasErrors = await responseContainsErrors(pending.responseHTML, pending);
        pending.resolveBaseline(hasErrors === false ? pending.submitted : await pending.previousBaseline);
    }

    async settleDocumentSubmission(pending) {
        try {
            const html = await pending.responseHTML;
            if (html) return;
        } catch (_error) {
            // Baseline resolution falls back to the previous defaults when the response cannot be read.
        }

        if (this.pendingSubmission?.generation === pending.generation) {
            void this.settleSubmission(pending.generation);
        }
    }

    async settleNonDocumentSubmission(pending) {
        let html;
        try {
            html = await pending.responseHTML;
        } catch (_error) {
            html = null;
        }

        if (this.pendingSubmission?.generation !== pending.generation) return;

        const streams = responseStreams(html, pending.subject);
        pending.hasRefresh = streams.some((stream) => stream.getAttribute("action") === "refresh");
        pending.streamExpected = countStreams(streams);
        this.settleStreamSubmission(pending);
    }

    settleStreamSubmission(pending) {
        if (!pending.streamExpected || !countsSatisfied(pending.streamExpected, pending.streamCompleted)) return;

        if (pending.hasRefresh && pending.refreshStarted) {
            pending.waitingForRefresh = true;

            return;
        }

        void this.settleSubmission(pending.generation);
    }

    async settleSubmission(generation) {
        const pending = this.pendingSubmission;
        if (!pending || pending.generation !== generation) return;

        let baseline;
        if (pending.hasRefresh) {
            baseline = formHasErrors(this.element) ? await pending.previousBaseline : pending.submitted;
            pending.resolveBaseline(baseline);
        } else if (pending.fetchResponse) {
            baseline = await pending.baseline;
        } else {
            baseline = !pending.succeeded || formHasErrors(this.element)
                ? await pending.previousBaseline
                : pending.submitted;
            pending.resolveBaseline(baseline);
        }

        if (this.pendingSubmission?.generation !== generation) return;

        this.pendingSubmission = null;
        this.baselinePromise = null;
        if (!this.element.isConnected) return;

        const options = [...this.selectTarget.options];
        const selected = options.map((option) => option.selected);

        options.forEach((option) => {
            if (!baseline.has(option.value)) return;

            option.defaultSelected = baseline.get(option.value);
        });

        options.forEach((option, index) => {
            option.selected = selected[index];
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

function isDocumentResponse(fetchResponse) {
    const contentType = fetchResponse?.contentType ?? "";

    return contentType.startsWith("text/html") || contentType.startsWith("application/xhtml+xml");
}

function readResponseHTML(fetchResponse) {
    return Promise.resolve().then(() => fetchResponse.responseHTML);
}

async function responseBodySignature(responseHTML) {
    try {
        const html = await responseHTML;
        if (typeof html !== "string") return null;

        return new window.DOMParser().parseFromString(html, "text/html").body.innerHTML;
    } catch (_error) {
        return null;
    }
}

async function responseContainsErrors(responseHTML, pending) {
    try {
        const html = await responseHTML;
        if (typeof html !== "string") return false;

        const response = new window.DOMParser().parseFromString(html, "text/html");
        if (!isDocumentResponse(pending.fetchResponse)) {
            return responseStreams(html, pending.subject)
                .some((stream) => stream.querySelector("template")?.content.querySelector('[aria-invalid="true"]'));
        }

        let scope = response.body;
        if (pending.frameId) {
            scope = [...response.querySelectorAll("turbo-frame")]
                .find((frame) => frame.id === pending.frameId);
            if (!scope) return false;
        }

        const form = responseForm(scope, pending.subject);

        return form ? form.querySelector('[aria-invalid="true"]') !== null : false;
    } catch (_error) {
        return null;
    }
}

function responseForm(scope, subject) {
    const forms = [scope, ...scope.querySelectorAll("form")].filter((element) => element.matches?.("form"));
    const formId = subject.form?.id;
    if (formId) {
        const matching = forms.find((form) => form.id === formId);
        if (matching) return matching;
    }

    const elementId = subject.element?.id;
    if (elementId) {
        const matchingElement = [scope, ...scope.querySelectorAll("[id]")]
            .find((element) => element.id === elementId);
        const matchingForm = matchingElement?.closest("form");
        if (matchingForm) return matchingForm;
    }

    if (subject.triggerId) {
        const matchingTrigger = [scope, ...scope.querySelectorAll("[id]")]
            .find((element) => element.id === subject.triggerId);
        const matchingForm = matchingTrigger?.closest("form");
        if (matchingForm) return matchingForm;
    }

    if (subject.selectName) {
        const matchingSelects = [...scope.querySelectorAll("select")]
            .filter((select) => select.name === subject.selectName);
        if (matchingSelects.length === 1) return matchingSelects[0].closest("form");
    }

    return null;
}

function responseStreams(html, subject) {
    if (typeof html !== "string" || html === "") return [];

    const response = new window.DOMParser().parseFromString(html, "text/html");
    const relatedIds = new Set();
    const introduced = [];
    [subject.form, subject.element].filter(Boolean).forEach((element) => {
        if (element.id) relatedIds.add(element.id);
        element.querySelectorAll("[id]").forEach((descendant) => relatedIds.add(descendant.id));
    });

    return [...response.querySelectorAll("turbo-stream")].filter((stream) => {
        const targetId = stream.getAttribute("target");
        const selector = stream.getAttribute("targets");
        let affects = streamAffectsSubmission(stream, subject) || (targetId && relatedIds.has(targetId));

        if (!affects && selector) {
            try {
                affects = introduced.some((element) => element.matches(selector) || element.querySelector(selector));
            } catch (_error) {
                affects = false;
            }
        }

        if (!affects) return false;

        const template = stream.querySelector("template")?.content;
        if (template) {
            const elements = [...template.children];
            introduced.push(...elements);
            template.querySelectorAll("[id]").forEach((element) => relatedIds.add(element.id));
        }

        return true;
    });
}

async function responseHasRefresh(responseHTML, subject) {
    try {
        const html = await responseHTML;

        return responseStreams(html, subject)
            .some((stream) => stream.getAttribute("action") === "refresh");
    } catch (_error) {
        return false;
    }
}

function streamAffectsSubmission(stream, subject) {
    if (!stream?.matches?.("turbo-stream")) return false;
    if (stream.getAttribute("action") === "refresh") return true;

    const document = subject.form?.ownerDocument ?? subject.element?.ownerDocument;
    if (!document) return false;

    const targets = [];
    const targetId = stream.getAttribute("target");
    if (targetId) {
        const target = document.getElementById(targetId);
        if (target) targets.push(target);
    }

    const selector = stream.getAttribute("targets");
    if (selector) {
        try {
            targets.push(...document.querySelectorAll(selector));
        } catch (_error) {
            return false;
        }
    }

    const subjects = [subject.form, subject.element].filter(Boolean);

    return targets.some((target) => subjects.some((element) => (
        target === element || target.contains(element) || element.contains(target)
    )));
}

function streamKey(stream) {
    const attributes = ["action", "target", "targets", "method", "request-id"]
        .map((attribute) => stream.getAttribute(attribute) ?? "")
        .join("\0");
    const template = stream.querySelector("template")?.innerHTML ?? "";

    return `${attributes}\0${template}`;
}

function countStreams(streams) {
    const counts = new Map();
    streams.forEach((stream) => incrementCount(counts, streamKey(stream)));

    return counts;
}

function incrementCount(counts, key) {
    counts.set(key, (counts.get(key) ?? 0) + 1);
}

function countsSatisfied(expected, completed) {
    return [...expected].every(([key, count]) => (completed.get(key) ?? 0) >= count);
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
