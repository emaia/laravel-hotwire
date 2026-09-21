# Video Embed

Renders a responsive YouTube or Vimeo iframe from a URL, with a link fallback for other providers.

## Usage

Pass the URL stored by your application directly to the component:

```blade
<hw:video-embed
    :url="$page->hero_video_url"
    title="Product walkthrough"
    loading="eager"
    privacy
/>
```

The component renders the final iframe on the server and does not require JavaScript. YouTube watch, Shorts, embed and
`youtu.be` URLs are supported, along with public Vimeo video URLs. Any other absolute HTTP or HTTPS URL renders as a
link that opens in a new tab.

Use the component when the application already has one known media URL, such as a CMS field rendered in a page hero or
content block.

## Editor-generated HTML

Rich text editors can produce an unknown number of `<oembed>` elements inside a larger HTML document:

```html
<figure class="media">
    <oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed>
</figure>
```

That is the standalone [`oembed` controller](../controllers/oembed.md) use case. Mount it around the rendered editor
HTML and style its structural slots in application CSS. The controller does not depend on this component.

## Props

| Prop      | Default          | Description                                                              |
|-----------|------------------|--------------------------------------------------------------------------|
| `url`     | Required         | Absolute HTTP or HTTPS media URL.                                        |
| `title`   | `Embedded media` | Accessible title for supported-provider iframes.                         |
| `ratio`   | `16/9`           | Positive number or numeric fraction applied to the responsive wrapper.  |
| `loading` | `lazy`           | Iframe loading strategy: `lazy` or `eager`. Use `eager` for hero media.  |
| `privacy` | `false`          | Uses `youtube-nocookie.com` for YouTube embeds. Ignored for other hosts. |

Attributes are applied to the responsive wrapper for supported providers or to the fallback link for other URLs.
An application `style` is appended after the generated aspect-ratio property, so it can override the prop when needed.

## Styling hooks

- `data-slot="video-embed"` - supported-provider wrapper
- `data-slot="video-embed-frame"` - YouTube or Vimeo iframe
- `data-slot="video-embed-link"` - unsupported-provider fallback link

The shared structural foundation reads `--video-embed-aspect-ratio`, which the `ratio` prop sets on supported embeds.
Spacing, surfaces and fallback-link appearance come from the selected preset.
