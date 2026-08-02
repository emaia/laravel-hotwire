# Frame Src

Injects the URL that rendered a form's Turbo Frame into `X-Turbo-Frame-Src`, allowing the server to redirect validation failures back to the same frame contents rather than the visible host page.

**Identifier:** `turbo--frame-src`
**Install:** `php artisan hotwire:controllers turbo/frame-src`

## Requirements

- `@hotwired/turbo`

## Usage

Add the controller to the form, its Turbo Frame, or another ancestor of the form. The request event bubbles from the form, so a sibling or descendant element will not receive it:

```html
<turbo-frame id="content" src="/posts/create">
    <form
        data-controller="turbo--frame-src"
        method="post"
        action="/posts"
    >
        <input name="title" />
        <button type="submit">Save</button>
    </form>
</turbo-frame>
```

If upgrading from a version that listened on `document`, move any instance mounted on a sibling or descendant to the form, frame, or a true form ancestor.

When the form is submitted inside the frame and validation fails, the `X-Turbo-Frame-Src` header identifies `/posts/create`, even if the visible page URL is still `/posts`. The server can redirect back to that source so Turbo finds the matching frame and displays validation errors.

The controller is intentionally scoped to same-frame submissions. If a form inside one frame targets a different frame, it does not guess which URL should render the validation response. Provide `_turbo_frame_src` or an explicit `X-Turbo-Frame-Src` for that application-specific flow.

## With the form component

If you're using `<hw:form>`, prefer `track-frame-src`. It includes a server-rendered hidden input containing the URL of the request that rendered the form:

```blade
<turbo-frame id="content" src="/posts/create">
    <hw:form action="/posts" method="post" track-frame-src>
        <hw:input name="title" />
        <button type="submit">Save</button>
    </hw:form>
</turbo-frame>
```

Both approaches complement each other: the deterministic hidden input has priority, while the controller provides a fallback header when no input is present. The server validates each candidate before using it as a redirect.

Custom explicit input or header values must be root-relative paths beginning with exactly one `/`, or absolute HTTP(S)
URLs whose host is trusted by the Turbo package. The controller resolves frame `src` values to absolute URLs before
sending them, so path-relative frame markup remains supported. See the [upgrade guide](../../upgrade.md) when migrating
published controllers or custom source values.

## How it works

1. On connect, the controller listens for `turbo:before-fetch-request` on its own element.
2. It handles only form requests that bubble through that element and whose `Turbo-Frame` header matches the form's nearest frame.
3. It leaves an explicitly supplied `X-Turbo-Frame-Src` untouched.
4. It uses the nearest frame `src`; an inline nested frame inherits the first `src` from a frame ancestor.
5. Relative sources are resolved against `document.baseURI`. A top-level inline frame with no source falls back to the document URL.
6. On the server, `TurboFormRequest` uses the header after `_turbo_frame_src` and before its internal session fallback.
7. On disconnect, the listener is removed.

The implementation supports both the plain header object emitted by current Turbo versions and a `Headers` instance supplied by another request listener. Header names are matched case-insensitively and controllers mounted in sibling frames cannot affect each other's requests.

If the nearest non-empty frame source is not a valid URL, the controller leaves the header unset rather than substituting
an ancestor or document URL. This keeps malformed markup from silently redirecting validation to unrelated content.
