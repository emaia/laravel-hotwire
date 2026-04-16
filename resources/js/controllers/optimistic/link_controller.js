import { Controller } from "@hotwired/stimulus";
import { dispatchOptimistic } from "./_dispatch";

// Dispatches optimistic UI when a Turbo-driven link is clicked.
//
//   <a data-controller="optimistic--link"
//      data-turbo-frame="detail" href="/posts/42">
//       <x-hwc::optimistic target="detail" action="update">…</x-hwc::optimistic>
//   </a>
//
// Skips the dispatch when the click won't be handled by Turbo (modifier keys,
// middle-click, target=_blank, data-turbo="false", or already-prevented).
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

        dispatchOptimistic(this.element);
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
