import { Controller } from "@hotwired/stimulus";

// Trigger wrapper: dispatches optimistic UI when a link is clicked.
//
// Pair on the same <a> with the optimistic--dispatch core controller:
//   <a data-controller="optimistic--dispatch link--optimistic"
//      data-turbo-frame="detail" href="/posts/42">…</a>
//
// Skips the optimistic dispatch when the click is not going to be handled by
// Turbo (modifier keys, middle-click, target=_blank, data-turbo="false", or
// already-prevented events) so the optimistic update never gets out of sync
// with a real navigation.
export default class extends Controller {
    connect() {
        this.onClick = this.onClick.bind(this);
        this.element.addEventListener("click", this.onClick);
    }

    disconnect() {
        this.element.removeEventListener("click", this.onClick);
    }

    onClick(event) {
        if (!this.#willTurboHandle(event)) return;

        const dispatcher = this.application.getControllerForElementAndIdentifier(
            this.element,
            "optimistic--dispatch",
        );
        if (dispatcher) dispatcher.dispatch();
    }

    #willTurboHandle(event) {
        if (event.defaultPrevented) return false;
        if (event.button !== undefined && event.button !== 0) return false;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

        const link = this.element;
        if (link.dataset.turbo === "false") return false;
        if (link.target && link.target !== "" && link.target !== "_self") return false;

        return true;
    }
}
