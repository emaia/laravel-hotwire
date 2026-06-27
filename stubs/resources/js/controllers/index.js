// @hotwire-loader v0.32.0
import { Stimulus } from "../libs/stimulus";
import { registerControllers } from "@emaia/stimulus-dynamic-loader";

const userControllers = import.meta.glob(
    "./**/*_controller.{js,ts}",
    { eager: false }
);

const packageControllers = import.meta.glob([
    "../../../vendor/emaia/laravel-hotwire/resources/js/controllers/**/*_controller.js",
], { eager: false });

registerControllers(Stimulus, packageControllers);
registerControllers(Stimulus, userControllers);
