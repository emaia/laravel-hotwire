// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import EmblaCarousel from "embla-carousel";

import { attachMorphRecovery } from "./_turbo_morph_recovery.js";

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
        this.initEmbla();

        this.detachMorphRecovery = attachMorphRecovery(this, {
            isStale: () => this.morphStateIsStale(),
            recover: () => this.initEmbla(this.embla?.selectedScrollSnap()),
        });
    }

    disconnect() {
        this.detachMorphRecovery?.();
        this.destroyEmbla();
    }

    initEmbla(selectedIndex = null) {
        this.destroyEmbla();

        // Structural hooks let subclasses reuse the markup and CSS under a different Stimulus identifier.
        const node = this.element.querySelector("[data-carousel-viewport]") ?? this.element;
        const options =
            selectedIndex === null || selectedIndex === undefined
                ? this.optionsValue
                : { ...this.optionsValue, startIndex: selectedIndex };

        this.syncAxis();
        this.embla = EmblaCarousel(node, options, this.emblaPlugins());
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
        this.syncProgress();

        this.dispatch("init", { detail: { embla: this.embla } });
    }

    morphStateIsStale() {
        if (!this.embla) return false;

        const slides = this.embla.slideNodes();
        if (slides.some((slide) => !document.contains(slide))) return true;

        const container = this.embla.containerNode();
        const currentContainer = this.element.querySelector("[data-carousel-container]");
        if (!document.contains(container) || (currentContainer && currentContainer !== container)) return true;
        // Server markup has no track transform; losing Embla's inline value resets the visible snap.
        if (container.style.transform === "") return true;

        const snaps = this.embla.scrollSnapList();
        const selected = this.embla.selectedScrollSnap();

        if (this.hasIndexLabelTarget && this.indexLabelTarget.textContent !== String(selected + 1)) return true;
        if (this.hasTotalLabelTarget && this.totalLabelTarget.textContent !== String(snaps.length)) return true;
        if (this.hasDotListTarget && this.dotListTarget.childElementCount !== snaps.length) return true;

        if (this.hasProgressTarget) {
            const expected = `${((selected + 1) / snaps.length) * 100}%`;
            if (this.progressTarget.style.width !== expected) return true;
        }

        if (this.hasPrevButtonTarget && this.prevButtonTarget.disabled === this.embla.canScrollPrev()) return true;
        if (this.hasNextButtonTarget && this.nextButtonTarget.disabled === this.embla.canScrollNext()) return true;

        return false;
    }

    destroyEmbla() {
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
     * Return Embla plugins from a subclass so dependencies stay in its lazy-loaded chunk.
     * See docs/controllers/carousel.md#plugins for the extension contract.
     */
    emblaPlugins() {
        return [];
    }

    onSelect() {
        this.syncSelected();
        this.syncNav();
        this.syncCounter();
        this.syncProgress();
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
        this.syncProgress();
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
        this.syncProgress();
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
        const snaps = this.embla.scrollSnapList();
        const current = this.embla.selectedScrollSnap();
        this.progressTarget.style.width = `${((current + 1) / snaps.length) * 100}%`;
    }

    syncCounter() {
        if (this.hasIndexLabelTarget) this.indexLabelTarget.textContent = this.embla.selectedScrollSnap() + 1;
        if (this.hasTotalLabelTarget) this.totalLabelTarget.textContent = this.embla.scrollSnapList().length;
    }

    syncAxis() {
        this.element.dataset.carouselAxis = this.optionsValue.axis === "y" ? "y" : "x";
    }
}
