# Input OTP controller

Enhances one native text input with server-rendered decorative segments. Prefer the
[Input OTP component](../components/input-otp.md), which supplies the numeric HTML constraints, Field integration and
accessible markup.

## Targets

| Target | Purpose |
| --- | --- |
| `input` | One native text input with a positive `maxlength` matching the segment count; defaults to the segment count if omitted |
| `slots` | Decorative row with `aria-hidden="true"` and initial `hidden` |
| `slot` | One server-rendered segment for each digit |

## Actions

- `input`: convert Unicode decimal digits to ASCII, remove other characters and dispatch completion after a changed
  complete value.
- `paste`: normalize clipboard text before the native length limit, replacing the whole value for a full code or the
  selection for a shorter fragment, then emit a bubbling input event.
- `navigate`: move between replacement digits with unmodified arrow keys in a full code; leave shortcuts and range
  selection native.
- `render`: synchronize digits, focus and selection without changing the native value.
- `placeCaret`: select the pressed segment before native pointer focus can expose the transparent input's caret position.
- `extendSelection` / `endSelection`: extend a pointer selection across segments and release pointer capture.
- `refresh`: resynchronize after a Turbo morph or reset without completion.

Wire `input`, `change` and `compositionend` to `input`; `paste` to `paste`; keydown to `navigate`; focus, blur, select
and keyup to `render`; pointerdown to `placeCaret`, pointermove to `extendSelection`,
pointerup/pointercancel to `endSelection`; and
`turbo:morph-element` on the root to `refresh`. Composition input is left untouched until
composition ends. Descendant morph events are ignored. Form reset and document selection listeners are attached and
cleaned up by the controller; unfocused instances skip unrelated selection changes once their highlight is cleared.

## Events

`input-otp:complete` bubbles from the component root with an empty detail. It signals a changed, exact-length code after
editing, not server verification. Mount, reset, morph and reconnection do not emit it. Never use completion as proof
that the code is authentic. See the component documentation for native fallback, auto-submit and security requirements.
