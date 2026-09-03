# Form control selection

Examples use the default `hw` component prefix. Replace it when the application configures another prefix.

| Submitted value or interaction | Prefer | Avoid confusing it with | Initial value prop |
| --- | --- | --- | --- |
| One native boolean/value pair | `<hw:checkbox>` | `<hw:toggle>` is pressed UI state; `<hw:switch>` is an on/off widget | `checked` |
| Several checkboxes sharing a name | `<hw:checkbox-group>` | Repeating standalone checkboxes loses group semantics and select-all support | `selected` |
| One choice from a visible list | `<hw:radio-group>` | `<hw:select>` is better for a long or compact list | `selected` |
| One compact native choice | `<hw:select>` | Native `multiple` requires an explicit `name="ids[]"` | `selected` |
| Searchable multiple selection | `<hw:multi-select>` | `<hw:select multiple>` has native behavior and a smaller API | `selected` |
| Toolbar or pressed state | `<hw:toggle>` / `<hw:toggle-group>` | A toggle is not automatically a submitted checkbox | `pressed` / `value` |
| Accessible binary setting | `<hw:switch>` | Use checkbox when native checkbox semantics are expected | `checked` |
| Short text, number, date or checkable native input | `<hw:input>` | Specialized controls expose behavior through documented props | `value` (`checked` for checkbox/radio) |
| Plain multiline text | `<hw:textarea>` | `<hw:rich-text>` stores editor-generated rich content | `value` |
| Tiptap editor with extensions/uploads | `<hw:rich-text>` | Textarea is more robust when formatting is unnecessary | `value` |
| Native file selection and current-file display | `<hw:file>` | `<hw:file-upload>` owns progress, previews and an upload pipeline | none |
| Managed uploads with progress/previews | `<hw:file-upload>` | Do not rebuild its internal `data-file-upload-*` contract manually | `value` |

For Laravel-aware controls, the initial value prop is the fallback beneath `old($errorKey, ...)` by default, so flashed
input wins without extra merge logic. Managed upload tokens use the submitted `name` as their old-input key. Native file
inputs cannot be repopulated, and standalone `<hw:toggle>` uses `pressed` interaction state instead of old input.

## Naming details

- `<hw:file multiple>`, `<hw:checkbox-group>` and `<hw:multi-select>` normalize their submitted name to `[]`.
- `<hw:select multiple>` is native and does not append `[]`; write the array name explicitly.
- Radio and array-checkbox ids include a value slug so repeated controls remain unique.
- Array validation may report both the base key and wildcard children; file controls account for `key` and `key.*`.
- Use `error-key` when Laravel validation uses a different key from the submitted HTML name.

Before using a less common prop, run `php artisan hotwire:docs <name> --component`.
