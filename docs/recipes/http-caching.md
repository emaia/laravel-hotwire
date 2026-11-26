# Cache public HTML with Hotwire

Cache pages and Frames whose URL always returns the same public HTML. Keep `FrameOrPage` routes, overlays with
per-opening IDs, Turbo Streams and session-backed forms at the origin. The cache policy is opt-in and does not
store responses or configure a CDN.

## Enable the guard

Prepend the guard to the global middleware in `bootstrap/app.php` so it sees the response after `StartSession`,
cookie middleware and Turbo's redirect handling have run:

```php
use Emaia\LaravelHotwire\Http\Middleware\EnforceCachePolicy;
use Illuminate\Foundation\Configuration\Middleware;

// In the application builder:
->withMiddleware(function (Middleware $middleware): void {
    $middleware->prepend(EnforceCachePolicy::class);
})
```

Declare `hotwire.cache:public` or `hotwire.cache:private` on the routes you want the guard to manage.
Routes without either declaration are unchanged. If both are declared, `private` wins. The guard neither
makes a private response public nor assigns a TTL. On a managed route, it preserves the application's policy only
for explicitly public HTML `200` responses to `GET` or `HEAD` with a positive `s-maxage` (or `max-age` when
`s-maxage` is absent), no session, no `Authorization`, and no `Set-Cookie`. It rejects streamed or encoded bodies,
`no-cache`, `no-store`, and `Vary` values other than `Accept-Encoding`. Other managed responses receive
`Cache-Control: private, no-store`.

## Put public routes outside `web`

Register a separate route file in the `then` callback of `withRouting(...)`. Include bindings if the routes need them,
but do not add `StartSession` or any middleware that personalizes the response:

```php
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

// In the `then` callback:
Route::middleware([SubstituteBindings::class, 'hotwire.cache:public'])
    ->group(base_path('routes/public.php'));
```

For example, `routes/public.php` can declare:

```php
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/stock', [ProductController::class, 'stock']);
```

Send an explicit lifetime from the public actions:

```php
return response()->view('products.show', compact('product'), 200, [
    'Cache-Control' => 'public, max-age=0, s-maxage=300',
]);
```

The stock action can always return `<hw:frame id="stock">...</hw:frame>`, regardless of `Turbo-Frame` or `Accept`.
If nested components generate IDs, use explicit IDs or render them in a fixed
[`ComponentId::withinScope()`](./stable-component-ids.md) scope so the header does not change the markup.
The same invariant applies to direct visits and prefetch. The guard cannot detect arbitrary Blade or middleware
that personalizes output: every public route must return the same representation regardless of incoming cookies,
locale headers, or Turbo headers. If language or filters change the output, include them in the URL and cache key.

## Keep interactive responses private

In `routes/web.php`, keep authorization, validation and CSRF in place:

```php
Route::middleware(['auth', 'hotwire.cache:private'])->group(function () {
    Route::get('/products/{product}/edit', [ProductController::class, 'edit']);
    Route::patch('/products/{product}', [ProductController::class, 'update']);
});
```

The edit action can use [FrameOrPage](../components/frame-or-page.md) for an overlay and a full-page fallback.
A successful submit can return a Turbo Stream and send a toast to `<hw:toaster :flash="false" />` on the public page.
If a redirect needs to render Laravel flash, errors, or old input, send it to a private page instead. Keep
`<hw:meta.csrf />` out of shared HTML. A form rendered in a private Frame has its own `_token`, but File Upload
reads the CSRF token from the document head; use a private document for that flow.

## Set the boundary at the cache

- Make only the canonical URLs eligible at the CDN. Bypass other HTML URLs and requests with `Authorization`
  before lookup. Preserve URL parameters that affect the response and respect `Cache-Control` from Laravel;
  do not force an Edge TTL or set conflicting CDN-specific headers.
- The HTTP guard does not control lookups by third-party Laravel cache middleware. Do not apply a response cache
  globally across public and private routes. Remove existing entries from both layers before enabling the new
  rules; headers on future responses cannot change a hit already served by a cache.
- Verify that a direct visit and a prefetch can hit the public cache, and that private Frame, Stream, and redirect
  requests still reach Laravel after warming the cache. Check both body and cache status, using two independent
  sessions for private forms and flash. Turbo's snapshot and prefetch caches are separate from HTTP caching;
  disable Turbo prefetch or snapshots on sensitive flows where appropriate.
