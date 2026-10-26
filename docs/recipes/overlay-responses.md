# Overlay responses

Use one frame-backed host of each type in the layout. A route can still serve its standalone page through
[`<hw:frame-or-page>`](../components/frame-or-page.md); a server decision can instead open a new overlay from a
Turbo Stream response. The two transports share the same presentation and instance lifecycle.

```blade
<hw:modal frame="modal" />
<hw:sheet frame="sheet" />
<hw:drawer frame="drawer" />
```

## Open from a server response

`response()->modal()`, `response()->sheet()` and `response()->drawer()` select the family. `open()` returns a
`TurboStreamBuilder`, so it can be returned directly or chained with other Stream operations:

```php
return response()->modal()->open(
    view('customers._create-form'),
    size: 'lg',
    source: route('customers.create'),
)->toast('info', 'Complete the customer record');
```

`open()` accepts a **content fragment**, not a full page layout or a `frame-or-page` view that chooses its page branch
for the current request. It creates a new instance on the family's host, whatever its Frame id: the layout declares
one host per family. A missing host, or two hosts of the same family, is reported in the console rather than silently
choosing one. Open an extra host of the same family through a Frame link to its id instead.
Pass a **View instance** rather than an already rendered string when the fragment contains package components:
`open(view('customers._form'), ...)` renders their generated ids in an isolated opening scope. Explicit application ids
and ids already embedded in strings remain application-owned; keep them unique across simultaneous instances.
`side:` accepts Sheet sides or Drawer directions (`top`/`bottom` normalize to `up`/`down` for Drawer). Modal accepts
`size:` but no `side:`. The options use the same validation as frame links and `<hw:frame-or-page>` responses.

For Stream-inserted forms, `source:` is required: use a same-origin URL that renders the form as a matching Frame on
validation failure. The package sets the form's `_turbo_frame_src` to that URL and does not inherit the host's old
`src` or redirect to the background page. `track-frame-src` remains useful for forms loaded by ordinary Frame
navigation. `source:` accepts a root-relative or same-origin absolute URL. A form without it is rejected. A streamed
form should submit inside the overlay's Frame; validation redirects then reuse that concrete instance.

Without `origin:`, the opening is **top-level**, even when the response belongs to an overlay request. Pass the
requesting overlay to open a **child** that is retired together with it:

```php
// Ambiguities found while importing: review them on top of the import form.
return response()->modal()->open(view('imports._review', $data), source: route('imports.review'), origin: $request->overlayOrigin());

// The sale is confirmed: close its Drawer and ask about the receipt in a separate Modal.
return response()->modal()->open(view('sales._receipt-prompt'))
    ->closeOverlay($request->overlayOrigin())
    ->toast('success', 'Sale confirmed');
```

Opening the receipt prompt as a child of the Drawer would retire it as soon as `closeOverlay()` closes the Drawer. An
invalid or retired origin does not fall back to the topmost overlay. `origin:` may also name a uniquely identified,
currently open static Modal, Sheet or Drawer root; closing that root retires its generated children without erasing the
static markup. For normal multi-step navigation inside an already open overlay, use an unqualified link inside its
Frame instead of opening another instance.

Keep workflows to at most three nested overlay levels as a UX guideline; deeper flows should be redesigned rather than
treated as unlimited nesting. This is a recommendation, not a runtime depth limit.

## Return to a parent and close

The destination region in the parent and the child instance to close are different identities. Mark the region with
[`<hw:overlay-return>`](../components/overlay-return.md) and select it with `overlay-return` on the opening link; the
client captures that **specific** region at opening. The child's response then updates only that region, even if other
forms use the same local name:

```php
$origin = $request->overlayOrigin();

return turbo_stream()
    ->returnFromOverlay($origin, view('sales._customer-field', [
        'customers' => $customers,
        'selected' => $customer->id,
    ]))
    ->closeOverlay($origin)
    ->toast('success', 'Customer created');
```

`request()->overlayOrigin()` resolves a validated instance from a primary or nested Frame request. It returns `null` for
a page or an ordinary Frame request, so the same action can redirect when it was not opened in an overlay:

