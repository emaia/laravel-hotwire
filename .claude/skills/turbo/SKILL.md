---
name: turbo
description: Hotwire Turbo => SPA-like speed with zero JavaScript. Covers Drive (full-page navigation), Frames (partial page sections), and Streams (multi-target updates). Use when building ajax navigation, lazy-loaded page sections, inline editing, pagination without reload, modals loaded from the server, flash messages via streams, real-time updates, or multi-section page updates. Code triggers => turbo-frame, turbo-stream, data-turbo-frame, data-turbo, data-turbo-action, turbo_stream(), TurboResponse, wantsTurboStream(), wasFromTurboFrame(), dom_id(), dom_class(), x-turbo::stream, x-turbo::frame. Also trigger when the user asks "how to update part of the page without reload", "how to make navigation feel like SPA", "how to lazy-load a section", "how to do inline editing", "how to push real-time updates from server". Do NOT trigger for client-side JS behavior (use stimulus) or reusable static UI.
---

# Turbo

Hotwire Turbo provides SPA-like speed with server-rendered HTML. No JavaScript to write. Three components work together:

- **Drive** -- Automatic AJAX navigation for all links and forms (zero config)
- **Frames** -- Scoped navigation that updates only one section of the page
- **Streams** -- Server-pushed DOM mutations (append, replace, remove, etc.)

## This Package's Setup

Turbo is provided by the `emaia/laravel-hotwire-turbo` dependency. It's installed automatically with `laravel-hotwire`.

```bash
composer require emaia/laravel-hotwire
php artisan hotwire:install  # sets up JS/CSS scaffolding including Turbo imports
```

## Decision Tree

```
Need to update the page?
+-- Full page navigation          -> Turbo Drive (automatic, already active)
+-- Single section from user click -> Turbo Frame
+-- Multiple sections from action  -> Turbo Stream (HTTP response)
+-- Real-time from server/others   -> Turbo Stream (SSE / WebSocket)
```

## Turbo Drive

Automatic SPA-like navigation. Every `<a>` click and `<form>` submit is intercepted, fetched via AJAX, and the `<body>`
is swapped. The browser URL and history update normally.

### Disabling for Specific Elements

```html
<!-- Disable on link/form -->
<a href="/external" data-turbo="false">External Link</a>

<!-- Disable for entire section -->
<div data-turbo="false">
    <a href="/normal">Normal link (no Turbo)</a>
</div>
```

### Blade Directives (in `<head>`)

```blade
@turboNocache
@turboNoPreview
@turboRefreshMethod('morph')
@turboRefreshScroll('preserve')
```

## Turbo Frames

Scope navigation to a section of the page. Links and forms inside a frame update only that frame's content.

### Blade Component

```blade
{{-- Basic frame --}}
<x-turbo::frame id="user-profile">
    @include('users.profile', ['user' => $user])
</x-turbo::frame>

{{-- Lazy-loaded frame --}}
<x-turbo::frame id="comments" src="/posts/{{ $post->id }}/comments" loading="lazy">
    <p>Loading comments...</p>
</x-turbo::frame>

{{-- Frame that navigates the whole page --}}
<x-turbo::frame id="navigation" target="_top">
    <a href="/dashboard">Dashboard</a>
</x-turbo::frame>
```

### Frame Matching

The server response must contain a `<turbo-frame>` (or `<x-turbo::frame>`) with the same `id`. Turbo extracts only the matching frame.

## Turbo Streams

Update multiple DOM elements from a single server response.

### Fluent Builder (Recommended)

```php
use Emaia\LaravelHotwireTurbo\Turbo;

// Respond with streams
return turbo_stream()
    ->append('messages', view('messages.item', compact('message')))
    ->remove('modal')
    ->update('counter', '<span>42</span>');

// With custom status code (e.g. validation errors)
return turbo_stream()
    ->replace('form', view('form', ['errors' => $errors]))
    ->withResponse(422);
```

### Conditional Response

