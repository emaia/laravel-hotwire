// @hotwire-package

const SIDES = {
    sheet: new Set(["left", "right", "top", "bottom"]),
    drawer: new Set(["up", "right", "down", "left"]),
};
const MODAL_SIZES = new Set(["sm", "md", "lg", "xl", "full", "auto"]);

export function createOverlayPresentation(controller) {
    const family = controller.identifier;
    const root = controller.element;
    let defaults = null;
    let size = null;
    let currentSide = null;
    let active = false;

    function managedElements() {
        if (family !== "modal") return [controller.dialogTarget];

        const dialog = controller.dialogTarget;
        const panel = dialog.querySelector(':scope > [data-slot="modal-panel"]');
        return [
            dialog,
            panel,
            panel?.querySelector(':scope > [data-slot="modal-content"]'),
            panel?.querySelector(':scope > [data-slot="modal-close-icon"]'),
        ].filter(Boolean);
    }

    function managedProperties() {
        if (family === "modal") return ["width", "max-width"];
        if (family === "sheet") return ["--sheet-width", "--sheet-height"];
        return ["--drawer-width", "--drawer-max-width", "--drawer-height"];
    }

    function styleTarget() {
        return family === "modal"
            ? controller.hasDialogTarget
                ? controller.dialogTarget
                : defaults?.attributes[0].element
            : root;
    }

    function snapshot() {
        const properties = Object.fromEntries(
            managedProperties().map((name) => [
                name,
                {
                    value: styleTarget().style.getPropertyValue(name),
                    priority: styleTarget().style.getPropertyPriority(name),
                },
            ]),
        );
        return {
            attributes: managedElements().map((element) => ({
                element,
                values: Object.fromEntries(
                    managedAttributes(element).map((name) => [name, element.getAttribute(name)]),
                ),
            })),
            properties,
            sheetMaxWidth:
                family === "sheet"
                    ? {
                          value: controller.dialogTarget.style.getPropertyValue("max-width"),
                          priority: controller.dialogTarget.style.getPropertyPriority("max-width"),
                      }
                    : null,
        };
    }

    function managedAttributes(element) {
        if (family === "sheet") return ["data-side"];
        if (family === "drawer") return ["data-direction", "data-axis"];
        return [element.dataset.slot === "modal-close-icon" ? "data-modal-size" : "data-size"];
    }

    function setAttribute(element, name, value) {
        if (value === null) element.removeAttribute(name);
        else element.setAttribute(name, value);
    }

    function setProperty(name, value) {
        if (value === null) styleTarget().style.removeProperty(name);
        else styleTarget().style.setProperty(name, value);
    }

    function restore() {
        if (!active || !defaults) return;
        for (const { element, values } of defaults.attributes) {
            if (!element.isConnected) continue;
            for (const [name, value] of Object.entries(values)) setAttribute(element, name, value);
        }
        for (const [name, { value, priority }] of Object.entries(defaults.properties)) {
            if (value) styleTarget().style.setProperty(name, value, priority);
            else styleTarget().style.removeProperty(name);
        }
        if (defaults.sheetMaxWidth) {
            const { value, priority } = defaults.sheetMaxWidth;
            const dialog = defaults.attributes[0].element;
            if (value) dialog.style.setProperty("max-width", value, priority);
            else dialog.style.removeProperty("max-width");
        }
        size = null;
        currentSide = null;
        defaults = null;
        active = false;
    }

    function normalizedSide(value) {
        if (family === "drawer") {
            if (value === "top") return "up";
            if (value === "bottom") return "down";
        }
        return value;
    }

    function apply(source) {
        if (!source) return false;
        const requestedFamily = source.getAttribute("data-overlay-family");
        const rawSide = source.getAttribute("data-overlay-side");
        const side = rawSide === null ? null : normalizedSide(rawSide);
        const requestedSize = source.getAttribute("data-overlay-size")?.trim() ?? null;
        if (side === null && requestedSize === null) return false;
        if (requestedFamily !== family) {
            console.error(`Overlay presentation expects "${requestedFamily}", but the receiving host is "${family}".`);
            return false;
        }
        if (side !== null && !SIDES[family]?.has(side)) {
            console.error(`Invalid overlay side "${rawSide}" for ${family}.`);
            return false;
        }
        if (requestedSize !== null && (!requestedSize || /[;{}\r\n]/.test(requestedSize))) {
            console.error(`Invalid overlay size "${requestedSize}" for ${family}.`);
            return false;
        }

        if (!active) defaults = snapshot();
        active = true;
        if (requestedSize !== null) size = requestedSize;

        if (family === "modal") {
            if (size !== null) {
                for (const element of managedElements()) {
                    setAttribute(element, managedAttributes(element)[0], size);
                }
                setProperty("max-width", MODAL_SIZES.has(size) ? null : size);
                setProperty("width", MODAL_SIZES.has(size) ? null : "100%");
            }
            return true;
        }

        if (side !== null) {
            controller.dialogTarget.setAttribute(family === "sheet" ? "data-side" : "data-direction", side);
        }
        currentSide =
            side ??
            currentSide ??
            controller.dialogTarget.getAttribute(family === "sheet" ? "data-side" : "data-direction");
        const horizontal = ["left", "right"].includes(currentSide);
        if (family === "drawer") controller.dialogTarget.dataset.axis = horizontal ? "x" : "y";

        if (family === "sheet") {
            if (horizontal && size !== null) {
                controller.dialogTarget.style.setProperty("max-width", "100%");
            } else if (defaults.sheetMaxWidth.value) {
                controller.dialogTarget.style.setProperty(
                    "max-width",
                    defaults.sheetMaxWidth.value,
                    defaults.sheetMaxWidth.priority,
                );
            } else {
                controller.dialogTarget.style.removeProperty("max-width");
            }
            setProperty(horizontal ? "--sheet-height" : "--sheet-width", null);
            setProperty(
                horizontal ? "--sheet-width" : "--sheet-height",
                size ??
                    (horizontal
                        ? defaults.properties["--sheet-width"].value || "75%"
                        : defaults.properties["--sheet-height"].value || "auto"),
            );
        } else if (horizontal) {
            setProperty("--drawer-height", null);
            setProperty("--drawer-width", size ?? (defaults.properties["--drawer-width"].value || "75vw"));
            setProperty("--drawer-max-width", size ?? (defaults.properties["--drawer-max-width"].value || "24rem"));
        } else {
            setProperty("--drawer-width", null);
            setProperty("--drawer-max-width", null);
            setProperty("--drawer-height", size ?? (defaults.properties["--drawer-height"].value || "auto"));
        }
        return true;
    }

    function preserves(event) {
        if (!active) return false;
        return defaults?.attributes.some(
            ({ element, values }) => event.target === element && Object.hasOwn(values, event.detail?.attributeName),
        );
    }

    function beforeMorph(event) {
        if (!active || !event.detail?.newElement) return;
        if (event.target === styleTarget()) {
            for (const name of managedProperties()) {
                defaults.properties[name] = {
                    value: event.detail.newElement.style.getPropertyValue(name),
                    priority: event.detail.newElement.style.getPropertyPriority(name),
                };
            }
        }
        if (family === "sheet" && event.target === controller.dialogTarget) {
            defaults.sheetMaxWidth = {
                value: event.detail.newElement.style.getPropertyValue("max-width"),
                priority: event.detail.newElement.style.getPropertyPriority("max-width"),
            };
        }
        const authored = defaults.attributes.find(({ element }) => element === event.target);
        if (authored) {
            for (const name of Object.keys(authored.values)) {
                authored.values[name] = event.detail.newElement.getAttribute(name);
            }
        }
    }

    function afterMorph(event) {
        if (!active) return;
        if (event.target === styleTarget() || defaults.attributes.some(({ element }) => element === event.target))
            reapply();
    }

    function reapply() {
        if (!active) return;
        const present = {
            getAttribute(name) {
                if (name === "data-overlay-family") return family;
                if (name === "data-overlay-size") return size;
                if (name === "data-overlay-side" && family !== "modal") {
                    return currentSide;
                }
                return null;
            },
        };
        apply(present);
    }

    return {
        apply,
        reset: restore,
        preserves,
        beforeMorph,
        afterMorph,
        get active() {
            return active;
        },
    };
}
