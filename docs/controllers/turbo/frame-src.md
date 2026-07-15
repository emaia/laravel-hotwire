# Frame Src

Injects the `X-Turbo-Frame-Src` header on Turbo Frame requests so the server can resolve the correct redirect URL when a form submission inside a frame fails validation.

**Identifier:** `turbo--frame-src`
**Install:** `php artisan hotwire:controllers turbo/frame-src`

## Requirements

- `@hotwired/turbo`

## Usage

Add the controller to any element inside a Turbo Frame — typically the form or the frame itself:

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

When the form is submitted inside the frame and validation fails, the `X-Turbo-Frame-Src` header tells the server which URL to redirect back to so the frame renders correctly instead of breaking out of its context.

## With the form component

If you're using `<hw:form>`, enable the `track-frame-src` prop which includes a server-side hidden input with the current URL (same purpose, no JS required):

```blade
<turbo-frame id="content" src="/posts/create">
    <hw:form action="/posts" method="post" track-frame-src>
        <hw:input name="title" />
        <button type="submit">Save</button>
    </hw:form>
</turbo-frame>
```

Both approaches complement each other — the directive provides the URL via form input (priority 1), while the controller provides it via HTTP header (priority 2). The server uses whichever is available.

## How it works

1. On connect, the controller registers a listener for `turbo:before-fetch-request` on `document`.
2. When a Turbo Frame request is about to be dispatched, it checks for the `Turbo-Frame` header.
3. If present, it sets `X-Turbo-Frame-Src` to `window.location.href` — the URL of the page that loaded the form.
4. On the server, `TurboFormRequest` uses this header (priority 2) to determine the redirect target on validation failure.
5. On disconnect, the listener is removed.
