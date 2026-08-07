# Morph Guard

Keep an in-progress editor or long form alive while Turbo refreshes the rest of the page.

Use Morph Guard when a page receives background or broadcast-driven refreshes while someone may be typing. Without a
guard, an outer page morph can replace the editor's Turbo Frame and discard client-side state such as an unsaved draft,
selection, focus, undo history, or a third-party editor instance. With the guard, surrounding status, counters, and
lists can update while the active editing frame stays untouched.

**Identifier:** `turbo--morph-guard`

Package controllers auto-load from the vendor directory, so using the identifier is enough. No controller publishing or
optional npm dependency is required.

## Quick start

### 1. Enable page morphs

Configure the page to merge refresh responses into the existing DOM instead of replacing the whole body:

```blade
<hw:meta refresh />
```

Return a refresh Stream from the external update or broadcast path:

```php
return turbo_stream()->refresh(method: 'morph');
```

### 2. Guard the stateful UI

Put the editor inside a Turbo Frame with a stable, document-unique `id`, then add the controller to the editing surface:

```blade
<hw:frame id="post-editor">
    <hw:rich-text
        name="content"
        :value="$post->content"
        data-controller="turbo--morph-guard"
    />
</hw:frame>
```

That is the complete integration. The Rich Text component keeps its own `rich-text` controller and merges the guard
identifier. While it is connected, refresh morphs update the rest of the page without replacing `#post-editor`.

## How it works

1. On connect, the controller finds the nearest Turbo Frame with a usable unique `id`.
2. It temporarily adds `data-turbo-permanent` to that frame. Turbo then keeps the current frame when an outer page morph
   contains a matching frame.
3. The rest of the page continues to morph normally.
4. On disconnect, the controller removes the marker it owns, allowing the next refresh to reconcile the frame with the
   server.
5. Before Turbo caches the page, the controller removes its transient marker so the clone stays clean, then reacquires
   the live frame if the editor is still connected.

Morph Guard does not store, merge, or replay server updates. It only keeps the current browser DOM in place while the
editor is active.

## Ending the guard

Remove the guarded element or its `turbo--morph-guard` controller token when editing ends. The controller disconnects and
releases the frame, so the next morph can apply the latest server-rendered contents.

Updates skipped while guarded are not queued. Trigger another refresh after editing ends when the frame must catch up
immediately.

## Long forms

The same pattern works for long forms:

```blade
<hw:frame id="profile-editor">
    <hw:form
        :action="route('profile.update')"
        method="put"
        data-controller="turbo--morph-guard"
    >
        {{-- stateful fields --}}
    </hw:form>
</hw:frame>
```

The guard belongs on the stateful part of the interface, not necessarily on the frame itself. Mounting it directly on
the frame also works.

## Requirements

- The guarded element must be inside a `<turbo-frame>` with a stable, document-unique `id`.
- The external refresh must use Turbo's morph renderer.

Frames without a usable unique `id` and guarded elements outside a frame are ignored without error.

## Ownership and cleanup

Multiple guards may share one frame. The frame remains permanent until the final guard disconnects. If the application
or server already rendered `data-turbo-permanent`, Morph Guard preserves that marker instead of claiming or removing it.

Package-owned markers stay out of restoration snapshots. After Turbo clones the snapshot, a connected guard reacquires
the live frame so an upcoming refresh morph can still preserve it. Disconnect removes the final package-owned claim.
Pre-existing application markers remain untouched.

## Scope and limitations

Morph Guard protects the nearest frame only when an outer morph traverses that frame element. It intentionally does not
intercept Turbo render events or resolve server conflicts.

It does not protect against:

- Navigation or reload of the guarded frame itself, which replaces the frame's children.
- Plain Turbo Stream `replace` or `update` actions.
- `update method="morph"` targeting the guarded frame, because that morph starts at its children.
- Ordinary page replacement when the incoming document does not contain the same permanent frame.
- Error-page replacement.

Use it for temporary client-owned editing state that should win over external refresh morphs. Do not use it when every
server update must appear immediately, or without a deliberate reconciliation strategy for changes skipped during the
guard.

## Customization

Run `php artisan hotwire:controllers turbo/morph-guard` only when you want to publish the controller into your application
and customize its implementation.
