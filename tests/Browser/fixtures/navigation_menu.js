import { Application } from "@hotwired/stimulus";
import "@hotwired/turbo";
import DropdownController from "../../../resources/js/controllers/dropdown_controller.js";
import ModalController from "../../../resources/js/controllers/modal_controller.js";
import NavigationMenuController from "../../../resources/js/controllers/navigation_menu_controller.js";

const app = Application.start();
app.register("dropdown", DropdownController);
app.register("modal", ModalController);
app.register("navigation-menu", NavigationMenuController);
