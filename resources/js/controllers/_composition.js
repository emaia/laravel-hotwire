// @hotwire-package
export function isComposing(event) {
    return Boolean(event?.isComposing || event?.keyCode === 229);
}
