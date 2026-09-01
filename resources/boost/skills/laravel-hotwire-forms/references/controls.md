# Form control selection

Examples use the default `hw` component prefix. Replace it when the application configures another prefix.

| Submitted value or interaction | Prefer | Avoid confusing it with |
| --- | --- | --- |
| One native boolean/value pair | `<hw:checkbox>` | `<hw:toggle>` is pressed UI state; `<hw:switch>` is an on/off widget |
| Several checkboxes sharing a name | `<hw:checkbox-group>` | Repeating standalone checkboxes loses group semantics and select-all support |
| One choice from a visible list | `<hw:radio-group>` | `<hw:select>` is better for a long or compact list |
| One compact native choice | `<hw:select>` | Native `multiple` requires an explicit `name="ids[]"` |
| Searchable multiple selection | `<hw:multi-select>` | `<hw:select multiple>` has native behavior and a smaller API |
| Toolbar or pressed state | `<hw:toggle>` / `<hw:toggle-group>` | A toggle is not automatically a submitted checkbox |
| Accessible binary setting | `<hw:switch>` | Use checkbox when native checkbox semantics are expected |
| Short text, number, date or checkable native input | `<hw:input>` | Specialized controls expose behavior through documented props |
| Plain multiline text | `<hw:textarea>` | `<hw:rich-text>` stores editor-generated rich content |
| Tiptap editor with extensions/uploads | `<hw:rich-text>` | Textarea is more robust when formatting is unnecessary |
| Native file selection and current-file display | `<hw:file>` | `<hw:file-upload>` owns progress, previews and an upload pipeline |
| Managed uploads with progress/previews | `<hw:file-upload>` | Do not rebuild its internal `data-file-upload-*` contract manually |

## Naming details

- `<hw:file multiple>`, `<hw:checkbox-group>` and `<hw:multi-select>` normalize their submitted name to `[]`.
- `<hw:select multiple>` is native and does not append `[]`; write the array name explicitly.
- Radio and array-checkbox ids include a value slug so repeated controls remain unique.
- Array validation may report both the base key and wildcard children; file controls account for `key` and `key.*`.
- Use `error-key` when Laravel validation uses a different key from the submitted HTML name.

Before using a less common prop, run `php artisan hotwire:docs <name> --component`.
