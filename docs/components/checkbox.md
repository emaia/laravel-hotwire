# Checkbox

Standalone native checkbox with Laravel-aware `old()` restoration, validation wiring, optional hidden unchecked value and
indeterminate state.

Use `<hw:checkbox>` when you need a single checkbox. Use `<hw:checkbox-group>` for repeated values that submit as an
array, and keep `<hw:input type="checkbox">` available for low-level compatibility.

## Quick example

```blade
<hw:field name="notify" label="Notifications" orientation="horizontal">
    <hw:checkbox value="1" :checked="$user->notify" unchecked-value="0" />
</hw:field>
```

## Props

| Prop                | Type                | Default             | Description                                                                 |
|---------------------|---------------------|---------------------|-----------------------------------------------------------------------------|
| `name`              | `string\|null`      | —                   | Input name. Inherited from `<hw:field>` when omitted                        |
| `id`                | `string\|null`      | derived from `name` | Override the auto-derived id                                                |
| `value`             | `mixed`             | `null`              | Posted value when checked. Browser default is `on` when omitted             |
| `checked`           | `bool\|string`      | `false`             | Initial checked state                                                       |
| `old`               | `bool`              | `true`              | Restore checked state from flashed old input                                |
| `errorKey`          | `string\|null`      | derived from `name` | Override when HTML `name` differs from the Laravel validation key           |
| `unchecked-value`   | `string\|null`      | `null`              | Render a hidden input with this value before the checkbox                   |
| `indeterminate`     | `bool`              | `false`             | Activate the `checkbox` controller to set the native indeterminate property |
| `auto-submit`       | `bool\|string`      | `false`             | Add auto-submit wiring; checkboxes default to immediate change submit       |
| `auto-submit-delay` | `int\|string\|null` | `null`              | Per-field debounce override when `auto-submit="debounced"` is used          |
| `class`             | `string`            | `""`                | Merged on the checkbox input                                                |

Any other HTML attribute (`disabled`, `data-*`, `aria-*`) passes through to the checkbox input.

## Old input

On validation redirects, the component restores the submitted state when Laravel has flashed `_old_input`:

```blade
<hw:checkbox name="notify" value="1" :checked="$user->notify" />
```

If `old('notify')` is present and equals `1`, the checkbox is checked. If Laravel flashed old input but `notify` is
missing, the checkbox is unchecked even when `checked` was `true`.

Disable this behavior when the checked state should always come from your own prop:

```blade
<hw:checkbox name="notify" :checked="$user->notify" :old="false" />
```

## Hidden unchecked value

Unchecked checkboxes do not submit a value. Pass `unchecked-value` when the backend expects an explicit falsey value:

```blade
<hw:checkbox name="notify" value="1" unchecked-value="0" />
```

This renders a hidden input before the checkbox so checked submissions still win by normal form ordering.

## Indeterminate state

HTML has no `indeterminate` attribute; it is a DOM property. The component mounts the lightweight `checkbox` controller
only when needed:

```blade
<hw:checkbox name="all" indeterminate />
```

The controller also re-syncs after `turbo:render`, so a Turbo morph does not leave the visual state stale.

## Auto-submit

`auto-submit` adds a change action to the input. The `auto-submit` controller should live on an ancestor form:

```blade
<hw:form auto-submit>
    <hw:checkbox name="notify" value="1" auto-submit />
</hw:form>
```

Checkboxes submit immediately by default. Use `auto-submit="debounced" auto-submit-delay="..."` when a checkbox should
share a debounced filter flow.

## Required controllers

`hotwire:check` looks for `checkbox` when `indeterminate` is used and `auto-submit` when `auto-submit` is used.
