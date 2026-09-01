---
name: laravel-hotwire-forms
description: >-
  Build forms with Laravel Hotwire using {{ '<'.config('hotwire.prefix', 'hw').':form>' }},
  {{ '<'.config('hotwire.prefix', 'hw').':field>' }} and form controls, validation error wiring, auto-submit filters,
  conditional fields, file uploads, and forms inside Turbo Frames and modals.
---
@php
    $prefix = config('hotwire.prefix', 'hw');
    $component = static fn (string $name): string => "<{$prefix}:{$name}>";
    $open = static fn (string $name, string $attributes = ''): string => "<{$prefix}:{$name}".($attributes === '' ? '' : " {$attributes}").'>';
    $close = static fn (string $name): string => "</{$prefix}:{$name}>";
    $self = static fn (string $name, string $attributes = ''): string => "<{$prefix}:{$name}".($attributes === '' ? '' : " {$attributes}").' />';
@endphp
# Laravel Hotwire Forms

## When to use

Load this skill for forms, validation errors, fields and controls, search/filter forms, conditional fields, uploads, or a
form rendered inside a Turbo Frame or overlay. For general Frame/Stream response design, also load
`laravel-hotwire-turbo-workflows`.

## Non-negotiables

- Prefer `{!! $component('form') !!}` and `{!! $component('field') !!}` over raw forms with repeated CSRF, method
  spoofing, labels and error markup.
- Do not add CSRF or method fields inside `{!! $component('form') !!}`. It emits CSRF for non-GET requests and method
  spoofing for PUT, PATCH and DELETE.
- Activate built-in form behavior with props such as `auto-submit`, not hand-written internal controller attributes.
  Active controllers protect their `data-{identifier}-*` namespace from conflicting attributes.
- Package styling comes from presets and semantic `data-slot` hooks. Do not assume package-authored utility classes.

## The field contract

`{!! $component('field') !!}` owns a field name and shares it with nested controls. The control inherits `name`, derives
its HTML `id` and Laravel error key, restores old input, and emits `aria-invalid` and `aria-describedby` when validation
fails. `{!! $component('field.error') !!}` reads the same error bag and stays in the DOM as a stable ARIA target.

```blade
{!! $open('field', 'name="email" label="Email" description="Use your work email." required') !!}
    {!! $self('input', 'type="email" autocomplete="email"') !!}
{!! $close('field') !!}
```

Do not repeat the inherited name unless the control intentionally submits under another name:

```blade
{{-- Wrong: the field and control contracts can diverge. --}}
{!! $open('field', 'name="email" label="Email"') !!}
    {!! $self('input', 'name="profile_email"') !!}
{!! $close('field') !!}

{{-- Right: inherit name, id and error key. --}}
{!! $open('field', 'name="email" label="Email"') !!}
    {!! $self('input') !!}
{!! $close('field') !!}
```

Derivation is deterministic: `items[0][name]` becomes id `items-0-name` and error key `items.0.name`; `address.street`
becomes id `address-street` and keeps error key `address.street`; a trailing `[]` is removed for identity. Use explicit
`id` or `error-key` only when the submitted name and UI/validation identity must differ.

For a manual layout, disable the automatic error and render primitives explicitly:

```blade
{!! $open('field', 'name="email" :error="false"') !!}
    {!! $open('field.label') !!}Email{!! $close('field.label') !!}
    {!! $self('input', 'type="email"') !!}
    {!! $open('field.description') !!}Use your work email.{!! $close('field.description') !!}
    {!! $self('field.error') !!}
{!! $close('field') !!}
```

## Choosing a control

Use the smallest control matching the submitted value:

| Need | Component |
| --- | --- |
| One boolean submitted by a native input | `{!! $component('checkbox') !!}` |
| Several related boolean values | `{!! $component('checkbox-group') !!}` |
| Pressed UI state or toolbar choice | `{!! $component('toggle') !!}` / `{!! $component('toggle-group') !!}` |
| Accessible on/off control | `{!! $component('switch') !!}` |
| One native option | `{!! $component('select') !!}` |
| Searchable multiple options | `{!! $component('multi-select') !!}` |
| Native file selection/current file | `{!! $component('file') !!}` |
| Upload pipeline, progress and previews | `{!! $component('file-upload') !!}` |
| Plain multiline text | `{!! $component('textarea') !!}` |
| Tiptap-backed rich content | `{!! $component('rich-text') !!}` |

Read [the full control matrix](references/controls.md) before choosing between similar controls.

## Form-level behavior

These props compose on one `{!! $component('form') !!}` root:

| Prop | Purpose |
| --- | --- |
| `auto-submit`, `auto-submit-delay` | Submit opted-in controls, with a form-level debounce |
| `unsaved-changes` | Confirm navigation away from dirty forms |
| `error-scroll` | Find and reveal the first `aria-invalid` control after a failed render |
| `clean-query-params` | Remove empty fields from GET query strings |
| `conditional-fields`, `state` | Drive dependent fields and server-render their initial visibility |
| `track-frame-src` | Preserve the GET URL that rendered a frame-hosted form |
| `frame` | Set the Turbo Frame submission target; model objects resolve through `dom_id()` |
| `enctype` | Set native encoding, including `multipart/form-data` for uploads |

Text inputs, textareas and sliders default to debounced auto-submit. Selects, checkboxes, switches and toggles default to
immediate submit. `auto-submit="debounced"` forces debounce on a discrete control. A control's `auto-submit` requires an
ancestor form with `auto-submit`.

## Filter forms

```blade
{!! $open('form', 'action="/items" method="get" auto-submit clean-query-params frame="results"') !!}
    {!! $self('input', 'type="search" name="q" placeholder="Search" auto-submit') !!}
    {!! $self('select', 'name="category" :options="$categories" nullable auto-submit') !!}
{!! $close('form') !!}

<turbo-frame id="results">
    ...
</turbo-frame>
```

## Validation and errors

Use normal Laravel validation. A Turbo form mutation must return a redirect on success/failure or HTML with an error
status such as 422; do not return a successful 200 HTML response to a failed POST and expect Turbo to render it.

```blade
{!! $open('form', 'action="/posts" method="post" error-scroll') !!}
    {!! $open('field', 'name="title" label="Title" required') !!}
        {!! $self('input') !!}
    {!! $close('field') !!}
    {!! $open('button', 'type="submit"') !!}Save{!! $close('button') !!}
{!! $close('form') !!}
```

## Frames and modals

When a frame's form GET URL differs from its mutation URL, add `track-frame-src` and extend
`Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest`. It validates the source and redirects failed validation back
to the URL that can render the matching frame. Use `frame="id"` when the form must submit into a specific frame.

```blade
{!! $open('modal', 'id="post-modal-shell" frame="post-modal"') !!}
    {!! $open('modal.title') !!}Edit post{!! $close('modal.title') !!}
{!! $close('modal') !!}

{!! $open('frame-or-page', 'frame="post-modal" layout="dashboard"') !!}
    {!! $open('form', 'action="/posts/1" method="patch" track-frame-src') !!}
        {!! $open('field', 'name="title" label="Title"') !!}
            {!! $self('input') !!}
        {!! $close('field') !!}
        {!! $open('button', 'type="submit"') !!}Save{!! $close('button') !!}
    {!! $close('form') !!}
{!! $close('frame-or-page') !!}
```

To close a reusable static modal from a successful Stream response, append a self-removing element with
`data-controller="modal-auto-close"`. This is a Stream response pattern, not a form prop.

## Conditional fields

`state` is the first-render fallback; Laravel `old()` input wins after validation. Disabled hidden controls are not
submitted.

```blade
{!! $open('form', 'action="/feedback" method="post" conditional-fields :state="$feedback"') !!}
    {!! $open('field', 'name="reason" label="Reason"') !!}
        {!! $self('select', ':options="$reasons"') !!}
    {!! $close('field') !!}

    {!! $open('conditional-field', 'when="reason=other"') !!}
        {!! $open('field', 'name="other_reason" label="Other reason"') !!}
            {!! $self('input') !!}
        {!! $close('field') !!}
    {!! $close('conditional-field') !!}
{!! $close('form') !!}
```

## File uploads

Use `{!! $component('file') !!}` for a native file control and `{!! $component('file-upload') !!}` for a managed upload
pipeline. File values cannot be restored with `old()`: `file-preserve` retains the browser `FileList` across failed Turbo
submissions, while `reset-files` clears it after successful submissions.

```blade
{!! $open('form', 'action="/documents" method="post" enctype="multipart/form-data"') !!}
    {!! $open('field', 'name="documents" label="Documents"') !!}
        {!! $self('file', 'multiple') !!}
    {!! $close('field') !!}
    {!! $open('button', 'type="submit"') !!}Upload{!! $close('button') !!}
{!! $close('form') !!}
```

## Verify

Run `php artisan hotwire:docs form --component`, `php artisan hotwire:docs field --component` for exact contracts, then
run `php artisan hotwire:check`.

## See also

- `laravel-hotwire-turbo-workflows` for response selection, Streams and morph reconciliation.
- `laravel-hotwire-ui-development` for overlays and preset styling.
- `laravel-hotwire-stimulus-controllers` for custom behaviors beyond component props.
