import { Application } from "@hotwired/stimulus";
import ImageViewer from "../../../resources/js/controllers/image_viewer_controller.js";
import Modal from "../../../resources/js/controllers/modal_controller.js";
import * as Turbo from "@hotwired/turbo";
import { installSnapshotNormalization, normalizeSnapshot } from "../../../resources/js/controllers/_snapshot.js";

window.Turbo = Turbo;
window.normalizeSnapshot = normalizeSnapshot;
installSnapshotNormalization(document);
window.app = Application.start();
window.app.register("image-viewer", ImageViewer);
window.app.register("modal", Modal);
window.viewer = () =>
    window.app.getControllerForElementAndIdentifier(document.querySelector("#gallery"), "image-viewer");
