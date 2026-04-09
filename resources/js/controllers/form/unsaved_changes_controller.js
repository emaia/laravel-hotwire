import { Controller } from "@hotwired/stimulus";

const LEAVING_PAGE_MESSAGE =
    "Você tentou sair desta página mas existem alterações no formulário que não foram salvas. " +
    "Se continuar, as alterações serão perdidas. Deseja continuar?";

export default class extends Controller {
    allow = false;

    initialize() {
        this.formChanges(this.element);
    }

    connect() {
        this.setupActions();
        document.addEventListener("turbo:render", this.setupActions.bind(this));
    }

    disconnect() {
        document.removeEventListener("turbo:render", this.setupActions.bind(this));
    }

    setupActions() {
        this.setupFormActions();
        this.setupSubmitButtonActions();
    }

    setupFormActions() {
        this.updateActions(this.element, [
            "turbo:before-visit@window->form--unsaved-changes#leavingPage",
            "form--unsaved-changes#allowFormSubmission",
        ]);
    }

    setupSubmitButtonActions() {
        const submitButton = this.element.querySelector('button[type="submit"]');
        if (submitButton) {
            this.updateActions(submitButton, ["form--unsaved-changes#allowFormSubmission"]);
        }
    }

    updateActions(element, newActions) {
        const currentActions = (element.dataset.action || "").split(" ").filter(Boolean);
        const updatedActions = [...new Set([...currentActions, ...newActions])];
        element.dataset.action = updatedActions.join(" ");
    }

    leavingPage(event) {
        if (this.formChanges(this.element).length > 0 && this.allow === false) {
            if (event.type === "turbo:before-visit") {
                if (!window.confirm(LEAVING_PAGE_MESSAGE)) {
                    event.preventDefault();
                }
            } else {
                event.returnValue = LEAVING_PAGE_MESSAGE;
                return event.returnValue;
            }
        }

        this.allow = false;
    }

    allowFormSubmission(event) {
        this.allow = true;
    }

    formChanges(form) {
        if (typeof form === "string") {
            form = document.getElementById(form);
        }

        if (!this.isValidForm(form)) {
            return null;
        }

        return this.findChangedElements(form);
    }

    isValidForm(form) {
        return form && form.nodeName && form.nodeName.toLowerCase() === "form";
    }

    findChangedElements(form) {
        const changedElements = [];

        for (let element of form.elements) {
            if (!element.hasAttribute("data-ignore-unsaved-change") && this.isElementChanged(element)) {
                changedElements.push(element);
            }
        }

        return changedElements;
    }

    isElementChanged(element) {
        const nodeName = element.nodeName.toLowerCase();

        switch (nodeName) {
            case "select":
                return this.isSelectChanged(element);
            case "textarea":
            case "input":
                return this.isInputChanged(element);
            default:
                return false;
        }
    }

    isSelectChanged(select) {
        let defaultIndex = 0;
        let isChanged = false;

        for (let i = 0; i < select.options.length; i++) {
            const option = select.options[i];
            isChanged = isChanged || option.selected !== option.defaultSelected;
            if (option.defaultSelected) defaultIndex = i;
        }

        if (isChanged && !select.multiple) {
            isChanged = defaultIndex !== select.selectedIndex;
        }

        return isChanged;
    }

    isInputChanged(input) {
        switch (input.type.toLowerCase()) {
            case "checkbox":
            case "radio":
                return input.checked !== input.defaultChecked;
            default:
                return input.value !== input.defaultValue;
        }
    }
}
