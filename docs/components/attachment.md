# Attachment

Composable file attachment primitive with media, metadata, state, actions and an optional full-card trigger. It is used
by `<hw:file-upload>` and can be used directly for server-rendered upload lists.

## Usage

```blade
<hw:attachment state="done">
    <hw:attachment.media>
        <hw:icon name="copy" />
    </hw:attachment.media>
    <hw:attachment.content>
        <hw:attachment.title>sales-dashboard.pdf</hw:attachment.title>
        <hw:attachment.description>PDF · 2.4 MB</hw:attachment.description>
    </hw:attachment.content>
    <hw:attachment.actions>
        <hw:attachment.action aria-label="Remove sales-dashboard.pdf">
            <hw:icon name="x" />
        </hw:attachment.action>
    </hw:attachment.actions>
</hw:attachment>
```

## Group

```blade
<hw:attachment.group>
    <hw:attachment>...</hw:attachment>
    <hw:attachment>...</hw:attachment>
</hw:attachment.group>
```

## Image Media

```blade
<hw:attachment orientation="vertical">
    <hw:attachment.media variant="image">
        <img src="{{ $photo->url }}" alt="{{ $photo->name }}">
    </hw:attachment.media>
    <hw:attachment.content>
        <hw:attachment.title>{{ $photo->name }}</hw:attachment.title>
        <hw:attachment.description>Uploaded · {{ $photo->size }}</hw:attachment.description>
    </hw:attachment.content>
</hw:attachment>
```

## States

`uploading` and `processing` apply a lightweight CSS shimmer to the title. `error` switches the card to the destructive
treatment; keep the failure reason in `attachment.description` so the state is not conveyed by color alone.

The shimmer motion is also available as the CSS hook `data-shimmer="true"` for package or app UI that needs the same
text shimmer without introducing another component.

## Props

| Component            | Prop          | Default      | Description                                         |
|----------------------|---------------|--------------|-----------------------------------------------------|
| `attachment`         | `state`       | `done`       | `idle`, `uploading`, `processing`, `error`, `done`. |
| `attachment`         | `size`        | `default`    | `default`, `sm`, `xs`.                              |
| `attachment`         | `orientation` | `horizontal` | `horizontal` or `vertical`.                         |
| `attachment.media`   | `variant`     | `icon`       | `icon` or `image`.                                  |
| `attachment.action`  | `variant`     | `ghost`      | Button variant.                                     |
| `attachment.action`  | `size`        | `icon-xs`    | Button size.                                        |
| `attachment.trigger` | `as`          | `button`     | Root element. Allowed: `button`, `a`, `div`, `span`. |

## Components

| Component                | Element      | Slot                     |
|--------------------------|--------------|--------------------------|
| `attachment.group`       | `div`        | `attachment-group`       |
| `attachment`             | `div`        | `attachment`             |
| `attachment.media`       | `div`        | `attachment-media`       |
| `attachment.content`     | `div`        | `attachment-content`     |
| `attachment.title`       | `span`       | `attachment-title`       |
| `attachment.description` | `p`          | `attachment-description` |
| `attachment.actions`     | `div`        | `attachment-actions`     |
| `attachment.action`      | `button`     | `attachment-action`      |
| `attachment.trigger`     | configurable | `attachment-trigger`     |

## Accessibility

`attachment.group` renders `role="list"`. Use list-compatible children such as `attachment` cards with `role="listitem"`
when you build a fully semantic list.

Label icon-only `attachment.action` buttons with `aria-label`. The action defaults to `type="button"`; if you turn it
into a submit button or wire it to a destructive form action, include Laravel's normal CSRF field/method spoofing in the
owning form. If `attachment.trigger` covers the card, give it an `aria-label` that explains what it opens. A trigger
rendered as `span` is not focusable unless you add the appropriate `tabindex` and keyboard handling yourself.
