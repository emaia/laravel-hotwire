import { Application } from "@hotwired/stimulus";
import AutofocusController from "../../../resources/js/controllers/autofocus_controller.js";
import RichTextController from "../../../resources/js/controllers/rich_text_controller.js";

window.focusApplication = Application.start();
window.focusApplication.register("autofocus", AutofocusController);
window.registerRichText = (identifier = "rich-text") =>
    window.focusApplication.register(identifier, RichTextController);
