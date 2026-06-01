# Slug

Autofills a slug field from a source input (typically a title) as the user types and stops the moment the user takes
control — either by editing the slug directly or by loading a page where the slug is already filled (edit screens).

**Identifier:** `slug`  
**Install:** `php artisan hotwire:controllers slug`

## Requirements

- No external dependencies.

> The generated slug is a **UX preview**. Always generate and validate the canonical slug on the server (`Str::slug`,
> uniqueness checks, etc.) — never trust the client value.

## Targets

| Target    | Required | Description                                                         |
|-----------|:--------:|---------------------------------------------------------------------|
| `source`  |    ✅     | The input to read from (the title). Listened to on `input`          |
| `slug`    |    ✅     | The input that holds and submits the slug                           |
| `preview` |    —     | An element whose text content mirrors the slug (e.g. a URL preview) |

## Stimulus Values

| Value        | Type      | Default | Description                                                          |
|--------------|-----------|---------|----------------------------------------------------------------------|
| `separator`  | `String`  | `-`     | Word separator used when generating the slug                         |
| `auto`       | `Boolean` | `true`  | When `false`, the field starts locked (fully manual slug)            |
| `max-length` | `Number`  | `0`     | `0` means no limit; otherwise the generated slug is truncated to fit |

## Behavior

- **On connect:** the controller locks if the slug is already filled (edit page) or `auto` is `false`. Otherwise, it
  generates once from the source — handy when the title is repopulated via `old()` after a validation error.
- **Typing in the source** regenerates the slug, unless locked.
- **Typing in the slug** locks it: the user has taken over, and the source no longer overwrites it.
- **`max-length`** applies only to the generated slug, truncating at the last separator before the limit (falling back
  to a hard cut for a single long word). It does not constrain manual typing — add `maxlength` to the input for that.
- The current state is reflected on the root element as `data-slug-locked="true|false"` so you can show or hide a
  "relink" control with CSS.

Setting the slug value programmatically does not fire an `input` event, so syncing never re-triggers the lock.

## Basic usage

```html

<div data-controller="slug">
    <input name="title" data-slug-target="source"/>
    <input name="slug" data-slug-target="slug"/>
</div>
```

## With a URL preview

```html

<div data-controller="slug">
    <input name="title" data-slug-target="source"/>
    <input name="slug" data-slug-target="slug"/>

    <p class="text-sm text-gray-500">example.com/blog/<span data-slug-target="preview"></span></p>
</div>
```

## With a max length and a "relink" button

The `relink` action unlocks the field and regenerates from the source. Pair it with `data-slug-locked` to only show
the button once the user has edited the slug:

```html

<div data-controller="slug" data-slug-max-length-value="60">
    <input name="title" data-slug-target="source"/>

    <input name="slug" data-slug-target="slug"/>
    <button type="button" data-action="slug#relink" class="hidden in-data-[slug-locked='true']:inline">
        Regenerate from title
    </button>
</div>
```

## Edit pages

On an edit screen the slug input already has a value, so the controller starts locked and never clobbers it:

```html

<div data-controller="slug">
    <input name="title" data-slug-target="source" value="{{ old('title', $post->title) }}"/>
    <input name="slug" data-slug-target="slug" value="{{ old('slug', $post->slug) }}"/>
</div>
```
