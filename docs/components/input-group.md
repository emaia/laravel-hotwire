# Input Group

Composable shell for adding icons, text, shortcuts, buttons and helper content around existing form controls.

## Usage

```blade
<hw:input-group>
    <hw:input name="search" placeholder="Search..." clearable />

    <hw:input-group.addon align="inline-start">
        <x-lucide-search class="size-4" />
    </hw:input-group.addon>
</hw:input-group>
```

`InputGroup` does not replace `<hw:input>`, `<hw:textarea>` or `<hw:button>`. Compose those existing components inside
the group so their validation, `old()` restoration, Stimulus wiring and accessibility behavior stay intact.

## Addons

Addons render after the form control in the DOM for predictable focus navigation. Use `align` to choose their visual
position:

| Align | Description |
| --- | --- |
| `inline-start` | Inline-start side of a one-line input; default. |
| `inline-end` | Inline-end side of a one-line input. |
| `block-start` | Above the control, useful for textarea headers. |
| `block-end` | Below the control, useful for textarea footers. |

```blade
<hw:input-group>
    <hw:input name="url" placeholder="example.com" />

    <hw:input-group.addon align="inline-start">
        https://
    </hw:input-group.addon>

    <hw:input-group.addon align="inline-end">
        <hw:button type="submit" variant="ghost" size="sm">Go</hw:button>
    </hw:input-group.addon>
</hw:input-group>
```

## Textarea

```blade
<hw:input-group>
    <hw:textarea name="message" />

    <hw:input-group.addon align="block-end">
        0/280
    </hw:input-group.addon>
</hw:input-group>
```

## Custom Controls

Use `data-slot="input-group-control"` when composing a custom control so the Nova preset can apply the same group focus
and layout styles.

```blade
<hw:input-group>
    <input data-slot="input-group-control" name="amount" inputmode="decimal" />

    <hw:input-group.addon align="inline-end">
        USD
    </hw:input-group.addon>
</hw:input-group>
```

## Styling Hooks

- `data-slot="input-group"`
- `data-slot="input-group-addon"`
- `data-align="inline-start|inline-end|block-start|block-end"`
- `data-slot="input-group-control"` for custom controls

## Required Controllers

None. Controllers come from the components you compose inside the group, such as `clear-input` on `<hw:input clearable>`.
