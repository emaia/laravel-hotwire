import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        id: String,
        lazy: {
            type: Boolean,
            default: true,
        },
    };

    initialize() {
        window.dataLayer = window.dataLayer || [];
    }

    connect() {
        this.assertIsGtmId(this.idValue);

        if (this.lazyValue) {
            const initGTMOnEvent = (event) => {
                this.initGTM();
                event.currentTarget.removeEventListener(
                    event.type,
                    initGTMOnEvent,
                );
            };

            document.addEventListener("scroll", initGTMOnEvent);
            document.addEventListener("mousemove", initGTMOnEvent);
            document.addEventListener("touchstart", initGTMOnEvent);
        } else {
            this.initGTM();
        }
    }

    initGTM() {
        if (window.gtmDidInit) {
            return false;
        }

        this.loadScript();

        window.gtmDidInit = true;
    }

    loadScript() {
        const script = document.createElement("script");
        script.async = this.lazyValue;
        script.onload = () => {
            window.dataLayer.push({
                event: "gtm.js",
                "gtm.start": new Date().getTime(),
                "gtm.uniqueEventId": 0,
            });
        };
        script.src = `https://www.googletagmanager.com/gtm.js?id=${this.idValue}`;

        document.head.appendChild(script);
    }

    event(evt) {
        if (!evt.params.eventName) {
            throw new Error("[GTM] Event name is required.");
        }

        if (typeof evt.params?.eventPayload === "object") {
            window.dataLayer.push({
                event: evt.params.eventName,
                ...evt.params.eventPayload,
            });
        } else {
            window.dataLayer.push({ event: evt.params.eventName });
        }
    }

    assertIsGtmId(id) {
        const GTM_ID_PATTERN = /^GTM-[0-9A-Z]+$/;

        if (typeof id !== "string" || !GTM_ID_PATTERN.test(id)) {
            const suggestion = String(id)
                .toUpperCase()
                .replace(/.*-|[^0-9A-Z]/g, "");
            const suggestionText =
                suggestion.length === 0
                    ? ""
                    : ` Did you mean 'GTM-${suggestion}'?`;
            throw new Error(
                `'${id}' is not a valid GTM-ID (${GTM_ID_PATTERN}).${suggestionText}`,
            );
        }
    }
}
