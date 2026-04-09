import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {}

    log(event) {
        console.log("Logging event...");
        console.log("Event:", event);
    }
}
