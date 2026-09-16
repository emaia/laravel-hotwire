<?php

/*
 * Study data: what each shadcn/ui corpus class means for the package's slot contract, and what each package
 * visual slot means against the corpus. `corpus.php` holds the extracted inventory; this file holds decisions.
 *
 * Upstream decisions: `equivalent` (same part, same name), `renamed` (same part, different name),
 * `divergent` (the package represents the concern differently) and `not-applicable` (no package counterpart).
 * Slot decisions mirror them, with `hotwire-only` where the corpus declares no class for the part.
 *
 * Neither direction is a runtime registry, a compatibility promise or public API: upstream classes never become
 * package API, and a decision here never widens the official presets. See `docs/preset-expressiveness.md`.
 */

return [
    'reference' => [
        'commit' => 'b6084839ad452b80c026cc11e588281206d49193',
        'upstream' => '3ba91b1cc83e1bbe4ab35a422ff2a694849c5048',
    ],

    // Upstream class => what the package does about it.
    'upstream' => [
        'cn-accordion' => ['decision' => 'equivalent', 'slot' => 'accordion'],
        'cn-accordion-content' => ['decision' => 'equivalent', 'slot' => 'accordion-content'],
        'cn-accordion-content-inner' => [
            'decision' => 'divergent',
            'slot' => 'accordion-content',
            'note' => 'Upstream needs an inner wrapper to animate height; the package collapses a single content element'
                .' through `::details-content` in the structural stylesheet.',
        ],
        'cn-accordion-item' => ['decision' => 'equivalent', 'slot' => 'accordion-item'],
        'cn-accordion-trigger' => ['decision' => 'equivalent', 'slot' => 'accordion-trigger'],
        'cn-alert' => ['decision' => 'equivalent', 'slot' => 'alert'],
        'cn-alert-action' => ['decision' => 'equivalent', 'slot' => 'alert-action'],
        'cn-alert-description' => ['decision' => 'equivalent', 'slot' => 'alert-description'],
        'cn-alert-dialog-content' => ['decision' => 'renamed', 'slot' => 'alert-dialog-panel'],
        'cn-alert-dialog-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-alert-dialog-description' => ['decision' => 'equivalent', 'slot' => 'alert-dialog-description'],
        'cn-alert-dialog-footer' => ['decision' => 'equivalent', 'slot' => 'alert-dialog-footer'],
        'cn-alert-dialog-header' => ['decision' => 'equivalent', 'slot' => 'alert-dialog-header'],
        'cn-alert-dialog-media' => [
            'decision' => 'divergent',
            'slot' => 'alert-dialog-header',
            'note' => 'Upstream adds a dedicated media well to the alert dialog header; the package composes the graphic'
                .' inside `alert-dialog-header` and has no package use case for a separate part yet.',
        ],
        'cn-alert-dialog-overlay' => ['decision' => 'renamed', 'slot' => 'alert-dialog-backdrop'],
        'cn-alert-dialog-overlay-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-alert-dialog-title' => ['decision' => 'equivalent', 'slot' => 'alert-dialog-title'],
        'cn-alert-title' => ['decision' => 'equivalent', 'slot' => 'alert-title'],
        'cn-alert-variant-default' => [
            'decision' => 'divergent',
            'slot' => 'alert',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-alert-variant-destructive' => [
            'decision' => 'divergent',
            'slot' => 'alert',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-attachment' => ['decision' => 'equivalent', 'slot' => 'attachment'],
        'cn-attachment-actions' => ['decision' => 'equivalent', 'slot' => 'attachment-actions'],
        'cn-attachment-content' => ['decision' => 'equivalent', 'slot' => 'attachment-content'],
        'cn-attachment-description' => ['decision' => 'equivalent', 'slot' => 'attachment-description'],
        'cn-attachment-group' => ['decision' => 'equivalent', 'slot' => 'attachment-group'],
        'cn-attachment-media' => ['decision' => 'equivalent', 'slot' => 'attachment-media'],
        'cn-attachment-media-variant-image' => [
            'decision' => 'divergent',
            'slot' => 'attachment-media',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-attachment-orientation-horizontal' => [
            'decision' => 'divergent',
            'slot' => 'attachment',
            'note' => 'Upstream spells the orientation into the class name; the package keeps one part and selects'
                .' `[data-orientation]` on it.',
        ],
        'cn-attachment-orientation-vertical' => [
            'decision' => 'divergent',
            'slot' => 'attachment',
            'note' => 'Upstream spells the orientation into the class name; the package keeps one part and selects'
                .' `[data-orientation]` on it.',
        ],
        'cn-attachment-size-default' => [
            'decision' => 'divergent',
            'slot' => 'attachment',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-attachment-size-sm' => [
            'decision' => 'divergent',
            'slot' => 'attachment',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-attachment-size-xs' => [
            'decision' => 'divergent',
            'slot' => 'attachment',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-attachment-title' => ['decision' => 'equivalent', 'slot' => 'attachment-title'],
        'cn-attachment-trigger' => ['decision' => 'equivalent', 'slot' => 'attachment-trigger'],
        'cn-avatar' => ['decision' => 'equivalent', 'slot' => 'avatar'],
        'cn-avatar-badge' => ['decision' => 'equivalent', 'slot' => 'avatar-badge'],
        'cn-avatar-fallback' => ['decision' => 'equivalent', 'slot' => 'avatar-fallback'],
        'cn-avatar-group-count' => ['decision' => 'equivalent', 'slot' => 'avatar-group-count'],
        'cn-avatar-image' => ['decision' => 'equivalent', 'slot' => 'avatar-image'],
        'cn-badge' => ['decision' => 'equivalent', 'slot' => 'badge'],
        'cn-badge-variant-default' => [
            'decision' => 'divergent',
            'slot' => 'badge',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-badge-variant-destructive' => [
            'decision' => 'divergent',
            'slot' => 'badge',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-badge-variant-ghost' => [
            'decision' => 'divergent',
            'slot' => 'badge',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-badge-variant-link' => [
            'decision' => 'divergent',
            'slot' => 'badge',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-badge-variant-outline' => [
            'decision' => 'divergent',
            'slot' => 'badge',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-badge-variant-secondary' => [
            'decision' => 'divergent',
            'slot' => 'badge',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-breadcrumb-ellipsis' => ['decision' => 'equivalent', 'slot' => 'breadcrumb-ellipsis'],
        'cn-breadcrumb-item' => ['decision' => 'equivalent', 'slot' => 'breadcrumb-item'],
        'cn-breadcrumb-link' => ['decision' => 'equivalent', 'slot' => 'breadcrumb-link'],
        'cn-breadcrumb-list' => ['decision' => 'equivalent', 'slot' => 'breadcrumb-list'],
        'cn-breadcrumb-page' => ['decision' => 'equivalent', 'slot' => 'breadcrumb-page'],
        'cn-breadcrumb-separator' => ['decision' => 'equivalent', 'slot' => 'breadcrumb-separator'],
        'cn-bubble' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-content' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-group' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-reactions' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-reactions-align-end' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-reactions-align-start' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-reactions-side-bottom' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-reactions-side-top' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-variant-default' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-variant-destructive' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-variant-ghost' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-variant-muted' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-variant-outline' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-variant-secondary' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-bubble-variant-tinted' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Bubble, so this anatomy has no registry counterpart.',
        ],
        'cn-button' => ['decision' => 'equivalent', 'slot' => 'button'],
        'cn-button-group' => ['decision' => 'equivalent', 'slot' => 'button-group'],
        'cn-button-group-orientation-horizontal' => [
            'decision' => 'divergent',
            'slot' => 'button-group',
            'note' => 'Upstream spells the orientation into the class name; the package keeps one part and selects'
                .' `[data-orientation]` on it.',
        ],
        'cn-button-group-orientation-vertical' => [
            'decision' => 'divergent',
            'slot' => 'button-group',
            'note' => 'Upstream spells the orientation into the class name; the package keeps one part and selects'
                .' `[data-orientation]` on it.',
        ],
        'cn-button-group-separator' => ['decision' => 'equivalent', 'slot' => 'button-group-separator'],
        'cn-button-group-text' => ['decision' => 'equivalent', 'slot' => 'button-group-text'],
        'cn-button-size-default' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-button-size-icon' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-button-size-icon-lg' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-button-size-icon-sm' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-button-size-icon-xs' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-button-size-lg' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-button-size-sm' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-button-size-xs' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-button-variant-default' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-button-variant-destructive' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-button-variant-ghost' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-button-variant-link' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-button-variant-outline' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-button-variant-secondary' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-calendar' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Calendar or date picker, so this anatomy has no registry counterpart.',
        ],
        'cn-calendar-caption' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Calendar or date picker, so this anatomy has no registry counterpart.',
        ],
        'cn-calendar-caption-label' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Calendar or date picker, so this anatomy has no registry counterpart.',
        ],
        'cn-calendar-dropdown-root' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Calendar or date picker, so this anatomy has no registry counterpart.',
        ],
        'cn-card' => ['decision' => 'equivalent', 'slot' => 'card'],
        'cn-card-content' => ['decision' => 'equivalent', 'slot' => 'card-content'],
        'cn-card-description' => ['decision' => 'equivalent', 'slot' => 'card-description'],
        'cn-card-footer' => ['decision' => 'equivalent', 'slot' => 'card-footer'],
        'cn-card-header' => ['decision' => 'equivalent', 'slot' => 'card-header'],
        'cn-card-title' => ['decision' => 'equivalent', 'slot' => 'card-title'],
        'cn-carousel-next' => ['decision' => 'renamed', 'slot' => 'carousel-next-button'],
        'cn-carousel-previous' => ['decision' => 'renamed', 'slot' => 'carousel-prev-button'],
        'cn-chart-tooltip' => [
            'decision' => 'not-applicable',
            'note' => 'The package\'s Chart delegates the tooltip to ECharts, which renders and styles it outside the slot'
                .' contract.',
        ],
        'cn-checkbox' => ['decision' => 'equivalent', 'slot' => 'checkbox'],
        'cn-checkbox-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-checkbox-indicator' => [
            'decision' => 'divergent',
            'slot' => 'checkbox',
            'note' => 'Upstream paints the check in a child element; the package styles a native `<input type=checkbox>`'
                .' and draws the mark with a pseudo-element, so no indicator part exists.',
        ],
        'cn-combobox-chip' => [
            'decision' => 'divergent',
            'note' => 'Upstream renders selected values as removable chips; the package\'s Multi Select shows a truncated'
                .' summary in `multi-select-value` and has no chip part.',
        ],
        'cn-combobox-chip-remove' => [
            'decision' => 'divergent',
            'note' => 'Upstream renders selected values as removable chips; the package\'s Multi Select shows a truncated'
                .' summary in `multi-select-value` and has no chip part.',
        ],
        'cn-combobox-chips' => [
            'decision' => 'divergent',
            'note' => 'Upstream renders selected values as removable chips; the package\'s Multi Select shows a truncated'
                .' summary in `multi-select-value` and has no chip part.',
        ],
        'cn-combobox-content' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-content',
            'note' => 'The package answers this need with Multi Select over a native `<select>`, so the part exists under'
                .' the `multi-select-*` anatomy rather than a Combobox port.',
        ],
        'cn-combobox-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-combobox-content-logical' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-content',
            'note' => 'Upstream animates the popup from logical `data-[side=inline-*]` values; the package\'s Floating UI'
                .' controllers resolve the placement and emit a physical `data-side`.',
        ],
        'cn-combobox-empty' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-empty',
            'note' => 'The package answers this need with Multi Select over a native `<select>`, so the part exists under'
                .' the `multi-select-*` anatomy rather than a Combobox port.',
        ],
        'cn-combobox-item' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-option',
            'note' => 'The package answers this need with Multi Select over a native `<select>`, so the part exists under'
                .' the `multi-select-*` anatomy rather than a Combobox port.',
        ],
        'cn-combobox-item-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-combobox-item-indicator' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-indicator',
            'note' => 'The package answers this need with Multi Select over a native `<select>`, so the part exists under'
                .' the `multi-select-*` anatomy rather than a Combobox port.',
        ],
        'cn-combobox-item-text' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-option-text',
            'note' => 'The package answers this need with Multi Select over a native `<select>`, so the part exists under'
                .' the `multi-select-*` anatomy rather than a Combobox port.',
        ],
        'cn-combobox-label' => [
            'decision' => 'divergent',
            'note' => 'Upstream groups options under a label inside the popup; the package\'s Multi Select list has no'
                .' grouping part.',
        ],
        'cn-combobox-list' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-list',
            'note' => 'The package answers this need with Multi Select over a native `<select>`, so the part exists under'
                .' the `multi-select-*` anatomy rather than a Combobox port.',
        ],
        'cn-combobox-separator' => [
            'decision' => 'divergent',
            'note' => 'Upstream separates option groups inside the popup; the package\'s Multi Select list has no separator'
                .' part.',
        ],
        'cn-combobox-trigger' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-trigger',
            'note' => 'The package answers this need with Multi Select over a native `<select>`, so the part exists under'
                .' the `multi-select-*` anatomy rather than a Combobox port.',
        ],
        'cn-combobox-trigger-icon' => [
            'decision' => 'divergent',
            'slot' => 'multi-select-trigger-icon',
            'note' => 'The package answers this need with Multi Select over a native `<select>`, so the part exists under'
                .' the `multi-select-*` anatomy rather than a Combobox port.',
        ],
        'cn-command' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-dialog' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-empty' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-group' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-input' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-input-group' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-input-icon' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-input-wrapper' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-item' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-item-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-command-list' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-separator' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-shortcut' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Command palette, so this anatomy has no registry counterpart.',
        ],
        'cn-command-shortcut-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-context-menu-checkbox-item' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-content' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-context-menu-content-logical' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-item' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-item-indicator' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-label' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-radio-item' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-separator' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-shortcut' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-sub-content' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-sub-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-context-menu-sub-trigger' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-context-menu-subcontent' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Context Menu, so this anatomy has no registry counterpart.',
        ],
        'cn-dialog-close' => ['decision' => 'renamed', 'slot' => 'modal-close'],
        'cn-dialog-content' => ['decision' => 'renamed', 'slot' => 'modal-panel'],
        'cn-dialog-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-dialog-description' => ['decision' => 'renamed', 'slot' => 'modal-description'],
        'cn-dialog-footer' => ['decision' => 'renamed', 'slot' => 'modal-footer'],
        'cn-dialog-header' => ['decision' => 'renamed', 'slot' => 'modal-header'],
        'cn-dialog-overlay' => ['decision' => 'renamed', 'slot' => 'modal-backdrop'],
        'cn-dialog-overlay-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-dialog-title' => ['decision' => 'renamed', 'slot' => 'modal-title'],
        'cn-drawer-content' => [
            'decision' => 'divergent',
            'slot' => 'drawer-content',
            'note' => 'Name collision: upstream\'s vaul variant calls the sliding panel `drawer-content`, while the'
                .' package\'s `drawer-content` is the scrollable region inside `drawer-popup`.',
        ],
        'cn-drawer-description' => ['decision' => 'equivalent', 'slot' => 'drawer-description'],
        'cn-drawer-footer' => ['decision' => 'equivalent', 'slot' => 'drawer-footer'],
        'cn-drawer-footer-base' => [
            'decision' => 'divergent',
            'slot' => 'drawer-footer',
            'note' => 'Upstream splits a substrate-neutral base layer from the drawer footer; the package styles one footer'
                .' part.',
        ],
        'cn-drawer-handle' => [
            'decision' => 'not-applicable',
            'note' => 'Vaul drag handle: the package\'s Drawer opens and closes through Presence and has no swipe gesture to'
                .' decorate.',
        ],
        'cn-drawer-header' => ['decision' => 'equivalent', 'slot' => 'drawer-header'],
        'cn-drawer-header-base' => [
            'decision' => 'divergent',
            'slot' => 'drawer-header',
            'note' => 'Upstream splits a substrate-neutral base layer from the drawer header; the package styles one header'
                .' part.',
        ],
        'cn-drawer-overlay' => ['decision' => 'renamed', 'slot' => 'drawer-backdrop'],
        'cn-drawer-popup' => ['decision' => 'equivalent', 'slot' => 'drawer-popup'],
        'cn-drawer-swipe-handle' => [
            'decision' => 'not-applicable',
            'note' => 'Vaul swipe affordance: the package\'s Drawer has no swipe gesture, and the study decided not to port'
                .' swipe semantics without the behavior.',
        ],
        'cn-drawer-title' => ['decision' => 'equivalent', 'slot' => 'drawer-title'],
        'cn-dropdown-menu-checkbox-item' => [
            'decision' => 'divergent',
            'slot' => 'dropdown-item',
            'note' => 'Upstream ships checkable menu items; the package\'s Dropdown has one item part and leaves selection'
                .' state to the composed control.',
        ],
        'cn-dropdown-menu-content' => ['decision' => 'renamed', 'slot' => 'dropdown-menu'],
        'cn-dropdown-menu-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-dropdown-menu-content-logical' => [
            'decision' => 'divergent',
            'slot' => 'dropdown-menu',
            'note' => 'Upstream animates the menu from logical `data-[side=inline-*]` values; the package\'s Floating UI'
                .' controllers resolve the placement and emit a physical `data-side`.',
        ],
        'cn-dropdown-menu-item' => ['decision' => 'renamed', 'slot' => 'dropdown-item'],
        'cn-dropdown-menu-item-indicator' => [
            'decision' => 'divergent',
            'slot' => 'dropdown-item',
            'note' => 'Upstream paints selection in a dedicated indicator child; the package\'s Dropdown item owns its own'
                .' marker.',
        ],
        'cn-dropdown-menu-label' => ['decision' => 'renamed', 'slot' => 'dropdown-label'],
        'cn-dropdown-menu-radio-item' => [
            'decision' => 'divergent',
            'slot' => 'dropdown-item',
            'note' => 'Upstream ships radio menu items; the package\'s Dropdown has one item part and leaves selection state'
                .' to the composed control.',
        ],
        'cn-dropdown-menu-separator' => ['decision' => 'renamed', 'slot' => 'dropdown-separator'],
        'cn-dropdown-menu-shortcut' => ['decision' => 'renamed', 'slot' => 'dropdown-shortcut'],
        'cn-dropdown-menu-sub-content' => [
            'decision' => 'divergent',
            'slot' => 'dropdown-menu',
            'note' => 'Upstream ships nested submenus; the package\'s Dropdown has a single menu surface.',
        ],
        'cn-dropdown-menu-sub-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-dropdown-menu-sub-trigger' => [
            'decision' => 'divergent',
            'slot' => 'dropdown-item',
            'note' => 'Upstream ships nested submenus; the package\'s Dropdown has no submenu trigger part.',
        ],
        'cn-dropdown-menu-subcontent' => [
            'decision' => 'divergent',
            'slot' => 'dropdown-menu',
            'note' => 'Upstream ships nested submenus; the package\'s Dropdown has a single menu surface.',
        ],
        'cn-empty' => ['decision' => 'renamed', 'slot' => 'empty-state'],
        'cn-empty-content' => ['decision' => 'renamed', 'slot' => 'empty-state-content'],
        'cn-empty-description' => ['decision' => 'renamed', 'slot' => 'empty-state-description'],
        'cn-empty-header' => ['decision' => 'renamed', 'slot' => 'empty-state-header'],
        'cn-empty-media' => ['decision' => 'renamed', 'slot' => 'empty-state-media'],
        'cn-empty-media-default' => [
            'decision' => 'divergent',
            'slot' => 'empty-state-media',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-empty-media-icon' => [
            'decision' => 'divergent',
            'slot' => 'empty-state-media',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-empty-title' => ['decision' => 'renamed', 'slot' => 'empty-state-title'],
        'cn-field' => ['decision' => 'equivalent', 'slot' => 'field'],
        'cn-field-content' => ['decision' => 'equivalent', 'slot' => 'field-content'],
        'cn-field-description' => ['decision' => 'equivalent', 'slot' => 'field-description'],
        'cn-field-error' => ['decision' => 'equivalent', 'slot' => 'field-error'],
        'cn-field-group' => ['decision' => 'equivalent', 'slot' => 'field-group'],
        'cn-field-label' => ['decision' => 'equivalent', 'slot' => 'field-label'],
        'cn-field-label-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-field-legend' => ['decision' => 'equivalent', 'slot' => 'field-legend'],
        'cn-field-separator' => ['decision' => 'equivalent', 'slot' => 'field-separator'],
        'cn-field-separator-content' => ['decision' => 'equivalent', 'slot' => 'field-separator-content'],
        'cn-field-set' => ['decision' => 'equivalent', 'slot' => 'field-set'],
        'cn-field-title' => ['decision' => 'equivalent', 'slot' => 'field-title'],
        'cn-hover-card-content' => ['decision' => 'equivalent', 'slot' => 'hover-card-content'],
        'cn-hover-card-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-hover-card-content-logical' => [
            'decision' => 'divergent',
            'slot' => 'hover-card-content',
            'note' => 'Upstream animates the surface from logical `data-[side=inline-*]` values; the package\'s Floating UI'
                .' controllers resolve the placement and emit a physical `data-side`.',
        ],
        'cn-input' => ['decision' => 'equivalent', 'slot' => 'input'],
        'cn-input-group' => ['decision' => 'equivalent', 'slot' => 'input-group'],
        'cn-input-group-addon' => ['decision' => 'equivalent', 'slot' => 'input-group-addon'],
        'cn-input-group-addon-align-block-end' => [
            'decision' => 'divergent',
            'slot' => 'input-group-addon',
            'note' => 'Upstream spells the alignment into the class name; the package keeps one part and selects'
                .' `[data-align]` on it.',
        ],
        'cn-input-group-addon-align-block-start' => [
            'decision' => 'divergent',
            'slot' => 'input-group-addon',
            'note' => 'Upstream spells the alignment into the class name; the package keeps one part and selects'
                .' `[data-align]` on it.',
        ],
        'cn-input-group-addon-align-inline-end' => [
            'decision' => 'divergent',
            'slot' => 'input-group-addon',
            'note' => 'Upstream spells the alignment into the class name; the package keeps one part and selects'
                .' `[data-align]` on it.',
        ],
        'cn-input-group-addon-align-inline-start' => [
            'decision' => 'divergent',
            'slot' => 'input-group-addon',
            'note' => 'Upstream spells the alignment into the class name; the package keeps one part and selects'
                .' `[data-align]` on it.',
        ],
        'cn-input-group-button' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream restyles a button nested in the group through its own class; the package composes its'
                .' Button, so the part is `button` while `input-group-control` stays the hook for custom controls.',
        ],
        'cn-input-group-button-size-icon-sm' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream declares a separate size scale for buttons nested in an input group; the package selects'
                .' `[data-size]` on the composed `button`.',
        ],
        'cn-input-group-button-size-icon-xs' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream declares a separate size scale for buttons nested in an input group; the package selects'
                .' `[data-size]` on the composed `button`.',
        ],
        'cn-input-group-button-size-sm' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream declares a separate size scale for buttons nested in an input group; the package selects'
                .' `[data-size]` on the composed `button`.',
        ],
        'cn-input-group-button-size-xs' => [
            'decision' => 'divergent',
            'slot' => 'button',
            'note' => 'Upstream declares a separate size scale for buttons nested in an input group; the package selects'
                .' `[data-size]` on the composed `button`.',
        ],
        'cn-input-group-input' => [
            'decision' => 'divergent',
            'slot' => 'input-group-control',
            'note' => 'The package exposes one `input-group-control` hook for any nested control instead of one class per'
                .' control type.',
        ],
        'cn-input-group-text' => [
            'decision' => 'divergent',
            'slot' => 'input-group-addon',
            'note' => 'Upstream styles text inside the addon through a separate class; the package folds it into'
                .' `input-group-addon`.',
        ],
        'cn-input-group-textarea' => [
            'decision' => 'divergent',
            'slot' => 'input-group-control',
            'note' => 'The package exposes one `input-group-control` hook for any nested control instead of one class per'
                .' control type.',
        ],
        'cn-input-otp' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no one-time-password input, so this anatomy has no registry counterpart.',
        ],
        'cn-input-otp-caret-line' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no one-time-password input, so this anatomy has no registry counterpart.',
        ],
        'cn-input-otp-group' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no one-time-password input, so this anatomy has no registry counterpart.',
        ],
        'cn-input-otp-separator' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no one-time-password input, so this anatomy has no registry counterpart.',
        ],
        'cn-input-otp-slot' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no one-time-password input, so this anatomy has no registry counterpart.',
        ],
        'cn-item' => ['decision' => 'equivalent', 'slot' => 'item'],
        'cn-item-actions' => ['decision' => 'equivalent', 'slot' => 'item-actions'],
        'cn-item-content' => ['decision' => 'equivalent', 'slot' => 'item-content'],
        'cn-item-description' => ['decision' => 'equivalent', 'slot' => 'item-description'],
        'cn-item-footer' => ['decision' => 'equivalent', 'slot' => 'item-footer'],
        'cn-item-group' => ['decision' => 'equivalent', 'slot' => 'item-group'],
        'cn-item-header' => ['decision' => 'equivalent', 'slot' => 'item-header'],
        'cn-item-media' => ['decision' => 'equivalent', 'slot' => 'item-media'],
        'cn-item-media-variant-default' => [
            'decision' => 'divergent',
            'slot' => 'item-media',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-item-media-variant-icon' => [
            'decision' => 'divergent',
            'slot' => 'item-media',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-item-media-variant-image' => [
            'decision' => 'divergent',
            'slot' => 'item-media',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-item-separator' => ['decision' => 'equivalent', 'slot' => 'item-separator'],
        'cn-item-size-default' => [
            'decision' => 'divergent',
            'slot' => 'item',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-item-size-sm' => [
            'decision' => 'divergent',
            'slot' => 'item',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-item-size-xs' => [
            'decision' => 'divergent',
            'slot' => 'item',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-item-title' => ['decision' => 'equivalent', 'slot' => 'item-title'],
        'cn-item-variant-default' => [
            'decision' => 'divergent',
            'slot' => 'item',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-item-variant-muted' => [
            'decision' => 'divergent',
            'slot' => 'item',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-item-variant-outline' => [
            'decision' => 'divergent',
            'slot' => 'item',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-kbd' => ['decision' => 'equivalent', 'slot' => 'kbd'],
        'cn-kbd-group' => ['decision' => 'equivalent', 'slot' => 'kbd-group'],
        'cn-label' => [
            'decision' => 'divergent',
            'slot' => 'field-label',
            'note' => 'Upstream ships a standalone Label primitive next to the Field-scoped label; the package has one'
                .' `field-label` part for both.',
        ],
        'cn-label-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-marker' => ['decision' => 'equivalent', 'slot' => 'marker'],
        'cn-marker-content' => ['decision' => 'equivalent', 'slot' => 'marker-content'],
        'cn-marker-icon' => ['decision' => 'equivalent', 'slot' => 'marker-icon'],
        'cn-marker-variant-border' => [
            'decision' => 'divergent',
            'slot' => 'marker',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-marker-variant-separator' => [
            'decision' => 'divergent',
            'slot' => 'marker',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-menu-translucent' => [
            'decision' => 'not-applicable',
            'note' => 'Upstream\'s menu treatment is a separate configuration dimension applied across menu surfaces; the'
                .' package leaves translucency to preset CSS rather than a part.',
        ],
        'cn-menu-translucent-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-menubar' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-checkbox-item' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-checkbox-item-indicator' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-content' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-content-logical' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-item' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-label' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-radio-item' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-radio-item-indicator' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-separator' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-shortcut' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-sub-content' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-sub-trigger' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-menubar-trigger' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Menubar, so this anatomy has no registry counterpart.',
        ],
        'cn-message' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Message, so this anatomy has no registry counterpart.',
        ],
        'cn-message-avatar' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Message, so this anatomy has no registry counterpart.',
        ],
        'cn-message-content' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Message, so this anatomy has no registry counterpart.',
        ],
        'cn-message-footer' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Message, so this anatomy has no registry counterpart.',
        ],
        'cn-message-group' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Message, so this anatomy has no registry counterpart.',
        ],
        'cn-message-header' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Message, so this anatomy has no registry counterpart.',
        ],
        'cn-message-scroller-content' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no chat Message, so this anatomy has no registry counterpart.',
        ],
        'cn-native-select' => ['decision' => 'renamed', 'slot' => 'select'],
        'cn-native-select-icon' => ['decision' => 'renamed', 'slot' => 'select-icon'],
        'cn-navigation-menu' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-content' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-indicator' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-indicator-arrow' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-link' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-list' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-popup' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-positioner' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-trigger' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-trigger-icon' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-navigation-menu-viewport' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Navigation Menu; Navbar is a plain navigation bar, not a port of the upstream'
                .' flyout anatomy.',
        ],
        'cn-pagination-content' => ['decision' => 'equivalent', 'slot' => 'pagination-content'],
        'cn-pagination-ellipsis' => ['decision' => 'equivalent', 'slot' => 'pagination-ellipsis'],
        'cn-pagination-next' => ['decision' => 'equivalent', 'slot' => 'pagination-next'],
        'cn-pagination-previous' => ['decision' => 'equivalent', 'slot' => 'pagination-previous'],
        'cn-popover-content' => ['decision' => 'equivalent', 'slot' => 'popover-content'],
        'cn-popover-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-popover-content-logical' => [
            'decision' => 'divergent',
            'slot' => 'popover-content',
            'note' => 'Upstream animates the surface from logical `data-[side=inline-*]` values; the package\'s Floating UI'
                .' controllers resolve the placement and emit a physical `data-side`.',
        ],
        'cn-popover-description' => ['decision' => 'equivalent', 'slot' => 'popover-description'],
        'cn-popover-header' => ['decision' => 'equivalent', 'slot' => 'popover-header'],
        'cn-popover-title' => ['decision' => 'equivalent', 'slot' => 'popover-title'],
        'cn-progress' => [
            'decision' => 'divergent',
            'slot' => 'progress-track',
            'note' => 'Name collision: upstream declares `cn-progress` and `cn-progress-track` with identical bodies for'
                .' the bar, while the package\'s `progress` is the row that holds label, track and value.',
        ],
        'cn-progress-indicator' => ['decision' => 'equivalent', 'slot' => 'progress-indicator'],
        'cn-progress-label' => ['decision' => 'equivalent', 'slot' => 'progress-label'],
        'cn-progress-track' => ['decision' => 'equivalent', 'slot' => 'progress-track'],
        'cn-progress-value' => ['decision' => 'equivalent', 'slot' => 'progress-value'],
        'cn-questionnaire' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-actions' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-choice' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-choice-content' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-choice-description' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-choice-indicator' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-choice-indicator-check' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-choice-indicator-dot' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-choices' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-description' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-error' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-input' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-input-wrapper' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-item' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-progress' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-shortcut' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-questionnaire-title' => [
            'decision' => 'not-applicable',
            'note' => 'Application-specific upstream block: the study decided not to port it, so it has no registry'
                .' counterpart.',
        ],
        'cn-radio-group' => ['decision' => 'equivalent', 'slot' => 'radio-group'],
        'cn-radio-group-indicator' => [
            'decision' => 'divergent',
            'slot' => 'radio-group-input',
            'note' => 'Upstream paints the dot in a child element; the package styles a native `<input type=radio>` and'
                .' draws the dot with a pseudo-element.',
        ],
        'cn-radio-group-indicator-icon' => [
            'decision' => 'divergent',
            'slot' => 'radio-group-input',
            'note' => 'Upstream paints the dot in a child element; the package styles a native `<input type=radio>` and'
                .' draws the dot with a pseudo-element.',
        ],
        'cn-radio-group-item' => ['decision' => 'renamed', 'slot' => 'radio-group-input'],
        'cn-radio-group-item-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-resizable-handle-icon' => [
            'decision' => 'not-applicable',
            'note' => 'The package ships no Resizable panel, so this anatomy has no registry counterpart.',
        ],
        'cn-scroll-area-scrollbar' => [
            'decision' => 'not-applicable',
            'note' => 'The package scrolls with native overflow and owns the geometry in `structural.css`, so there is no'
                .' custom scrollbar part.',
        ],
        'cn-scroll-area-thumb' => [
            'decision' => 'not-applicable',
            'note' => 'The package scrolls with native overflow and owns the geometry in `structural.css`, so there is no'
                .' custom scrollbar thumb part.',
        ],
        'cn-select-content' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-select-content-logical' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-empty-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-select-group' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-item' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-item-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-select-item-indicator' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-item-text' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-label' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-scroll-down-button' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-scroll-up-button' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-separator' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-trigger' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-trigger-icon' => [
            'decision' => 'divergent',
            'slot' => 'select-icon',
            'note' => 'Upstream draws the chevron inside a custom trigger; the package overlays `select-icon` on a native'
                .' `<select>`.',
        ],
        'cn-select-value' => [
            'decision' => 'divergent',
            'slot' => 'select',
            'note' => 'The package\'s Select is a native `<select>`; upstream\'s listbox anatomy is rendered by the browser'
                .' and has no package part.',
        ],
        'cn-select-value-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-separator' => ['decision' => 'equivalent', 'slot' => 'separator'],
        'cn-separator-horizontal' => [
            'decision' => 'divergent',
            'slot' => 'separator',
            'note' => 'Upstream spells the orientation into the class name; the package keeps one part and selects'
                .' `[data-orientation]` on it.',
        ],
        'cn-separator-vertical' => [
            'decision' => 'divergent',
            'slot' => 'separator',
            'note' => 'Upstream spells the orientation into the class name; the package keeps one part and selects'
                .' `[data-orientation]` on it.',
        ],
        'cn-sheet-close' => ['decision' => 'equivalent', 'slot' => 'sheet-close'],
        'cn-sheet-content' => ['decision' => 'equivalent', 'slot' => 'sheet-content'],
        'cn-sheet-description' => ['decision' => 'equivalent', 'slot' => 'sheet-description'],
        'cn-sheet-footer' => ['decision' => 'equivalent', 'slot' => 'sheet-footer'],
        'cn-sheet-header' => ['decision' => 'equivalent', 'slot' => 'sheet-header'],
        'cn-sheet-overlay' => ['decision' => 'renamed', 'slot' => 'sheet-backdrop'],
        'cn-sheet-title' => ['decision' => 'equivalent', 'slot' => 'sheet-title'],
        'cn-sidebar-content' => ['decision' => 'equivalent', 'slot' => 'sidebar-content'],
        'cn-sidebar-footer' => ['decision' => 'equivalent', 'slot' => 'sidebar-footer'],
        'cn-sidebar-gap' => ['decision' => 'equivalent', 'slot' => 'sidebar-gap'],
        'cn-sidebar-group' => ['decision' => 'equivalent', 'slot' => 'sidebar-group'],
        'cn-sidebar-group-action' => ['decision' => 'equivalent', 'slot' => 'sidebar-group-action'],
        'cn-sidebar-group-content' => ['decision' => 'equivalent', 'slot' => 'sidebar-group-content'],
        'cn-sidebar-group-label' => ['decision' => 'equivalent', 'slot' => 'sidebar-group-label'],
        'cn-sidebar-header' => ['decision' => 'equivalent', 'slot' => 'sidebar-header'],
        'cn-sidebar-inner' => ['decision' => 'equivalent', 'slot' => 'sidebar-inner'],
        'cn-sidebar-input' => ['decision' => 'equivalent', 'slot' => 'sidebar-input'],
        'cn-sidebar-inset' => ['decision' => 'equivalent', 'slot' => 'sidebar-inset'],
        'cn-sidebar-menu' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu'],
        'cn-sidebar-menu-action' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu-action'],
        'cn-sidebar-menu-badge' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu-badge'],
        'cn-sidebar-menu-button' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu-button'],
        'cn-sidebar-menu-button-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-sidebar-menu-button-size-default' => [
            'decision' => 'divergent',
            'slot' => 'sidebar-menu-button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-sidebar-menu-button-size-lg' => [
            'decision' => 'divergent',
            'slot' => 'sidebar-menu-button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-sidebar-menu-button-size-sm' => [
            'decision' => 'divergent',
            'slot' => 'sidebar-menu-button',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-sidebar-menu-button-variant-default' => [
            'decision' => 'divergent',
            'slot' => 'sidebar-menu-button',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-sidebar-menu-button-variant-outline' => [
            'decision' => 'divergent',
            'slot' => 'sidebar-menu-button',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-sidebar-menu-skeleton' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu-skeleton'],
        'cn-sidebar-menu-skeleton-icon' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu-skeleton-icon'],
        'cn-sidebar-menu-skeleton-text' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu-skeleton-text'],
        'cn-sidebar-menu-sub' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu-sub'],
        'cn-sidebar-menu-sub-button' => ['decision' => 'equivalent', 'slot' => 'sidebar-menu-sub-button'],
        'cn-sidebar-rail' => ['decision' => 'equivalent', 'slot' => 'sidebar-rail'],
        'cn-sidebar-separator' => ['decision' => 'equivalent', 'slot' => 'sidebar-separator'],
        'cn-skeleton' => ['decision' => 'equivalent', 'slot' => 'skeleton'],
        'cn-slider' => ['decision' => 'equivalent', 'slot' => 'slider'],
        'cn-slider-range' => [
            'decision' => 'divergent',
            'slot' => 'slider',
            'note' => 'The package styles a native `<input type=range>`; upstream\'s track, range and thumb children are'
                .' drawn by the browser through vendor pseudo-elements.',
        ],
        'cn-slider-thumb' => [
            'decision' => 'divergent',
            'slot' => 'slider',
            'note' => 'The package styles a native `<input type=range>`; upstream\'s track, range and thumb children are'
                .' drawn by the browser through vendor pseudo-elements.',
        ],
        'cn-slider-track' => [
            'decision' => 'divergent',
            'slot' => 'slider',
            'note' => 'The package styles a native `<input type=range>`; upstream\'s track, range and thumb children are'
                .' drawn by the browser through vendor pseudo-elements.',
        ],
        'cn-switch' => ['decision' => 'equivalent', 'slot' => 'switch'],
        'cn-switch-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-switch-thumb' => [
            'decision' => 'divergent',
            'slot' => 'switch',
            'note' => 'Upstream moves a thumb element; the package styles a native checkbox and moves the knob with a'
                .' pseudo-element.',
        ],
        'cn-switch-thumb-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-table' => ['decision' => 'equivalent', 'slot' => 'table'],
        'cn-table-body' => ['decision' => 'equivalent', 'slot' => 'table-body'],
        'cn-table-caption' => ['decision' => 'equivalent', 'slot' => 'table-caption'],
        'cn-table-cell' => ['decision' => 'equivalent', 'slot' => 'table-cell'],
        'cn-table-cell-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-table-container' => ['decision' => 'equivalent', 'slot' => 'table-container'],
        'cn-table-footer' => ['decision' => 'equivalent', 'slot' => 'table-footer'],
        'cn-table-head' => ['decision' => 'equivalent', 'slot' => 'table-head'],
        'cn-table-head-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-table-header' => ['decision' => 'equivalent', 'slot' => 'table-header'],
        'cn-table-row' => ['decision' => 'equivalent', 'slot' => 'table-row'],
        'cn-table-row-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-tabs' => ['decision' => 'equivalent', 'slot' => 'tabs'],
        'cn-tabs-content' => ['decision' => 'renamed', 'slot' => 'tabs-panel'],
        'cn-tabs-list' => ['decision' => 'equivalent', 'slot' => 'tabs-list'],
        'cn-tabs-trigger' => ['decision' => 'equivalent', 'slot' => 'tabs-trigger'],
        'cn-tabs-trigger-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-textarea' => ['decision' => 'equivalent', 'slot' => 'textarea'],
        'cn-toast' => ['decision' => 'equivalent', 'slot' => 'toast'],
        'cn-toggle' => ['decision' => 'equivalent', 'slot' => 'toggle'],
        'cn-toggle-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-toggle-group' => ['decision' => 'equivalent', 'slot' => 'toggle-group'],
        'cn-toggle-group-item' => ['decision' => 'equivalent', 'slot' => 'toggle-group-item'],
        'cn-toggle-size-default' => [
            'decision' => 'divergent',
            'slot' => 'toggle',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-toggle-size-lg' => [
            'decision' => 'divergent',
            'slot' => 'toggle',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-toggle-size-sm' => [
            'decision' => 'divergent',
            'slot' => 'toggle',
            'note' => 'Upstream spells the size into the class name; the package keeps one part and selects `[data-size]`'
                .' on it.',
        ],
        'cn-toggle-variant-default' => [
            'decision' => 'divergent',
            'slot' => 'toggle',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-toggle-variant-outline' => [
            'decision' => 'divergent',
            'slot' => 'toggle',
            'note' => 'Upstream spells the variant into the class name; the package keeps one part and selects'
                .' `[data-variant]` on it.',
        ],
        'cn-tooltip-arrow' => ['decision' => 'equivalent', 'slot' => 'tooltip-arrow'],
        'cn-tooltip-arrow-logical' => [
            'decision' => 'divergent',
            'slot' => 'tooltip-arrow',
            'note' => 'Upstream animates the arrow from logical `data-[side=inline-*]` values; the package\'s Floating UI'
                .' controllers resolve the placement and emit a physical `data-side`.',
        ],
        'cn-tooltip-content' => ['decision' => 'renamed', 'slot' => 'tooltip'],
        'cn-tooltip-content-aria' => [
            'decision' => 'not-applicable',
            'note' => 'React Aria substrate rule: it restyles the part for whichever state attributes React Aria emits on'
                .' it — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,'
                .' `data-focus-visible`, `data-placeholder` or `peer-data-disabled` — none of which the package DOM'
                .' carries.',
        ],
        'cn-tooltip-content-logical' => [
            'decision' => 'divergent',
            'slot' => 'tooltip',
            'note' => 'Upstream animates the surface from logical `data-[side=inline-*]` values; the package\'s Floating UI'
                .' controllers resolve the placement and emit a physical `data-side`.',
        ],
    ],

    // Package visual slot => what the corpus says about it.
    'slots' => [
        'accordion' => ['decision' => 'equivalent', 'class' => 'cn-accordion'],
        'accordion-content' => ['decision' => 'equivalent', 'class' => 'cn-accordion-content'],
        'accordion-item' => ['decision' => 'equivalent', 'class' => 'cn-accordion-item'],
        'accordion-trigger' => ['decision' => 'equivalent', 'class' => 'cn-accordion-trigger'],
        'accordion-trigger-icon' => [
            'decision' => 'divergent',
            'note' => 'The corpus reaches this glyph through a descendant or attribute selector on its parent\'s class; the'
                .' package names the icon so it owns its own layout.',
        ],
        'alert' => ['decision' => 'equivalent', 'class' => 'cn-alert'],
        'alert-action' => ['decision' => 'equivalent', 'class' => 'cn-alert-action'],
        'alert-description' => ['decision' => 'equivalent', 'class' => 'cn-alert-description'],
        'alert-dialog-action' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes Buttons in the alert dialog footer and declares no action classes; the package'
                .' names confirm and cancel so a preset can style them independently.',
        ],
        'alert-dialog-backdrop' => ['decision' => 'renamed', 'class' => 'cn-alert-dialog-overlay'],
        'alert-dialog-body' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s dialog panel is one scroll context; the package separates a scrollable region inside the'
                .' panel.',
        ],
        'alert-dialog-cancel' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes Buttons in the alert dialog footer and declares no action classes; the package'
                .' names confirm and cancel so a preset can style them independently.',
        ],
        'alert-dialog-description' => ['decision' => 'equivalent', 'class' => 'cn-alert-dialog-description'],
        'alert-dialog-footer' => ['decision' => 'equivalent', 'class' => 'cn-alert-dialog-footer'],
        'alert-dialog-header' => ['decision' => 'equivalent', 'class' => 'cn-alert-dialog-header'],
        'alert-dialog-overlay' => [
            'decision' => 'divergent',
            'note' => 'Upstream\'s `*-overlay` class is the dimming element, which the package calls `*-backdrop`; the'
                .' package\'s overlay is the presence layer that hosts the backdrop and the panel.',
        ],
        'alert-dialog-panel' => ['decision' => 'renamed', 'class' => 'cn-alert-dialog-content'],
        'alert-dialog-title' => ['decision' => 'equivalent', 'class' => 'cn-alert-dialog-title'],
        'alert-icon' => [
            'decision' => 'divergent',
            'note' => 'The corpus reaches this glyph through a descendant or attribute selector on its parent\'s class; the'
                .' package names the icon so it owns its own layout.',
        ],
        'alert-title' => ['decision' => 'equivalent', 'class' => 'cn-alert-title'],
        'attachment' => ['decision' => 'equivalent', 'class' => 'cn-attachment'],
        'attachment-action' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles the attachment\'s action row as a whole; the package names each action control.',
        ],
        'attachment-actions' => ['decision' => 'equivalent', 'class' => 'cn-attachment-actions'],
        'attachment-content' => ['decision' => 'equivalent', 'class' => 'cn-attachment-content'],
        'attachment-description' => ['decision' => 'equivalent', 'class' => 'cn-attachment-description'],
        'attachment-group' => ['decision' => 'equivalent', 'class' => 'cn-attachment-group'],
        'attachment-media' => ['decision' => 'equivalent', 'class' => 'cn-attachment-media'],
        'attachment-title' => ['decision' => 'equivalent', 'class' => 'cn-attachment-title'],
        'attachment-trigger' => ['decision' => 'equivalent', 'class' => 'cn-attachment-trigger'],
        'avatar' => ['decision' => 'equivalent', 'class' => 'cn-avatar'],
        'avatar-badge' => ['decision' => 'equivalent', 'class' => 'cn-avatar-badge'],
        'avatar-fallback' => ['decision' => 'equivalent', 'class' => 'cn-avatar-fallback'],
        'avatar-group' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles only the overflow count of an avatar group; the package names the group wrapper'
                .' too.',
        ],
        'avatar-group-count' => ['decision' => 'equivalent', 'class' => 'cn-avatar-group-count'],
        'avatar-image' => ['decision' => 'equivalent', 'class' => 'cn-avatar-image'],
        'back-to-top' => ['decision' => 'hotwire-only', 'note' => 'The corpus declares no class for this part.'],
        'badge' => ['decision' => 'equivalent', 'class' => 'cn-badge'],
        'breadcrumb' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles the breadcrumb list and its items; the `<nav>` root has no class.',
        ],
        'breadcrumb-ellipsis' => ['decision' => 'equivalent', 'class' => 'cn-breadcrumb-ellipsis'],
        'breadcrumb-item' => ['decision' => 'equivalent', 'class' => 'cn-breadcrumb-item'],
        'breadcrumb-link' => ['decision' => 'equivalent', 'class' => 'cn-breadcrumb-link'],
        'breadcrumb-list' => ['decision' => 'equivalent', 'class' => 'cn-breadcrumb-list'],
        'breadcrumb-page' => ['decision' => 'equivalent', 'class' => 'cn-breadcrumb-page'],
        'breadcrumb-separator' => ['decision' => 'equivalent', 'class' => 'cn-breadcrumb-separator'],
        'button' => ['decision' => 'equivalent', 'class' => 'cn-button'],
        'button-group' => ['decision' => 'equivalent', 'class' => 'cn-button-group'],
        'button-group-separator' => ['decision' => 'equivalent', 'class' => 'cn-button-group-separator'],
        'button-group-text' => ['decision' => 'equivalent', 'class' => 'cn-button-group-text'],
        'card' => ['decision' => 'equivalent', 'class' => 'cn-card'],
        'card-action' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s Card ships an action area in React, but the corpus declares no class for it.',
        ],
        'card-content' => ['decision' => 'equivalent', 'class' => 'cn-card-content'],
        'card-description' => ['decision' => 'equivalent', 'class' => 'cn-card-description'],
        'card-footer' => ['decision' => 'equivalent', 'class' => 'cn-card-footer'],
        'card-header' => ['decision' => 'equivalent', 'class' => 'cn-card-header'],
        'card-title' => ['decision' => 'equivalent', 'class' => 'cn-card-title'],
        'carousel' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles only the carousel\'s navigation buttons.',
        ],
        'carousel-counter' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles only the carousel\'s navigation buttons.',
        ],
        'carousel-dot-button' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles only the carousel\'s navigation buttons.',
        ],
        'carousel-dot-list' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles only the carousel\'s navigation buttons.',
        ],
        'carousel-next-button' => ['decision' => 'renamed', 'class' => 'cn-carousel-next'],
        'carousel-prev-button' => ['decision' => 'renamed', 'class' => 'cn-carousel-previous'],
        'carousel-progress' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles only the carousel\'s navigation buttons.',
        ],
        'carousel-progress-wrapper' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles only the carousel\'s navigation buttons.',
        ],
        'checkbox' => ['decision' => 'equivalent', 'class' => 'cn-checkbox'],
        'checkbox-group' => [
            'decision' => 'divergent',
            'note' => 'Upstream reaches this part as a `data-slot` descendant of its parent\'s class instead of giving it'
                .' one; the package names it so a preset can style it directly.',
        ],
        'checkbox-group-input' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles every checkbox with `cn-checkbox`; the package gives the grouped control its own'
                .' slot so a preset can treat it apart from a standalone checkbox.',
        ],
        'checkbox-group-item' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes Field parts around each grouped control; the package names the label wrapper.',
        ],
        'checkbox-group-item-content' => [
            'decision' => 'divergent',
            'note' => 'Upstream leans on Field and Label composition for the text beside a grouped control; the package'
                .' names the content column.',
        ],
        'clear-input-button' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no clear-input affordance.'],
        'color-scheme-toggle' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus declares no class for this part.',
        ],
        'drawer-backdrop' => ['decision' => 'renamed', 'class' => 'cn-drawer-overlay'],
        'drawer-close' => [
            'decision' => 'divergent',
            'note' => 'The corpus declares a close class for Dialog and Sheet only; the package names the close control in'
                .' every overlay family.',
        ],
        'drawer-content' => [
            'decision' => 'divergent',
            'note' => 'Name collision: upstream\'s vaul variant calls the sliding panel `drawer-content`, while the'
                .' package\'s `drawer-content` is the scrollable region inside `drawer-popup`.',
        ],
        'drawer-description' => ['decision' => 'equivalent', 'class' => 'cn-drawer-description'],
        'drawer-footer' => ['decision' => 'equivalent', 'class' => 'cn-drawer-footer'],
        'drawer-header' => ['decision' => 'equivalent', 'class' => 'cn-drawer-header'],
        'drawer-overlay' => [
            'decision' => 'divergent',
            'note' => 'Upstream\'s `*-overlay` class is the dimming element, which the package calls `*-backdrop`; the'
                .' package\'s overlay is the presence layer that hosts the backdrop and the panel.',
        ],
        'drawer-popup' => ['decision' => 'equivalent', 'class' => 'cn-drawer-popup'],
        'drawer-title' => ['decision' => 'equivalent', 'class' => 'cn-drawer-title'],
        'drawer-trigger' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes a plain Button as the trigger and declares no trigger class; the package names the'
                .' part so a preset can select its expanded state.',
        ],
        'dropdown' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s dropdown root renders no styled element; the package\'s root anchors the trigger and the'
                .' menu.',
        ],
        'dropdown-group' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles no group wrapper inside the dropdown menu; the package names it so a preset can'
                .' space grouped items.',
        ],
        'dropdown-item' => ['decision' => 'renamed', 'class' => 'cn-dropdown-menu-item'],
        'dropdown-label' => ['decision' => 'renamed', 'class' => 'cn-dropdown-menu-label'],
        'dropdown-menu' => ['decision' => 'renamed', 'class' => 'cn-dropdown-menu-content'],
        'dropdown-separator' => ['decision' => 'renamed', 'class' => 'cn-dropdown-menu-separator'],
        'dropdown-shortcut' => ['decision' => 'renamed', 'class' => 'cn-dropdown-menu-shortcut'],
        'dropdown-trigger' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes a plain Button as the trigger and declares no trigger class; the package names the'
                .' part so a preset can select its expanded state.',
        ],
        'dropdown-trigger-icon' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus declares no dropdown trigger class, so it names no glyph inside one.',
        ],
        'empty-state' => ['decision' => 'renamed', 'class' => 'cn-empty'],
        'empty-state-content' => ['decision' => 'renamed', 'class' => 'cn-empty-content'],
        'empty-state-description' => ['decision' => 'renamed', 'class' => 'cn-empty-description'],
        'empty-state-header' => ['decision' => 'renamed', 'class' => 'cn-empty-header'],
        'empty-state-media' => ['decision' => 'renamed', 'class' => 'cn-empty-media'],
        'empty-state-title' => ['decision' => 'renamed', 'class' => 'cn-empty-title'],
        'field' => ['decision' => 'equivalent', 'class' => 'cn-field'],
        'field-content' => ['decision' => 'equivalent', 'class' => 'cn-field-content'],
        'field-description' => ['decision' => 'equivalent', 'class' => 'cn-field-description'],
        'field-error' => ['decision' => 'equivalent', 'class' => 'cn-field-error'],
        'field-group' => ['decision' => 'equivalent', 'class' => 'cn-field-group'],
        'field-label' => ['decision' => 'equivalent', 'class' => 'cn-field-label'],
        'field-legend' => ['decision' => 'equivalent', 'class' => 'cn-field-legend'],
        'field-separator' => ['decision' => 'equivalent', 'class' => 'cn-field-separator'],
        'field-separator-content' => ['decision' => 'equivalent', 'class' => 'cn-field-separator-content'],
        'field-separator-line' => [
            'decision' => 'divergent',
            'note' => 'Upstream draws the separator rule on `field-separator` itself; the package splits the line into its'
                .' own part so labelled separators keep the rule symmetric.',
        ],
        'field-set' => ['decision' => 'equivalent', 'class' => 'cn-field-set'],
        'field-title' => ['decision' => 'equivalent', 'class' => 'cn-field-title'],
        'file-input' => ['decision' => 'hotwire-only', 'note' => 'The corpus styles no file input.'],
        'file-upload' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no upload pipeline; its Attachment block covers the rendered file card only.',
        ],
        'file-upload-actions' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no upload pipeline; its Attachment block covers the rendered file card only.',
        ],
        'file-upload-dropzone' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no upload pipeline; its Attachment block covers the rendered file card only.',
        ],
        'file-upload-feedback' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no upload pipeline; its Attachment block covers the rendered file card only.',
        ],
        'file-upload-image-base' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no upload pipeline; its Attachment block covers the rendered file card only.',
        ],
        'file-upload-image-preview' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no upload pipeline; its Attachment block covers the rendered file card only.',
        ],
        'file-wrapper' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles the bare control; the package wraps it so adornments such as the clear button and'
                .' the counter can be positioned against it.',
        ],
        'hover-card' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s popover root renders no styled element; the package\'s root anchors the trigger and the'
                .' surface.',
        ],
        'hover-card-content' => ['decision' => 'equivalent', 'class' => 'cn-hover-card-content'],
        'hover-card-trigger' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes a plain Button as the trigger and declares no trigger class; the package names the'
                .' part so a preset can select its expanded state.',
        ],
        'icon' => [
            'decision' => 'divergent',
            'note' => 'The corpus reaches this glyph through a descendant or attribute selector on its parent\'s class; the'
                .' package names the icon so it owns its own layout.',
        ],
        'input' => ['decision' => 'equivalent', 'class' => 'cn-input'],
        'input-group' => ['decision' => 'equivalent', 'class' => 'cn-input-group'],
        'input-group-addon' => ['decision' => 'equivalent', 'class' => 'cn-input-group-addon'],
        'input-group-control' => [
            'decision' => 'divergent',
            'note' => 'Upstream declares one class per nested control type (`input`, `textarea`, `button`); the package'
                .' exposes a single control hook, which upstream also emits as a `data-slot`.',
        ],
        'input-wrapper' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles the bare control; the package wraps it so adornments such as the clear button and'
                .' the counter can be positioned against it.',
        ],
        'item' => ['decision' => 'equivalent', 'class' => 'cn-item'],
        'item-actions' => ['decision' => 'equivalent', 'class' => 'cn-item-actions'],
        'item-content' => ['decision' => 'equivalent', 'class' => 'cn-item-content'],
        'item-description' => ['decision' => 'equivalent', 'class' => 'cn-item-description'],
        'item-footer' => ['decision' => 'equivalent', 'class' => 'cn-item-footer'],
        'item-group' => ['decision' => 'equivalent', 'class' => 'cn-item-group'],
        'item-header' => ['decision' => 'equivalent', 'class' => 'cn-item-header'],
        'item-media' => ['decision' => 'equivalent', 'class' => 'cn-item-media'],
        'item-separator' => ['decision' => 'equivalent', 'class' => 'cn-item-separator'],
        'item-title' => ['decision' => 'equivalent', 'class' => 'cn-item-title'],
        'kbd' => ['decision' => 'equivalent', 'class' => 'cn-kbd'],
        'kbd-group' => ['decision' => 'equivalent', 'class' => 'cn-kbd-group'],
        'marker' => ['decision' => 'equivalent', 'class' => 'cn-marker'],
        'marker-content' => ['decision' => 'equivalent', 'class' => 'cn-marker-content'],
        'marker-icon' => ['decision' => 'equivalent', 'class' => 'cn-marker-icon'],
        'modal-backdrop' => ['decision' => 'renamed', 'class' => 'cn-dialog-overlay'],
        'modal-close' => ['decision' => 'renamed', 'class' => 'cn-dialog-close'],
        'modal-close-icon' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles the enclosing control but gives its glyph no class of its own; the package names'
                .' the icon so a preset can size and place it directly.',
        ],
        'modal-content' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s dialog panel is one scroll context; the package separates a scrollable region inside the'
                .' panel.',
        ],
        'modal-description' => ['decision' => 'renamed', 'class' => 'cn-dialog-description'],
        'modal-footer' => ['decision' => 'renamed', 'class' => 'cn-dialog-footer'],
        'modal-header' => ['decision' => 'renamed', 'class' => 'cn-dialog-header'],
        'modal-overlay' => [
            'decision' => 'divergent',
            'note' => 'Upstream\'s `*-overlay` class is the dimming element, which the package calls `*-backdrop`; the'
                .' package\'s overlay is the presence layer that hosts the backdrop and the panel.',
        ],
        'modal-panel' => ['decision' => 'renamed', 'class' => 'cn-dialog-content'],
        'modal-positioner' => [
            'decision' => 'divergent',
            'note' => 'Upstream positions the dialog panel with fixed positioning on the panel itself; the package'
                .' separates positioning into its own part.',
        ],
        'modal-title' => ['decision' => 'renamed', 'class' => 'cn-dialog-title'],
        'modal-trigger' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes a plain Button as the trigger and declares no trigger class; the package names the'
                .' part so a preset can select its expanded state.',
        ],
        'multi-select' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-content' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-empty' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-indicator' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-list' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-native' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Multi Select is backed by a native `<select>` with in-popup search and validation;'
                .' upstream\'s Combobox has no counterpart for this part.',
        ],
        'multi-select-option' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-option-text' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-search' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Multi Select is backed by a native `<select>` with in-popup search and validation;'
                .' upstream\'s Combobox has no counterpart for this part.',
        ],
        'multi-select-search-icon' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Multi Select is backed by a native `<select>` with in-popup search and validation;'
                .' upstream\'s Combobox has no counterpart for this part.',
        ],
        'multi-select-select-all' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Multi Select is backed by a native `<select>` with in-popup search and validation;'
                .' upstream\'s Combobox has no counterpart for this part.',
        ],
        'multi-select-trigger' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-trigger-icon' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'multi-select-validation' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Multi Select is backed by a native `<select>` with in-popup search and validation;'
                .' upstream\'s Combobox has no counterpart for this part.',
        ],
        'multi-select-value' => [
            'decision' => 'divergent',
            'note' => 'The package\'s Multi Select answers upstream\'s Combobox over a native `<select>`; the parts'
                .' correspond but neither the anatomy nor the names are a port.',
        ],
        'navbar' => [
            'decision' => 'divergent',
            'note' => 'Upstream\'s Navigation Menu covers this ground with a flyout anatomy the package does not port;'
                .' Navbar is a plain navigation bar.',
        ],
        'navbar-item' => [
            'decision' => 'divergent',
            'note' => 'Upstream\'s Navigation Menu covers this ground with a flyout anatomy the package does not port;'
                .' Navbar is a plain navigation bar.',
        ],
        'oembed' => ['decision' => 'hotwire-only', 'note' => 'The corpus declares no class for this part.'],
        'oembed-frame' => ['decision' => 'hotwire-only', 'note' => 'The corpus declares no class for this part.'],
        'oembed-link' => ['decision' => 'hotwire-only', 'note' => 'The corpus declares no class for this part.'],
        'pagination' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus styles the pagination content, previous, next and ellipsis only; the `<nav>` root has no'
                .' class.',
        ],
        'pagination-content' => ['decision' => 'equivalent', 'class' => 'cn-pagination-content'],
        'pagination-ellipsis' => ['decision' => 'equivalent', 'class' => 'cn-pagination-ellipsis'],
        'pagination-item' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles pagination links with the Button classes inside `cn-pagination-content`; the package'
                .' names the item and its link.',
        ],
        'pagination-link' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles pagination links with the Button classes inside `cn-pagination-content`; the package'
                .' names the item and its link.',
        ],
        'pagination-next' => ['decision' => 'equivalent', 'class' => 'cn-pagination-next'],
        'pagination-next-content' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles the previous and next buttons as a whole; the package names their inner label so a'
                .' preset can hide or reflow it.',
        ],
        'pagination-next-icon' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles the enclosing control but gives its glyph no class of its own; the package names'
                .' the icon so a preset can size and place it directly.',
        ],
        'pagination-next-label' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles the previous and next buttons as a whole; the package names their inner label so a'
                .' preset can hide or reflow it.',
        ],
        'pagination-next-loading-content' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Pagination swaps to a Turbo loading state, which has no upstream counterpart.',
        ],
        'pagination-next-loading-label' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Pagination swaps to a Turbo loading state, which has no upstream counterpart.',
        ],
        'pagination-next-spinner' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Pagination swaps to a Turbo loading state, which has no upstream counterpart.',
        ],
        'pagination-previous' => ['decision' => 'equivalent', 'class' => 'cn-pagination-previous'],
        'pagination-previous-label' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles the previous and next buttons as a whole; the package names their inner label so a'
                .' preset can hide or reflow it.',
        ],
        'popover' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s popover root renders no styled element; the package\'s root anchors the trigger and the'
                .' surface.',
        ],
        'popover-content' => ['decision' => 'equivalent', 'class' => 'cn-popover-content'],
        'popover-description' => ['decision' => 'equivalent', 'class' => 'cn-popover-description'],
        'popover-header' => ['decision' => 'equivalent', 'class' => 'cn-popover-header'],
        'popover-title' => ['decision' => 'equivalent', 'class' => 'cn-popover-title'],
        'popover-trigger' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes a plain Button as the trigger and declares no trigger class; the package names the'
                .' part so a preset can select its expanded state.',
        ],
        'progress' => [
            'decision' => 'divergent',
            'note' => 'Upstream\'s `cn-progress` styles the bar itself, which is the package\'s `progress-track`; the corpus'
                .' declares no class for the row that holds label, track and value.',
        ],
        'progress-indicator' => ['decision' => 'equivalent', 'class' => 'cn-progress-indicator'],
        'progress-label' => ['decision' => 'equivalent', 'class' => 'cn-progress-label'],
        'progress-track' => ['decision' => 'equivalent', 'class' => 'cn-progress-track'],
        'progress-value' => ['decision' => 'equivalent', 'class' => 'cn-progress-value'],
        'radio-group' => ['decision' => 'equivalent', 'class' => 'cn-radio-group'],
        'radio-group-input' => ['decision' => 'renamed', 'class' => 'cn-radio-group-item'],
        'radio-group-item' => [
            'decision' => 'divergent',
            'note' => 'Name collision: upstream\'s `cn-radio-group-item` is the control itself (mapped to'
                .' `radio-group-input`), while the package\'s `radio-group-item` is the label wrapper around control and'
                .' content.',
        ],
        'radio-group-item-content' => [
            'decision' => 'divergent',
            'note' => 'Upstream leans on Field and Label composition for the text beside a grouped control; the package'
                .' names the content column.',
        ],
        'read-more' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no progressive disclosure block.'],
        'read-more-content' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no progressive disclosure block.',
        ],
        'read-more-fade' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no progressive disclosure block.'],
        'read-more-trigger' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no progressive disclosure block.',
        ],
        'read-more-trigger-icon' => [
            'decision' => 'hotwire-only',
            'note' => 'The corpus has no progressive disclosure block.',
        ],
        'reveal' => ['decision' => 'hotwire-only', 'note' => 'The corpus declares no class for this part.'],
        'rich-text' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no rich text editor.'],
        'rich-text-editor' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no rich text editor.'],
        'rich-text-toolbar' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no rich text editor.'],
        'rich-text-toolbar-button' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no rich text editor.'],
        'scroll-progress' => ['decision' => 'hotwire-only', 'note' => 'The corpus declares no class for this part.'],
        'select' => ['decision' => 'renamed', 'class' => 'cn-native-select'],
        'select-icon' => ['decision' => 'renamed', 'class' => 'cn-native-select-icon'],
        'select-wrapper' => [
            'decision' => 'divergent',
            'note' => 'Upstream positions the native select chevron against the control alone; the package adds a wrapper'
                .' so the icon has a positioning context.',
        ],
        'separator' => ['decision' => 'equivalent', 'class' => 'cn-separator'],
        'sheet-backdrop' => ['decision' => 'renamed', 'class' => 'cn-sheet-overlay'],
        'sheet-close' => ['decision' => 'equivalent', 'class' => 'cn-sheet-close'],
        'sheet-close-icon' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles the enclosing control but gives its glyph no class of its own; the package names'
                .' the icon so a preset can size and place it directly.',
        ],
        'sheet-content' => ['decision' => 'equivalent', 'class' => 'cn-sheet-content'],
        'sheet-description' => ['decision' => 'equivalent', 'class' => 'cn-sheet-description'],
        'sheet-footer' => ['decision' => 'equivalent', 'class' => 'cn-sheet-footer'],
        'sheet-header' => ['decision' => 'equivalent', 'class' => 'cn-sheet-header'],
        'sheet-overlay' => [
            'decision' => 'divergent',
            'note' => 'Upstream\'s `*-overlay` class is the dimming element, which the package calls `*-backdrop`; the'
                .' package\'s overlay is the presence layer that hosts the backdrop and the panel.',
        ],
        'sheet-title' => ['decision' => 'equivalent', 'class' => 'cn-sheet-title'],
        'sheet-trigger' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes a plain Button as the trigger and declares no trigger class; the package names the'
                .' part so a preset can select its expanded state.',
        ],
        'side-panel' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Side Panel has no upstream counterpart.',
        ],
        'side-panel-inset' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Side Panel has no upstream counterpart.',
        ],
        'side-panel-panel-content' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Side Panel has no upstream counterpart.',
        ],
        'side-panel-trigger' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Side Panel has no upstream counterpart.',
        ],
        'side-panel-trigger-icon' => [
            'decision' => 'hotwire-only',
            'note' => 'The package\'s Side Panel has no upstream counterpart.',
        ],
        'sidebar' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s Sidebar ships this wrapper in React, but the corpus declares no class for it.',
        ],
        'sidebar-backdrop' => [
            'decision' => 'divergent',
            'note' => 'Upstream\'s mobile sidebar reuses the Sheet overlay; the package gives the sidebar its own backdrop.',
        ],
        'sidebar-brand' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no sidebar brand block.'],
        'sidebar-brand-icon' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no sidebar brand block.'],
        'sidebar-brand-logo' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no sidebar brand block.'],
        'sidebar-container' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s Sidebar ships this wrapper in React, but the corpus declares no class for it.',
        ],
        'sidebar-content' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-content'],
        'sidebar-footer' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-footer'],
        'sidebar-gap' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-gap'],
        'sidebar-group' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-group'],
        'sidebar-group-action' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-group-action'],
        'sidebar-group-content' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-group-content'],
        'sidebar-group-label' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-group-label'],
        'sidebar-header' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-header'],
        'sidebar-inner' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-inner'],
        'sidebar-input' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-input'],
        'sidebar-inset' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-inset'],
        'sidebar-menu' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu'],
        'sidebar-menu-action' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu-action'],
        'sidebar-menu-badge' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu-badge'],
        'sidebar-menu-button' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu-button'],
        'sidebar-menu-item' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s Sidebar ships this wrapper in React, but the corpus declares no class for it.',
        ],
        'sidebar-menu-skeleton' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu-skeleton'],
        'sidebar-menu-skeleton-icon' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu-skeleton-icon'],
        'sidebar-menu-skeleton-text' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu-skeleton-text'],
        'sidebar-menu-sub' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu-sub'],
        'sidebar-menu-sub-button' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-menu-sub-button'],
        'sidebar-menu-sub-item' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s Sidebar ships this wrapper in React, but the corpus declares no class for it.',
        ],
        'sidebar-rail' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-rail'],
        'sidebar-separator' => ['decision' => 'equivalent', 'class' => 'cn-sidebar-separator'],
        'sidebar-trigger' => [
            'decision' => 'divergent',
            'note' => 'Upstream composes a plain Button as the trigger and declares no trigger class; the package names the'
                .' part so a preset can select its expanded state.',
        ],
        'sidebar-wrapper' => [
            'decision' => 'hotwire-only',
            'note' => 'Upstream\'s Sidebar ships this wrapper in React, but the corpus declares no class for it.',
        ],
        'skeleton' => ['decision' => 'equivalent', 'class' => 'cn-skeleton'],
        'slider' => ['decision' => 'equivalent', 'class' => 'cn-slider'],
        'spinner' => [
            'decision' => 'divergent',
            'note' => 'Upstream reaches this part as a `data-slot` descendant of its parent\'s class instead of giving it'
                .' one; the package names it so a preset can style it directly.',
        ],
        'sticky' => ['decision' => 'hotwire-only', 'note' => 'The corpus declares no class for this part.'],
        'switch' => ['decision' => 'equivalent', 'class' => 'cn-switch'],
        'table' => ['decision' => 'equivalent', 'class' => 'cn-table'],
        'table-body' => ['decision' => 'equivalent', 'class' => 'cn-table-body'],
        'table-caption' => ['decision' => 'equivalent', 'class' => 'cn-table-caption'],
        'table-cell' => ['decision' => 'equivalent', 'class' => 'cn-table-cell'],
        'table-container' => ['decision' => 'equivalent', 'class' => 'cn-table-container'],
        'table-footer' => ['decision' => 'equivalent', 'class' => 'cn-table-footer'],
        'table-head' => ['decision' => 'equivalent', 'class' => 'cn-table-head'],
        'table-header' => ['decision' => 'equivalent', 'class' => 'cn-table-header'],
        'table-row' => ['decision' => 'equivalent', 'class' => 'cn-table-row'],
        'tabs' => ['decision' => 'equivalent', 'class' => 'cn-tabs'],
        'tabs-list' => ['decision' => 'equivalent', 'class' => 'cn-tabs-list'],
        'tabs-panel' => ['decision' => 'renamed', 'class' => 'cn-tabs-content'],
        'tabs-trigger' => ['decision' => 'equivalent', 'class' => 'cn-tabs-trigger'],
        'textarea' => ['decision' => 'equivalent', 'class' => 'cn-textarea'],
        'textarea-counter' => ['decision' => 'hotwire-only', 'note' => 'The corpus has no character counter.'],
        'textarea-wrapper' => [
            'decision' => 'divergent',
            'note' => 'Upstream styles the bare control; the package wraps it so adornments such as the clear button and'
                .' the counter can be positioned against it.',
        ],
        'timeago' => ['decision' => 'hotwire-only', 'note' => 'The corpus declares no class for this part.'],
        'toast' => ['decision' => 'equivalent', 'class' => 'cn-toast'],
        'toast-body' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles only the toast card and leaves the inner anatomy to the upstream toast runtime;'
                .' the package owns the card\'s parts in Blade.',
        ],
        'toast-close' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles only the toast card and leaves the inner anatomy to the upstream toast runtime;'
                .' the package owns the card\'s parts in Blade.',
        ],
        'toast-content' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles only the toast card and leaves the inner anatomy to the upstream toast runtime;'
                .' the package owns the card\'s parts in Blade.',
        ],
        'toast-description' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles only the toast card and leaves the inner anatomy to the upstream toast runtime;'
                .' the package owns the card\'s parts in Blade.',
        ],
        'toast-icon' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles only the toast card and leaves the inner anatomy to the upstream toast runtime;'
                .' the package owns the card\'s parts in Blade.',
        ],
        'toast-title' => [
            'decision' => 'divergent',
            'note' => 'The corpus styles only the toast card and leaves the inner anatomy to the upstream toast runtime;'
                .' the package owns the card\'s parts in Blade.',
        ],
        'toggle' => ['decision' => 'equivalent', 'class' => 'cn-toggle'],
        'toggle-group' => ['decision' => 'equivalent', 'class' => 'cn-toggle-group'],
        'toggle-group-item' => ['decision' => 'equivalent', 'class' => 'cn-toggle-group-item'],
        'tooltip' => ['decision' => 'renamed', 'class' => 'cn-tooltip-content'],
        'tooltip-arrow' => ['decision' => 'equivalent', 'class' => 'cn-tooltip-arrow'],
    ],
];
