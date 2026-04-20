# RFC 0001 — Flat controller naming convention

- **Status:** Draft
- **Author:** Ednilson Maia
- **Created:** 2026-04-19
- **Target release:** v0.8.0

---

## Summary

Replace the current hierarchical namespace convention (9 UI-role folders, identifiers like `dialog--modal`) with a flat structure at the top level, reserving subfolders only for distinct technical substrates (`turbo/`, `optimistic/`, `dev/`).

Drop bucket namespaces (`lib/`, `utils/`). Rename third-party wrappers by function (`input-mask`, `tooltip`). File extensions are preserved as-is (mixed `.js`/`.ts`); TypeScript standardization is deferred.

Since the package is pre-1.0 and not yet in active external use, this ships as a direct change in v0.8.0 without migration tooling.

## Motivation

### Problems with the current convention

1. **`lib/` groups by dependency, not function.** `lib--maska` is input masking (form concern), `lib--tippy` is a tooltip (overlay concern), `lib--gtm` is analytics. The "lib" grouping tells users nothing.
2. **`utils/` is a catch-all bucket.** `animated-number`, `clipboard`, `hotkey`, `timeago` share nothing beyond "didn't fit elsewhere".
3. **Vague names.** `media--pending`, `form--remote`, `frame--view-transition` require opening the file to understand intent.
4. **Namespace boundaries force arbitrary judgment** for future controllers (`autocomplete` — form or overlay? `dropdown` — nav or overlay?).

### Evidence from Basecamp Fizzy