```php
if (request()->wantsTurboStream()) {
    return turbo_stream()->remove(dom_id($message));
}

return redirect()->route('messages.index');

// Scoped to a specific frame
if (request()->wasFromTurboFrame('modal') && request()->wantsTurboStream()) {
    return turbo_stream()->update('modal-content', view('messages.edit', compact('message')));
}

return view('messages.edit', compact('message'));
```

### DOM Helpers

```php
$message = Message::find(15);

dom_id($message)            // "message_15"
dom_id($message, 'edit')    // "edit_message_15"
dom_class($message)         // "message"

// In Blade
<div id="@domid($message)">{{ $message->body }}</div>
```

### Stream Blade Templates

```blade
{{-- resources/views/messages/streams/created.blade.php --}}
<x-turbo::stream action="append" target="messages">
    @include('messages._message', ['message' => $message])
</x-turbo::stream>

<x-turbo::stream action="update" target="message-count">
    <span>{{ $count }}</span>
</x-turbo::stream>

<x-turbo::stream action="remove" target="new-message-form" />
```

```php
// Return a stream view
return turbo_stream_view('messages.streams.created', compact('message', 'count'));
```

### Available Actions

| Action | Description |
|--------|-------------|
| `append` | Add content at end of target |
| `prepend` | Add content at start of target |
| `replace` | Replace entire target element |
| `update` | Replace target's innerHTML |
| `remove` | Remove target element |
| `before` | Insert content before target |
| `after` | Insert content after target |
| `morph` | Morph the target element |
| `refresh` | Trigger page refresh |

### Request Detection

```php
// Check if request wants Turbo Stream response
if (request()->wantsTurboStream()) { ... }

// Check if request came from a Turbo Frame
if (request()->wasFromTurboFrame()) { ... }
if (request()->wasFromTurboFrame('modal')) { ... }
```

### Testing

```php
use Emaia\LaravelHotwireTurbo\Testing\InteractsWithTurbo;

class MessageControllerTest extends TestCase
{
    use InteractsWithTurbo;

    public function test_delete_returns_stream()
    {
        $this->turbo()
            ->delete("/messages/{$message->id}")
            ->assertTurboStream(fn ($streams) => $streams
                ->has(1)
                ->hasTurboStream(fn ($stream) => $stream
                    ->where('action', 'remove')
                    ->where('target', dom_id($message))
                )
            );
    }
}
```

## Full Controller Example

```php
class MessageController extends Controller
{
    public function store(Request $request)
    {
        $message = Message::create($request->validated());

        if (request()->wantsTurboStream()) {
            return turbo_stream()
                ->append('messages', view('messages.item', compact('message')))
                ->update('message-form', view('messages.form'))
                ->update('message-count', '<span>' . Message::count() . '</span>');
        }

        return redirect()->route('messages.index');
    }

    public function destroy(Message $message)
    {
        $message->delete();

        if (request()->wantsTurboStream()) {
            return turbo_stream()->remove(dom_id($message));
        }

        return redirect()->route('messages.index');
    }
}
```

## Key Principles

**Server returns full HTML pages.** Turbo works best when the server always returns a complete, valid HTML page. Turbo
Drive replaces the body, Turbo Frames extract the matching frame.

**Frame IDs must match.** The frame in the response must have the same `id` as the frame on the page.

**Streams are for side effects.** Use Streams when a single action needs to update multiple unrelated parts of the page.
If you're only updating one section, a Frame is simpler.

**Stimulus complements Turbo.** Turbo handles navigation and server communication. Stimulus handles client-side
behavior (animations, toggles, clipboard). They work together -- Stimulus controllers survive Turbo Frame swaps within
their scope, and reconnect properly on Drive navigation.

## References

- **Full API** (Drive events, Frame attributes, Stream actions): [references/api.md](references/api.md)
- **Patterns** (forms, modals, search, pagination, inline editing): [references/patterns.md](references/patterns.md)
- **Gotchas** (caching issues, form handling, Stimulus integration): [references/gotchas.md](references/gotchas.md)