```php
$origin = $request->overlayOrigin();

if ($origin === null) {
    return to_route('customers.index')->with('success', 'Customer created');
}
```

It never guesses the first Modal, the default host, or the top of the stack, and it throws when the overlay headers
contradict each other. For a static overlay without a Frame request, pass its root id explicitly:

```php
return turbo_stream()->closeOverlay('settings-dialog');
```

The static dialog closes through its normal animation and keeps its markup for reopening. For an instance already
closed or retired, the action is idempotent. `closeOverlay` is a Stream action, not an empty `update` that erases a
reusable host. The return leaves the child open if its captured destination was replaced, and a following close action
in the same response respects that failure. The saved form is blocked against accidental resubmission; close the child
and review the parent instead of creating the record again. Rich/contextual fields use an application-owned fragment
or native Turbo Frame; see
[let the parent render its own field](../components/overlay-return.md#let-the-parent-render-its-own-field).

If the destination already has a **known, unique DOM id**, a native `replace` or `update` Stream can be composed with
`closeOverlay($origin)` instead. Do not assume that the child Frame id also identifies the field in its parent.
Leaving out `closeOverlay()` keeps an overlay open for "Save and continue". A global `refresh` may discard other open
drafts; use it only when that is intentional. Stream order determines when actions start, not when CSS exit animations
finish. Enable `view-transition` on the layout host when needed; response metadata cannot toggle it per opening.

## Detect an overlay for rendering

Use `request()->wasFromOverlay(?string $host = null): bool` when rendering only needs a yes/no decision:

```php
return view('customers.edit', [
    'customer' => $customer,
    'compact' => $request->wasFromOverlay(host: 'customer-editor'),
]);
```

The named `host:` argument matches the **canonical host id**, exactly and case-sensitively, not the overlay family.
For `<hw:modal frame="customer-editor" />`, use `host: 'customer-editor'`, not `host: 'modal'`. Family filtering is
deliberately unsupported: requests do not carry the family, and adding it would require a separate client protocol
change. Do not infer it from a host name.

Without an argument (or with `host: null`), the predicate is equivalent to `overlayOrigin() !== null`, including requests
from internal Frames. A page or ordinary Frame returns `false`, even if the Frame is named `modal`, `sheet` or `drawer`.
An internal Frame retains its own `Turbo-Frame` id; detecting its owning overlay does not select the outer host's
`frame-or-page` branch. For declarative page/Frame markup, prefer `frame-or-page.frame` and `frame-or-page.page`.

Both request helpers validate the same context. Invalid or contradictory headers throw `InvalidArgumentException`,
even when the host filter would not match. An empty or invalid host filter also throws; it must start with an ASCII
letter or digit, contain only letters, digits, `_`, `.`, `:` or `-`, and be at most 100 characters long.
In middleware or layouts, an old or forged inconsistent header can therefore cause HTTP 500 under default exception
handling. This API does not translate those failures to HTTP 400. Neither helper authorizes access, authenticates the
headers, nor proves that an instance is still open in the browser.

The client already identifies the opening that owns the destination Frame, not the top of the stack. The server uses
that validated origin without falling back to an ancestor. Static overlays without instance context cannot be inferred.
For application-authored Frames inside static overlays only, an app may deliberately combine
`wasFromOverlay(host: 'modal') || wasFromTurboFrame('modal')` for compatibility. This is not needed for
`<hw:modal frame="modal" />`, which already creates instances and sends their context.

## Refresh is not a close action

Prefer targeted `replace`/`update` operations plus `closeOverlay($origin)` when other overlays hold drafts. A native
`refresh` remains a **page-level** operation: Turbo decides whether to ignore it (for example, because its `request-id`
belongs to a recent local request) or visit the current page. Overlays neither cancel nor replay that action. An ignored
refresh leaves their drafts open; an accepted page replacement or morph discards generated instances with the old page.
Do not use `refresh` as a promise that the parent form will survive.

Likewise, `closeOverlay($origin)->refresh()` does not wait for exit motion before visiting. A family's `open(...)`
starts the builder and can be followed by other Stream actions, but there is no animation transaction, delayed-close
macro or implicit "close on any HTTP success".
If the server needs a new step in the same instance, return the matching Frame rather than closing and reopening it.
An `open(...)` correlated to that origin creates its child, not a replacement sibling: closing the ancestor retires
that child too. Opening from an already closed origin is rejected rather than attached to another active overlay.

## Loading and validation sources

A host opening respects direct or inherited `data-turbo="false"` and the configured Turbo confirmation callback.
Links with `data-turbo-method` or `data-turbo-stream` keep Turbo's native transport and target, rather than being
converted into a GET opening. Use a family response to open a new instance from such an action. Requests targeting `_top`
receive no overlay headers. A nested Frame targeting the primary Frame gets the primary instance context; requests
remaining in an internal Frame carry only their enclosing origin.

Origin resolution prefers the matching enclosing Frame on the request source, so repeated inner Frame IDs in stacked
instances do not cross-correlate their requests.

A Frame link to a canonical host is an **explicit opening**. Its generated primary Frame loads eagerly even if the
hand-written host uses `loading="lazy"`, since a hidden overlay cannot intersect the viewport to begin its own request.
This does not change the canonical host or any nested Frames: a `loading="lazy"` Frame inside the form still waits for
visibility normally. Closing a generated instance retires its opening; a late response cannot populate a new instance.

`open(view(...), source: ...)` instead inserts its already-rendered fragment without fetching `source` first. The URL
is the validation/re-render source, **not** a second lazy content request. Use a Frame link when content itself should
be fetched on demand. Nested lazy Frames can be used in either transport, but their ids must be unique per instance;
pass explicit scoped ids for application-authored Frames.

## JavaScript loading

The default lazy loader imports a controller when its `data-controller` appears in the DOM, not when its overlay opens.
A hidden host in the layout therefore loads its controller and shared helpers immediately after discovery. Modal, Sheet
and Drawer share those dependencies rather than downloading a separate copy for each family.

A page with no overlay controllers does not load this code unless the application opts into eager loading or preloading.
An eager Modal also brings its helpers into the initial JavaScript graph. Measure production chunks with the application's
actual loader configuration; lazy Frame content and lazy controller imports are separate concerns.

## Public entries and compatibility

- `response()->modal()`, `sheet()` and `drawer()` return a family-specific `OverlayResponse` entry.
- `open(string|Htmlable $content, ?string $side = null, ?string $size = null, ?string $source = null,
  ?string $origin = null)` returns the existing `TurboStreamBuilder`; use named optional arguments. Without `origin` the
  opening is top-level.
- `request()->overlayOrigin(): ?string` resolves validated request context, returns `null` without overlay context and
  throws on contradictory headers; it is not an authorization check.
- `request()->wasFromOverlay(?string $host = null): bool` detects validated overlay context; `host` matches the
  canonical host id, not a family.
- `turbo_stream()->closeOverlay(string $origin)` returns the builder and requires an explicit concrete Frame or static
  root id. There is no default host, stack-top fallback or family-specific close alias.
- `turbo_stream()->returnFromOverlay(string $origin, string|Htmlable $content)` returns the builder and replaces the
  captured region's contents with the supplied HTML. It does not load a separate renderer or wait for nested Frames.
- `<hw:button>` accepts `overlay-side`, `overlay-size` and `overlay-return`; `overlay` names the family only when `frame`
  is not `modal`, `sheet` or `drawer`. [`<hw:overlay-return>`](../components/overlay-return.md) marks the parent region.
- Macros the application registers before the package boots are kept; registration is idempotent. Existing Stream
  actions are also preserved.

Use `closeOverlay()` for all three families, including static roots. No marker controller or application-defined
`closeModal`/`autoCloseOverlay` macro is needed.
Empty `update`/`replace` Streams targeting a concrete primary Frame remain supported for content-driven closing, but
must not target the canonical host to close a generated child.
