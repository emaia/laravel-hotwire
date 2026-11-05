import { Application } from "@hotwired/stimulus";
import RichText from "../../../resources/js/controllers/rich_text_controller.js";
import MapController from "../../../resources/js/controllers/map_controller.js";
import Tooltip from "../../../resources/js/controllers/tooltip_controller.js";
import { installSnapshotNormalization, normalizeSnapshot } from "../../../resources/js/controllers/_snapshot.js";
import Modal from "../../../resources/js/controllers/modal_controller.js";
import Dropdown from "../../../resources/js/controllers/dropdown_controller.js";
import Chart from "../../../resources/js/controllers/chart_controller.js";
import Carousel from "../../../resources/js/controllers/carousel_controller.js";
import MorphGuard from "../../../resources/js/controllers/turbo/morph_guard_controller.js";
import FileUpload from "../../../resources/js/controllers/file_upload_controller.js";

installSnapshotNormalization(document);
window.normalizeSnapshot = normalizeSnapshot;

window.widgets = Application.start();
window.widgets.register("custom-editor", class extends RichText {});
window.widgets.register("custom-map", class extends MapController {
    tileLayerUrl() { return "data:image/png;base64,"; }
});
window.widgets.register("tooltip", Tooltip);
window.widgets.register("modal", Modal);
window.widgets.register("dropdown", Dropdown);
window.widgets.register("chart", Chart);
window.widgets.register("carousel", Carousel);
window.widgets.register("turbo--morph-guard", MorphGuard);
window.widgets.register("file-upload", FileUpload);
window.widget = (id, identifier) => window.widgets.getControllerForElementAndIdentifier(document.getElementById(id), identifier);
