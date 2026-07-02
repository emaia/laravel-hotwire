# Field

Compose accessible, Laravel-aware form fields with labels, controls, helper text, validation errors, and semantic groups.

The field set provides small primitives that share `data-slot` styling hooks and integrate with Laravel validation through
`name`, `errorKey`, `required`, and `@aware`.

## Usage

```blade
<x-hwc::field.group>
    <x-hwc::field name="email" label="Email" description="We will never share your email." required>
        <x-hwc::input type="email" />
    </x-hwc::field>

    <x-hwc::field name="password" label="Password">
        <x-hwc::input type="password" />
    </x-hwc::field>
</x-hwc::field.group>
```

For full control over ordering and content, compose the primitives manually:

```blade
<x-hwc::field name="email" :error="false">
    <x-hwc::field.label>Email</x-hwc::field.label>
    <x-hwc::input type="email" />
    <x-hwc::field.description>Use your work email address.</x-hwc::field.description>
    <x-hwc::field.error />
</x-hwc::field>
```

All field primitives forward extra HTML attributes. Use `class`, `id`, `data-*`, and `aria-*` directly on the element
that should receive them.

## Composition

### Field

A single control with label, helper text, and validation feedback.

```text
field
├── field.label
├── input / select / textarea / checkbox-group / file / file-upload
├── field.description
└── field.error
```

### Field Group

Related fields in one vertical stack. Use `field.separator` between logical sections when needed.

```text
field.group
├── field
│   ├── field.label
│   ├── input
│   ├── field.description
│   └── field.error
├── field.separator
└── field
    ├── field.label
    └── input
```

### Field Set

Semantic grouping with a legend and description, usually containing a `field.group`.

```text
field.set
├── field.legend
├── field.description
└── field.group
    ├── field
    │   ├── field.label
    │   ├── input
    │   └── field.error
    └── field
        ├── field.label
        └── input
```

## Examples

### Basic Input

```blade
<x-hwc::field name="username" label="Username" description="Choose a unique username.">
    <x-hwc::input autocomplete="off" />
</x-hwc::field>
```

### Manual Layout

```blade
<x-hwc::field name="document" required :error="false">
    <x-hwc::field.label class="font-bold">Document</x-hwc::field.label>
    <x-hwc::field.description>CPF or CNPJ.</x-hwc::field.description>
    <x-hwc::input clearable mask="cpf-cnpj" />
    <x-hwc::field.error />
</x-hwc::field>
```

### Checkbox Or Switch Row

Use `field.content` when a horizontal control needs a title and description beside it.

```blade
<x-hwc::field name="marketing" orientation="horizontal">
    <x-hwc::input type="checkbox" value="1" />

    <x-hwc::field.content>
        <x-hwc::field.title>Marketing emails</x-hwc::field.title>
        <x-hwc::field.description>Receive occasional product updates.</x-hwc::field.description>
    </x-hwc::field.content>
</x-hwc::field>
```

Use `field.label` instead of `field.title` when a real label association is needed:

```blade
<x-hwc::field name="remember" orientation="horizontal">
    <x-hwc::input type="checkbox" value="1" />
    <x-hwc::field.label>Remember me</x-hwc::field.label>
</x-hwc::field>
```

### Field Set

```blade
<x-hwc::field.set>
    <x-hwc::field.legend>Billing Address</x-hwc::field.legend>
    <x-hwc::field.description>The address associated with your payment method.</x-hwc::field.description>

    <x-hwc::field.group>
        <x-hwc::field name="street" label="Street Address">
            <x-hwc::input />
        </x-hwc::field>

        <x-hwc::field name="city" label="City">
            <x-hwc::input />
        </x-hwc::field>
    </x-hwc::field.group>
</x-hwc::field.set>
```

### Separator

```blade
<x-hwc::field.group>
    <x-hwc::field name="email" label="Email">
        <x-hwc::input type="email" />
    </x-hwc::field>

    <x-hwc::field.separator>Or</x-hwc::field.separator>

    <x-hwc::field name="phone" label="Phone">
        <x-hwc::input type="tel" />
    </x-hwc::field>
</x-hwc::field.group>
```

