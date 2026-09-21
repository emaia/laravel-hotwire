# OEmbed

Converts editor-generated `<oembed url="...">` elements into YouTube or Vimeo iframes, or fallback links. Use it when a
rich text document can contain an unknown number of embeds.

**Identifier:** `oembed`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers oembed`.

## Requirements

- No external dependencies.

## Supported providers

| Provider | Recognized URL formats                                                           |
|----------|----------------------------------------------------------------------------------|
| YouTube  | `youtube.com/watch?v=`, `youtube.com/embed/`, `youtube.com/shorts/`, `youtu.be/` |
| Vimeo    | `vimeo.com/{id}`                                                                 |

Unrecognized URLs become links with `target="_blank"` and `rel="noopener noreferrer"`.

## Usage

Mount the controller around the rendered editor document:

```blade
<article data-controller="oembed">
    {!! $post->content !!}
</article>
```

For a supported provider, the controller replaces the nearest `<figure>` (or the `<oembed>` itself) with a wrapper and
iframe. Other URLs become links. New embeds inserted inside the connected root by Turbo Morph or Turbo Streams are
processed automatically. It emits no classes and does not depend on a package visual module.

CKEditor commonly generates source markup like:

```html
<figure class="media">
    <oembed url="https://vimeo.com/123456789"></oembed>
</figure>
```

## Example output

For a YouTube source, the controller produces:

```html
<div data-slot="oembed">
    <iframe
        data-slot="oembed-frame"
        src="https://www.youtube.com/embed/dQw4w9WgXcQ"
        frameborder="0"
        allowfullscreen
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
    ></iframe>
</div>
```

For unrecognized URLs:

```html
<a data-slot="oembed-link" href="https://example.com/video" target="_blank" rel="noopener noreferrer">
    https://example.com/video
</a>
```

## Direct component alternative

When the application already has one URL, such as a dedicated CMS video field, use the server-rendered
[`<hw:video-embed>` component](../components/video-embed.md) instead. It provides package styling without requiring this
controller or client-side transformation.

## Styling hooks

The controller's slots are stable, application-styled hooks. They do not select the package `video-embed` preset module:

- `data-slot="oembed"` - supported-provider wrapper
- `data-slot="oembed-frame"` - generated iframe
- `data-slot="oembed-link"` - fallback link
