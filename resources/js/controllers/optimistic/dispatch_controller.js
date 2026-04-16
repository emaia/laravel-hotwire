import { Controller } from "@hotwired/stimulus";
import { dispatchOptimistic } from "./_dispatch";

export default class extends Controller {
    dispatch() {
        dispatchOptimistic(this.element);
    }
}
