# Auto Save

Automatically saves a form after the user changes it. Designed for draft editors, settings screens and inline edit
forms where saving should feel native and unobtrusive.

**Identifier:** `auto-save`
**Install:** `php artisan hotwire:controllers auto-save`

## Requirements

- No external dependencies.
- A `<form>` element as the controller root.
- Turbo is recommended so the submission can complete without a full page reload.

## Targets

| Target      | Description                                                                 |
|-------------|-----------------------------------------------------------------------------|
| `status`    | Optional element that receives the current status text                       |
| `submitter` | Optional submit button used as the real submitter for `requestSubmit()`      |

## Stimulus Values

| Value             | Type     | Default | Description                                          |
|-------------------|----------|---------|------------------------------------------------------|
| `delay`           | `Number` | `750`   | Debounce delay for `input` events                    |
| `change-delay`    | `Number` | `0`     | Debounce delay for `change` events                   |
| `status-duration` | `Number` | `2000`  | How long the `Saved` status is shown before clearing |

## CSS Classes

| Class    | Applied when                            |
|----------|-----------------------------------------|
| `dirty`  | The form has changes waiting to be saved |
| `saving` | A submit is in progress                 |
| `saved`  | The last submit completed successfully  |
| `error`  | The last submit failed                  |

## Actions

| Action             | Description                         |
|--------------------|-------------------------------------|
| `auto-save#save`   | Saves immediately                   |
| `auto-save#cancel` | Cancels the pending save timer      |

## Events

| Event                | Description                        |
|----------------------|------------------------------------|
| `auto-save:dirty`    | Fired when unsaved changes exist   |
| `auto-save:saving`   | Fired when a submit starts         |
| `auto-save:saved`    | Fired after a successful submit    |
| `auto-save:error`    | Fired after a failed submit        |

## Basic usage

```html
<form method="post" action="/posts/1" data-controller="auto-save">
    @csrf
    @method('PATCH')

    <input type="text" name="title" value="{{ $post->title }}" />
    <textarea name="body">{{ $post->body }}</textarea>

    <span data-auto-save-target="status"></span>
</form>
```

The controller listens for `input` and `change` events on the form, so individual fields do not need `data-action`
attributes.

## Custom debounce

```html
<form
    method="post"
    action="/posts/1"
    data-controller="auto-save"
    data-auto-save-delay-value="1000"
    data-auto-save-change-delay-value="250"
>
    @csrf
    @method('PATCH')

    <input type="text" name="title" value="{{ $post->title }}" />
    <select name="status">
        <option value="draft">Draft</option>
        <option value="published">Published</option>
    </select>
</form>
```

## State-based styling

The current state is written to `data-auto-save-state`.

```html
<form method="post" action="/settings" data-controller="auto-save">
    @csrf
    @method('PATCH')

    <input type="text" name="company_name" value="{{ $settings->company_name }}" />

    <span data-auto-save-target="status"></span>
</form>
```

```css
form[data-auto-save-state="saving"] {
    opacity: 0.75;
}

form[data-auto-save-state="error"] {
    outline: 1px solid red;
}
```

You can also configure classes:

```html
<form
    method="post"
    action="/settings"
    data-controller="auto-save"
    data-auto-save-saving-class="opacity-75"
    data-auto-save-error-class="ring-1 ring-red-500"
>
    @csrf
    @method('PATCH')

    <input type="text" name="company_name" value="{{ $settings->company_name }}" />
</form>
```

## Custom submitter

Use a `submitter` target when the autosave request should go through a specific button with its own submit metadata,
such as `formaction`, `formmethod` or `data-turbo-frame`.

```html
<form method="post" action="/posts/1" data-controller="auto-save">
    @csrf
    @method('PATCH')

    <input type="text" name="title" value="{{ $post->title }}" />

    <button
        type="submit"
        hidden
        data-auto-save-target="submitter"
        data-turbo-frame="draft-status"
        formaction="/posts/1/draft"
    >
        Save draft
    </button>
</form>

<turbo-frame id="draft-status"></turbo-frame>
```

## Ignoring fields

Fields marked with `data-auto-save-ignore` are ignored when deciding whether the form has unsaved changes.

```html
<form method="post" action="/posts/1" data-controller="auto-save">
    @csrf
    @method('PATCH')

    <input type="hidden" name="active_tab" value="seo" data-auto-save-ignore />
    <input type="text" name="title" value="{{ $post->title }}" />
</form>
```

## Manual save trigger

```html
<form method="post" action="/posts/1" data-controller="auto-save">
    @csrf
    @method('PATCH')

    <textarea name="body">{{ $post->body }}</textarea>

    <button type="button" data-action="auto-save#save">
        Save now
    </button>
</form>
```

## How it works

The controller calls `form.requestSubmit()` instead of using `fetch()` directly. That keeps browser validation, CSRF
tokens, Laravel method spoofing, Turbo form handling, `formaction`, `formmethod` and `data-turbo-frame` behavior intact.

If the user changes the form while a save is already running, the controller queues one more save after the current
request finishes.