[basecamp/fizzy](https://github.com/basecamp/fizzy/tree/main/app/javascript/controllers) organizes 66 controllers flat, with one substrate subfolder (`bridge/` for Strada). Flat works because names are descriptive compounds (`copy_to_clipboard`, not `clipboard`; `fetch_on_visible`, not `visibility`). Widget names stay single-word only when unambiguous (`dialog`, `tooltip`, `combobox`).

Aligning with this convention makes the package legible to its target audience and removes the UI-role classification burden.

## Proposal

### Convention rules

1. **Flat at top level.** Subfolders only for technical substrates: `turbo/`, `optimistic/`, `dev/` (future: `bridge/`, possibly `upload/`).
2. **Function-first names, not library-first.** `input-mask`, not `maska`. `tooltip`, not `tippy`.
3. **Compound names when a single word is ambiguous.** `copy-to-clipboard`, `confirm-dialog`, `lazy-image`.
4. **Verbs for actions, nouns for widgets.** Avoid bare adjectives (`pending`, `remote`).
5. **File:** `snake_case_controller.{js,ts}`. **Identifier:** `kebab-case`; `{substrate}--{name}` only inside subfolders.
6. **No generic buckets.** Never `utils`, `lib`, `helpers`, `shared`, `misc`.
7. **File extensions preserved.** Existing `.js` stays `.js`; existing `.ts` stays `.ts`. Full TypeScript migration is out of scope for v0.8.

### Directory layout

```
resources/js/controllers/
  animated_number_controller.ts
  auto_submit_controller.js
  autoresize_controller.js
  autoselect_controller.js
  char_counter_controller.ts
  checkbox_select_all_controller.ts
  clean_query_params_controller.js
  clear_input_controller.js
  confirm_dialog_controller.js
  copy_to_clipboard_controller.ts
  dialog_controller.js
  gtm_controller.js
  hotkey_controller.ts
  input_mask_controller.js
  lazy_image_controller.js
  oembed_controller.js
  remote_form_controller.js
  reset_files_controller.js
  timeago_controller.ts
  toast_controller.js
  toaster_controller.js
  tooltip_controller.js
  unsaved_changes_controller.js
  dev/
    log_controller.js
  optimistic/
    _dispatch.js
    dispatch_controller.js
    form_controller.js
    link_controller.js
  turbo/
    polling_controller.js
    progress_controller.js
    view_transition_controller.js
```

### Rename map

| Current identifier | New identifier | New file |
|--------------------|----------------|----------|
| `dev--log` | `dev--log` | `dev/log_controller.js` |
| `dialog--confirm` | `confirm-dialog` | `confirm_dialog_controller.js` |
| `dialog--modal` | `dialog` | `dialog_controller.js` |
| `form--autoselect` | `autoselect` | `autoselect_controller.js` |
| `form--autosubmit` | `auto-submit` | `auto_submit_controller.js` |
| `form--char-counter` | `char-counter` | `char_counter_controller.ts` |
| `form--checkbox-select-all` | `checkbox-select-all` | `checkbox_select_all_controller.ts` |
| `form--clean-querystring` | `clean-query-params` | `clean_query_params_controller.js` |
| `form--clear-input` | `clear-input` | `clear_input_controller.js` |
| `form--remote` | `remote-form` | `remote_form_controller.js` |
| `form--reset-files` | `reset-files` | `reset_files_controller.js` |
| `form--textarea-autogrow` | `autoresize` | `autoresize_controller.js` |
| `form--unsaved-changes` | `unsaved-changes` | `unsaved_changes_controller.js` |
| `frame--polling` | `turbo--polling` | `turbo/polling_controller.js` |
| `frame--progress` | `turbo--progress` | `turbo/progress_controller.js` |
| `frame--view-transition` | `turbo--view-transition` | `turbo/view_transition_controller.js` |
| `lib--gtm` | `gtm` | `gtm_controller.js` |
| `lib--maska` | `input-mask` | `input_mask_controller.js` |
| `lib--tippy` | `tooltip` | `tooltip_controller.js` |
| `media--oembed` | `oembed` | `oembed_controller.js` |
| `media--pending` | `lazy-image` | `lazy_image_controller.js` |
| `notification--toast` | `toast` | `toast_controller.js` |
| `notification--toaster` | `toaster` | `toaster_controller.js` |
| `optimistic--dispatch` | `optimistic--dispatch` | `optimistic/dispatch_controller.js` |
| `optimistic--form` | `optimistic--form` | `optimistic/form_controller.js` |
| `optimistic--link` | `optimistic--link` | `optimistic/link_controller.js` |
| `utils--animated-number` | `animated-number` | `animated_number_controller.ts` |
| `utils--clipboard` | `copy-to-clipboard` | `copy_to_clipboard_controller.ts` |
| `utils--hotkey` | `hotkey` | `hotkey_controller.ts` |
| `utils--timeago` | `timeago` | `timeago_controller.ts` |

### Impact on `HasStimulusControllers`

| Component | Before | After |
|-----------|--------|-------|
| `ConfirmDialog` | `['dialog--confirm']` | `['confirm-dialog']` |
| `FlashContainer` | `['notification--toaster']` | `['toaster']` |
| `FlashMessage` | `['notification--toast']` | `['toast']` |
| `Modal` | `['dialog--modal']` | `['dialog']` |
| `Timeago` | `['utils--timeago']` | `['timeago']` |

The `Modal` PHP component is also renamed to `Dialog` (Blade tag: `<x-hwc::dialog>`) for consistency.

### Impact on docs

Move `docs/controllers/{namespace}/{name}.md` → `docs/controllers/{name}.md` (flat). Keep `turbo/`, `optimistic/`, `dev/` subfolders.

### Impact on commands

- `CheckCommand::identifierToParts()` — verify it handles identifiers without `--`.
- `MakeControllerCommand` — when no substrate is given, scaffold flat.
- `PublishControllersCommand` — walks new layout.

## Execution checklist (v0.8.0)

- [ ] Move/rename all 30 controllers per the map (extensions preserved).
- [ ] Update `HasStimulusControllers` return values in all components.
- [ ] Rename `Modal` component → `Dialog`; update `COMPONENTS` map, Blade view, and tests.
- [ ] Update all files under `docs/controllers/` to the new flat layout.
- [ ] Update README controller table.
- [ ] Update `CLAUDE.md` conventions section.
- [ ] Update tests: `CheckCommand`, `MakeControllerCommand`, service provider.
- [ ] `composer test` green.
- [ ] `composer analyse` green.
- [ ] Manual smoke test: `hotwire:install` + `hotwire:check` end-to-end.

## Roadmap (post-v0.8.0)

Candidates for future minor releases. Not commitments — just direction so the flat convention has a runway.

| Version | Theme | Candidates |
|---------|-------|------------|
| v0.9 | Form essentials | `combobox`, `multi-combobox`, `auto-save`, `slug-generator`, `conditional-field`, `dependent-select`, `upload-preview`, `password-reveal`, `currency-input` |
| v0.10 | Overlay expansion | `dialog-manager`, `dropdown`, `popover`, `drawer`, `command-palette` |
| v0.11 | Turbo substrate | `turbo/lazy-frame`, `turbo/stream-source`, `turbo/broadcast-from`, `turbo/refresh-on-event`, `turbo/prefetch-on-hover` |
| v0.12 | Navigation & content | `infinite-scroll`, `scroll-to`, `scroll-spy`, `sticky`, `navigable-list`, `pagination`, `syntax-highlight`, `lightbox`, `details`, `truncate`, `theme` |
| v0.13 | Interaction & state | `toggle-class`, `toggle-enable`, `element-removal`, `sortable`, `share`, `click-outside`, `disable-on-submit` |
| v0.14 | Data & tables | `filter`, `column-sort`, `batch-actions`, `csv-export`, `table-row-link` |
| v0.15 | System & persistence | `local-save`, `timezone-cookie`, `session-timeout`, `idle-logout` |
| v0.16 | Media | `video-player`, `audio-player`, `image-zoom`, `image-crop`, `qr-code` |
| future | Uploads (if grows to substrate) | `upload/direct`, `upload/chunked`, `upload/dropzone` |
| future | Strada bridge | `bridge/*` |

## Alternatives considered

**A — Keep 9 namespaces, fix only `lib/` and `utils/`.** Redistribute into UI-role namespaces. Smaller blast radius, but still forces classification for ambiguous cases and doesn't align with Basecamp.

**B — Deep UI-role taxonomy (12 namespaces).** Overengineered for a ~30-controller library; identifiers stay long; still forces boundary calls.

**C — Flat with substrate subfolders (this proposal).** Matches Basecamp. Shorter identifiers. Clear rule: subfolder only for substrate.

## Resolved decisions

1. **`remote-form` kept** for v0.8. Native `<button form="id">` covers most cases, but the controller adds ergonomics (`reset()` action, cross-form triggering). Revisit later if unused.
2. **`Modal` component renamed to `Dialog`** (Blade tag: `<x-hwc::dialog>`), matching the controller rename and the underlying `<dialog>` element.
