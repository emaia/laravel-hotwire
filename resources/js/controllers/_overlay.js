// @hotwire-package
// Shared overlay lifecycle for modal, sheet, drawer and sidebar controllers.
// Wires FocusTrap, body scroll lock, outside-click dismiss, Escape key,
// focus return and enter/exit class toggling with configurable durations.

import { FocusTrap } from "./_focus_trap.js";

const bodyScrollLock = {
    count: 0,
    classes: new Set(),
    paddingRight: null,
};

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

        if (lockScroll) lockBodyScroll(lockScrollClasses);

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

        if (lockScroll) unlockBodyScroll();

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

        if (lockScroll) lockBodyScroll(lockScrollClasses);

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

function lockBodyScroll(classes) {
    if (bodyScrollLock.count === 0) {
        bodyScrollLock.paddingRight = document.body.style.paddingRight;

        const clientWidth = document.documentElement.clientWidth;
        const scrollbarWidth = clientWidth > 0 ? Math.max(0, window.innerWidth - clientWidth) : 0;
        if (scrollbarWidth > 0) {
            const currentPadding = bodyScrollLock.paddingRight.trim();
            document.body.style.paddingRight = currentPadding === ""
                ? `${scrollbarWidth}px`
                : `calc(${currentPadding} + ${scrollbarWidth}px)`;
        }
    }

    for (const className of classes) {
        bodyScrollLock.classes.add(className);
    }

    document.body.classList.add(...classes);
    bodyScrollLock.count++;
}

function unlockBodyScroll() {
    if (bodyScrollLock.count === 0) return;

    bodyScrollLock.count--;
    if (bodyScrollLock.count > 0) return;

    document.body.classList.remove(...bodyScrollLock.classes);
    bodyScrollLock.classes.clear();
    document.body.style.paddingRight = bodyScrollLock.paddingRight ?? "";
    bodyScrollLock.paddingRight = null;
}