## Laravel Behavior

`<x-hwc::field>` propagates context to nested field-aware controls via `@aware`:

| Context    | Used By                                                                                              | Purpose                                                                          |
|------------|------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------|
| `name`     | `field.label`, `input`, `select`, `textarea`, `checkbox-group`, `file`, `file-upload`, `field.error` | Derives `for`, `id`, `name`, `aria-describedby`, and validation keys.            |
| `errorKey` | Controls and `field.error`                                                                           | Looks up Laravel validation messages when HTML name differs from validation key. |
| `required` | `field.label` and controls                                                                           | Renders the required marker and ARIA required state.                             |

Controls emit `aria-describedby="{id}-error"`. `field.error` keeps the matching element in the DOM, hidden when there are
no messages, so the ARIA reference stays stable.

```blade
<x-hwc::field name="variables[0][name]" error-key="indicator.name">
    <x-hwc::field.label>Variable</x-hwc::field.label>
    <x-hwc::input />
    <x-hwc::field.error />
</x-hwc::field>
```

If you opt out of the auto-rendered error with `:error="false"`, render `<x-hwc::field.error>` yourself somewhere in the
field.

## Responsive Layout

`field` supports three orientations:

| Orientation  | Behavior                                                                                   |
|--------------|--------------------------------------------------------------------------------------------|
| `vertical`   | Default. Stacks label, control, description, and error.                                    |
| `horizontal` | Aligns direct children in a row. Pair with `field.content` for title and description text. |
| `responsive` | Starts vertical and switches to a horizontal row at the `md` breakpoint.                   |

```blade
<x-hwc::field name="email" label="Email" orientation="responsive">
    <x-hwc::input type="email" />
</x-hwc::field>
```

Unlike shadcn's container-query implementation, Laravel Hotwire uses viewport breakpoints by default. This avoids making
`field.group` a size container, which would break intrinsic-width surfaces such as `<x-hwc::modal size="auto">`.

## API Reference

### `<x-hwc::field.set>`

Renders a semantic `<fieldset>` for related fields.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<x-hwc::field.set>
    <x-hwc::field.legend>Delivery</x-hwc::field.legend>
    <x-hwc::field.group>...</x-hwc::field.group>
</x-hwc::field.set>
```

### `<x-hwc::field.legend>`

Renders a semantic `<legend>` for a field set.

| Prop      | Type            | Default  | Description                                                    |
|-----------|-----------------|----------|----------------------------------------------------------------|
| `variant` | `legend\|label` | `legend` | Switches between fieldset legend sizing and label-like sizing. |

```blade
<x-hwc::field.legend variant="label">Notification Preferences</x-hwc::field.legend>
```

### `<x-hwc::field.group>`

Stacks related field components with the preset spacing.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<x-hwc::field.group>
    <x-hwc::field>...</x-hwc::field>
    <x-hwc::field>...</x-hwc::field>
</x-hwc::field.group>
```

### `<x-hwc::field>`

Core wrapper for a single field. Provides context propagation, optional auto-rendered label/description/error, and
orientation state for the preset.

| Prop             | Type                               | Default    | Description                                                                                  |
|------------------|------------------------------------|------------|----------------------------------------------------------------------------------------------|
| `name`           | `string\|null`                     | `null`     | Field name propagated to nested field-aware children.                                        |
| `label`          | `string\|null`                     | `null`     | Auto-renders `field.label` before the slot. Empty string skips it.                           |
| `description`    | `string\|null`                     | `null`     | Auto-renders `field.description` after the slot and before the error. Empty string skips it. |
| `required-label` | `string`                           | `"*"`      | Marker text passed to the auto-rendered `field.label`.                                       |
| `error-key`      | `string\|null`                     | `null`     | Overrides Laravel validation key derivation.                                                 |
| `required`       | `bool\|null`                       | `null`     | Propagates required state to label and controls.                                             |
| `error`          | `bool`                             | `true`     | Auto-renders `field.error` when `name` is set.                                               |
| `orientation`    | `vertical\|horizontal\|responsive` | `vertical` | Layout state consumed by the preset.                                                         |

