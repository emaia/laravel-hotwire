# Input OTP

Numeric one-time code input with visual segments over one native text field. Preserves leading zeroes, native form
submission, autofill and Laravel Field validation. Without JavaScript, it remains a regular usable input.

## Quick example

```blade
<hw:field name="code" label="Authenticator code" description="Enter the six digits from your app." required>
    <hw:input-otp />
</hw:field>
```

The native input uses `type="text"`, `inputmode="numeric"`, `autocomplete="one-time-code"`, an exact-length numeric
`pattern`, `minlength` and `maxlength`. These numeric constraints cannot be overridden independently of `length`.
Browser autocomplete remains a hint, not a guarantee that an authenticator or password manager can supply the code.

## Props

| Prop | Default | Description |
| --- | --- | --- |
| `name` | inherited from Field | Submission name; derives id and validation key |
| `id` | derived | Native input id and label destination |
| `length` | `6` | Number of ASCII digits, from 1 to 32 |
| `value` | `null` | Explicit initial string, including leading zeroes |
| `error-key` | derived from name | Laravel validation key override |
| `old` | `false` | Opt into restoring flashed old input |
| `auto-submit` | `false` | Submit only when editing produces a changed complete code |
| `auto-submit-delay` | `null` | Delay override with `auto-submit="debounced"` |
| `class` | empty | Additional classes on the native input |
| `wrapper-class` | empty | Additional classes on the component root |
| `stimulus` | `null` | Additional Stimulus attributes on the root |

Native attributes, including `required`, `disabled`, `readonly`, `autofocus`, `form`, `autocomplete`, `aria-label` and
`aria-describedby`, apply to the input. `data-*` attributes apply to the wrapper, where custom controllers and actions
compose with `input-otp`. The active `data-input-otp-*` namespace is protected. There is no `controller` override prop:
compose behavior through an additional controller or the completion event.

## Editing and accessibility

- There is one form value and one tab stop, not one input per digit or a duplicate hidden input.
- Segments are decorative and hidden from assistive technology; Field labels, descriptions and errors name the real input.
- With JavaScript, Unicode decimal digits (including Arabic-Indic, Persian and full-width digits) are converted to ASCII;
  other characters are removed. Paste strips formatting, so `001 234` becomes `001234`. A pasted full code replaces the
  entire value regardless of the selection; shorter fragments replace only the selection.
- Arrow keys, Home/End, Backspace/Delete, keyboard selection and copying use the native input. Clicking a populated
  segment selects its digit for replacement without an intermediate focus jump. In a full code, typing advances the
  replacement selection to the next digit, and unmodified arrows select the previous/next digit. Pointer drag and
  Shift-click extend the selection across segments, using the active digit as the anchor for a collapsed caret.
  Shift+Left/Right/Home/End in a full code extends or shrinks the selection around its anchor digit, including when
  reversing direction. Home/End without Shift remain native.
- Form reset and Turbo morph/reconnection update the display without triggering completion.
- Forced-colors and print use the visible native input rather than the decorative presentation.

## Submit on completion

```blade
<hw:form action="/challenge" method="post" auto-submit>
    <hw:field name="code" label="Authenticator code" required>
        <hw:input-otp auto-submit />
    </hw:field>
    <hw:button type="submit">Verify</hw:button>
</hw:form>
```

The opt-in reuses the form's `auto-submit` controller and native `requestSubmit()` validation. It does not submit per
digit or when mounting, resetting or restoring a complete value. Keep a submit button for the no-JavaScript fallback.
The bubbling `input-otp:complete` event has no code in its detail; application behavior can react without publishing
the sensitive value through an event payload. Editing a complete value can emit another completion.

## Security boundary

This component only captures a code. Generate and validate OTPs, enforce expiry and attempt limits, and manage recovery
on the server. Validate the value as a string with an exact ASCII-digit length; never cast it to an integer.

`old` defaults to false, but the component cannot stop Laravel from flashing submitted values into the session. Exclude
the field from flashed input in the application. For sensitive authentication pages, configure private/no-store HTTP
responses and disable Turbo caching or omit sensitive regions from snapshots. Do not log the code or echo it in success
messages. Recovery codes and other alphanumeric tokens should use a regular text input.

## Styling hooks

- `data-slot="input-otp"`: root; `data-enhanced="true"` only while the controller owns the segmented presentation.
- `data-slot="input-otp-input"`: real native field and fallback appearance.
- `data-slot="input-otp-slots"`: structural decorative row; `hidden` until enhancement is ready.
- `data-slot="input-otp-slot"`: one digit; `data-active` follows focus/caret and `data-selected` follows selection.

Nova and Bloom share the same DOM and mechanics. Presets own surfaces, dimensions, selected state and visible focus.
