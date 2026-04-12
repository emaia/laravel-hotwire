# Remote

Utility controller for common form operations: remote submit and reset.

**Identifier:** `form--remote`

## Requirements

- No external dependencies.

## Targets

| Target      | Description            |
|-------------|------------------------|
| `submitBtn` | The form submit button |

## Actions

| Action                      | Description                               |
|-----------------------------|-------------------------------------------|
| `form--remote#remoteSubmit` | Programmatically clicks the submit button |
| `form--remote#reset`        | Resets the form to its initial state      |

## Remote submit

Useful when the submit button is outside the form or when another element needs to trigger the submission:

```html

<form data-controller="form--remote">
    <input type="text" name="title"/>

    <button type="submit" data-form--remote-target="submitBtn">Save</button>
</form>

<!-- External button that triggers the submit -->
<button data-action="form--remote#remoteSubmit">Save from outside</button>
```

## Form reset

```html

<form data-controller="form--remote">
    <input type="text" name="search"/>

    <button type="submit">Search</button>
    <button type="button" data-action="form--remote#reset">Clear</button>
</form>
```

## Usage in modal with Turbo Frame

```html

<x-hwc::modal>
    <x-slot:trigger>
        <button data-action="dialog--modal#open" type="button">New item</button>
    </x-slot:trigger>

    <form
        data-controller="form--remote"
        action="/items"
        method="post"
    >
        @csrf
        <input type="text" name="title"/>

        <footer>
            <button type="button" data-action="dialog--modal#close">Cancel</button>
            <button type="submit" data-form--remote-target="submitBtn">Create</button>
        </footer>
    </form>
</x-hwc::modal>
```
