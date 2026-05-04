import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["trigger", "selectedLabel", "popover", "listbox", "input", "filter"];
    // footer é opcional, tratado com hasFooterTarget

    // ─── Estado interno ───────────────────────────────────
    allOptions = [];
    options = [];
    activeIndex = -1;
    isMultiple = false;
    selectedOptions = null;
    placeholder = "";
    closeOnSelect = false;

    connect() {
        this.allOptions = Array.from(this.listboxTarget.querySelectorAll('[role="option"]'));
        this.options = this.allOptions.filter((opt) => opt.getAttribute("aria-disabled") !== "true");
        this.isMultiple = this.listboxTarget.getAttribute("aria-multiselectable") === "true";
        this.selectedOptions = this.isMultiple ? new Set() : null;
        this.placeholder =
            this.isMultiple && this.element.dataset.placeholder ? this.element.dataset.placeholder : null;
        this.closeOnSelect = this.element.dataset.closeOnSelect === "true";

        this._hasTransition = this.calcHasTransition();
        this.setupListeners();
        this.closePopover(true, false); // fecha sem transição, sem foco
        this.initializeValue();
        this.dispatch("basecoat:initialized", { bubbles: true });
    }

    disconnect() {
        this.removeListeners();
    }

    // ─── Getters computados ──────────────────────────────
    get visibleOptions() {
        // Retorna as opções habilitadas e visíveis (sem aria-hidden true)
        return this.options.filter((opt) => opt.getAttribute("aria-hidden") !== "true");
    }

    getValue(opt) {
        return opt.dataset.value ?? opt.textContent.trim();
    }

    calcHasTransition() {
        const style = getComputedStyle(this.popoverTarget);
        return parseFloat(style.transitionDuration) > 0 || parseFloat(style.transitionDelay) > 0;
    }

    // ─── Navegação visual ────────────────────────────────
    setActiveOption(index) {
        const { options, activeIndex, triggerTarget: trigger } = this;

        if (activeIndex > -1 && options[activeIndex]) {
            options[activeIndex].classList.remove("active");
        }

        this.activeIndex = index;

        if (index > -1) {
            const opt = options[index];
            opt.classList.add("active");
            trigger.setAttribute("aria-activedescendant", opt.id || "");
        } else {
            trigger.removeAttribute("aria-activedescendant");
        }
    }

    // ─── Atualização do valor selecionado ────────────────
    updateValue(optionOrOptions, triggerEvent = true) {
        const {
            isMultiple,
            selectedOptions,
            selectedLabelTarget: label,
            inputTarget: input,
            options,
            placeholder,
        } = this;

        let value;

        if (isMultiple) {
            const opts = Array.isArray(optionOrOptions) ? optionOrOptions : [];
            selectedOptions.clear();
            opts.forEach((opt) => selectedOptions.add(opt));

            const selected = options.filter((opt) => selectedOptions.has(opt));
            if (selected.length === 0) {
                label.textContent = placeholder;
                label.classList.add("text-muted-foreground");
            } else {
                label.textContent = selected.map((opt) => opt.dataset.label || opt.textContent.trim()).join(", ");
                label.classList.remove("text-muted-foreground");
            }

            value = selected.map((opt) => this.getValue(opt));
            input.value = JSON.stringify(value);
        } else {
            const option = optionOrOptions;
            if (!option) return;
            label.innerHTML = option.innerHTML;
            value = this.getValue(option);
            input.value = value;
        }

        options.forEach((opt) => {
            if (isMultiple ? selectedOptions.has(opt) : opt === optionOrOptions) {
                opt.setAttribute("aria-selected", "true");
            } else {
                opt.removeAttribute("aria-selected");
            }
        });

        if (triggerEvent) {
            this.element.dispatchEvent(
                new CustomEvent("change", {
                    detail: { value },
                    bubbles: true,
                }),
            );
        }

        // Atualiza visual do teclado (item ativo = último selecionado)
        if (!isMultiple) {
            const idx = options.indexOf(optionOrOptions);
            this.setActiveOption(idx >= 0 ? idx : -1);
        }
    }

    // ─── Controle do popover ─────────────────────────────
    closePopover(focusOnTrigger = true, useTransition = true) {
        const popover = this.popoverTarget;
        if (popover.getAttribute("aria-hidden") === "true") return;

        // Limpa o filtro (se existir)
        if (this.hasFilterTarget) {
            const filter = this.filterTarget;
            const resetFilter = () => {
                filter.value = "";
                this.allOptions.forEach((opt) => opt.setAttribute("aria-hidden", "false"));
            };

            if (useTransition && this._hasTransition) {
                popover.addEventListener("transitionend", resetFilter, { once: true });
            } else {
                resetFilter();
            }
        }

        // Limpa styles de overflow aplicados na abertura
        this._resetOverflowStyles();

        if (focusOnTrigger) this.triggerTarget.focus();
        popover.setAttribute("aria-hidden", "true");
        this.triggerTarget.setAttribute("aria-expanded", "false");
        this.setActiveOption(-1);
    }

    _resetOverflowStyles() {
        const popover = this.popoverTarget;
        popover.style.left = "";
        popover.style.right = "";
        popover.style.maxHeight = "";
        this.listboxTarget.style.maxHeight = "";
        this.listboxTarget.style.overflowY = "";
    }

    openPopover() {
        // Fecha outros popovers/seletores
        document.dispatchEvent(
            new CustomEvent("basecoat:popover", {
                detail: { source: this.element },
            }),
        );

        this.popoverTarget.setAttribute("aria-hidden", "false");
        this.triggerTarget.setAttribute("aria-expanded", "true");

        // ─── Overflow prevention ────────────────────────
        this._applyOverflowPrevention();

        // Foco no filtro (se existir) após transição
        if (this.hasFilterTarget) {
            const filter = this.filterTarget;
            if (this._hasTransition) {
                this.popoverTarget.addEventListener("transitionend", () => filter.focus(), { once: true });
            } else {
                filter.focus();
            }
        }

        // Posiciona no item já selecionado
        const selected = this.listboxTarget.querySelector('[role="option"][aria-selected="true"]');
        if (selected) {
            const idx = this.options.indexOf(selected);
            this.setActiveOption(idx);
            selected.scrollIntoView({ block: "nearest" });
        }
    }

    _applyOverflowPrevention() {
        const trigger = this.triggerTarget;
        const popover = this.popoverTarget;
        const listbox = this.listboxTarget;

        this._resetOverflowStyles();

        const vr = trigger.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const gap = 4;

        // Horizontal: se popover ultrapassa viewport à direita
        if (vr.left + popover.offsetWidth > vw) {
            popover.style.left = "auto";
            popover.style.right = gap + "px";
        }

        // Vertical: limita altura do listbox ao espaço disponível abaixo do trigger
        const available = vh - vr.bottom - gap;
        if (available < popover.offsetHeight) {
            listbox.style.maxHeight = Math.max(available, 80) + "px";
            listbox.style.overflowY = "auto";
        }
    }

    // ─── Foco no filtro após transição ──────────────
    _focusFilter() {
        if (this.hasFilterTarget) {
            const filter = this.filterTarget;
            if (this._hasTransition) {
                this.popoverTarget.addEventListener("transitionend", () => filter.focus(), { once: true });
            } else {
                filter.focus();
            }
        }
    }

    // ─── Posiciona scroll no item selecionado ───────
    _scrollToSelected() {
        const selected = this.listboxTarget.querySelector('[role="option"][aria-selected="true"]');
        if (selected) {
            const idx = this.options.indexOf(selected);
            this.setActiveOption(idx);
            selected.scrollIntoView({ block: "nearest" });
        }
    }

    togglePopover = () => {
        if (this.triggerTarget.getAttribute("aria-expanded") === "true") {
            this.closePopover();
        } else {
            this.openPopover();
        }
    };

    // ─── Seleção programática ────────────────────────────
    selectValue(value) {
        if (this.isMultiple) {
            const opt = this.options.find((o) => this.getValue(o) === value && !this.selectedOptions.has(o));
            if (!opt) return;
            this.selectedOptions.add(opt);
            this.updateValue(this.options.filter((o) => this.selectedOptions.has(o)));
        } else {
            const opt = this.options.find((o) => this.getValue(o) === value);
            if (!opt) return;
            if (this.inputTarget.value !== value) {
                this.updateValue(opt);
            }
            this.closePopover();
        }
    }

    // ─── Inicialização do valor atual ───────────────────
    initializeValue() {
        const { options, inputTarget: input } = this;

        if (this.isMultiple) {
            const ariaSelected = options.filter((opt) => opt.getAttribute("aria-selected") === "true");
            try {
                const parsed = JSON.parse(input.value || "[]");
                const validValues = new Set(options.map((o) => this.getValue(o)));
                const initialValues = Array.isArray(parsed) ? parsed.filter((v) => validValues.has(v)) : [];
                const initialOptions = initialValues.length
                    ? initialValues.map((v) => options.find((o) => this.getValue(o) === v)).filter(Boolean)
                    : [...ariaSelected];
                this.updateValue(initialOptions, false);
            } catch {
                this.updateValue(ariaSelected, false);
            }
        } else {
            const initialOption = options.find((opt) => this.getValue(opt) === input.value) || options[0];
            if (initialOption) this.updateValue(initialOption, false);
        }
    }

    // ─── Filtro ──────────────────────────────────────────
    filterOptions = () => {
        const searchTerm = this.filterTarget.value.trim().toLowerCase();
        this.setActiveOption(-1);

        this.allOptions.forEach((option) => {
            if (option.hasAttribute("data-force")) {
                option.setAttribute("aria-hidden", "false");
                return;
            }

            const text = (option.dataset.filter || option.textContent).trim().toLowerCase();
            const keywords = (option.dataset.keywords || "")
                .toLowerCase()
                .split(/[\s,]+/)
                .filter(Boolean);
            const matches = text.includes(searchTerm) || keywords.some((kw) => kw.includes(searchTerm));
            option.setAttribute("aria-hidden", String(!matches));
        });
    };

    // ─── Navegação por teclado ──────────────────────────
    handleKeyNavigation = (event) => {
        const isOpen = this.popoverTarget.getAttribute("aria-hidden") === "false";

        if (!["ArrowDown", "ArrowUp", "Enter", "Home", "End", "Escape"].includes(event.key)) return;

        if (!isOpen) {
            if (event.key !== "Enter" && event.key !== "Escape") {
                event.preventDefault();
                this.triggerTarget.click();
            }
            return;
        }

        event.preventDefault();

        if (event.key === "Escape") {
            this.closePopover();
            return;
        }

        if (event.key === "Enter") {
            if (this.activeIndex > -1) {
                const option = this.options[this.activeIndex];
                if (this.isMultiple) {
                    this.toggleMultipleValue(option);
                    if (this.closeOnSelect) this.closePopover();
                } else {
                    if (this.inputTarget.value !== this.getValue(option)) {
                        this.updateValue(option);
                    }
                    this.closePopover();
                }
            }
            return;
        }

        const vis = this.visibleOptions;
        if (vis.length === 0) return;

        const currentVisibleIdx = this.activeIndex > -1 ? vis.indexOf(this.options[this.activeIndex]) : -1;
        let nextIdx = currentVisibleIdx;

        switch (event.key) {
            case "ArrowDown":
                if (currentVisibleIdx < vis.length - 1) nextIdx = currentVisibleIdx + 1;
                break;
            case "ArrowUp":
                if (currentVisibleIdx > 0) nextIdx = currentVisibleIdx - 1;
                else if (currentVisibleIdx === -1) nextIdx = 0;
                break;
            case "Home":
                nextIdx = 0;
                break;
            case "End":
                nextIdx = vis.length - 1;
                break;
        }

        if (nextIdx !== currentVisibleIdx) {
            const newActive = vis[nextIdx];
            this.setActiveOption(this.options.indexOf(newActive));
            newActive.scrollIntoView({ block: "nearest", behavior: "smooth" });
        }
    };

    toggleMultipleValue = (option) => {
        const { selectedOptions, options } = this;
        if (selectedOptions.has(option)) {
            selectedOptions.delete(option);
        } else {
            selectedOptions.add(option);
        }
        this.updateValue(options.filter((o) => selectedOptions.has(o)));
    };

    // ─── Eventos de lista ────────────────────────────────
    onListboxClick = (event) => {
        const clicked = event.target.closest('[role="option"]');
        if (!clicked) return;
        const option = this.options.find((o) => o === clicked);
        if (!option) return;

        if (this.isMultiple) {
            this.toggleMultipleValue(option);
            if (this.closeOnSelect) {
                this.closePopover();
            } else {
                this.setActiveOption(this.options.indexOf(option));
                if (this.hasFilterTarget) this.filterTarget.focus();
                else this.triggerTarget.focus();
            }
        } else {
            if (this.inputTarget.value !== this.getValue(option)) {
                this.updateValue(option);
            }
            this.closePopover();
        }
    };

    onListboxMousemove = (event) => {
        const option = event.target.closest('[role="option"]');
        if (option && this.visibleOptions.includes(option)) {
            const idx = this.options.indexOf(option);
            if (idx !== this.activeIndex) this.setActiveOption(idx);
        }
    };

    onListboxMouseleave = () => {
        const selected = this.listboxTarget.querySelector('[role="option"][aria-selected="true"]');
        if (selected) {
            this.setActiveOption(this.options.indexOf(selected));
        } else {
            this.setActiveOption(-1);
        }
    };

    onClickOutside = (event) => {
        if (!this.element.contains(event.target)) this.closePopover(false);
    };

    onExternalPopover = (event) => {
        if (event.detail.source !== this.element) this.closePopover(false);
    };

    // ─── Gerenciamento de listeners ──────────────────────
    setupListeners() {
        this._onTriggerClick = this.togglePopover;
        this._onTriggerKeydown = this.handleKeyNavigation;
        this._onFilterInput = this.filterOptions;
        this._onFilterKeydown = this.handleKeyNavigation;
        this._onListboxClick = this.onListboxClick;
        this._onListboxMousemove = this.onListboxMousemove;
        this._onListboxMouseleave = this.onListboxMouseleave;
        this._onClickOutside = this.onClickOutside;
        this._onExternalPopover = this.onExternalPopover;

        this.triggerTarget.addEventListener("click", this._onTriggerClick);
        this.triggerTarget.addEventListener("keydown", this._onTriggerKeydown);

        if (this.hasFilterTarget) {
            this.filterTarget.addEventListener("input", this._onFilterInput);
            this.filterTarget.addEventListener("keydown", this._onFilterKeydown);
        }

        this.listboxTarget.addEventListener("click", this._onListboxClick);
        this.listboxTarget.addEventListener("mousemove", this._onListboxMousemove);
        this.listboxTarget.addEventListener("mouseleave", this._onListboxMouseleave);

        document.addEventListener("click", this._onClickOutside);
        document.addEventListener("basecoat:popover", this._onExternalPopover);
    }

    removeListeners() {
        this.triggerTarget?.removeEventListener("click", this._onTriggerClick);
        this.triggerTarget?.removeEventListener("keydown", this._onTriggerKeydown);

        if (this.hasFilterTarget) {
            this.filterTarget?.removeEventListener("input", this._onFilterInput);
            this.filterTarget?.removeEventListener("keydown", this._onFilterKeydown);
        }

        this.listboxTarget?.removeEventListener("click", this._onListboxClick);
        this.listboxTarget?.removeEventListener("mousemove", this._onListboxMousemove);
        this.listboxTarget?.removeEventListener("mouseleave", this._onListboxMouseleave);

        document.removeEventListener("click", this._onClickOutside);
        document.removeEventListener("basecoat:popover", this._onExternalPopover);
    }
}
