---
name: laravel-hotwire-turbo-workflows
description: >-
  Build Laravel Hotwire workflows with Turbo Frames, Turbo Streams, morph refreshes, request detection, DOM helpers,
  frame-aware validation, cache handling, and optimistic updates.
---
@php
    $prefix = config('hotwire.prefix', 'hw');
    $component = static fn (string $name): string => "<{$prefix}:{$name}>";
    $open = static fn (string $name, string $attributes = ''): string => "<{$prefix}:{$name}".($attributes === '' ? '' : " {$attributes}").'>';
    $close = static fn (string $name): string => "</{$prefix}:{$name}>";
@endphp
# Laravel Hotwire Turbo Workflows

## When to use

Load this skill for partial navigation, lazy sections, inline editing, Streams, morph refreshes, Turbo cache behavior,
optimistic updates, or responses that differ between Frames and full-page requests. Load `laravel-hotwire-forms` for the
field/control contract and detailed validation markup.

## Choose the smallest response

1. Use a redirect for ordinary successful mutations and non-JavaScript fallback.
2. Use a regular Frame response when only the requesting frame changes. The response must contain the matching frame id.
3. Use a Turbo Stream response when one mutation changes multiple independent DOM targets.
4. Use an optimistic template only when immediate provisional feedback materially improves the interaction; always
   reconcile it with server-authoritative HTML.

## Request and DOM helpers

```php
$request->wantsTurboStream();
$request->wasFromTurboFrame();
$request->wasFromTurboFrame('post-modal');
$request->turboFrameId();
$request->turboFrameSource();

dom_id($post);              // post_123
dom_id($post, 'edit');      // edit_post_123
dom_class($post);           // post
```

Treat frame source values as redirect hints, never authorization evidence.

## Stream builder

Return `turbo_stream()` directly. Chain `append`, `prepend`, `replace`, `update`, `remove`, `before`, `after` or
`refresh`; `*All` variants target CSS selectors. `replace` and `update` accept `method: 'morph'`; there is no standalone
`morph()` stream action. Use `withResponse()` only when status or headers must differ.

```php
public function update(UpdatePostRequest $request, Post $post)
{
    $post->update($request->validated());

    if ($request->wantsTurboStream()) {
        return turbo_stream()
            ->replace(dom_id($post), view('posts._post', compact('post')))
            ->update('post-modal')
            ->toast('success', 'Post updated');
    }

    return redirect()->route('posts.show', $post);
}
```

Builder strings are HTML by default. Render a trusted Blade view or call `escape()` for plain user text. `view()`,
`partial()` and `escape()` modify the previously added stream, so add an action first.

```php
return turbo_stream()
    ->refresh(method: 'morph', scroll: 'preserve', requestId: $request->turboRequestId())
    ->withResponse(422);
```

## Frame-or-page responses

Use `{!! $component('frame-or-page') !!}` when one template must serve both direct navigation and a Frame request.
Provide exactly one of `frame` or `frames`; multiple frames require a layout.

```blade
{!! $open('frame-or-page', 'frame="post-modal" layout="dashboard"') !!}
    {!! $open('modal.title') !!}Edit post{!! $close('modal.title') !!}
    ...
{!! $close('frame-or-page') !!}
```

A frame-backed overlay root already owns its frame. Do not render another frame with the same id.

## Frame-aware validation

When the form GET URL and mutation URL differ, combine `{!! $component('form') !!}` `track-frame-src` with
`Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest`. On validation failure it chooses a validated frame source and
redirects to a URL that can render the matching frame. Keep a normal redirect fallback for full-page/non-JavaScript use.

## Optimistic updates

`{!! $component('optimistic') !!}` emits a stream template; it does not mount a dispatcher. Put `optimistic--form` on a
form, `optimistic--link` on a link, or call `optimistic--dispatch#dispatch` for a custom trigger.

```blade
{!! $open('form', 'action="/messages" method="post" data-controller="optimistic--form" data-optimistic--form-reset-value="true"') !!}
    {!! $open('textarea', 'name="content"') !!}{!! $close('textarea') !!}

    {!! $open('optimistic', 'target="messages" action="append"') !!}
        <article>
            <p data-field="content"></p>
            <small>Sending...</small>
        </article>
    {!! $close('optimistic') !!}

    {!! $open('button', 'type="submit"') !!}Send{!! $close('button') !!}
{!! $close('form') !!}
```

The dispatcher writes form values through `textContent`, marks inserted roots with `data-optimistic`, and can reset a
form only after a successful Turbo submission. Reconcile success and rejection with server HTML, commonly a morph
refresh. Mark transient optimistic DOM `data-turbo-temporary` or otherwise prevent it from surviving Turbo cache.

## Turbo lifecycle rules

- Make stream actions idempotent where retries or broadcasts can repeat them.
- Preserve focus-sensitive nodes with stable ids and morph rules rather than replacing broad page regions.
- Clean transient UI in `turbo:before-cache`; cached snapshots must not preserve open overlays or provisional state.
- Coordinate controllers listening to `turbo:submit-end` around `event.detail.success`.
- Prefer one authoritative server render over manually keeping several client-side copies in sync.

## Verify

Run `php artisan hotwire:docs frame-or-page --component`, `php artisan hotwire:docs optimistic --component`, and
`php artisan hotwire:check`.

## See also

- `laravel-hotwire-forms` for controls, errors and the complete frame-hosted form recipe.
- `laravel-hotwire-ui-development` for frame-backed and nested overlays.
- `laravel-hotwire-stimulus-controllers` for custom Turbo event listeners and cleanup.
