import { Application } from "@hotwired/stimulus";
import DropdownController from "../../../resources/js/controllers/dropdown_controller.js";
import { installSnapshotNormalization } from "../../../resources/js/controllers/_snapshot.js";

installSnapshotNormalization(document);
window.app = Application.start();
window.app.register("dropdown", DropdownController);
