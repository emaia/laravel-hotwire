// @hotwire-package
// Shared by file-preserve and reset-files: both treat a re-rendered form that
// contains a field marked aria-invalid="true" as a failed (validation) submit.
export function formHasErrors(element) {
    const form = element.closest("form");
    return !!form && form.querySelector('[aria-invalid="true"]') !== null;
}
