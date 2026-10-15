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
for the current request. It creates a new instance on the family's default host. Optional `host:` selects a different
declared host of the same family; a missing or ambiguous host is reported rather than silently choosing another.
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

When the opening response belongs to an existing overlay request, its validated origin is included automatically and
the new instance is its child. To open as a child in another context, pass `origin:` explicitly. An invalid or retired
origin does not fall back to the topmost overlay. With no origin, opening is top-level. For normal multi-step navigation
inside an already open overlay, use an unqualified link inside its Frame instead of opening another instance.

## Return to a parent and close

The destination region in the parent and the child instance to close are different identities. With
`data-overlay-return-to` and `data-overlay-return-region` on the parent form, the client captures the **specific**
region at opening. Return HTML can then update only that region, even if other forms use the same local name:

```php
$origin = $request->overlayOrigin();

return turbo_stream()
    ->action('hotwire-overlay-return', $origin, view('customers._options', [
        'customers' => $customers,
        'selected' => $customer->id,
    ]))
    ->closeOverlay($origin)
    ->toast('success', 'Customer created');
```

`request()->overlayOrigin()` resolves a validated instance from a primary or nested Frame request. Without an
unambiguous overlay context it throws; it never guesses the first Modal, the default host, or the top of the stack.
For a static overlay without a Frame request, pass its root id explicitly:

```php
return turbo_stream()->closeOverlay('settings-dialog');
```

The static dialog closes through its normal animation and keeps its markup for reopening. For an instance already
closed or retired, the action is idempotent. `closeOverlay` is a Stream action, not an empty `update` that erases a
reusable host. The return action leaves the child open if its captured destination was replaced, and a following close
action in the same response respects that failure. See
[frame-backed overlay instances](../components/frame-or-page.md#frame-backed-overlay-instances) for the region contract.

If the destination already has a **known, unique DOM id**, a native `replace` or `update` Stream can be composed with
`closeOverlay($origin)` instead. Do not assume that the child Frame id also identifies the field in its parent.
Leaving out `closeOverlay()` keeps an overlay open for "Save and continue". A global `refresh` may discard other open
drafts; use it only when that is intentional. Stream order determines when actions start, not when CSS exit animations
finish. Enable `view-transition` on the layout host when needed; response metadata cannot toggle it per opening.

## Let a rich consumer render its own field

A reusable customer form need not know whether its caller uses a native select or a searchable Multi Select. A native
consumer uses the return fragment directly. A richer consumer can instead declare its own **same-origin GET renderer**
on a stable wrapper around the field, including labels and errors that must be updated with it:

```blade
<hw:form action="/sales" method="post">
    <hw:field name="company_id" label="Company">
        <hw:select :options="$companies" />
    </hw:field>
    <div data-overlay-return-region="customer"
         data-overlay-return-src="{{ route('sales.fields.customer') }}"
         data-overlay-return-fields="company_id">
        @include('sales._customer-field')
    </div>
    <hw:button as="a" href="/customers/create" frame="modal" data-overlay-return-to="customer">
        New customer
    </hw:button>
</hw:form>
```

The successful creation response adds the saved record's identity, alongside the native fallback fragment:

```php
return turbo_stream()
    ->action('hotwire-overlay-return', $request->overlayOrigin(), view('customers._options', $data), [
        'data-overlay-result' => (string) $customer->getKey(),
    ])
    ->closeOverlay($request->overlayOrigin())
    ->toast('success', 'Customer created');
```

For a region with a renderer, the package ignores the fallback fragment and requests its URL with `result=<id>` and
`context[company_id]=<captured value>`. Only explicitly declared, enabled scalar controls belonging to the initiating
form are captured; the draft is not serialized. The renderer receives a `Turbo-Frame` header scoped to the child opening
for generated component identities, but returns a **non-empty HTML fragment**, not a matching Frame or a page layout.
It owns field names, markup, filtering and selected values. Headers, result ids and captured context are not
authorization: authenticate/authorize and revalidate the selection on the server.

The context's control nodes and values are checked before and after the GET and again on retry. Replaced forms/regions,
changed renderer declarations or changed context expire the return without rediscovering another destination. A `409`
means the saved record cannot be selected for that context; the old field remains intact and the child stays open.
Redirects, empty/page responses, non-HTML responses and failed requests are not applied.

Creation and returning are separate operations. While rendering, a following `closeOverlay()` records close intent;
it closes only after the field is successfully updated. A temporary renderer failure offers **Retry return**, reusing
the saved identity without repeating the creation POST. The successful child form cannot be resubmitted during this
process. Closing/discarding its opening aborts the GET, and late/duplicate results cannot update another form.
Without a close action, a successful return does not implicitly close the child.

Frame-backed Blade hosts include the feedback template. Raw HTML hosts using this mode must include a
`template[data-hotwire-overlay-return-feedback]` with a focusable `[data-hotwire-overlay-return-error]` alert containing
`[data-hotwire-overlay-return-message]` and a `button[data-hotwire-overlay-return-retry]`. The package clones this template
instead of asking the application to manage pending/retry UI or overlay lifecycle in JavaScript.
