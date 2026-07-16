# Field

Compose accessible, Laravel-aware form fields with labels, controls, helper text, validation errors, and semantic groups.

The field set provides small primitives that share `data-slot` styling hooks and integrate with Laravel validation through
`name`, `errorKey`, `required`, and `@aware`.

## Usage

```blade
<hw:field.group>
    <hw:field name="email" label="Email" description="We will never share your email." required>
        <hw:input type="email" />
    </hw:field>

    <hw:field name="password" label="Password">
        <hw:input type="password" />
    </hw:field>
</hw:field.group>
```

For full control over ordering and content, compose the primitives manually:

```blade
<hw:field name="email" :error="false">
    <hw:field.label>Email</hw:field.label>
    <hw:input type="email" />
    <hw:field.description>Use your work email address.</hw:field.description>
    <hw:field.error />
</hw:field>
```

All field primitives forward extra HTML attributes. Use `class`, `id`, `data-*`, and `aria-*` directly on the element
that should receive them.

## Composition

### Field

A single control with label, helper text, and validation feedback.

```text
field
├── field.label
├── input / checkbox / switch / select / textarea / checkbox-group / radio-group / file / file-upload
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
<hw:field name="username" label="Username" description="Choose a unique username.">
    <hw:input autocomplete="off" />
</hw:field>
```

### Manual Layout

```blade
<hw:field name="document" required :error="false">
    <hw:field.label class="font-bold">Document</hw:field.label>
    <hw:field.description>CPF or CNPJ.</hw:field.description>
    <hw:input clearable mask="cpf-cnpj" />
    <hw:field.error />
</hw:field>
```

### Checkbox Or Switch Row

Use `field.content` when a horizontal control needs a title and description beside it.

```blade
<hw:field name="marketing" orientation="horizontal">
    <hw:switch value="1" />

    <hw:field.content>
        <hw:field.title>Marketing emails</hw:field.title>
        <hw:field.description>Receive occasional product updates.</hw:field.description>
    </hw:field.content>
</hw:field>
```

Use `field.label` instead of `field.title` when a real label association is needed:

```blade
<hw:field name="remember" orientation="horizontal">
    <hw:checkbox value="1" />
    <hw:field.label>Remember me</hw:field.label>
</hw:field>
```

### Field Set

```blade
<hw:field.set>
    <hw:field.legend>Billing Address</hw:field.legend>
    <hw:field.description>The address associated with your payment method.</hw:field.description>

    <hw:field.group>
        <hw:field name="street" label="Street Address">
            <hw:input />
        </hw:field>

        <hw:field name="city" label="City">
            <hw:input />
        </hw:field>
    </hw:field.group>
</hw:field.set>
```

### Separator

```blade
<hw:field.group>
    <hw:field name="email" label="Email">
        <hw:input type="email" />
    </hw:field>

    <hw:field.separator>Or</hw:field.separator>

    <hw:field name="phone" label="Phone">
        <hw:input type="tel" />
    </hw:field>
</hw:field.group>
```

## Laravel Behavior

`<hw:field>` propagates context to nested field-aware controls via `@aware`:

| Context    | Used By                                                                                              | Purpose                                                                          |
|------------|------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------|
| `name`     | `field.label`, `input`, `checkbox`, `switch`, `select`, `textarea`, `checkbox-group`, `radio-group`, `file`, `file-upload`, `field.error` | Derives `for`, `id`, `name`, `aria-describedby`, and validation keys.            |
| `errorKey` | Controls and `field.error`                                                                           | Looks up Laravel validation messages when HTML name differs from validation key. |
| `required` | `field.label` and controls                                                                           | Renders the required marker and ARIA required state.                             |

Controls emit `aria-describedby="{id}-error"`. `field.error` keeps the matching element in the DOM, hidden when there are
no messages, so the ARIA reference stays stable.

```blade
<hw:field name="variables[0][name]" error-key="indicator.name">
    <hw:field.label>Variable</hw:field.label>
    <hw:input />
    <hw:field.error />
</hw:field>
```

If you opt out of the auto-rendered error with `:error="false"`, render `<hw:field.error>` yourself somewhere in the
field.

## Responsive Layout

`field` supports three orientations:

