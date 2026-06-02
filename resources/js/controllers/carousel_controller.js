import { Controller } from "@hotwired/stimulus";
import EmblaCarousel from "embla-carousel";

import "./carousel.css";

export default class extends Controller {
    static targets = ["viewport", "container", "prevButton", "nextButton", "dotList", "dotTemplate"];

    static values = {
        options: { type: Object, default: {} },
    };

    static classes = ["activeDot", "disabledNav"];

    initialize() {
        this.onSelect = this.onSelect.bind(this);
        this.onReInit = this.onReInit.bind(this);
        this.onSettle = this.onSettle.bind(this);
        this.onSlidesInView = this.onSlidesInView.bind(this);
        this.onSlidesChanged = this.onSlidesChanged.bind(this);
        this.dotNodes = [];
    }

    connect() {
        const node = this.hasViewportTarget ? this.viewportTarget : this.element;

        this.embla = EmblaCarousel(node, this.optionsValue);
        this.renderDots();

        this.embla.on("select", this.onSelect);
        this.embla.on("reInit", this.onReInit);
        this.embla.on("settle", this.onSettle);
        this.embla.on("slidesInView", this.onSlidesInView);
        this.embla.on("slidesChanged", this.onSlidesChanged);

        this.syncSelected();
        this.syncNav();

        this.dispatch("init", { detail: { embla: this.embla } });
    }

    disconnect() {
        if (this.embla) {
            this.embla.destroy();
            this.embla = null;
        }
        this.dotNodes = [];
    }

    next() {
        this.embla?.scrollNext();
    }

    prev() {
        this.embla?.scrollPrev();
    }

    scrollTo(event) {
        const index = event?.params?.index ?? 0;
        this.embla?.scrollTo(index);
    }

    teardownForCache() {
        if (!this.embla) return;
        this.embla.destroy();
        this.embla = null;
    }

    optionsValueChanged() {
        if (!this.embla) return;
        this.embla.reInit(this.optionsValue);
    }

    onSelect() {
        this.syncSelected();
        this.syncNav();
        this.dispatch("select", {
            detail: {
                index: this.embla.selectedScrollSnap(),
                previousIndex: this.embla.previousScrollSnap(),
                slidesInView: this.embla.slidesInView(),
            },
        });
    }

    onReInit() {
        this.renderDots();
        this.syncSelected();
        this.syncNav();
    }

    onSettle() {
        this.dispatch("settle");
    }

    onSlidesInView() {
        this.dispatch("slides-in-view", {
            detail: { inView: this.embla.slidesInView() },
        });
    }

    onSlidesChanged() {
        this.renderDots();
        this.syncSelected();
        this.syncNav();
        this.dispatch("slides-changed");
    }

    renderDots() {
        if (!this.hasDotListTarget) return;

        const snaps = this.embla.scrollSnapList();
        const template = this.hasDotTemplateTarget ? this.dotTemplateTarget.content.firstElementChild : null;

        this.dotListTarget.innerHTML = "";
        this.dotNodes = snaps.map((_, index) => {
            let node;
            if (template) {
                node = template.cloneNode(true);
            } else {
                node = document.createElement("button");
                node.type = "button";
                node.dataset.action = "carousel#scrollTo";
            }
            node.dataset.carouselIndexParam = String(index);
            node.setAttribute("aria-label", `Go to slide ${index + 1}`);
            this.dotListTarget.appendChild(node);
            return node;
        });
    }

    syncSelected() {
        if (this.dotNodes.length === 0) return;

        const selected = this.embla.selectedScrollSnap();
        this.dotNodes.forEach((node, index) => {
            const active = index === selected;

            if (active) {
                node.setAttribute("aria-current", "true");
            } else {
                node.removeAttribute("aria-current");
            }

            if (this.hasActiveDotClass) {
                this.activeDotClasses.forEach((cls) => node.classList.toggle(cls, active));
            }
        });
    }

    syncNav() {
        const canPrev = this.embla.canScrollPrev();
        const canNext = this.embla.canScrollNext();

        if (this.hasPrevButtonTarget) {
            this.prevButtonTarget.disabled = !canPrev;
            this.toggleDisabledClass(this.prevButtonTarget, !canPrev);
        }
        if (this.hasNextButtonTarget) {
            this.nextButtonTarget.disabled = !canNext;
            this.toggleDisabledClass(this.nextButtonTarget, !canNext);
        }
    }

    toggleDisabledClass(element, disabled) {
        if (!this.hasDisabledNavClass) return;
        this.disabledNavClasses.forEach((cls) => {
            element.classList.toggle(cls, disabled);
        });
    }
}
