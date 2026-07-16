# Switch

Native checkbox rendered with `role="switch"` and a switch-specific visual preset.

Use `<hw:switch>` for boolean on/off settings while keeping native form submission and Laravel validation behavior.

## Quick example

```blade
<hw:field name="enabled" orientation="horizontal">
    <hw:switch value="1" :checked="$feature->enabled" unchecked-value="0" />

    <hw:field.content>
        <hw:field.title>Enable feature</hw:field.title>
        <hw:field.description>Turn this on for the current workspace.</hw:field.description>
    </hw:field.content>
</hw:field>
```

## Props

| Prop                | Type                | Default             | Description                                                         |
|---------------------|---------------------|---------------------|---------------------------------------------------------------------|
| `name`              | `string\|null`      | —                   | Input name. Inherited from `<hw:field>` when omitted                |
| `id`                | `string\|null`      | derived from `name` | Override the auto-derived id                                        |
| `value`             | `mixed`             | `null`              | Posted value when checked. Browser default is `on` when omitted     |
| `checked`           | `bool\|string`      | `false`             | Initial checked state                                               |
| `old`               | `bool`              | `true`              | Restore checked state from flashed old input                        |
| `errorKey`          | `string\|null`      | derived from `name` | Override when HTML `name` differs from the Laravel validation key   |
| `unchecked-value`   | `string\|null`      | `null`              | Render a hidden input with this value before the switch             |
| `auto-submit`       | `bool\|string`      | `false`             | Add auto-submit wiring; switches default to immediate change submit |
| `auto-submit-delay` | `int\|string\|null` | `null`              | Per-field debounce override when `auto-submit="debounced"` is used  |
| `size`              | `string`            | `"default"`         | `default` or `sm`                                                   |
| `class`             | `string`            | `""`                | Merged on the switch input                                          |

Any other HTML attribute (`disabled`, `data-*`, `aria-*`) passes through to the switch input.

## Old input

The switch restores checked state from Laravel old input using the same rules as `<hw:checkbox>`:

```blade
<hw:switch name="enabled" value="1" :checked="$feature->enabled" />
```

Pass `:old="false"` when the state should always come from the `checked` prop.

## Hidden unchecked value

Switches are checkboxes under the hood, so unchecked switches do not submit a value unless you opt in:

```blade
<hw:switch name="enabled" value="1" unchecked-value="0" />
```

## Choice card

Wrap a whole `<hw:field>` in `<hw:field.label>` for a clickable card. This mirrors shadcn's choice-card pattern and lets
the preset style checked, disabled and invalid states from the field/switch state.

```blade
<hw:field.label>
    <hw:field name="share_focus" orientation="horizontal">
        <hw:field.content>
            <hw:field.title>Share across devices</hw:field.title>
            <hw:field.description>
                Focus is shared across devices, and turns off when you leave the app.
            </hw:field.description>
        </hw:field.content>

        <hw:switch value="1" :checked="$settings->share_focus" />
    </hw:field>
</hw:field.label>
```

For disabled cards, pass `disabled` to the field and to the switch:

```blade
<hw:field.label>
    <hw:field name="share_focus" orientation="horizontal" disabled>
        <hw:field.content>
            <hw:field.title>Share across devices</hw:field.title>
            <hw:field.description>Unavailable for this workspace.</hw:field.description>
        </hw:field.content>

        <hw:switch value="1" disabled />
    </hw:field>
</hw:field.label>
```

For invalid cards, pass `invalid` to the field. The switch also emits `aria-invalid="true"` automatically when the field
has validation errors.

```blade
<hw:field.label>
    <hw:field name="terms" orientation="horizontal" invalid>
        <hw:field.content>
            <hw:field.title>Accept terms and conditions</hw:field.title>
            <hw:field.description>You must accept the terms to continue.</hw:field.description>
        </hw:field.content>

        <hw:switch value="1" />
    </hw:field>
</hw:field.label>
```

## Size

```blade
<hw:switch name="compact" size="sm" />
<hw:switch name="default" />
```

## Auto-submit

`auto-submit` adds a change action to the switch. The `auto-submit` controller should live on an ancestor form:

```blade
<hw:form auto-submit>
    <hw:switch name="enabled" value="1" auto-submit />
</hw:form>
```

Switches submit immediately by default. Use `auto-submit="debounced" auto-submit-delay="..."` for delayed filter flows.

## Required controllers

`hotwire:check` looks for `auto-submit` when `auto-submit` is used.
