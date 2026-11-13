import { Application } from "@hotwired/stimulus";
import ClearInputController from "../../../resources/js/controllers/clear_input_controller.js";
import AutoSubmitController from "../../../resources/js/controllers/auto_submit_controller.js";
import ModalController from "../../../resources/js/controllers/modal_controller.js";
import DropdownController from "../../../resources/js/controllers/dropdown_controller.js";
import MultiSelectController from "../../../resources/js/controllers/multi_select_controller.js";

window.app = Application.start();
for (const [name, controller] of Object.entries({
    "clear-input": ClearInputController,
    "auto-submit": AutoSubmitController,
    modal: ModalController,
    dropdown: DropdownController,
    "multi-select": MultiSelectController,
}))
    window.app.register(name, controller);

window.submissions = [];
document.querySelector("#filters").addEventListener("submit", (event) => {
    event.preventDefault();
    window.submissions.push(new FormData(event.target).get("q"));
});
