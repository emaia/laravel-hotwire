# Turbo Gotchas & Debugging

Common pitfalls and solutions when working with Turbo.

---

## Frame Matching Issues

### Missing Frame in Response

```html
<!-- Request from frame id="products" -->
<!-- Response MUST contain matching frame -->

<!-- ❌ Error: frame not found -->
<div>Product details...</div>

<!-- ✅ Correct -->
<turbo-frame id="products">
    Product details...
</turbo-frame>
```

### Handle Missing Frame Gracefully

```javascript
document.addEventListener('turbo:frame-missing', (event) => {
    // Option 1: Do full page visit
    event.preventDefault();
    event.detail.visit(event.detail.response);

    // Option 2: Show error message
    // event.target.innerHTML = '<p>Content not available</p>';
});
```

### Frame ID Uniqueness

```html
<!-- ❌ Duplicate IDs cause issues -->
<turbo-frame id="item">Item 1</turbo-frame>
<turbo-frame id="item">Item 2</turbo-frame>

<!-- ✅ Use unique IDs -->
<turbo-frame id="item_1">Item 1</turbo-frame>
<turbo-frame id="item_2">Item 2</turbo-frame>
```

---

## Caching Problems

### Stale Content After Navigation

```javascript
// Clean up dynamic content before caching
document.addEventListener('turbo:before-cache', () => {
    // Remove modals
    document.querySelectorAll('.modal').forEach(m => m.remove());

    // Reset forms
    document.querySelectorAll('form').forEach(f => f.reset());

    // Close dropdowns
    document.querySelectorAll('.dropdown.open').forEach(d => {
        d.classList.remove('open');
    });
});
```

### Exclude Elements from Cache

```html
<!-- Element removed before caching -->
<div data-turbo-temporary>
    Flash message that shouldn't persist
</div>

<!-- Disable caching for page -->
<meta name="turbo-cache-control" content="no-cache">
```

### Persist Elements Across Navigation

```html
<!-- Audio player survives navigation -->
<div id="audio-player" data-turbo-permanent>
    <audio src="..."></audio>
</div>
```

Note: `data-turbo-permanent` requires stable `id` attribute.

---

## Form Submission Issues

### Form Not Submitting via Turbo

```html
<!-- ❌ External action bypasses Turbo by default -->
<form action="https://external.com/submit">

    <!-- ✅ Force Turbo (if needed) -->
    <form action="https://external.com/submit" data-turbo="true">

        <!-- Or explicitly disable -->
        <form action="https://external.com/submit" data-turbo="false">
```

### Redirect After Form Submission

```php
// ❌ Standard redirect may not work correctly with frames
return redirect()->route('items.index');

// ✅ Check if request wants Turbo Stream and respond accordingly
if (request()->wantsTurboStream()) {
    return turbo_stream()->append('items', view('items._item', compact('item')))->respond();
}

return redirect()->route('items.index');
```

### Stream Response for Non-GET Forms

Turbo automatically expects stream responses for POST/PUT/DELETE forms. If not returning streams, use a standard redirect:

```php
return redirect()->route('items.index');
```

### Validation Errors in Frames

Use `TurboFormRequest` so validation errors redirect back correctly within frames:

```php
use Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest;

class StoreItemRequest extends TurboFormRequest
{
    public function rules(): array
    {
        return ['title' => 'required|string|max:255'];
    }
}
```

Or handle manually — Laravel's default redirect-back behavior works with Turbo Frames since the frame re-renders with errors.

---

## JavaScript Initialization

### Scripts Not Running After Navigation

Turbo doesn't re-execute `<script>` tags in body. Use events:

```javascript
// ❌ Only runs on first page load
document.addEventListener('DOMContentLoaded', () => {
    initializeWidgets();
});

// ✅ Runs after every Turbo navigation
document.addEventListener('turbo:load', () => {
    initializeWidgets();
});

// ✅ Even better: use Stimulus
// Controllers automatically connect/disconnect
```

### Third-Party Libraries

```javascript
// Initialize on load, cleanup on cache
document.addEventListener('turbo:load', () => {
    window.datepickers = document.querySelectorAll('.datepicker');
    window.datepickers.forEach(el => new Datepicker(el));
});

document.addEventListener('turbo:before-cache', () => {
    window.datepickers?.forEach(el => el._datepicker?.destroy());
});
```

### Stimulus Integration (Recommended)

```javascript
// resources/js/controllers/lib/datepicker_controller.js
import { Controller } from '@hotwired/stimulus';
import Datepicker from 'datepicker';

export default class extends Controller {
    connect() {
        this.datepicker = new Datepicker(this.element);
    }

    disconnect() {
        this.datepicker.destroy();
    }
}
```

