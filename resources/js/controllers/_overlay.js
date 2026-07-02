// @hotwire-package
// Shared overlay lifecycle for modal, sheet, drawer and sidebar controllers.
// Wires FocusTrap, body scroll lock, outside-click dismiss, Escape key,
// focus return and enter/exit class toggling with configurable durations.

import { FocusTrap } from "./_focus_trap.js";

export function createOverlay(controller, {
    modalTarget,
    backdropTarget,
    dialogTarget,
    hiddenClasses,
    visibleClasses,
    backdropHiddenClasses,
    backdropVisibleClasses,
    dialogHiddenClasses,
    dialogVisibleClasses,
    lockScrollClasses,
    lockScroll = true,
    openDuration = 300,
    closeDuration = 300,
    closeOnEscape = true,
    closeOnClickOutside = true,
    onOpen,
    onClose,
    getTriggerElement,
    isClickInsideCheck,
}) {
    let isOpen = false;
    let isOpening = false;
    let isClosing = false;
    let focusTrap = null;
    let triggerElement = null;

    if (modalTarget) {
        focusTrap = new FocusTrap(modalTarget);
    }

    function handleEscapeKey(event) {
        if (closeOnEscape && event.key === "Escape" && isOpen) {
            close();
        }
    }

    function handleClickOutside(event) {
        if (!closeOnClickOutside || !isOpen) return;

        if (event.target === dialogTarget) return;

        if (isClickInsideCheck) {
            const inside = isClickInsideCheck(event);
            if (inside) return;
        } else if (dialogTarget?.contains(event.target)) {
            return;
        }

        close();
    }

    document.addEventListener("keydown", handleEscapeKey);

    function open() {
        if (isOpening || isClosing || isOpen) return;

        isOpen = true;
        isOpening = true;
        triggerElement = typeof getTriggerElement === "function"
            ? getTriggerElement()
            : document.activeElement;

        modalTarget.hidden = false;
        modalTarget.setAttribute("data-open", "true");

        if (lockScroll) {
            document.body.classList.add(...lockScrollClasses);
        }

        requestAnimationFrame(() => {
            modalTarget.classList.remove(...hiddenClasses);
            modalTarget.classList.add(...visibleClasses);

            backdropTarget.classList.remove(...backdropHiddenClasses);
            backdropTarget.classList.add(...backdropVisibleClasses);

            dialogTarget.classList.remove(...dialogHiddenClasses);
            dialogTarget.classList.add(...dialogVisibleClasses);

            focusTrap?.activate();

            setTimeout(() => {
                isOpening = false;

                if (typeof onOpen === "function") {
                    onOpen();
                }
            }, openDuration);
        });
    }

    function close() {
        if (isClosing || !isOpen) return;

        isOpen = false;
        isClosing = true;

        focusTrap?.deactivate();

        modalTarget.setAttribute("data-open", "false");
        modalTarget.classList.remove(...visibleClasses);
        modalTarget.classList.add(...hiddenClasses);

        backdropTarget.classList.remove(...backdropVisibleClasses);
        backdropTarget.classList.add(...backdropHiddenClasses);

        dialogTarget.classList.remove(...dialogVisibleClasses);
        dialogTarget.classList.add(...dialogHiddenClasses);

        setTimeout(() => {
            modalTarget.hidden = true;
            isClosing = false;

            if (typeof onClose === "function") {
                onClose();
            }
        }, closeDuration);

        if (lockScroll) {
            document.body.classList.remove(...lockScrollClasses);
        }

        if (triggerElement && !triggerElement.disabled && typeof triggerElement.focus === "function") {
            triggerElement.focus();
        }
    }

    function cleanup() {
        document.removeEventListener("keydown", handleEscapeKey);
        focusTrap?.deactivate();

        if (isOpen && !isClosing) {
            close();
        }
    }

    function setOpen() {
        if (isOpen) return;

        isOpen = true;
        isOpening = false;
        isClosing = false;

        modalTarget.hidden = false;
        modalTarget.setAttribute("data-open", "true");
        modalTarget.classList.remove(...hiddenClasses);
        modalTarget.classList.add(...visibleClasses);

        backdropTarget.classList.remove(...backdropHiddenClasses);
        backdropTarget.classList.add(...backdropVisibleClasses);

        dialogTarget.classList.remove(...dialogHiddenClasses);
        dialogTarget.classList.add(...dialogVisibleClasses);

        if (lockScroll) {
            document.body.classList.add(...lockScrollClasses);
        }

        focusTrap?.activate();

        if (typeof onOpen === "function") {
            onOpen();
        }
    }

    // Set initial state after a renderFrame so the DOM is ready
    Object.defineProperty(close, "isClosing", { get: () => isClosing });

    return {
        get isOpen() { return isOpen; },
        get isClosing() { return isClosing; },
        setOpen,
        open,
        close,
        cleanup,
    };
}
