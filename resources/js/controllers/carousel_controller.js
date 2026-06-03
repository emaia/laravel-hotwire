import { Controller } from "@hotwired/stimulus";
import EmblaCarousel from "embla-carousel";

import "./carousel.css";

export default class extends Controller {
    static targets = ["prevButton", "nextButton", "dotList", "dotTemplate", "progress", "indexLabel", "totalLabel"];

    static values = {
        options: { type: Object, default: {} },
    };

    initialize() {
        this.onSelect = this.onSelect.bind(this);
        this.onReInit = this.onReInit.bind(this);
        this.onScroll = this.onScroll.bind(this);
        this.onSettle = this.onSettle.bind(this);
        this.onSlidesInView = this.onSlidesInView.bind(this);
        this.onSlidesChanged = this.onSlidesChanged.bind(this);
        this.dotNodes = [];
    }

    connect() {
        // Viewport/container are found by their structural hooks (not Stimulus
        // targets) so they stay identifier-independent — a subclass reuses them
        // and the same CSS without a per-identifier attribute.
        const node = this.element.querySelector("[data-carousel-viewport]") ?? this.element;

        this.syncAxis();
        this.embla = EmblaCarousel(node, this.optionsValue, this.emblaPlugins());
        this.renderDots();

        this.embla.on("select", this.onSelect);
        this.embla.on("reInit", this.onReInit);
        this.embla.on("scroll", this.onScroll);
        this.embla.on("settle", this.onSettle);
        this.embla.on("slidesInView", this.onSlidesInView);
        this.embla.on("slidesChanged", this.onSlidesChanged);

        this.syncSelected();
        this.syncNav();
        this.syncCounter();

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

    play() {
        this.embla?.plugins()?.autoplay?.play();
    }

    stop() {
        this.embla?.plugins()?.autoplay?.stop();
    }

    teardownForCache() {
        if (!this.embla) return;
        this.embla.destroy();
        this.embla = null;
    }

    optionsValueChanged() {
        this.syncAxis();
        if (!this.embla) return;
        this.embla.reInit(this.optionsValue, this.emblaPlugins());
    }

    /**
     * Override point for Embla plugins. Subclass this controller, install the
     * plugin packages you want, and return their instances — they load lazily
     * with your subclass's chunk. See docs/controllers/carousel.md#plugins.
     */
    emblaPlugins() {
        return [];
    }

    onSelect() {
        this.syncSelected();
        this.syncNav();
        this.syncCounter();
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
        this.syncCounter();
    }

    onScroll() {
        this.syncProgress();
        this.dispatch("scroll", { detail: { progress: this.embla.scrollProgress() } });
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
        this.syncCounter();
        this.dispatch("slides-changed");
    }

    renderDots() {
        if (!this.hasDotListTarget) return;

        const snaps = this.embla.scrollSnapList();
        const template = this.hasDotTemplateTarget ? this.dotTemplateTarget.content.firstElementChild : null;
        // When slidesToScroll groups slides, a dot is a group/page, not a single slide.
        const noun = snaps.length === this.embla.slideNodes().length ? "slide" : "group";

        this.dotListTarget.innerHTML = "";
        this.dotNodes = snaps.map((_, index) => {
            let node;
            if (template) {
                node = template.cloneNode(true);
            } else {
                node = document.createElement("button");
                node.type = "button";
                node.dataset.action = `${this.identifier}#scrollTo`;
            }
            node.setAttribute(`data-${this.identifier}-index-param`, String(index));
            node.setAttribute("aria-label", `Go to ${noun} ${index + 1}`);
            this.dotListTarget.appendChild(node);
            return node;
        });
    }

    syncSelected() {
        if (this.dotNodes.length === 0) return;

        const selected = this.embla.selectedScrollSnap();
        this.dotNodes.forEach((node, index) => {
            if (index === selected) {
                node.setAttribute("aria-current", "true");
            } else {
                node.removeAttribute("aria-current");
            }
        });
    }

    syncNav() {
        if (this.hasPrevButtonTarget) this.prevButtonTarget.disabled = !this.embla.canScrollPrev();
        if (this.hasNextButtonTarget) this.nextButtonTarget.disabled = !this.embla.canScrollNext();
    }

    syncProgress() {
        if (!this.hasProgressTarget) return;
        this.progressTarget.style.width = `${this.embla.scrollProgress() * 100}%`;
    }

    syncCounter() {
        if (this.hasIndexLabelTarget) this.indexLabelTarget.textContent = this.embla.selectedScrollSnap() + 1;
        if (this.hasTotalLabelTarget) this.totalLabelTarget.textContent = this.embla.scrollSnapList().length;
    }

    syncAxis() {
        this.element.dataset.carouselAxis = this.optionsValue.axis === "y" ? "y" : "x";
    }
}
