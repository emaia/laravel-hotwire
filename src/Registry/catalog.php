<?php

use Emaia\LaravelHotwire\Components\Accordion;
use Emaia\LaravelHotwire\Components\Alert;
use Emaia\LaravelHotwire\Components\AlertDialog;
use Emaia\LaravelHotwire\Components\AspectRatio;
use Emaia\LaravelHotwire\Components\Attachment;
use Emaia\LaravelHotwire\Components\Avatar;
use Emaia\LaravelHotwire\Components\BackToTop;
use Emaia\LaravelHotwire\Components\Badge;
use Emaia\LaravelHotwire\Components\Breadcrumb;
use Emaia\LaravelHotwire\Components\Button;
use Emaia\LaravelHotwire\Components\ButtonGroup;
use Emaia\LaravelHotwire\Components\Card;
use Emaia\LaravelHotwire\Components\Carousel;
use Emaia\LaravelHotwire\Components\Chart;
use Emaia\LaravelHotwire\Components\Checkbox;
use Emaia\LaravelHotwire\Components\CheckboxGroup;
use Emaia\LaravelHotwire\Components\CheckboxGroup\Item as CheckboxGroupItem;
use Emaia\LaravelHotwire\Components\ColorScheme\Script as ColorSchemeScript;
use Emaia\LaravelHotwire\Components\ColorScheme\Toggle as ColorSchemeToggle;
use Emaia\LaravelHotwire\Components\ConditionalField;
use Emaia\LaravelHotwire\Components\ControllerPreloads;
use Emaia\LaravelHotwire\Components\Drawer;
use Emaia\LaravelHotwire\Components\Dropdown;
use Emaia\LaravelHotwire\Components\EmptyState;
use Emaia\LaravelHotwire\Components\Field;
use Emaia\LaravelHotwire\Components\Field\Error as FieldError;
use Emaia\LaravelHotwire\Components\Field\Group as FieldGroup;
use Emaia\LaravelHotwire\Components\Field\Label as FieldLabel;
use Emaia\LaravelHotwire\Components\File;
use Emaia\LaravelHotwire\Components\FileUpload;
use Emaia\LaravelHotwire\Components\Form;
use Emaia\LaravelHotwire\Components\Frame;
use Emaia\LaravelHotwire\Components\FrameOrPage;
use Emaia\LaravelHotwire\Components\FrameOrPage\Frame as FrameOrPageFrame;
use Emaia\LaravelHotwire\Components\FrameOrPage\Page as FrameOrPagePage;
use Emaia\LaravelHotwire\Components\HoverCard;
use Emaia\LaravelHotwire\Components\Icon;
use Emaia\LaravelHotwire\Components\Input;
use Emaia\LaravelHotwire\Components\InputGroup;
use Emaia\LaravelHotwire\Components\Item;
use Emaia\LaravelHotwire\Components\Kbd;
use Emaia\LaravelHotwire\Components\Map;
use Emaia\LaravelHotwire\Components\Marker;
use Emaia\LaravelHotwire\Components\Meta;
use Emaia\LaravelHotwire\Components\Meta\Cache as MetaCache;
use Emaia\LaravelHotwire\Components\Meta\ColorScheme as MetaColorScheme;
use Emaia\LaravelHotwire\Components\Meta\Csrf as MetaCsrf;
use Emaia\LaravelHotwire\Components\Meta\Prefetch as MetaPrefetch;
use Emaia\LaravelHotwire\Components\Meta\Refresh as MetaRefresh;
use Emaia\LaravelHotwire\Components\Meta\Root as MetaRoot;
use Emaia\LaravelHotwire\Components\Meta\ViewTransition as MetaViewTransition;
use Emaia\LaravelHotwire\Components\Meta\VisitControl as MetaVisitControl;
use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\Components\MultiSelect;
use Emaia\LaravelHotwire\Components\Navbar;
use Emaia\LaravelHotwire\Components\Navbar\Item as NavbarItem;
use Emaia\LaravelHotwire\Components\Optimistic;
use Emaia\LaravelHotwire\Components\Pagination;
use Emaia\LaravelHotwire\Components\Popover;
use Emaia\LaravelHotwire\Components\Progress;
use Emaia\LaravelHotwire\Components\RadioGroup;
use Emaia\LaravelHotwire\Components\RadioGroup\Item as RadioGroupItem;
use Emaia\LaravelHotwire\Components\ReadMore;
use Emaia\LaravelHotwire\Components\Reveal;
use Emaia\LaravelHotwire\Components\Reveal\Item as RevealItem;
use Emaia\LaravelHotwire\Components\RichText;
use Emaia\LaravelHotwire\Components\ScrollProgress;
use Emaia\LaravelHotwire\Components\Select;
use Emaia\LaravelHotwire\Components\Separator;
use Emaia\LaravelHotwire\Components\Sheet;
use Emaia\LaravelHotwire\Components\Sidebar;
use Emaia\LaravelHotwire\Components\SidePanel;
use Emaia\LaravelHotwire\Components\Skeleton;
use Emaia\LaravelHotwire\Components\Slider;
use Emaia\LaravelHotwire\Components\Spinner;
use Emaia\LaravelHotwire\Components\Sticky;
use Emaia\LaravelHotwire\Components\SwitchInput;
use Emaia\LaravelHotwire\Components\Table;
use Emaia\LaravelHotwire\Components\Tabs;
use Emaia\LaravelHotwire\Components\Textarea;
use Emaia\LaravelHotwire\Components\Timeago;
use Emaia\LaravelHotwire\Components\Toast;
use Emaia\LaravelHotwire\Components\Toaster;
use Emaia\LaravelHotwire\Components\Toggle;
use Emaia\LaravelHotwire\Components\ToggleGroup;
use Emaia\LaravelHotwire\Components\ToggleGroup\Item as ToggleGroupItem;

/**
 * @param  string[]  $visual
 * @param  string[]  $structural
 * @return array<string, 'visual'|'structural'>
 */
$slots = static fn (array $visual = [], array $structural = []): array => [
    ...array_fill_keys($visual, 'visual'),
    ...array_fill_keys($structural, 'structural'),
];

