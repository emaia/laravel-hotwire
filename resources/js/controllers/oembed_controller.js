// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const PROVIDERS = [
    {
        test: /(?:youtube\.com\/(?:watch\?.*v=|embed\/|shorts\/)|youtu\.be\/)([\w-]+)/,
        embed: (id) => `https://www.youtube.com/embed/${id}`,
    },
    {
        test: /vimeo\.com\/(\d+)/,
        embed: (id) => `https://player.vimeo.com/video/${id}`,
    },
];

export default class extends Controller {
    connect() {
        this.processEmbeds(this.element);

        this.observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => this.processEmbeds(node));
            });
        });
        this.observer.observe(this.element, { childList: true, subtree: true });
    }

    disconnect() {
        this.observer?.disconnect();
        this.observer = null;
    }

    processEmbeds(root) {
        const nodes = [];

        if (root instanceof Element && root.matches("oembed[url]")) nodes.push(root);
        root.querySelectorAll?.("oembed[url]").forEach((node) => nodes.push(node));

        nodes.forEach((node) => this.processEmbed(node));
    }

    processEmbed(node) {
        if (!this.element.contains(node)) return;

        const url = node.getAttribute("url");
        const figure = node.closest("figure") || node;

        const provider = PROVIDERS.find((p) => p.test.test(url));

        if (provider) {
            const id = url.match(provider.test)[1];
            const wrapper = document.createElement("div");
            wrapper.dataset.slot = "oembed";

            const iframe = document.createElement("iframe");
            iframe.dataset.slot = "oembed-frame";
            iframe.src = provider.embed(id);
            iframe.setAttribute("frameborder", "0");
            iframe.setAttribute("allowfullscreen", "");
            iframe.setAttribute(
                "allow",
                "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture",
            );

            wrapper.appendChild(iframe);
            figure.replaceWith(wrapper);
        } else {
            const link = document.createElement("a");
            link.dataset.slot = "oembed-link";
            link.href = url;
            link.textContent = url;
            link.target = "_blank";
            link.rel = "noopener noreferrer";
            figure.replaceWith(link);
        }
    }
}