```html
<input type="text" data-controller="datepicker">
```

---

## Stream Issues

### Stream Not Processing

Check Content-Type header. It must be `text/vnd.turbo-stream.html`. The `turbo_stream()->respond()` helper and `response()->turboStream()` macro set this automatically. If building manually, ensure the header is set.

### Target Not Found

```html
<!-- ❌ Target doesn't exist -->
<turbo-stream action="append" target="nonexistent">
    <template>Content</template>
</turbo-stream>

<!-- Verify target exists in DOM -->
<div id="messages">...</div>
<turbo-stream action="append" target="messages">
```

### Multiple Streams in Response

```php
// ✅ Multiple streams work fine
return turbo_stream()
    ->append('items', view('items._item', compact('item')))
    ->update('count', '<span>42</span>')
    ->remove('empty-state')
    ->respond();
```

Or in Blade:

```blade
<x-turbo::stream action="append" target="items">
    @include('items._item', ['item' => $item])
</x-turbo::stream>

<x-turbo::stream action="update" target="count">
    <span>42</span>
</x-turbo::stream>

<x-turbo::stream action="remove" target="empty-state" />
```

---

## URL and History Issues

### Frame Navigation Not Updating URL

```html
<!-- Add data-turbo-action to update browser URL -->
<turbo-frame id="products" data-turbo-action="advance">
```

### Wrong URL After Form Submission

```php
// Use standard redirect — Laravel sends 302 by default, which Turbo follows
return redirect()->route('items.index');
```

### Back Button Shows Stale Data

```javascript
// Force fresh fetch on restoration visits
document.addEventListener('turbo:before-render', (event) => {
    if (event.detail.isPreview) {
        // This is a cached preview, fresh content follows
    }
});
```

---

## Lazy Loading Gotchas

### Lazy Frame Not Loading

```html
<!-- Frame must be in viewport to trigger -->
<turbo-frame id="comments" src="/comments" loading="lazy">
    <p>Loading...</p>
</turbo-frame>

<!-- If frame is hidden, it won't load -->
<div style="display: none">
    <turbo-frame id="hidden" src="/data" loading="lazy">
        <!-- Never loads! -->
    </turbo-frame>
</div>
```

### Lazy Frames with Turbo Drive

Known issue: lazy frames may not load after Turbo Drive navigation if they weren't on the first page.

Workaround:

```html
<!-- Use eager loading for critical content -->
<turbo-frame id="nav" src="/nav" loading="eager">
```

---

## Performance Tips

### Avoid Large Stream Responses

```php
// ❌ Sending entire list
turbo_stream()->replace('items', view('items.index', compact('allItems')))->respond();

// ✅ Send only the new item
turbo_stream()->append('items', view('items._item', compact('item')))->respond();
```

### Preload Critical Pages

```html
<a href="/dashboard" data-turbo-preload>Dashboard</a>
```

### Disable Prefetch for Heavy Pages

```html
<a href="/reports" data-turbo-prefetch="false">Heavy Report</a>
```

---

## Debugging

### Enable Turbo Debug Mode

```javascript
// In browser console
Turbo.session.drive  // Check if Drive is enabled

// Log all Turbo events
    ['click', 'before-visit', 'visit', 'before-cache', 'before-render',
    'render', 'load', 'frame-load', 'frame-render'].forEach(name => {
    document.addEventListener(`turbo:${name}`, (e) => {
        console.log(`turbo:${name}`, e);
    });
});
```

### Check Frame State

```javascript
const frame = document.querySelector('turbo-frame#products');
console.log({
    id: frame.id,
    src: frame.src,
    loading: frame.loading,
    disabled: frame.disabled,
    complete: frame.complete
});
```

### Network Tab

1. Open DevTools Network tab
2. Filter by "Fetch/XHR"
3. Check request headers for `Turbo-Frame`
4. Check response Content-Type for streams

### Common Console Errors

| Error                            | Cause                                     |
|----------------------------------|-------------------------------------------|
| "Response has no matching frame" | Response missing `<turbo-frame id="...">` |
| "Form responses must redirect"   | POST without redirect or stream           |
| Stream not processing            | Wrong Content-Type header                 |

---

## Real-time Stream Source Issues

### Stream Source Placement

```html
<!-- ❌ In <head> - persists across navigation, duplicates -->
<head>
    <turbo-stream-source src="...">
</head>

<!-- ✅ In <body> - properly managed -->
<body>
    <turbo-stream-source src="...">
</body>
```

### Connection Not Established

Check browser console for WebSocket/SSE errors. Verify the URL is correct and the server is running.