```blade
<x-hwc::field name="email" label="Email" orientation="horizontal">
    <x-hwc::input type="email" />
</x-hwc::field>
```

### `<x-hwc::field.content>`

Flex column for title/label and description when a control sits beside text.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<x-hwc::field.content>
    <x-hwc::field.title>Notifications</x-hwc::field.title>
    <x-hwc::field.description>Email, SMS, and push options.</x-hwc::field.description>
</x-hwc::field.content>
```

### `<x-hwc::field.label>`

Form `<label>` that derives `for` from the surrounding field and renders an optional required marker.

| Prop             | Type           | Default             | Description                                           |
|------------------|----------------|---------------------|-------------------------------------------------------|
| `for`            | `string\|null` | derived from `name` | Overrides the label target. Pass `for=""` to omit it. |
| `name`           | `string\|null` | inherited           | Used to derive `for` when `for` is omitted.           |
| `value`          | `string\|null` | `null`              | Label text as an alternative to slot content.         |
| `required`       | `bool\|null`   | inherited           | Shows the required marker.                            |
| `required-label` | `string`       | `"*"`               | Required marker text.                                 |

```blade
<x-hwc::field.label for="email">Email</x-hwc::field.label>
```

If the label wraps an `<input>`, `<select>`, or `<textarea>`, the component omits `for` and uses HTML's implicit labeling
pattern.

### `<x-hwc::field.title>`

Non-label heading styled like a field label. Use it inside `field.content` when the control is already labeled or does
not need label association.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<x-hwc::field.title>Enable Touch ID</x-hwc::field.title>
```

### `<x-hwc::field.description>`

Helper text slot for a field.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<x-hwc::field.description>We never share your email.</x-hwc::field.description>
```

### `<x-hwc::field.separator>`

Visual divider for separating sections inside a `field.group`. Accepts optional-centered content.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<x-hwc::field.separator>Or continue with</x-hwc::field.separator>
```

### `<x-hwc::field.error>`

Accessible error container bound to a field name or explicit validation key. It remains in the DOM when empty and renders
multiple messages as a list.

| Prop        | Type                  | Default                   | Description                                                            |
|-------------|-----------------------|---------------------------|------------------------------------------------------------------------|
| `name`      | `string\|null`        | inherited                 | Drives `errorKey` and `id` derivation.                                 |
| `error-key` | `string\|null`        | derived from `name`       | Overrides Laravel validation key lookup.                               |
| `messages`  | `string\|array\|null` | `$errors->get($errorKey)` | Overrides the message source.                                          |
| `id`        | `string\|null`        | `{derivedId}-error`       | Overrides the element id. Must match the control's `aria-describedby`. |

```blade
<x-hwc::field.error name="email" />
```

```blade
<x-hwc::field.error :messages="['Choose another username.']" />
```

## Styling Hooks

| Slot                      | Purpose                                |
|---------------------------|----------------------------------------|
| `field-set`               | Semantic `<fieldset>` wrapper.         |
| `field-legend`            | Semantic `<legend>` for a field set.   |
| `field-group`             | Group wrapper for related fields.      |
| `field`                   | Individual field wrapper.              |
| `field-label`             | Label element.                         |
| `field-label-required`    | Required marker inside `field.label`.  |
| `field-content`           | Text/content column next to a control. |
| `field-title`             | Non-label field heading.               |
| `field-description`       | Helper text.                           |
| `field-error`             | Error message container.               |
| `field-separator`         | Separator wrapper.                     |
| `field-separator-line`    | Decorative separator line.             |
| `field-separator-content` | Optional centered separator text.      |

Override styles after importing the preset:

```css
[data-slot="field-group"] {
    @apply gap-4;
}
```
