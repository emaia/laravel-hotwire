// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const DIVISIONS = [
    { unit: "second", amount: 60 },
    { unit: "minute", amount: 60 },
    { unit: "hour", amount: 24 },
    { unit: "day", amount: 7 },
    { unit: "week", amount: 4.34524 },
    { unit: "month", amount: 12 },
    { unit: "year", amount: Infinity },
];

export default class extends Controller {
    static values = {
        datetime: String,
        refreshInterval: Number,
        includeSeconds: Boolean,
        addSuffix: Boolean,
        locale: String,
    };

    initialize() {
        this.isValid = true;
        this.connected = false;
        this.refreshTimer = null;
    }

    connect() {
        this.connected = true;
        this.buildFormatters();
        this.load();

        if (this.hasRefreshIntervalValue && this.isValid) {
            this.startRefreshing();
        }
    }

    disconnect() {
        this.connected = false;
        this.stopRefreshing();
    }

    localeValueChanged() {
        if (!this.connected) return;

        this.buildFormatters();
        this.load();
    }

    load() {
        const datetime = this.datetimeValue;
        const date = Date.parse(datetime);

        if (date !== date) {
            this.isValid = false;

            console.error(
                `Value given in 'data-timeago-datetime' is not a valid date (${datetime}). Please provide a ISO 8601 compatible datetime string. Displaying given value instead.`,
            );
        }

        this.element.dateTime = datetime;
        this.element.textContent = this.isValid
            ? this.format(this.distance(date))
            : datetime;
    }

    distance(date) {
        let duration = (date - Date.now()) / 1000;
        let index = this.includeSecondsValue ? 0 : 1;

        if (!this.includeSecondsValue) duration /= DIVISIONS[0].amount;

        while (index < DIVISIONS.length - 1 && Math.abs(duration) >= DIVISIONS[index].amount) {
            duration /= DIVISIONS[index].amount;
            index++;
        }

        const unit = DIVISIONS[index].unit;
        let value = Math.round(duration);

        if (Math.abs(value) === 0 && unit !== "second") {
            value = duration > 0 ? 1 : -1;
        }

        return { value, unit };
    }

    format({ value, unit }) {
        if (this.addSuffixValue) {
            return this.relativeTimeFormatter.format(value, unit);
        }

        return this.unitFormatter(unit).format(Math.abs(value));
    }

    buildFormatters() {
        this.formatterLocale = this.localeValue || document.documentElement.lang || undefined;
        this.relativeTimeFormatter = new Intl.RelativeTimeFormat(this.formatterLocale, { numeric: "auto" });
        this.unitFormatters = new Map();
    }

    unitFormatter(unit) {
        if (!this.unitFormatters.has(unit)) {
            this.unitFormatters.set(unit, new Intl.NumberFormat(
                this.formatterLocale,
                { style: "unit", unit, unitDisplay: "long" },
            ));
        }

        return this.unitFormatters.get(unit);
    }

    startRefreshing() {
        this.refreshTimer = setInterval(() => {
            this.load();
        }, this.refreshIntervalValue);
    }

    stopRefreshing() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }
}