return [
    'components' => [
        'accordion' => [
            'class' => Accordion::class,
            'view' => 'hotwire::component-views.accordion',
            'docs' => 'docs/components/accordion.md',
            'category' => 'display',
            'description' => 'Native details/summary accordion primitives with single or multiple item coordination',
            'controllers' => ['accordion'],
            'styling' => [
                'slots' => $slots(['accordion', 'accordion-item', 'accordion-trigger', 'accordion-trigger-icon', 'accordion-content']),
            ],
        ],
        'alert' => [
            'class' => Alert::class,
            'view' => 'hotwire::component-views.alert',
            'docs' => 'docs/components/alert.md',
            'category' => 'feedback',
            'description' => 'Inline alert with title, description, action and semantic variants',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['alert', 'alert-title', 'alert-description', 'alert-action']),
            ],
        ],
        'alert-dialog' => [
            'class' => AlertDialog::class,
            'view' => 'hotwire::component-views.alert-dialog',
            'docs' => 'docs/components/alert-dialog.md',
            'category' => 'overlay',
            'description' => 'Accessible alert dialog with state-driven motion that intercepts clicks before proceeding',
            'controllers' => ['alert-dialog'],
            'styling' => [
                'slots' => $slots(
                    ['alert-dialog-overlay', 'alert-dialog-backdrop', 'alert-dialog-panel', 'alert-dialog-header', 'alert-dialog-title', 'alert-dialog-description', 'alert-dialog-body', 'alert-dialog-footer', 'alert-dialog-cancel', 'alert-dialog-action'],
                    ['alert-dialog', 'alert-dialog-trigger'],
                ),
            ],
        ],
        'aspect-ratio' => [
            'class' => AspectRatio::class,
            'view' => 'hotwire::component-views.aspect-ratio',
            'docs' => 'docs/components/aspect-ratio.md',
            'category' => 'display',
            'description' => 'Static media wrapper that preserves a configurable aspect ratio',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['aspect-ratio']),
            ],
        ],
        'attachment' => [
            'class' => Attachment::class,
            'view' => 'hotwire::component-views.attachment',
            'docs' => 'docs/components/attachment.md',
            'category' => 'display',
            'description' => 'Composable file attachment primitive with media, metadata, state and actions',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['attachment', 'attachment-group', 'attachment-media', 'attachment-content', 'attachment-title', 'attachment-description', 'attachment-actions', 'attachment-trigger', 'attachment-action']),
            ],
        ],
        'avatar' => [
            'class' => Avatar::class,
            'view' => 'hotwire::component-views.avatar',
            'docs' => 'docs/components/avatar.md',
            'category' => 'display',
            'description' => 'User avatar with image, generated initials fallback, badge and grouped display primitives',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['avatar', 'avatar-image', 'avatar-fallback', 'avatar-badge', 'avatar-group', 'avatar-group-count']),
            ],
        ],
        'back-to-top' => [
            'class' => BackToTop::class,
            'view' => 'hotwire::component-views.back-to-top',
            'docs' => 'docs/components/back-to-top.md',
            'category' => 'utility',
            'description' => 'Fixed accessible button that appears after scrolling and returns the page to the top',
            'controllers' => ['back-to-top'],
            'styling' => [
                'slots' => $slots(['back-to-top']),
            ],
        ],
        'badge' => [
            'class' => Badge::class,
            'view' => 'hotwire::component-views.badge',
            'docs' => 'docs/components/badge.md',
            'category' => 'display',
            'description' => 'Compact status label with semantic variants and optional link rendering',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['badge']),
            ],
        ],
        'breadcrumb' => [
            'class' => Breadcrumb::class,
            'view' => 'hotwire::component-views.breadcrumb',
            'docs' => 'docs/components/breadcrumb.md',
            'category' => 'navigation',
            'description' => 'Semantic navigation trail with composed subcomponents and an items shortcut',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['breadcrumb', 'breadcrumb-list', 'breadcrumb-item', 'breadcrumb-link', 'breadcrumb-page', 'breadcrumb-separator', 'breadcrumb-ellipsis']),
            ],
        ],
        'button' => [
            'class' => Button::class,
            'view' => 'hotwire::component-views.button',
            'docs' => 'docs/components/button.md',
            'category' => 'display',
            'description' => 'Displays a button or a component that looks like a button.',
            'controllers' => ['hotkey', 'tooltip'],
            'styling' => [
                'slots' => $slots(['button']),
            ],
        ],
        'button-group' => [
            'class' => ButtonGroup::class,
            'view' => 'hotwire::component-views.button-group',
            'docs' => 'docs/components/button-group.md',
            'category' => 'display',
            'description' => 'Groups related buttons and button-like controls with shared borders and orientation state',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['button-group', 'button-group-separator', 'button-group-text']),
            ],
        ],
        'card' => [
            'class' => Card::class,
            'view' => 'hotwire::component-views.card',
            'docs' => 'docs/components/card.md',
            'category' => 'display',
            'description' => 'Composable content container with header, action, content and footer slots',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['card', 'card-header', 'card-title', 'card-description', 'card-action', 'card-content', 'card-footer']),
            ],
        ],
        'carousel' => [
            'class' => Carousel::class,
            'view' => 'hotwire::component-views.carousel',
            'docs' => 'docs/components/carousel.md',
            'category' => 'display',
            'description' => 'Carousel/slider (Embla) with navigation, dots, responsive options and CSS-variable sizing',
            'controllers' => ['carousel'],
            'styling' => [
                'slots' => $slots(
                    ['carousel', 'carousel-progress', 'carousel-counter', 'carousel-prev-button', 'carousel-next-button', 'carousel-dot-button', 'carousel-dot-list', 'carousel-progress-wrapper'],
                    ['carousel-viewport', 'carousel-container', 'carousel-nav-wrapper'],
                ),
            ],
        ],
        'chart' => [
            'class' => Chart::class,
            'view' => 'hotwire::component-views.chart',
            'docs' => 'docs/components/chart.md',
            'category' => 'display',
            'description' => 'Apache ECharts wrapper — inline option or URL-fetched, theme + sizing props, controller swap for subclass extensibility',
            'controllers' => ['chart'],
            'styling' => [
                'slots' => $slots(structural: ['chart']),
            ],
        ],
        'checkbox' => [
            'class' => Checkbox::class,
            'view' => 'hotwire::component-views.checkbox',
            'docs' => 'docs/components/checkbox.md',
            'category' => 'forms',
            'description' => 'Standalone native checkbox with old input restore, unchecked hidden value and optional indeterminate state',
            'controllers' => ['checkbox', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['checkbox']),
            ],
        ],
        'checkbox-group' => [
            'class' => CheckboxGroup::class,
            'view' => 'hotwire::component-views.checkbox-group',
            'docs' => 'docs/components/checkbox-group.md',
            'category' => 'forms',
            'description' => 'Checkbox group with options, rich item composition and optional select-all master checkbox',
            'controllers' => ['checkbox-select-all', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['checkbox-group', 'checkbox-group-item', 'checkbox-group-input', 'checkbox-group-item-content']),
            ],
        ],
        'checkbox-group.item' => [
            'class' => CheckboxGroupItem::class,
            'view' => 'hotwire::component-views.checkbox-group-item',
            'docs' => 'docs/components/checkbox-group.md',
            'category' => 'forms',
            'description' => 'Rich checkbox-group item that inherits name, selected state, validation and select-all wiring',
            'controllers' => ['checkbox-select-all', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['checkbox-group-item', 'checkbox-group-input', 'checkbox-group-item-content']),
            ],
        ],
        'color-scheme.script' => [
            'class' => ColorSchemeScript::class,
            'view' => 'hotwire::component-views.color-scheme-script',
            'docs' => 'docs/components/color-scheme.md',
            'category' => 'utility',
            'description' => 'Inline anti-flash script that applies the initial light or dark color scheme before CSS paints',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'color-scheme.toggle' => [
            'class' => ColorSchemeToggle::class,
            'view' => 'hotwire::component-views.color-scheme-toggle',
            'docs' => 'docs/components/color-scheme.md',
            'category' => 'utility',
            'description' => 'Button that cycles persisted light, dark and system color scheme modes',
            'controllers' => ['color-scheme', 'tooltip'],
            'styling' => [
                'slots' => $slots(['color-scheme-toggle', 'color-scheme-icon']),
            ],
        ],
        'conditional-field' => [
            'class' => ConditionalField::class,
            'view' => 'hotwire::component-views.conditional-field',
            'docs' => 'docs/components/conditional-field.md',
            'category' => 'forms',
            'description' => 'Renders a dependent block for the conditional-fields controller — single source of truth for the show/hide rule on both client and server',
            'controllers' => ['conditional-fields'],
            'styling' => [
                'slots' => $slots(structural: ['conditional-field']),
            ],
        ],
        'controller-preloads' => [
            'class' => ControllerPreloads::class,
            'view' => 'hotwire::component-views.controller-preloads',
            'docs' => 'docs/components/controller-preloads.md',
            'category' => 'utility',
            'description' => 'Emits Vite modulepreload links for selected application or package Stimulus controllers',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'drawer' => [
            'class' => Drawer::class,
            'view' => 'hotwire::component-views.drawer',
            'docs' => 'docs/components/drawer.md',
            'category' => 'overlay',
            'description' => 'Off-canvas drawer with state-driven motion, focus trap and Escape/click-outside dismissal',
            'controllers' => ['drawer', 'turbo--view-transition'],
            'styling' => [
                'slots' => $slots(
                    ['drawer-overlay', 'drawer-trigger', 'drawer-backdrop', 'drawer-popup', 'drawer-content', 'drawer-header', 'drawer-title', 'drawer-description', 'drawer-footer', 'drawer-close'],
                    ['drawer'],
                ),
            ],
        ],
        'dropdown' => [
            'class' => Dropdown::class,
            'view' => 'hotwire::component-views.dropdown',
            'docs' => 'docs/components/dropdown.md',
            'category' => 'overlay',
            'description' => 'Accessible disclosure dropdown with state-driven presence, responsive positioning and outside-click/Escape dismissal',
            'controllers' => ['dropdown'],
            'styling' => [
                'slots' => $slots(['dropdown', 'dropdown-trigger', 'dropdown-trigger-icon', 'dropdown-menu', 'dropdown-group', 'dropdown-label', 'dropdown-item', 'dropdown-separator', 'dropdown-shortcut']),
            ],
        ],
        'empty-state' => [
            'class' => EmptyState::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/empty-state.md',
            'category' => 'display',
            'description' => 'Composable empty state with media, title, description and action content slots',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['empty-state', 'empty-state-header', 'empty-state-media', 'empty-state-title', 'empty-state-description', 'empty-state-content']),
            ],
        ],
        'field' => [
            'class' => Field::class,
            'view' => 'hotwire::component-views.field',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Wraps label, input, description and error — propagates scoped name/id/errorKey/required context',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(
                    ['field-set', 'field-legend', 'field-group', 'field', 'field-label', 'field-content', 'field-title', 'field-description', 'field-error', 'field-separator', 'field-separator-line', 'field-separator-content'],
                    ['field-label-required'],
                ),
            ],
        ],
        'field.error' => [
            'class' => FieldError::class,
            'view' => 'hotwire::component-views.field-error',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Always-present error container bound to a form field via name/errorKey',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['field-error']),
            ],
        ],
        'field.group' => [
            'class' => FieldGroup::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Groups form fields and enables responsive field orientation layout',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['field-group']),
            ],
        ],
        'field.label' => [
            'class' => FieldLabel::class,
            'view' => 'hotwire::component-views.field-label',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Form label with auto-derived for/id and optional required marker',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['field-label'], ['field-label-required']),
            ],
        ],
        'file' => [
            'class' => File::class,
            'view' => 'hotwire::component-views.file',
            'docs' => 'docs/components/file.md',
            'category' => 'forms',
            'description' => 'File input with auto id/errorKey, ARIA, optional current file display and Turbo morph reset',
            'controllers' => ['file-preserve', 'reset-files'],
            'styling' => [
                'slots' => $slots(['file-wrapper', 'file-input']),
            ],
        ],
        'file-upload' => [
            'class' => FileUpload::class,
            'view' => 'hotwire::component-views.file-upload',
            'docs' => 'docs/components/file-upload.md',
            'category' => 'forms',
            'description' => 'Attachment-backed native upload protocol with managed JSON and server-owned Turbo Stream modes',
            'controllers' => ['file-upload'],
            'styling' => [
                'slots' => $slots(
                    ['file-upload', 'file-upload-dropzone', 'file-upload-image-base', 'file-upload-image-preview', 'file-upload-feedback', 'file-upload-actions', 'attachment-group', 'empty-state-description'],
                    ['file-upload-announcer'],
                ),
            ],
        ],
        'form' => [
            'class' => Form::class,
            'view' => 'hotwire::component-views.form',
            'docs' => 'docs/components/form.md',
            'category' => 'forms',
            'description' => 'Form wrapper with optional Stimulus behaviors, CSRF, and Turbo Frame redirect support',
            'controllers' => ['auto-submit', 'unsaved-changes', 'error-scroll', 'clean-query-params', 'conditional-fields'],
            'styling' => [
                'slots' => $slots(structural: ['form']),
            ],
        ],
        'frame' => [
            'class' => Frame::class,
            'view' => 'hotwire::component-views.frame',
            'docs' => 'docs/components/frame.md',
            'category' => 'turbo',
            'description' => 'DX-friendly Turbo Frame wrapper with lazy, advance and replace aliases',
            'controllers' => ['turbo--polling', 'turbo--view-transition', 'turbo--preserve-scroll'],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'frame-or-page' => [
            'class' => FrameOrPage::class,
            'view' => 'hotwire::component-views.frame-or-page',
            'docs' => 'docs/components/frame-or-page.md',
            'category' => 'turbo',
            'description' => 'Renders shared and lazy contextual content as one of several Turbo Frames or as a page layout',
            'controllers' => ['turbo--polling', 'turbo--view-transition'],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'frame-or-page.frame' => [
            'class' => FrameOrPageFrame::class,
            'view' => 'hotwire::component-views.frame-or-page-branch',
            'docs' => 'docs/components/frame-or-page.md',
            'category' => 'turbo',
            'description' => 'Lazily renders contextual content only for a matching Frame Or Page frame request',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'frame-or-page.page' => [
            'class' => FrameOrPagePage::class,
            'view' => 'hotwire::component-views.frame-or-page-branch',
            'docs' => 'docs/components/frame-or-page.md',
            'category' => 'turbo',
            'description' => 'Lazily renders contextual content only for the Frame Or Page full-page branch',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'hover-card' => [
            'class' => HoverCard::class,
            'view' => 'hotwire::component-views.hover-card',
            'docs' => 'docs/components/hover-card.md',
            'category' => 'overlay',
            'description' => 'Anchored hover/focus preview card with delayed Floating UI positioning and state-driven presence',
            'controllers' => ['hover-card'],
            'styling' => [
                'slots' => $slots(['hover-card', 'hover-card-trigger', 'hover-card-content']),
            ],
        ],
        'icon' => [
            'class' => Icon::class,
            'view' => 'hotwire::component-views.icon',
            'docs' => 'docs/components/icon.md',
            'category' => 'display',
            'description' => 'Inline SVG icon from the embedded Lucide subset (~21 icons)',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['icon']),
            ],
        ],
        'input' => [
            'class' => Input::class,
            'view' => 'hotwire::component-views.input',
            'docs' => 'docs/components/input.md',
            'category' => 'forms',
            'description' => 'Form input with auto id/errorKey, ARIA, optional mask/clear/auto-select',
            'controllers' => ['auto-select', 'clear-input', 'input-mask', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['input-wrapper', 'input', 'clear-input-button']),
            ],
        ],
        'input-group' => [
            'class' => InputGroup::class,
            'view' => 'hotwire::component-views.input-group',
            'docs' => 'docs/components/input-group.md',
            'category' => 'forms',
            'description' => 'Composable input shell for addons, actions, shortcuts and helper content',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['input-group', 'input-group-addon']),
            ],
        ],
        'item' => [
            'class' => Item::class,
            'view' => 'hotwire::component-views.item',
            'docs' => 'docs/components/item.md',
            'category' => 'display',
            'description' => 'Composable list item primitive with media, content, actions, header, footer and separator slots',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['item-group', 'item', 'item-media', 'item-content', 'item-title', 'item-description', 'item-actions', 'item-header', 'item-footer', 'item-separator']),
            ],
        ],
        'kbd' => [
            'class' => Kbd::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/kbd.md',
            'category' => 'display',
            'description' => 'Keyboard input hint with optional grouped shortcut rendering',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['kbd', 'kbd-group']),
            ],
        ],
        'map' => [
            'class' => Map::class,
            'view' => 'hotwire::component-views.map',
            'docs' => 'docs/components/map.md',
            'category' => 'display',
            'description' => 'Leaflet wrapper — inline center/markers or GeoJSON URL, OSM tiles by default, subclass-friendly tile/handler hooks',
            'controllers' => ['map'],
            'styling' => [
                'slots' => $slots(structural: ['map']),
            ],
        ],
        'marker' => [
            'class' => Marker::class,
            'view' => 'hotwire::component-views.marker',
            'docs' => 'docs/components/marker.md',
            'category' => 'display',
            'description' => 'Lightweight visual primitive for timelines, activity feeds and lists',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['marker', 'marker-icon', 'marker-content']),
            ],
        ],
        'meta' => [
            'class' => Meta::class,
            'view' => 'hotwire::component-views.meta',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Composes the Hotwire head metas, rendering only the ones the application opts into',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'meta.cache' => [
            'class' => MetaCache::class,
            'view' => 'hotwire::component-views.meta-tag',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Declares turbo-cache-control to opt a page out of the cache or its preview',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'meta.color-scheme' => [
            'class' => MetaColorScheme::class,
            'view' => 'hotwire::component-views.meta-tag',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Declares color-scheme so form controls and scrollbars follow the active theme',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'meta.csrf' => [
            'class' => MetaCsrf::class,
            'view' => 'hotwire::component-views.meta-tag',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Declares csrf-token, which the File Upload controller reads for its requests',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'meta.prefetch' => [
            'class' => MetaPrefetch::class,
            'view' => 'hotwire::component-views.meta-tag',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Declares turbo-prefetch, the link hover prefetching switch',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'meta.refresh' => [
            'class' => MetaRefresh::class,
            'view' => 'hotwire::component-views.meta-refresh',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Declares turbo-refresh-method and turbo-refresh-scroll for page refreshes',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'meta.root' => [
            'class' => MetaRoot::class,
            'view' => 'hotwire::component-views.meta-tag',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Declares turbo-root, the path prefix Turbo Drive is allowed to navigate within',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'meta.view-transition' => [
            'class' => MetaViewTransition::class,
            'view' => 'hotwire::component-views.meta-tag',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Declares view-transition so same-origin navigations animate',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'meta.visit-control' => [
            'class' => MetaVisitControl::class,
            'view' => 'hotwire::component-views.meta-tag',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Declares turbo-visit-control to force a full reload when visiting the page',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(),
            ],
        ],
        'modal' => [
            'class' => Modal::class,
            'view' => 'hotwire::component-views.modal',
            'docs' => 'docs/components/modal.md',
            'category' => 'overlay',
            'description' => 'Accessible modal with state-driven motion, focus trap and Turbo integration',
            'controllers' => ['modal', 'turbo--view-transition'],
            'styling' => [
                'slots' => $slots(
                    ['modal-overlay', 'modal-trigger', 'modal-backdrop', 'modal-positioner', 'modal-panel', 'modal-content', 'modal-header', 'modal-title', 'modal-description', 'modal-footer', 'modal-close', 'modal-close-icon'],
                    ['modal'],
                ),
            ],
        ],
        'multi-select' => [
            'class' => MultiSelect::class,
            'view' => 'hotwire::component-views.multi-select',
            'docs' => 'docs/components/multi-select.md',
            'category' => 'forms',
            'description' => 'Searchable multi-value select with state-driven floating presence and native form submission',
            'controllers' => ['multi-select', 'clear-input'],
            'styling' => [
                'slots' => $slots(['multi-select', 'multi-select-native', 'multi-select-validation', 'multi-select-trigger', 'multi-select-value', 'multi-select-trigger-icon', 'multi-select-content', 'multi-select-search', 'multi-select-search-icon', 'multi-select-select-all', 'multi-select-indicator', 'multi-select-option-text', 'multi-select-list', 'multi-select-option', 'multi-select-empty']),
            ],
        ],
        'navbar' => [
            'class' => Navbar::class,
            'view' => 'hotwire::component-views.navbar',
            'docs' => 'docs/components/navbar.md',
            'category' => 'navigation',
            'description' => 'Horizontal or vertical navigation bar for real links with current-page state and optional sticky sugar',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['navbar', 'navbar-item', 'sticky']),
            ],
        ],
        'navbar.item' => [
            'class' => NavbarItem::class,
            'view' => 'hotwire::component-views.navbar-item',
            'docs' => 'docs/components/navbar.md',
            'category' => 'navigation',
            'description' => 'Navbar item that renders as a link or button with current and disabled semantics',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['navbar-item']),
            ],
        ],
        'optimistic' => [
            'class' => Optimistic::class,
            'view' => 'hotwire::component-views.optimistic',
            'docs' => 'docs/components/optimistic.md',
            'category' => 'turbo',
            'description' => 'Declares an inline optimistic Turbo Stream action for any Turbo trigger',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(structural: ['optimistic']),
            ],
        ],
        'pagination' => [
            'class' => Pagination::class,
            'view' => 'hotwire::component-views.pagination',
            'docs' => 'docs/components/pagination.md',
            'category' => 'navigation',
            'description' => 'Pagination navigation primitives with Laravel paginator display modes and Turbo Frame support',
            'controllers' => ['pagination'],
            'styling' => [
                'slots' => $slots(['pagination', 'pagination-content', 'pagination-item', 'pagination-link', 'pagination-previous', 'pagination-previous-label', 'pagination-next', 'pagination-next-content', 'pagination-next-label', 'pagination-next-loading-content', 'pagination-next-loading-label', 'pagination-next-spinner', 'pagination-next-icon', 'pagination-ellipsis'], structural: ['pagination-status']),
            ],
        ],
        'popover' => [
            'class' => Popover::class,
            'view' => 'hotwire::component-views.popover',
            'docs' => 'docs/components/popover.md',
            'category' => 'overlay',
            'description' => 'Anchored click-triggered popover with state-driven presence for rich arbitrary content',
            'controllers' => ['popover'],
            'styling' => [
                'slots' => $slots(['popover', 'popover-trigger', 'popover-content', 'popover-header', 'popover-title', 'popover-description']),
            ],
        ],
        'progress' => [
            'class' => Progress::class,
            'view' => 'hotwire::component-views.progress',
            'docs' => 'docs/components/progress.md',
            'category' => 'feedback',
            'description' => 'Server-rendered progress primitive with label, value, track and indicator slots',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['progress', 'progress-track', 'progress-indicator', 'progress-label', 'progress-value']),
            ],
        ],
        'radio-group' => [
            'class' => RadioGroup::class,
            'view' => 'hotwire::component-views.radio-group',
            'docs' => 'docs/components/radio-group.md',
            'category' => 'forms',
            'description' => 'Native radio group with options, rich item composition, old input restore and validation wiring',
            'controllers' => ['auto-submit'],
            'styling' => [
                'slots' => $slots(['radio-group', 'radio-group-item', 'radio-group-input', 'radio-group-item-content']),
            ],
        ],
        'radio-group.item' => [
            'class' => RadioGroupItem::class,
            'view' => 'hotwire::component-views.radio-group-item',
            'docs' => 'docs/components/radio-group.md',
            'category' => 'forms',
            'description' => 'Rich radio-group item that inherits name, selected state and validation wiring',
            'controllers' => ['auto-submit'],
            'styling' => [
                'slots' => $slots(['radio-group-item', 'radio-group-input', 'radio-group-item-content']),
            ],
        ],
        'read-more' => [
            'class' => ReadMore::class,
            'view' => 'hotwire::component-views.read-more',
            'docs' => 'docs/components/read-more.md',
            'category' => 'display',
            'description' => 'Overflow-aware content preview with accessible expansion and first-paint clamping',
            'controllers' => ['read-more'],
            'styling' => [
                'slots' => $slots(
                    ['read-more', 'read-more-content', 'read-more-fade', 'read-more-trigger', 'read-more-trigger-icon'],
                    ['read-more-viewport'],
                ),
            ],
        ],
        'reveal' => [
            'class' => Reveal::class,
            'view' => 'hotwire::component-views.reveal',
            'docs' => 'docs/components/reveal.md',
            'category' => 'display',
            'description' => 'Progressively enhanced staggered entrance cascade for direct children or explicit items',
            'controllers' => ['reveal'],
            'styling' => [
                'slots' => $slots(['reveal', 'reveal-item']),
            ],
        ],
        'reveal.item' => [
            'class' => RevealItem::class,
            'view' => 'hotwire::component-views.reveal-item',
            'docs' => 'docs/components/reveal.md',
            'category' => 'display',
            'description' => 'Explicit nested item with an automatically shared cascade index',
            'controllers' => ['reveal'],
            'styling' => [
                'slots' => $slots(['reveal-item']),
            ],
        ],
        'rich-text' => [
            'class' => RichText::class,
            'view' => 'hotwire::component-views.rich-text',
            'docs' => 'docs/components/rich-text.md',
            'category' => 'forms',
            'description' => 'Tiptap-backed rich text editor with optional default toolbar, output as HTML or JSON, and image-upload event hook',
            'controllers' => ['rich-text', 'rich-text-toolbar'],
            'styling' => [
                'slots' => $slots(
                    ['rich-text', 'rich-text-toolbar', 'rich-text-toolbar-button', 'rich-text-editor'],
                    ['rich-text-input'],
                ),
            ],
        ],
        'scroll-progress' => [
            'class' => ScrollProgress::class,
            'view' => 'hotwire::component-views.scroll-progress',
            'docs' => 'docs/components/scroll-progress.md',
            'category' => 'utility',
            'description' => 'Fixed scroll progress bar that fills as the page scrolls',
            'controllers' => ['scroll-progress'],
            'styling' => [
                'slots' => $slots(['scroll-progress']),
            ],
        ],
        'select' => [
            'class' => Select::class,
            'view' => 'hotwire::component-views.select',
            'docs' => 'docs/components/select.md',
            'category' => 'forms',
            'description' => 'Select dropdown with auto id/errorKey, ARIA, old() merge and placeholder support',
            'controllers' => ['auto-submit'],
            'styling' => [
                'slots' => $slots(['select-wrapper', 'select', 'select-icon']),
            ],
        ],
        'separator' => [
            'class' => Separator::class,
            'view' => 'hotwire::component-views.separator',
            'docs' => 'docs/components/separator.md',
            'category' => 'display',
            'description' => 'Horizontal or vertical visual separator with semantic orientation hooks',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['separator']),
            ],
        ],
        'sheet' => [
            'class' => Sheet::class,
            'view' => 'hotwire::component-views.sheet',
            'docs' => 'docs/components/sheet.md',
            'category' => 'overlay',
            'description' => 'Off-canvas sheet with state-driven motion, focus trap and side-aware slide transitions',
            'controllers' => ['sheet', 'turbo--view-transition'],
            'styling' => [
                'slots' => $slots(
                    ['sheet-overlay', 'sheet-trigger', 'sheet-backdrop', 'sheet-content', 'sheet-close-icon', 'sheet-header', 'sheet-title', 'sheet-description', 'sheet-footer', 'sheet-close'],
                    ['sheet'],
                ),
            ],
        ],
        'side-panel' => [
            'class' => SidePanel::class,
            'view' => 'hotwire::component-views.side-panel',
            'docs' => 'docs/components/side-panel.md',
            'category' => 'navigation',
            'description' => 'Composable collapsible panel for secondary navigation and workspace tools',
            'controllers' => ['side-panel'],
            'styling' => [
                'slots' => $slots(['side-panel', 'side-panel-panel-content', 'side-panel-trigger', 'side-panel-trigger-icon', 'side-panel-inset'], ['side-panel-panel']),
            ],
        ],
        'sidebar' => [
            'class' => Sidebar::class,
            'view' => 'hotwire::component-views.sidebar',
            'docs' => 'docs/components/sidebar.md',
            'category' => 'navigation',
            'description' => 'Composable app sidebar with provider state, mobile Presence and navigation primitives',
            'controllers' => ['sidebar', 'reveal'],
            'styling' => [
                'slots' => $slots(['sidebar-wrapper', 'sidebar', 'sidebar-backdrop', 'sidebar-trigger', 'sidebar-rail', 'sidebar-inset', 'sidebar-header', 'sidebar-brand', 'sidebar-brand-logo', 'sidebar-brand-icon', 'sidebar-footer', 'sidebar-content', 'sidebar-input', 'sidebar-separator', 'sidebar-group', 'sidebar-group-label', 'sidebar-group-action', 'sidebar-group-content', 'sidebar-menu', 'sidebar-menu-item', 'sidebar-menu-button', 'sidebar-menu-action', 'sidebar-menu-badge', 'sidebar-menu-skeleton', 'sidebar-menu-skeleton-icon', 'sidebar-menu-skeleton-text', 'sidebar-menu-sub', 'sidebar-menu-sub-item', 'sidebar-menu-sub-button', 'sidebar-gap', 'sidebar-container', 'sidebar-inner']),
            ],
        ],
        'skeleton' => [
            'class' => Skeleton::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/skeleton.md',
            'category' => 'feedback',
            'description' => 'Animated placeholder block for loading states',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['skeleton']),
            ],
        ],
        'slider' => [
            'class' => Slider::class,
            'view' => 'hotwire::component-views.slider',
            'docs' => 'docs/components/slider.md',
            'category' => 'forms',
            'description' => 'Native scalar range input with Laravel field integration and progressive visual fill',
            'controllers' => ['slider', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['slider']),
            ],
        ],
        'spinner' => [
            'class' => Spinner::class,
            'view' => 'hotwire::component-views.spinner',
            'docs' => 'docs/components/spinner.md',
            'category' => 'feedback',
            'description' => 'Animated SVG spinner — no JavaScript required',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['spinner']),
            ],
        ],
        'sticky' => [
            'class' => Sticky::class,
            'view' => 'hotwire::component-views.sticky',
            'docs' => 'docs/components/sticky.md',
            'category' => 'navigation',
            'description' => 'Generic top or bottom sticky surface primitive with configurable offset and tag',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['sticky']),
            ],
        ],
        'switch' => [
            'class' => SwitchInput::class,
            'view' => 'hotwire::component-views.switch',
            'docs' => 'docs/components/switch.md',
            'category' => 'forms',
            'description' => 'Native checkbox rendered as an accessible switch with old input restore and unchecked hidden value',
            'controllers' => ['auto-submit'],
            'styling' => [
                'slots' => $slots(['switch']),
            ],
        ],
        'table' => [
            'class' => Table::class,
            'view' => 'hotwire::component-views.table',
            'docs' => 'docs/components/table.md',
            'category' => 'display',
            'description' => 'Responsive table wrapper with semantic row, cell, header, footer and caption primitives',
            'controllers' => [],
            'styling' => [
                'slots' => $slots(['table-container', 'table', 'table-header', 'table-body', 'table-footer', 'table-row', 'table-head', 'table-cell', 'table-caption']),
            ],
        ],
        'tabs' => [
            'class' => Tabs::class,
            'view' => 'hotwire::component-views.tabs',
            'docs' => 'docs/components/tabs.md',
            'category' => 'display',
            'description' => 'Accessible tab primitives backed by the tabs controller, with server-rendered active state',
            'controllers' => ['tabs'],
            'styling' => [
                'slots' => $slots(['tabs', 'tabs-list', 'tabs-trigger', 'tabs-panel']),
            ],
        ],
        'textarea' => [
            'class' => Textarea::class,
            'view' => 'hotwire::component-views.textarea',
            'docs' => 'docs/components/textarea.md',
            'category' => 'forms',
            'description' => 'Textarea with auto-resize and optional char counter',
            'controllers' => ['auto-resize', 'char-counter', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['textarea-wrapper', 'textarea']),
            ],
        ],
        'timeago' => [
            'class' => Timeago::class,
            'view' => 'hotwire::component-views.timeago',
            'docs' => 'docs/components/timeago.md',
            'category' => 'utility',
            'description' => 'Self-refreshing relative timestamp element wrapping the timeago controller',
            'controllers' => ['timeago'],
            'styling' => [
                'slots' => $slots(['timeago']),
            ],
        ],
        'toast' => [
            'class' => Toast::class,
            'view' => 'hotwire::component-views.toast',
            'docs' => 'docs/components/toast.md',
            'category' => 'feedback',
            'description' => 'Fires a toast notification from the Laravel session or from explicit props',
            'controllers' => ['toast'],
            'styling' => [
                // The trigger only carries the payload and removes itself on connect; the visible
                // toast is built by the manager under its own slots.
                'slots' => $slots(structural: ['toast-trigger']),
            ],
        ],
        'toaster' => [
            'class' => Toaster::class,
            'view' => 'hotwire::component-views.toaster',
            'docs' => 'docs/components/toaster.md',
            'category' => 'feedback',
            'description' => 'Hosts the toast stack, reads the session flash and persists across Turbo Drive navigations',
            'controllers' => ['toaster', 'toast'],
            'styling' => [
                'slots' => $slots(structural: ['toaster', 'toast-trigger']),
            ],
        ],
        'toggle' => [
            'class' => Toggle::class,
            'view' => 'hotwire::component-views.toggle',
            'docs' => 'docs/components/toggle.md',
            'category' => 'forms',
            'description' => 'Accessible two-state button with optional hidden input and auto-submit integration',
            'controllers' => ['toggle', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['toggle']),
            ],
        ],
        'toggle-group' => [
            'class' => ToggleGroup::class,
            'view' => 'hotwire::component-views.toggle-group',
            'docs' => 'docs/components/toggle-group.md',
            'category' => 'forms',
            'description' => 'Single or multiple pressed-button group with hidden-input form submission',
            'controllers' => ['toggle-group', 'toggle', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['toggle-group', 'toggle-group-item']),
            ],
        ],
        'toggle-group.item' => [
            'class' => ToggleGroupItem::class,
            'view' => 'hotwire::component-views.toggle-group-item',
            'docs' => 'docs/components/toggle-group.md',
            'category' => 'forms',
            'description' => 'Button item for toggle groups with aria-pressed and hidden-input synchronization',
            'controllers' => ['toggle-group', 'toggle', 'auto-submit'],
            'styling' => [
                'slots' => $slots(['toggle-group-item']),
            ],
        ],
    ],
    'controllers' => [
        'accordion' => [
            'source' => 'resources/js/controllers/accordion_controller.js',
            'docs' => 'docs/controllers/accordion.md',
            'category' => 'display',
            'description' => 'Coordinates native details/summary accordion items for single, multiple and disabled behavior',
        ],
        'alert-dialog' => [
            'source' => 'resources/js/controllers/alert_dialog_controller.js',
            'docs' => 'docs/controllers/alert-dialog.md',
            'category' => 'overlay',
            'description' => 'Intercepts clicks and waits for state-driven dialog confirmation before proceeding',
        ],
        'animated-number' => [
            'source' => 'resources/js/controllers/animated_number_controller.js',
            'docs' => 'docs/controllers/animated-number.md',
            'category' => 'display',
            'description' => 'Animates a number from start to end value, with scroll-triggered lazy mode',
        ],
        'auto-resize' => [
            'source' => 'resources/js/controllers/auto_resize_controller.js',
            'docs' => 'docs/controllers/auto-resize.md',
            'category' => 'forms',
            'description' => 'Expands a textarea to fit its content as the user types',
        ],
        'auto-save' => [
            'source' => 'resources/js/controllers/auto_save_controller.js',
            'docs' => 'docs/controllers/auto-save.md',
            'category' => 'forms',
            'description' => 'Automatically saves a form after changes, with debounce and status feedback',
        ],
        'auto-select' => [
            'source' => 'resources/js/controllers/auto_select_controller.js',
            'docs' => 'docs/controllers/auto-select.md',
            'category' => 'forms',
            'description' => 'Selects all text in an input when it receives focus',
        ],
        'auto-submit' => [
            'source' => 'resources/js/controllers/auto_submit_controller.js',
            'docs' => 'docs/controllers/auto-submit.md',
            'category' => 'forms',
            'description' => 'Submits a form automatically on input or change events, with debounce support',
        ],
        'autofocus' => [
            'source' => 'resources/js/controllers/autofocus_controller.js',
            'docs' => 'docs/controllers/autofocus.md',
            'category' => 'forms',
            'description' => 'Focuses the first matching field on connect and on turbo:frame-load, with autofocus-attribute, first-focusable and target strategies',
        ],
        'back-to-top' => [
            'source' => 'resources/js/controllers/back_to_top_controller.js',
            'docs' => 'docs/controllers/back-to-top.md',
            'category' => 'utility',
            'description' => 'Toggles a data-visible attribute on the element as the page scrolls past a threshold, and exposes a scrollToTop action that respects prefers-reduced-motion',
        ],
        'carousel' => [
            'source' => 'resources/js/controllers/carousel_controller.js',
            'docs' => 'docs/controllers/carousel.md',
            'category' => 'display',
            'description' => 'Carousel/slider — wraps Embla Carousel with navigation, dots and Turbo-friendly lifecycle',
            'npm' => ['embla-carousel' => '^8.6.0'],
        ],
        'char-counter' => [
            'source' => 'resources/js/controllers/char_counter_controller.js',
            'docs' => 'docs/controllers/char-counter.md',
            'category' => 'forms',
            'description' => 'Shows a live character count with count-up or countdown mode',
        ],
        'chart' => [
            'source' => 'resources/js/controllers/chart_controller.js',
            'docs' => 'docs/controllers/chart.md',
            'category' => 'display',
            'description' => 'Apache ECharts wrapper — server-rendered option, optional URL fetch, ResizeObserver, subclass-friendly defaults',
            'npm' => ['echarts' => '^6.1.0'],
        ],
        'checkbox' => [
            'source' => 'resources/js/controllers/checkbox_controller.js',
            'docs' => 'docs/controllers/checkbox.md',
            'category' => 'forms',
            'description' => 'Applies native checkbox indeterminate state from Stimulus values and re-syncs after Turbo renders',
        ],
        'checkbox-select-all' => [
            'source' => 'resources/js/controllers/checkbox_select_all_controller.js',
            'docs' => 'docs/controllers/checkbox-select-all.md',
            'category' => 'forms',
            'description' => 'Select-all checkbox that controls a group, with indeterminate state',
        ],
        'clean-query-params' => [
            'source' => 'resources/js/controllers/clean_query_params_controller.js',
            'docs' => 'docs/controllers/clean-query-params.md',
            'category' => 'forms',
            'description' => 'Strips empty fields from the query string before submitting a GET form',
        ],
        'clear-input' => [
            'source' => 'resources/js/controllers/clear_input_controller.js',
            'docs' => 'docs/controllers/clear-input.md',
            'category' => 'forms',
            'description' => 'Adds a clear button that appears when the input has a value',
        ],
        'color-scheme' => [
            'source' => 'resources/js/controllers/color_scheme_controller.js',
            'docs' => 'docs/controllers/color-scheme.md',
            'category' => 'utility',
            'description' => 'Persists light, dark or system color scheme mode and synchronizes html[data-theme]',
        ],
        'conditional-fields' => [
            'source' => 'resources/js/controllers/conditional_fields_controller.js',
            'docs' => 'docs/controllers/conditional-fields.md',
            'category' => 'forms',
            'description' => 'Show/hide dependent fields based on the value of other form fields — auto-detects triggers from data-when-* attributes',
        ],
        'copy-to-clipboard' => [
            'source' => 'resources/js/controllers/copy_to_clipboard_controller.js',
            'docs' => 'docs/controllers/copy-to-clipboard.md',
            'category' => 'utility',
            'description' => 'Copies text to the clipboard and shows a temporary success label',
        ],
        'dev--duplicate-ids' => [
            'source' => 'resources/js/controllers/dev/duplicate_ids_controller.js',
            'docs' => 'docs/controllers/dev/duplicate-ids.md',
            'category' => 'dev',
            'description' => 'Warns when DOM ids are duplicated in one render root, with guidance for automatic component ids',
        ],
        'dev--log' => [
            'source' => 'resources/js/controllers/dev/log_controller.js',
            'docs' => 'docs/controllers/dev/log.md',
            'category' => 'dev',
            'description' => 'Logs Stimulus events to the browser console for debugging',
        ],
        'disclosure' => [
            'source' => 'resources/js/controllers/disclosure_controller.js',
            'docs' => 'docs/controllers/disclosure.md',
            'category' => 'display',
            'description' => 'Show/hide collapsible content with aria-expanded sync for FAQ items, panels and accordions',
        ],
        'drawer' => [
            'source' => 'resources/js/controllers/drawer_controller.js',
            'docs' => 'docs/controllers/drawer.md',
            'category' => 'overlay',
            'description' => 'Off-canvas drawer with state-driven motion, focus trap and Escape/click-outside dismissal',
        ],
        'dropdown' => [
            'source' => 'resources/js/controllers/dropdown_controller.js',
            'docs' => 'docs/controllers/dropdown.md',
            'category' => 'overlay',
            'description' => 'Accessible disclosure dropdown with state-driven presence, responsive positioning and outside-click/Escape dismissal',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'error-scroll' => [
            'source' => 'resources/js/controllers/error_scroll_controller.js',
            'docs' => 'docs/controllers/error-scroll.md',
            'category' => 'forms',
            'description' => 'Scrolls to the first validation error inside a container after frame render or full-page render',
        ],
        'file-preserve' => [
            'source' => 'resources/js/controllers/file_preserve_controller.js',
            'docs' => 'docs/controllers/file-preserve.md',
            'category' => 'forms',
            'description' => 'Captures and restores file input selection across Turbo morphs and frame navigations',
        ],
        'file-upload' => [
            'source' => 'resources/js/controllers/file_upload_controller.js',
            'docs' => 'docs/controllers/file-upload.md',
            'category' => 'forms',
            'description' => 'Native upload transport with queueing, managed outputs, hybrid JSON and raw Turbo Streams',
        ],
        'gtm' => [
            'source' => 'resources/js/controllers/gtm_controller.js',
            'docs' => 'docs/controllers/gtm.md',
            'category' => 'utility',
            'description' => 'Loads Google Tag Manager lazily and fires custom events via data-action',
        ],
        'hotkey' => [
            'source' => 'resources/js/controllers/hotkey_controller.js',
            'docs' => 'docs/controllers/hotkey.md',
            'category' => 'utility',
            'description' => 'Binds keyboard shortcuts to click or focus an element',
        ],
        'hover-card' => [
            'source' => 'resources/js/controllers/hover_card_controller.js',
            'docs' => 'docs/controllers/hover-card.md',
            'category' => 'overlay',
            'description' => 'Delayed hover/focus preview card with state-driven presence, Escape dismissal and Floating UI positioning',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'input-mask' => [
            'source' => 'resources/js/controllers/input_mask_controller.js',
            'docs' => 'docs/controllers/input-mask.md',
            'category' => 'forms',
            'description' => 'Applies input masks via Maska (phone, date, custom patterns)',
            'npm' => ['maska' => '^3.2.0'],
        ],
        'lazy-image' => [
            'source' => 'resources/js/controllers/lazy_image_controller.js',
            'docs' => 'docs/controllers/lazy-image.md',
            'category' => 'display',
            'description' => 'Polls until an image URL becomes available, then displays it',
        ],
        'map' => [
            'source' => 'resources/js/controllers/map_controller.js',
            'docs' => 'docs/controllers/map.md',
            'category' => 'display',
            'description' => 'Leaflet wrapper — center/zoom/markers values, GeoJSON URL fetch, ResizeObserver, subclass hooks for tile layer and event listeners',
            'npm' => ['leaflet' => '^1.9.4'],
        ],
        'modal' => [
            'source' => 'resources/js/controllers/modal_controller.js',
            'docs' => 'docs/controllers/modal.md',
            'category' => 'overlay',
            'description' => 'Accessible modal with state-driven motion, focus trap and Turbo integration',
        ],
        'modal-auto-close' => [
            'source' => 'resources/js/controllers/modal_auto_close_controller.js',
            'docs' => 'docs/controllers/modal-auto-close.md',
            'category' => 'overlay',
            'description' => 'Closes the nearest modal on connect — for server-driven dismissal via Turbo Stream',
        ],
        'money-input' => [
            'source' => 'resources/js/controllers/money_input_controller.js',
            'docs' => 'docs/controllers/money-input.md',
            'category' => 'forms',
            'description' => 'Classic money input with locale-aware formatting and right-aligned fractional entry',
        ],
        'multi-select' => [
            'source' => 'resources/js/controllers/multi_select_controller.js',
            'docs' => 'docs/controllers/multi-select.md',
            'category' => 'forms',
            'description' => 'Searchable multi-value select with select-all, max selection and state-driven Floating UI presence',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'oembed' => [
            'source' => 'resources/js/controllers/oembed_controller.js',
            'docs' => 'docs/controllers/oembed.md',
            'category' => 'display',
            'description' => 'Transforms oembed tags into responsive iframes for YouTube, Vimeo and others',
            'styling' => [
                'slots' => $slots(['oembed', 'oembed-frame', 'oembed-link']),
            ],
        ],
        'optimistic--dispatch' => [
            'source' => 'resources/js/controllers/optimistic/dispatch_controller.js',
            'docs' => 'docs/controllers/optimistic/dispatch.md',
            'category' => 'turbo',
            'description' => 'Escape-hatch controller that exposes optimistic dispatch for custom triggers',
        ],
        'optimistic--form' => [
            'source' => 'resources/js/controllers/optimistic/form_controller.js',
            'docs' => 'docs/controllers/optimistic/form.md',
            'category' => 'turbo',
            'description' => 'Dispatches optimistic UI updates immediately when a Turbo form submits',
        ],
        'optimistic--link' => [
            'source' => 'resources/js/controllers/optimistic/link_controller.js',
            'docs' => 'docs/controllers/optimistic/link.md',
            'category' => 'turbo',
            'description' => 'Dispatches optimistic UI updates immediately when a Turbo-driven link is clicked',
        ],
        'pagination' => [
            'source' => 'resources/js/controllers/pagination_controller.js',
            'docs' => 'docs/controllers/pagination.md',
            'category' => 'navigation',
            'description' => 'Loads additional paginator pages from server-rendered HTML, with manual and IntersectionObserver activation',
        ],
        'password-visibility' => [
            'source' => 'resources/js/controllers/password_visibility_controller.js',
            'docs' => 'docs/controllers/password-visibility.md',
            'category' => 'forms',
            'description' => 'Toggles a password input between hidden and visible, keeping the trigger ARIA state in sync',
        ],
        'popover' => [
            'source' => 'resources/js/controllers/popover_controller.js',
            'docs' => 'docs/controllers/popover.md',
            'category' => 'overlay',
            'description' => 'Anchored click-triggered popover with state-driven presence, focus return and Floating UI positioning',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'read-more' => [
            'source' => 'resources/js/controllers/read_more_controller.js',
            'docs' => 'docs/controllers/read-more.md',
            'category' => 'display',
            'description' => 'Measures overflowing content and coordinates accessible collapsed and expanded states',
        ],
        'remote-form' => [
            'source' => 'resources/js/controllers/remote_form_controller.js',
            'docs' => 'docs/controllers/remote-form.md',
            'category' => 'forms',
            'description' => 'Submits a form from a decoupled trigger element outside the form',
        ],
        'reset-files' => [
            'source' => 'resources/js/controllers/reset_files_controller.js',
            'docs' => 'docs/controllers/reset-files.md',
            'category' => 'forms',
            'description' => 'Clears file inputs automatically after a successful Turbo morph',
        ],
        'reveal' => [
            'source' => 'resources/js/controllers/reveal_controller.js',
            'docs' => 'docs/controllers/reveal.md',
            'category' => 'display',
            'description' => 'Coordinates load and per-item scroll reveal cascades with Turbo-safe cleanup',
        ],
        'rich-text' => [
            'source' => 'resources/js/controllers/rich_text_controller.js',
            'docs' => 'docs/controllers/rich-text.md',
            'category' => 'forms',
            'description' => 'Tiptap-backed rich text editor — syncs a hidden textarea, dispatches change/state/focus/blur and an optional image-upload event for app-side handling',
            'npm' => [
                '@tiptap/core' => '^2.0',
                '@tiptap/starter-kit' => '^2.0',
                '@tiptap/extension-placeholder' => '^2.0',
                '@tiptap/extension-link' => '^2.0',
                '@tiptap/extension-underline' => '^2.0',
            ],
        ],
        'rich-text-toolbar' => [
            'source' => 'resources/js/controllers/rich_text_toolbar_controller.js',
            'docs' => 'docs/controllers/rich-text-toolbar.md',
            'category' => 'forms',
            'description' => 'Optional toolbar paired with the rich-text controller via a Stimulus outlet — reflects active marks and runs Tiptap chain commands',
        ],
        'scroll-progress' => [
            'source' => 'resources/js/controllers/scroll_progress_controller.js',
            'docs' => 'docs/controllers/scroll-progress.md',
            'category' => 'utility',
            'description' => 'Displays a progress bar that follows the scroll position',
        ],
        'sheet' => [
            'source' => 'resources/js/controllers/sheet_controller.js',
            'docs' => 'docs/controllers/sheet.md',
            'category' => 'overlay',
            'description' => 'Off-canvas sheet with state-driven motion, focus trap and side-aware slide transitions',
        ],
        'side-panel' => [
            'source' => 'resources/js/controllers/side_panel_controller.js',
            'docs' => 'docs/controllers/side-panel.md',
            'category' => 'navigation',
            'description' => 'Controls an inline collapsible panel with cookie persistence and nested scope isolation',
        ],
        'sidebar' => [
            'source' => 'resources/js/controllers/sidebar_controller.js',
            'docs' => 'docs/controllers/sidebar.md',
            'category' => 'navigation',
            'description' => 'Controls desktop sidebar state and a Presence-driven mobile overlay',
        ],
        'slider' => [
            'source' => 'resources/js/controllers/slider_controller.js',
            'docs' => 'docs/controllers/slider.md',
            'category' => 'forms',
            'description' => 'Keeps a native range input visual fill synchronized with its current value',
        ],
        'slug' => [
            'source' => 'resources/js/controllers/slug_controller.js',
            'docs' => 'docs/controllers/slug.md',
            'category' => 'forms',
            'description' => 'Auto-fills a slug field from a source input until the user edits it, with preview and max-length',
        ],
        'tabs' => [
            'source' => 'resources/js/controllers/tabs_controller.js',
            'docs' => 'docs/controllers/tabs.md',
            'category' => 'display',
            'description' => 'Accessible tabs with roving tabindex, arrow/Home/End keyboard navigation and automatic activation',
        ],
        'timeago' => [
            'source' => 'resources/js/controllers/timeago_controller.js',
            'docs' => 'docs/controllers/timeago.md',
            'category' => 'utility',
            'description' => 'Displays a self-refreshing relative timestamp (e.g. "3 minutes ago")',
        ],
        'toast' => [
            'source' => 'resources/js/controllers/toast_controller.js',
            'docs' => 'docs/controllers/toast.md',
            'category' => 'feedback',
            'description' => 'Fires a single toast from session flash or explicit props',
        ],
        'toaster' => [
            'source' => 'resources/js/controllers/toaster_controller.js',
            'docs' => 'docs/controllers/toaster.md',
            'category' => 'feedback',
            'description' => 'Renders and manages the toast stack, persisting it across Turbo Drive navigations',
            'styling' => [
                'slots' => $slots([
                    'toast',
                    'toast-icon',
                    'toast-content',
                    'toast-body',
                    'toast-title',
                    'toast-description',
                    'toast-close',
                ]),
            ],
        ],
        'toggle' => [
            'source' => 'resources/js/controllers/toggle_controller.js',
            'docs' => 'docs/controllers/toggle.md',
            'category' => 'forms',
            'description' => 'Synchronizes a two-state button with aria-pressed, data-state and an optional hidden input',
        ],
        'toggle-group' => [
            'source' => 'resources/js/controllers/toggle_group_controller.js',
            'docs' => 'docs/controllers/toggle-group.md',
            'category' => 'forms',
            'description' => 'Coordinates pressed-button groups so single groups keep one active item and form inputs stay synchronized',
        ],
        'tooltip' => [
            'source' => 'resources/js/controllers/tooltip_controller.js',
            'docs' => 'docs/controllers/tooltip.md',
            'category' => 'overlay',
            'description' => 'Adds accessible hover/focus tooltips with state-driven presence, Floating UI positioning and top-layer promotion',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
            'styling' => [
                'slots' => $slots(['tooltip', 'tooltip-arrow']),
            ],
        ],
        'turbo--frame-src' => [
            'source' => 'resources/js/controllers/turbo/frame_src_controller.js',
            'docs' => 'docs/controllers/turbo/frame-src.md',
            'category' => 'turbo',
            'description' => 'Injects the X-Turbo-Frame-Src header on same-frame form submissions for correct redirect resolution',
        ],
        'turbo--morph-guard' => [
            'source' => 'resources/js/controllers/turbo/morph_guard_controller.js',
            'docs' => 'docs/controllers/turbo/morph-guard.md',
            'category' => 'turbo',
            'description' => 'Keeps the nearest Turbo Frame permanent during active editing so external morph refreshes preserve local state',
        ],
        'turbo--polling' => [
            'source' => 'resources/js/controllers/turbo/polling_controller.js',
            'docs' => 'docs/controllers/turbo/polling.md',
            'category' => 'turbo',
            'description' => 'Reloads a Turbo Frame at regular intervals without user interaction',
        ],
        'turbo--preserve-scroll' => [
            'source' => 'resources/js/controllers/turbo/preserve_scroll_controller.js',
            'docs' => 'docs/controllers/turbo/preserve-scroll.md',
            'category' => 'turbo',
            'description' => 'Preserves page scroll around Turbo Frame renders that replace focused content',
        ],
        'turbo--progress' => [
            'source' => 'resources/js/controllers/turbo/progress_controller.js',
            'docs' => 'docs/controllers/turbo/progress.md',
            'category' => 'turbo',
            'description' => 'Extends the Turbo Drive progress bar to cover Frame and Stream requests',
        ],
        'turbo--view-transition' => [
            'source' => 'resources/js/controllers/turbo/view_transition_controller.js',
            'docs' => 'docs/controllers/turbo/view-transition.md',
            'category' => 'turbo',
            'description' => 'Applies the View Transitions API when rendering Turbo Frame content',
        ],
        'unsaved-changes' => [
            'source' => 'resources/js/controllers/unsaved_changes_controller.js',
            'docs' => 'docs/controllers/unsaved-changes.md',
            'category' => 'forms',
            'description' => 'Warns the user before navigating away with unsaved form changes',
        ],
    ],
];
