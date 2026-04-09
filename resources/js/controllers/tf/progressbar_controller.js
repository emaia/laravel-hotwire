import * as Turbo from "@hotwired/turbo";
import { Controller } from "@hotwired/stimulus";

// prettier-ignore
export default class extends Controller {
    connect() {
        this.showProgressBar = () => {
            if (Turbo.session.adapter.progressBar) {
                Turbo.session.adapter.progressBar.show();
            }
        };

        this.hideProgressBar = () => {
            if (Turbo.session.adapter.progressBar) {
                Turbo.session.adapter.progressBar.hide();
            }
        };

        document.addEventListener("turbo:before-fetch-request", this.showProgressBar);
        document.addEventListener("turbo:frame-render", this.hideProgressBar);
        document.addEventListener("turbo:before-stream-render", this.hideProgressBar);
    }

    disconnect() {
        document.removeEventListener("turbo:before-fetch-request", this.showProgressBar);
        document.removeEventListener("turbo:frame-render", this.hideProgressBar);
        document.removeEventListener("turbo:before-stream-render", this.hideProgressBar);
    }
}
