# Spinner

Loading indicator for pending UI states. Pure HTML/CSS, no JavaScript.

## Usage

```blade
<hw:spinner />
```

The component renders an accessible SVG status indicator with `data-slot="spinner"`, `role="status"` and
`aria-label="Loading"`.

## Size

The Nova preset matches the shadcn base-nova reference with a default `size-4` spinner. The spinner inherits the current
text color, so it keeps contrast inside buttons, badges and other colored containers. Override the size or color with any
utility class:

```blade
<hw:spinner />
<hw:spinner class="size-6" />
<hw:spinner class="size-8 text-primary" />
```

## In Buttons And Badges

Use `data-icon` when the spinner sits next to a label so button and badge spacing can adapt.

```blade
<hw:button disabled>
    <hw:spinner data-icon="inline-start" />
    Loading...
</hw:button>

<hw:badge>
    <hw:spinner data-icon="inline-start" />
    Syncing
</hw:badge>
```

## Conditional Display

The spinner has no built-in visibility behavior — it always renders. Hide or show it with your own CSS, typically tied
to `aria-busy` on a parent element:

```blade
<button type="submit" aria-busy="false">
    Save
    <hw:spinner class="hidden aria-busy:block" />
</button>
```

Turbo automatically toggles `aria-busy="true"` on forms during submission, so the spinner appears while the request is
in flight and disappears when the response arrives.

## Styling Hooks

- `data-slot="spinner"`
- `data-icon="inline-start|inline-end"`
