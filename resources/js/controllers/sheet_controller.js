// @hotwire-package
import DrawerController from "./drawer_controller.js";

export default class SheetController extends DrawerController {
    static values = {
        ...DrawerController.values,
        openDuration: { type: Number, default: 300 },
        closeDuration: { type: Number, default: 300 },
    };
}
