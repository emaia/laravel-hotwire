# Remote Form

Proxy controller for form submissions that need to be triggered from a decoupled element.

**Identifier:** `remote-form`

## Requirements

- No external dependencies.

## Targets

| Target      | Description                                                    |
|-------------|----------------------------------------------------------------|
| `submitBtn` | The real submit button that should be clicked programmatically |

## Actions

| Action                      | Description                              |
|-----------------------------|------------------------------------------|
| `remote-form#remoteSubmit` | Clicks the configured `submitBtn` target |

## Remote submit

Use `remoteSubmit` when a visible trigger should submit through a real submit button that carries the actual form
metadata, such as `formaction`, `formmethod` or `data-turbo-frame`.

```html

<div data-controller="remote-form">
    <select name="content_type" data-action="change->remote-form#remoteSubmit">
        <option value="">Choose a content type</option>
        <option value="article">Article</option>
        <option value="video">Video</option>
    </select>

    <form method="post">
        @csrf

        <button
            type="submit"
            class="hidden"
            data-remote-form-target="submitBtn"
            data-turbo-frame="content-type-frame"
            formaction="/content-types/preview"
        >
            Load content type
        </button>
    </form>
</div>

<turbo-frame id="content-type-frame"></turbo-frame>
```

The select is only the trigger. The hidden submit button is the real request source, so Turbo sees its
`data-turbo-frame` and the browser uses its `formaction`. The trigger and `submitBtn` target must be inside the same
`remote-form` controller scope.