| Orientation  | Behavior                                                                                   |
|--------------|--------------------------------------------------------------------------------------------|
| `vertical`   | Default. Stacks label, control, description, and error.                                    |
| `horizontal` | Aligns direct children in a row. Pair with `field.content` for title and description text. |
| `responsive` | Starts vertical and switches to a horizontal row at the `md` breakpoint.                   |

Pass `disabled` or `invalid` to emit `data-disabled="true"` / `data-invalid="true"` on the field wrapper. These states are
used by card-style labels and other CSS presets; they do not disable nested controls by themselves.

```blade
<hw:field name="email" label="Email" orientation="responsive">
    <hw:input type="email" />
</hw:field>
```

Responsive fields use viewport breakpoints by default. This avoids making `field.group` a size container, which would
break intrinsic-width surfaces such as `<hw:modal size="auto">`.

## API Reference

### `<hw:field.set>`

Renders a semantic `<fieldset>` for related fields.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<hw:field.set>
    <hw:field.legend>Delivery</hw:field.legend>
    <hw:field.group>...</hw:field.group>
</hw:field.set>
```

### `<hw:field.legend>`

Renders a semantic `<legend>` for a field set.

| Prop      | Type            | Default  | Description                                                    |
|-----------|-----------------|----------|----------------------------------------------------------------|
| `variant` | `legend\|label` | `legend` | Switches between fieldset legend sizing and label-like sizing. |

```blade
<hw:field.legend variant="label">Notification Preferences</hw:field.legend>
```

### `<hw:field.group>`

Stacks related field components with the preset spacing.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<hw:field.group>
    <hw:field>...</hw:field>
    <hw:field>...</hw:field>
</hw:field.group>
```

### `<hw:field>`

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
<hw:field name="email" label="Email" orientation="horizontal">
    <hw:input type="email" />
</hw:field>
```

### `<hw:field.content>`

Flex column for title/label and description when a control sits beside text.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<hw:field.content>
    <hw:field.title>Notifications</hw:field.title>
    <hw:field.description>Email, SMS, and push options.</hw:field.description>
</hw:field.content>
```

### `<hw:field.label>`

Form `<label>` that derives `for` from the surrounding field and renders an optional required marker.

| Prop             | Type           | Default             | Description                                           |
|------------------|----------------|---------------------|-------------------------------------------------------|
| `for`            | `string\|null` | derived from `name` | Overrides the label target. Pass `for=""` to omit it. |
| `name`           | `string\|null` | inherited           | Used to derive `for` when `for` is omitted.           |
| `value`          | `string\|null` | `null`              | Label text as an alternative to slot content.         |
| `required`       | `bool\|null`   | inherited           | Shows the required marker.                            |
| `required-label` | `string`       | `"*"`               | Required marker text.                                 |

```blade
<hw:field.label for="email">Email</hw:field.label>
```

If the label wraps an `<input>`, `<select>`, or `<textarea>`, the component omits `for` and uses HTML's implicit labeling
pattern.

### `<hw:field.title>`

Non-label heading styled like a field label. Use it inside `field.content` when the control is already labeled or does
not need label association.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<hw:field.title>Enable Touch ID</hw:field.title>
```

### `<hw:field.description>`

Helper text slot for a field.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<hw:field.description>We never share your email.</hw:field.description>
```

### `<hw:field.separator>`

Visual divider for separating sections inside a `field.group`. Accepts optional-centered content.

| Prop | Type | Default | Description                                             |
|------|------|---------|---------------------------------------------------------|
| —    | —    | —       | No dedicated props. Extra HTML attributes pass through. |

```blade
<hw:field.separator>Or continue with</hw:field.separator>
```

### `<hw:field.error>`

Accessible error container bound to a field name or explicit validation key. It remains in the DOM when empty and renders
multiple messages as a list.

| Prop        | Type                  | Default                   | Description                                                            |
|-------------|-----------------------|---------------------------|------------------------------------------------------------------------|
| `name`      | `string\|null`        | inherited                 | Drives `errorKey` and `id` derivation.                                 |
| `error-key` | `string\|null`        | derived from `name`       | Overrides Laravel validation key lookup.                               |
| `messages`  | `string\|array\|null` | `$errors->get($errorKey)` | Overrides the message source.                                          |
| `id`        | `string\|null`        | `{derivedId}-error`       | Overrides the element id. Must match the control's `aria-describedby`. |

```blade
<hw:field.error name="email" />
```

```blade
<hw:field.error :messages="['Choose another username.']" />
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
