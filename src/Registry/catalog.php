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
use Emaia\LaravelHotwire\Components\Tooltip;

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
            'description' => 'Native disclosure accordion with single or multiple open items and disabled-item support',
            'controllers' => ['accordion'],
            'styling' => [
                'slots' => [
                    ['class' => Accordion::class],
                ],
            ],
        ],
        'alert' => [
            'class' => Alert::class,
            'view' => 'hotwire::component-views.alert',
            'docs' => 'docs/components/alert.md',
            'category' => 'feedback',
            'description' => 'Inline alert with title, description, icon, action and semantic variants',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Alert::class],
                ],
            ],
        ],
        'alert-dialog' => [
            'class' => AlertDialog::class,
            'view' => 'hotwire::component-views.alert-dialog',
            'docs' => 'docs/components/alert-dialog.md',
            'category' => 'overlay',
            'description' => 'Accessible inline or shared confirmation dialog for links, forms and Turbo actions',
            'controllers' => ['alert-dialog'],
            'styling' => [
                'slots' => [
                    ['class' => AlertDialog::class],
                ],
            ],
        ],
        'aspect-ratio' => [
            'class' => AspectRatio::class,
            'view' => 'hotwire::component-views.aspect-ratio',
            'docs' => 'docs/components/aspect-ratio.md',
            'category' => 'display',
            'description' => 'JavaScript-free media container with a configurable or intrinsic aspect ratio',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => AspectRatio::class],
                ],
            ],
        ],
        'attachment' => [
            'class' => Attachment::class,
            'view' => 'hotwire::component-views.attachment',
            'docs' => 'docs/components/attachment.md',
            'category' => 'display',
            'description' => 'Composable file card with media, metadata, states, actions and an optional full-card trigger',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Attachment::class],
                ],
            ],
        ],
        'avatar' => [
            'class' => Avatar::class,
            'view' => 'hotwire::component-views.avatar',
            'docs' => 'docs/components/avatar.md',
            'category' => 'display',
            'description' => 'User avatar with image, initials fallback, status badge and overlapping groups',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Avatar::class],
                ],
            ],
        ],
        'back-to-top' => [
            'class' => BackToTop::class,
            'view' => 'hotwire::component-views.back-to-top',
            'docs' => 'docs/components/back-to-top.md',
            'category' => 'utility',
            'description' => 'Accessible button revealed after a scroll threshold to return to the page top',
            'controllers' => ['back-to-top'],
            'styling' => [
                'slots' => [
                    ['class' => BackToTop::class],
                ],
            ],
        ],
        'badge' => [
            'class' => Badge::class,
            'view' => 'hotwire::component-views.badge',
            'docs' => 'docs/components/badge.md',
            'category' => 'display',
            'description' => 'Compact status or metadata label with semantic variants and optional link rendering',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Badge::class],
                ],
            ],
        ],
        'breadcrumb' => [
            'class' => Breadcrumb::class,
            'view' => 'hotwire::component-views.breadcrumb',
            'docs' => 'docs/components/breadcrumb.md',
            'category' => 'navigation',
            'description' => 'Semantic navigation trail with composable items, ellipses and Turbo Frame targets',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Breadcrumb::class],
                ],
            ],
        ],
        'button' => [
            'class' => Button::class,
            'view' => 'hotwire::component-views.button',
            'docs' => 'docs/components/button.md',
            'category' => 'display',
            'description' => 'Button or link action with variants, sizes, Turbo Frame targets, hotkeys and tooltips',
            'controllers' => ['hotkey', 'tooltip'],
            'styling' => [
                'slots' => [
                    ['class' => Button::class],
                ],
            ],
        ],
        'button-group' => [
            'class' => ButtonGroup::class,
            'view' => 'hotwire::component-views.button-group',
            'docs' => 'docs/components/button-group.md',
            'category' => 'display',
            'description' => 'Horizontal or vertical group for related buttons, text and separators',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => ButtonGroup::class],
                ],
            ],
        ],
        'card' => [
            'class' => Card::class,
            'view' => 'hotwire::component-views.card',
            'docs' => 'docs/components/card.md',
            'category' => 'display',
            'description' => 'Composable content card with header, title, description, action, body and footer',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Card::class],
                ],
            ],
        ],
        'carousel' => [
            'class' => Carousel::class,
            'view' => 'hotwire::component-views.carousel',
            'docs' => 'docs/components/carousel.md',
            'category' => 'display',
            'description' => 'Embla carousel with responsive options, navigation, dots, progress and slide counter',
            'controllers' => ['carousel'],
            'styling' => [
                'slots' => [
                    ['class' => Carousel::class],
                ],
            ],
        ],
        'chart' => [
            'class' => Chart::class,
            'view' => 'hotwire::component-views.chart',
            'docs' => 'docs/components/chart.md',
            'category' => 'display',
            'description' => 'Apache ECharts visualization from inline options or a JSON endpoint with optional polling',
            'controllers' => ['chart'],
            'styling' => [
                'slots' => [
                    ['class' => Chart::class],
                ],
            ],
        ],
        'checkbox' => [
            'class' => Checkbox::class,
            'view' => 'hotwire::component-views.checkbox',
            'docs' => 'docs/components/checkbox.md',
            'category' => 'forms',
            'description' => 'Native checkbox with old input, validation, unchecked values and indeterminate state',
            'controllers' => ['checkbox', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => Checkbox::class],
                ],
            ],
        ],
        'checkbox-group' => [
            'class' => CheckboxGroup::class,
            'view' => 'hotwire::component-views.checkbox-group',
            'docs' => 'docs/components/checkbox-group.md',
            'category' => 'forms',
            'description' => 'Native checkbox set with generated or rich items, validation and optional select-all control',
            'controllers' => ['checkbox-select-all', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => CheckboxGroup::class],
                ],
            ],
        ],
        'checkbox-group.item' => [
            'class' => CheckboxGroupItem::class,
            'view' => 'hotwire::component-views.checkbox-group-item',
            'docs' => 'docs/components/checkbox-group.md',
            'category' => 'forms',
            'description' => 'Nested checkbox option with inherited selection, validation and select-all support',
            'controllers' => ['checkbox-select-all', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => CheckboxGroup::class, 'only' => ['item', 'input', 'item-content']],
                ],
            ],
        ],
        'color-scheme.script' => [
            'class' => ColorSchemeScript::class,
            'view' => 'hotwire::component-views.color-scheme-script',
            'docs' => 'docs/components/color-scheme.md',
            'category' => 'utility',
            'description' => 'Initial light, dark or system color scheme selection with persisted preference',
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
            'description' => 'Button for cycling persisted light, dark and system color schemes with optional transitions',
            'controllers' => ['color-scheme', 'tooltip'],
            'styling' => [
                'slots' => [
                    ['class' => ColorSchemeToggle::class],
                ],
            ],
        ],
        'conditional-field' => [
            'class' => ConditionalField::class,
            'view' => 'hotwire::component-views.conditional-field',
            'docs' => 'docs/components/conditional-field.md',
            'category' => 'forms',
            'description' => 'Dependent form block with declarative server and client visibility rules',
            'controllers' => ['conditional-fields'],
            'styling' => [
                'slots' => [
                    ['class' => ConditionalField::class],
                ],
            ],
        ],
        'controller-preloads' => [
            'class' => ControllerPreloads::class,
            'view' => 'hotwire::component-views.controller-preloads',
            'docs' => 'docs/components/controller-preloads.md',
            'category' => 'utility',
            'description' => 'Production module preloads for selected application or package Stimulus controllers',
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
            'description' => 'Accessible directional drawer with focus management and optional Turbo Frame content',
            'controllers' => ['drawer', 'turbo--view-transition'],
            'styling' => [
                'slots' => [
                    ['class' => Drawer::class],
                ],
            ],
        ],
        'dropdown' => [
            'class' => Dropdown::class,
            'view' => 'hotwire::component-views.dropdown',
            'docs' => 'docs/components/dropdown.md',
            'category' => 'overlay',
            'description' => 'Accessible disclosure dropdown with responsive positioning and native tab-order navigation',
            'controllers' => ['dropdown'],
            'styling' => [
                'slots' => [
                    ['class' => Dropdown::class],
                ],
            ],
        ],
        'empty-state' => [
            'class' => EmptyState::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/empty-state.md',
            'category' => 'display',
            'description' => 'Composable empty state for zero-result, first-run and unavailable-content screens',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => EmptyState::class],
                ],
            ],
        ],
        'field' => [
            'class' => Field::class,
            'view' => 'hotwire::component-views.field',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Laravel-aware field composition with labels, help text, validation errors and set semantics',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Field::class],
                ],
            ],
        ],
        'field.error' => [
            'class' => FieldError::class,
            'view' => 'hotwire::component-views.field-error',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Persistent accessible validation error container for one or multiple messages',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Field::class, 'only' => ['error']],
                ],
            ],
        ],
        'field.group' => [
            'class' => FieldGroup::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Vertical layout stack for related fields and form sections',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Field::class, 'only' => ['group']],
                ],
            ],
        ],
        'field.label' => [
            'class' => FieldLabel::class,
            'view' => 'hotwire::component-views.field-label',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Form label for controls or control sets with derived association and required marker',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Field::class, 'only' => ['label', 'label-required']],
                ],
            ],
        ],
        'file' => [
            'class' => File::class,
            'view' => 'hotwire::component-views.file',
            'docs' => 'docs/components/file.md',
            'category' => 'forms',
            'description' => 'Native file input with validation, current-file links, failed Turbo submit restore and optional reset',
            'controllers' => ['file-preserve', 'reset-files'],
            'styling' => [
                'slots' => [
                    ['class' => File::class],
                ],
            ],
        ],
        'file-upload' => [
            'class' => FileUpload::class,
            'view' => 'hotwire::component-views.file-upload',
            'docs' => 'docs/components/file-upload.md',
            'category' => 'forms',
            'description' => 'App-integrated drag-and-drop uploader with progress and JSON or Turbo Stream output',
            'controllers' => ['file-upload'],
            'styling' => [
                'slots' => [
                    ['class' => FileUpload::class, 'only' => ['root', 'dropzone', 'image-base', 'image-preview', 'feedback', 'actions']],
                    ['class' => Attachment::class, 'only' => ['group']],
                    ['class' => EmptyState::class, 'only' => ['description']],
                    ['class' => FileUpload::class, 'only' => ['announcer']],
                ],
            ],
        ],
        'form' => [
            'class' => Form::class,
            'view' => 'hotwire::component-views.form',
            'docs' => 'docs/components/form.md',
            'category' => 'forms',
            'description' => 'Laravel form with CSRF, method spoofing, Turbo Frames, auto-submit and unsaved-change protection',
            'controllers' => ['auto-submit', 'unsaved-changes', 'error-scroll', 'clean-query-params', 'conditional-fields'],
            'styling' => [
                'slots' => [
                    ['class' => Form::class],
                ],
            ],
        ],
        'frame' => [
            'class' => Frame::class,
            'view' => 'hotwire::component-views.frame',
            'docs' => 'docs/components/frame.md',
            'category' => 'turbo',
            'description' => 'Turbo Frame with lazy loading, history control, polling, transitions and scroll preservation',
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
            'description' => 'Shared Turbo Frame payload and full-layout page rendering from one view',
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
            'description' => 'Lazy contextual content for the active configured Turbo Frame branch',
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
            'description' => 'Lazy contextual content for the full-page or unmatched Turbo Frame branch',
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
            'description' => 'Hover- or focus-triggered preview card for lightweight, mostly non-interactive context',
            'controllers' => ['hover-card'],
            'styling' => [
                'slots' => [
                    ['class' => HoverCard::class],
                ],
            ],
        ],
        'icon' => [
            'class' => Icon::class,
            'view' => 'hotwire::component-views.icon',
            'docs' => 'docs/components/icon.md',
            'category' => 'display',
            'description' => 'Inline SVG icon from the package-bundled Lucide icon set',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Icon::class],
                ],
            ],
        ],
        'input' => [
            'class' => Input::class,
            'view' => 'hotwire::component-views.input',
            'docs' => 'docs/components/input.md',
            'category' => 'forms',
            'description' => 'Laravel-aware input with validation, masks, clearing, selection and auto-submit',
            'controllers' => ['auto-select', 'clear-input', 'input-mask', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => Input::class],
                ],
            ],
        ],
        'input-group' => [
            'class' => InputGroup::class,
            'view' => 'hotwire::component-views.input-group',
            'docs' => 'docs/components/input-group.md',
            'category' => 'forms',
            'description' => 'Composable control shell with addons, actions, shortcuts and helper content',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => InputGroup::class],
                ],
            ],
        ],
        'item' => [
            'class' => Item::class,
            'view' => 'hotwire::component-views.item',
            'docs' => 'docs/components/item.md',
            'category' => 'display',
            'description' => 'Composable list row with media, content, actions and link or button semantics',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Item::class],
                ],
            ],
        ],
        'kbd' => [
            'class' => Kbd::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/kbd.md',
            'category' => 'display',
            'description' => 'Keyboard input hint for shortcuts and grouped key combinations',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Kbd::class],
                ],
            ],
        ],
        'map' => [
            'class' => Map::class,
            'view' => 'hotwire::component-views.map',
            'docs' => 'docs/components/map.md',
            'category' => 'display',
            'description' => 'Leaflet map with OpenStreetMap tiles, inline markers or GeoJSON and automatic fitting',
            'controllers' => ['map'],
            'styling' => [
                'slots' => [
                    ['class' => Map::class],
                ],
            ],
        ],
        'marker' => [
            'class' => Marker::class,
            'view' => 'hotwire::component-views.marker',
            'docs' => 'docs/components/marker.md',
            'category' => 'display',
            'description' => 'Visual marker with icon and content variants for timelines, activity feeds and lists',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Marker::class],
                ],
            ],
        ],
        'meta' => [
            'class' => Meta::class,
            'view' => 'hotwire::component-views.meta',
            'docs' => 'docs/components/meta.md',
            'category' => 'turbo',
            'description' => 'Opt-in bundle of validated Hotwire meta tags with practical defaults',
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
            'description' => 'Turbo cache policy for disabling page caching or preview caching',
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
            'description' => 'Document color-scheme metadata for supported light and dark modes',
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
            'description' => 'CSRF token metadata consumed by File Upload requests',
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
            'description' => 'Turbo Drive link-prefetch policy with explicit enabled or disabled state',
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
            'description' => 'Turbo page-refresh method and scroll policy for replace or morph updates',
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
            'description' => 'Turbo Drive navigation boundary for a configurable path prefix',
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
            'description' => 'Same-origin view-transition opt-in for page navigations',
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
            'description' => 'Turbo visit policy that forces a full page reload',
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
            'description' => 'Accessible modal dialog with focus management and optional Turbo Frame content',
            'controllers' => ['modal', 'turbo--view-transition'],
            'styling' => [
                'slots' => [
                    ['class' => Modal::class],
                ],
            ],
        ],
        'multi-select' => [
            'class' => MultiSelect::class,
            'view' => 'hotwire::component-views.multi-select',
            'docs' => 'docs/components/multi-select.md',
            'category' => 'forms',
            'description' => 'Searchable multi-value form control with selection limits and native submission',
            'controllers' => ['multi-select', 'clear-input'],
            'styling' => [
                'slots' => [
                    ['class' => MultiSelect::class],
                ],
            ],
        ],
        'navbar' => [
            'class' => Navbar::class,
            'view' => 'hotwire::component-views.navbar',
            'docs' => 'docs/components/navbar.md',
            'category' => 'navigation',
            'description' => 'Horizontal or vertical navigation with current state, Turbo Frame targets and sticky layout',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Navbar::class],
                    ['class' => Sticky::class, 'only' => ['root']],
                ],
            ],
        ],
        'navbar.item' => [
            'class' => NavbarItem::class,
            'view' => 'hotwire::component-views.navbar-item',
            'docs' => 'docs/components/navbar.md',
            'category' => 'navigation',
            'description' => 'Navigation link, button or text item with current, disabled and Turbo Frame states',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Navbar::class, 'only' => ['item']],
                ],
            ],
        ],
        'optimistic' => [
            'class' => Optimistic::class,
            'view' => 'hotwire::component-views.optimistic',
            'docs' => 'docs/components/optimistic.md',
            'category' => 'turbo',
            'description' => 'Inline optimistic Turbo Stream payload for forms, links and custom Turbo triggers',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Optimistic::class],
                ],
            ],
        ],
        'pagination' => [
            'class' => Pagination::class,
            'view' => 'hotwire::component-views.pagination',
            'docs' => 'docs/components/pagination.md',
            'category' => 'navigation',
            'description' => 'Laravel pagination with Turbo Frame, Turbo Stream, load-more and infinite modes',
            'controllers' => ['pagination'],
            'styling' => [
                'slots' => [
                    ['class' => Pagination::class],
                ],
            ],
        ],
        'popover' => [
            'class' => Popover::class,
            'view' => 'hotwire::component-views.popover',
            'docs' => 'docs/components/popover.md',
            'category' => 'overlay',
            'description' => 'Anchored click-triggered panel for interactive content with adaptive positioning',
            'controllers' => ['popover'],
            'styling' => [
                'slots' => [
                    ['class' => Popover::class],
                ],
            ],
        ],
        'progress' => [
            'class' => Progress::class,
            'view' => 'hotwire::component-views.progress',
            'docs' => 'docs/components/progress.md',
            'category' => 'feedback',
            'description' => 'Composable progress display with ARIA semantics, label, value, track and indicator',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Progress::class],
                ],
            ],
        ],
        'radio-group' => [
            'class' => RadioGroup::class,
            'view' => 'hotwire::component-views.radio-group',
            'docs' => 'docs/components/radio-group.md',
            'category' => 'forms',
            'description' => 'Native radio group with generated or rich items, validation and auto-submit',
            'controllers' => ['auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => RadioGroup::class],
                ],
            ],
        ],
        'radio-group.item' => [
            'class' => RadioGroupItem::class,
            'view' => 'hotwire::component-views.radio-group-item',
            'docs' => 'docs/components/radio-group.md',
            'category' => 'forms',
            'description' => 'Nested radio option with inherited selection, form and validation context',
            'controllers' => ['auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => RadioGroup::class, 'only' => ['item', 'input', 'item-content']],
                ],
            ],
        ],
        'read-more' => [
            'class' => ReadMore::class,
            'view' => 'hotwire::component-views.read-more',
            'docs' => 'docs/components/read-more.md',
            'category' => 'display',
            'description' => 'Overflow-aware content preview with first-paint clamping and accessible expansion',
            'controllers' => ['read-more'],
            'styling' => [
                'slots' => [
                    ['class' => ReadMore::class],
                ],
            ],
        ],
        'reveal' => [
            'class' => Reveal::class,
            'view' => 'hotwire::component-views.reveal',
            'docs' => 'docs/components/reveal.md',
            'category' => 'display',
            'description' => 'Staggered entrance for direct or nested content with load or scroll triggers',
            'controllers' => ['reveal'],
            'styling' => [
                'slots' => [
                    ['class' => Reveal::class],
                ],
            ],
        ],
        'reveal.item' => [
            'class' => RevealItem::class,
            'view' => 'hotwire::component-views.reveal-item',
            'docs' => 'docs/components/reveal.md',
            'category' => 'display',
            'description' => 'Nested reveal item with parent-assigned stagger ordering',
            'controllers' => ['reveal'],
            'styling' => [
                'slots' => [
                    ['class' => Reveal::class, 'only' => ['item']],
                ],
            ],
        ],
        'rich-text' => [
            'class' => RichText::class,
            'view' => 'hotwire::component-views.rich-text',
            'docs' => 'docs/components/rich-text.md',
            'category' => 'forms',
            'description' => 'Tiptap editor with optional toolbar, HTML or JSON submission and app-managed image uploads',
            'controllers' => ['rich-text', 'rich-text-toolbar'],
            'styling' => [
                'slots' => [
                    ['class' => RichText::class],
                ],
            ],
        ],
        'scroll-progress' => [
            'class' => ScrollProgress::class,
            'view' => 'hotwire::component-views.scroll-progress',
            'docs' => 'docs/components/scroll-progress.md',
            'category' => 'utility',
            'description' => 'Fixed page-scroll progress indicator with configurable update throttling',
            'controllers' => ['scroll-progress'],
            'styling' => [
                'slots' => [
                    ['class' => ScrollProgress::class],
                ],
            ],
        ],
        'select' => [
            'class' => Select::class,
            'view' => 'hotwire::component-views.select',
            'docs' => 'docs/components/select.md',
            'category' => 'forms',
            'description' => 'Native select with placeholders, old input, validation and auto-submit',
            'controllers' => ['auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => Select::class],
                ],
            ],
        ],
        'separator' => [
            'class' => Separator::class,
            'view' => 'hotwire::component-views.separator',
            'docs' => 'docs/components/separator.md',
            'category' => 'display',
            'description' => 'Semantic horizontal or vertical separator for content sections',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Separator::class],
                ],
            ],
        ],
        'sheet' => [
            'class' => Sheet::class,
            'view' => 'hotwire::component-views.sheet',
            'docs' => 'docs/components/sheet.md',
            'category' => 'overlay',
            'description' => 'Accessible off-canvas dialog on any edge with optional Turbo Frame content',
            'controllers' => ['sheet', 'turbo--view-transition'],
            'styling' => [
                'slots' => [
                    ['class' => Sheet::class],
                ],
            ],
        ],
        'side-panel' => [
            'class' => SidePanel::class,
            'view' => 'hotwire::component-views.side-panel',
            'docs' => 'docs/components/side-panel.md',
            'category' => 'navigation',
            'description' => 'Nestable in-flow side panel for navigation, filters and workspace tools',
            'controllers' => ['side-panel'],
            'styling' => [
                'slots' => [
                    ['class' => SidePanel::class],
                ],
            ],
        ],
        'sidebar' => [
            'class' => Sidebar::class,
            'view' => 'hotwire::component-views.sidebar',
            'docs' => 'docs/components/sidebar.md',
            'category' => 'navigation',
            'description' => 'Responsive app sidebar with collapse modes, mobile overlay and persisted state',
            'controllers' => ['sidebar', 'reveal', 'tooltip'],
            'styling' => [
                'slots' => [
                    ['class' => Sidebar::class],
                ],
                'preset_properties' => [
                    'sidebar' => [
                        '--sidebar-floating-inset' => '0rem',
                        '--sidebar-floating-edge' => '0px',
                    ],
                ],
            ],
        ],
        'skeleton' => [
            'class' => Skeleton::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/skeleton.md',
            'category' => 'feedback',
            'description' => 'Animated loading placeholder with reusable shimmer styling',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Skeleton::class],
                ],
            ],
        ],
        'slider' => [
            'class' => Slider::class,
            'view' => 'hotwire::component-views.slider',
            'docs' => 'docs/components/slider.md',
            'category' => 'forms',
            'description' => 'Native single-thumb range input with vertical support, validation and auto-submit',
            'controllers' => ['slider', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => Slider::class],
                ],
            ],
        ],
        'spinner' => [
            'class' => Spinner::class,
            'view' => 'hotwire::component-views.spinner',
            'docs' => 'docs/components/spinner.md',
            'category' => 'feedback',
            'description' => 'Accessible CSS-only loading indicator with reduced-motion behavior',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Spinner::class],
                ],
            ],
        ],
        'sticky' => [
            'class' => Sticky::class,
            'view' => 'hotwire::component-views.sticky',
            'docs' => 'docs/components/sticky.md',
            'category' => 'navigation',
            'description' => 'Top or bottom sticky surface for navigation, persistent actions and summary content',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Sticky::class],
                ],
            ],
        ],
        'switch' => [
            'class' => SwitchInput::class,
            'view' => 'hotwire::component-views.switch',
            'docs' => 'docs/components/switch.md',
            'category' => 'forms',
            'description' => 'Native ARIA switch with old input, optional unchecked value and auto-submit',
            'controllers' => ['auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => SwitchInput::class],
                ],
            ],
        ],
        'table' => [
            'class' => Table::class,
            'view' => 'hotwire::component-views.table',
            'docs' => 'docs/components/table.md',
            'category' => 'display',
            'description' => 'Responsive semantic table primitives for headers, bodies, footers, captions, rows and cells',
            'controllers' => [],
            'styling' => [
                'slots' => [
                    ['class' => Table::class],
                ],
            ],
        ],
        'tabs' => [
            'class' => Tabs::class,
            'view' => 'hotwire::component-views.tabs',
            'docs' => 'docs/components/tabs.md',
            'category' => 'display',
            'description' => 'ARIA tab set with horizontal or vertical keyboard navigation and server-selected state',
            'controllers' => ['tabs'],
            'styling' => [
                'slots' => [
                    ['class' => Tabs::class],
                ],
            ],
        ],
        'textarea' => [
            'class' => Textarea::class,
            'view' => 'hotwire::component-views.textarea',
            'docs' => 'docs/components/textarea.md',
            'category' => 'forms',
            'description' => 'Textarea with optional auto-resize, character counter, validation and auto-submit',
            'controllers' => ['auto-resize', 'char-counter', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => Textarea::class],
                ],
            ],
        ],
        'timeago' => [
            'class' => Timeago::class,
            'view' => 'hotwire::component-views.timeago',
            'docs' => 'docs/components/timeago.md',
            'category' => 'utility',
            'description' => 'Localized relative timestamp with server fallback and optional automatic refresh',
            'controllers' => ['timeago'],
            'styling' => [
                'slots' => [
                    ['class' => Timeago::class],
                ],
            ],
        ],
        'toast' => [
            'class' => Toast::class,
            'view' => 'hotwire::component-views.toast',
            'docs' => 'docs/components/toast.md',
            'category' => 'feedback',
            'description' => 'Toast notification from props, session flash or Turbo Streams into a Toaster',
            'controllers' => ['toast'],
            'styling' => [
                'slots' => [
                    ['class' => Toast::class],
                ],
            ],
        ],
        'toaster' => [
            'class' => Toaster::class,
            'view' => 'hotwire::component-views.toaster',
            'docs' => 'docs/components/toaster.md',
            'category' => 'feedback',
            'description' => 'Persistent toast stack for session flash, Turbo Streams and JavaScript notifications',
            'controllers' => ['toaster', 'toast'],
            'styling' => [
                'slots' => [
                    ['class' => Toaster::class],
                    ['class' => Toast::class],
                ],
            ],
        ],
        'toggle' => [
            'class' => Toggle::class,
            'view' => 'hotwire::component-views.toggle',
            'docs' => 'docs/components/toggle.md',
            'category' => 'forms',
            'description' => 'ARIA-pressed two-state button with optional form value and auto-submit',
            'controllers' => ['toggle', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => Toggle::class],
                ],
            ],
        ],
        'toggle-group' => [
            'class' => ToggleGroup::class,
            'view' => 'hotwire::component-views.toggle-group',
            'docs' => 'docs/components/toggle-group.md',
            'category' => 'forms',
            'description' => 'Pressed-button group with single or multiple selection, optional form submission and auto-submit',
            'controllers' => ['toggle-group', 'toggle', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => ToggleGroup::class],
                ],
            ],
        ],
        'toggle-group.item' => [
            'class' => ToggleGroupItem::class,
            'view' => 'hotwire::component-views.toggle-group-item',
            'docs' => 'docs/components/toggle-group.md',
            'category' => 'forms',
            'description' => 'Nested pressed button with inherited group selection, form and disabled state',
            'controllers' => ['toggle-group', 'toggle', 'auto-submit'],
            'styling' => [
                'slots' => [
                    ['class' => ToggleGroup::class, 'only' => ['item']],
                ],
            ],
        ],
        'tooltip' => [
            'class' => Tooltip::class,
            'view' => 'hotwire::component-views.tooltip',
            'docs' => 'docs/components/tooltip.md',
            'category' => 'overlay',
            'description' => 'Accessible non-interactive help for hover and focus triggers with anchored positioning',
            'controllers' => ['tooltip'],
            'styling' => [
                'slots' => [
                    ['class' => Tooltip::class],
                ],
            ],
        ],
    ],
    'controllers' => [
        'accordion' => [
            'source' => 'resources/js/controllers/accordion_controller.js',
            'docs' => 'docs/controllers/accordion.md',
            'category' => 'display',
            'description' => 'Coordinates native disclosure items in single or multiple mode and blocks disabled items',
        ],
        'alert-dialog' => [
            'source' => 'resources/js/controllers/alert_dialog_controller.js',
            'docs' => 'docs/controllers/alert-dialog.md',
            'category' => 'overlay',
            'description' => 'Guards click actions with an accessible confirmation dialog',
        ],
        'animated-number' => [
            'source' => 'resources/js/controllers/animated_number_controller.js',
            'docs' => 'docs/controllers/animated-number.md',
            'category' => 'display',
            'description' => 'Animates numeric content immediately or once it enters the viewport',
        ],
        'auto-resize' => [
            'source' => 'resources/js/controllers/auto_resize_controller.js',
            'docs' => 'docs/controllers/auto-resize.md',
            'category' => 'forms',
            'description' => 'Resizes a textarea to fit its content after input, window resize and Turbo renders',
        ],
        'auto-save' => [
            'source' => 'resources/js/controllers/auto_save_controller.js',
            'docs' => 'docs/controllers/auto-save.md',
            'category' => 'forms',
            'description' => 'Saves changed forms with debouncing, status feedback and queued follow-up saves',
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
            'description' => 'Submits forms through immediate or debounced actions without duplicate IME commits',
        ],
        'autofocus' => [
            'source' => 'resources/js/controllers/autofocus_controller.js',
            'docs' => 'docs/controllers/autofocus.md',
            'category' => 'forms',
            'description' => 'Focuses a configured field initially and after relevant Turbo Frame loads',
        ],
        'back-to-top' => [
            'source' => 'resources/js/controllers/back_to_top_controller.js',
            'docs' => 'docs/controllers/back-to-top.md',
            'category' => 'utility',
            'description' => 'Reveals a control after a scroll threshold and returns to the top with reduced-motion support',
        ],
        'carousel' => [
            'source' => 'resources/js/controllers/carousel_controller.js',
            'docs' => 'docs/controllers/carousel.md',
            'category' => 'display',
            'description' => 'Runs Embla carousels with navigation, dots, progress, responsive options and dynamic slides',
            'npm' => ['embla-carousel' => '^8.6.0'],
        ],
        'char-counter' => [
            'source' => 'resources/js/controllers/char_counter_controller.js',
            'docs' => 'docs/controllers/char-counter.md',
            'category' => 'forms',
            'description' => 'Displays typed or remaining character counts with live updates',
        ],
        'chart' => [
            'source' => 'resources/js/controllers/chart_controller.js',
            'docs' => 'docs/controllers/chart.md',
            'category' => 'display',
            'description' => 'Renders responsive Apache ECharts from inline or fetched options with optional polling',
            'npm' => ['echarts' => '^6.1.0'],
        ],
        'checkbox' => [
            'source' => 'resources/js/controllers/checkbox_controller.js',
            'docs' => 'docs/controllers/checkbox.md',
            'category' => 'forms',
            'description' => 'Synchronizes native checkbox indeterminate state across changes and Turbo renders',
        ],
        'checkbox-select-all' => [
            'source' => 'resources/js/controllers/checkbox_select_all_controller.js',
            'docs' => 'docs/controllers/checkbox-select-all.md',
            'category' => 'forms',
            'description' => 'Synchronizes a master checkbox with grouped selections and indeterminate state',
        ],
        'clean-query-params' => [
            'source' => 'resources/js/controllers/clean_query_params_controller.js',
            'docs' => 'docs/controllers/clean-query-params.md',
            'category' => 'forms',
            'description' => 'Removes empty values from GET form submissions before building the query string',
        ],
        'clear-input' => [
            'source' => 'resources/js/controllers/clear_input_controller.js',
            'docs' => 'docs/controllers/clear-input.md',
            'category' => 'forms',
            'description' => 'Clears a targeted input, controls button visibility and emits input events',
        ],
        'color-scheme' => [
            'source' => 'resources/js/controllers/color_scheme_controller.js',
            'docs' => 'docs/controllers/color-scheme.md',
            'category' => 'utility',
            'description' => 'Persists light, dark or system mode across tabs with optional View Transitions',
        ],
        'conditional-fields' => [
            'source' => 'resources/js/controllers/conditional_fields_controller.js',
            'docs' => 'docs/controllers/conditional-fields.md',
            'category' => 'forms',
            'description' => 'Shows and disables dependent fields using declarative value and checkbox-state rules',
        ],
        'copy-to-clipboard' => [
            'source' => 'resources/js/controllers/copy_to_clipboard_controller.js',
            'docs' => 'docs/controllers/copy-to-clipboard.md',
            'category' => 'utility',
            'description' => 'Copies targeted text or input values and shows temporary success feedback',
        ],
        'dev--duplicate-ids' => [
            'source' => 'resources/js/controllers/dev/duplicate_ids_controller.js',
            'docs' => 'docs/controllers/dev/duplicate-ids.md',
            'category' => 'dev',
            'description' => 'Warns during development when duplicate DOM IDs appear within its render root',
        ],
        'dev--log' => [
            'source' => 'resources/js/controllers/dev/log_controller.js',
            'docs' => 'docs/controllers/dev/log.md',
            'category' => 'dev',
            'description' => 'Logs action events to the browser console for development debugging',
        ],
        'disclosure' => [
            'source' => 'resources/js/controllers/disclosure_controller.js',
            'docs' => 'docs/controllers/disclosure.md',
            'category' => 'display',
            'description' => 'Toggles an inline panel, synchronizes ARIA state and emits change events',
        ],
        'drawer' => [
            'source' => 'resources/js/controllers/drawer_controller.js',
            'docs' => 'docs/controllers/drawer.md',
            'category' => 'overlay',
            'description' => 'Controls an accessible off-canvas overlay with optional Turbo Frame content',
        ],
        'dropdown' => [
            'source' => 'resources/js/controllers/dropdown_controller.js',
            'docs' => 'docs/controllers/dropdown.md',
            'category' => 'overlay',
            'description' => 'Controls a responsive floating disclosure panel with selectable-item dismissal',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'error-scroll' => [
            'source' => 'resources/js/controllers/error_scroll_controller.js',
            'docs' => 'docs/controllers/error-scroll.md',
            'category' => 'forms',
            'description' => 'Scrolls to the first validation error after Turbo Frame or full-page renders',
        ],
        'file-preserve' => [
            'source' => 'resources/js/controllers/file_preserve_controller.js',
            'docs' => 'docs/controllers/file-preserve.md',
            'category' => 'forms',
            'description' => 'Restores selected files when a submitted Turbo form re-renders with validation errors',
        ],
        'file-upload' => [
            'source' => 'resources/js/controllers/file_upload_controller.js',
            'docs' => 'docs/controllers/file-upload.md',
            'category' => 'forms',
            'description' => 'Uploads validated files through queued requests with JSON or Turbo Stream responses',
        ],
        'gtm' => [
            'source' => 'resources/js/controllers/gtm_controller.js',
            'docs' => 'docs/controllers/gtm.md',
            'category' => 'utility',
            'description' => 'Loads Google Tag Manager immediately or on interaction and pushes data layer events',
        ],
        'hotkey' => [
            'source' => 'resources/js/controllers/hotkey_controller.js',
            'docs' => 'docs/controllers/hotkey.md',
            'category' => 'utility',
            'description' => 'Clicks or focuses its element from shortcuts while ignoring editable input',
        ],
        'hover-card' => [
            'source' => 'resources/js/controllers/hover_card_controller.js',
            'docs' => 'docs/controllers/hover-card.md',
            'category' => 'overlay',
            'description' => 'Shows a delayed floating preview on hover or focus with Escape dismissal',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'input-mask' => [
            'source' => 'resources/js/controllers/input_mask_controller.js',
            'docs' => 'docs/controllers/input-mask.md',
            'category' => 'forms',
            'description' => 'Applies Maska static, dynamic, reverse and custom-token masks to text inputs',
            'npm' => ['maska' => '^3.2.0'],
        ],
        'lazy-image' => [
            'source' => 'resources/js/controllers/lazy_image_controller.js',
            'docs' => 'docs/controllers/lazy-image.md',
            'category' => 'display',
            'description' => 'Polls for an image URL up to a limit and then renders responsive picture content',
        ],
        'map' => [
            'source' => 'resources/js/controllers/map_controller.js',
            'docs' => 'docs/controllers/map.md',
            'category' => 'display',
            'description' => 'Renders Leaflet maps with OpenStreetMap tiles, inline markers or fetched GeoJSON',
            'npm' => ['leaflet' => '^1.9.4'],
        ],
        'modal' => [
            'source' => 'resources/js/controllers/modal_controller.js',
            'docs' => 'docs/controllers/modal.md',
            'category' => 'overlay',
            'description' => 'Controls an accessible modal with optional Turbo Frame-driven content',
        ],
        'modal-auto-close' => [
            'source' => 'resources/js/controllers/modal_auto_close_controller.js',
            'docs' => 'docs/controllers/modal-auto-close.md',
            'category' => 'overlay',
            'description' => 'Closes the nearest modal when inserted by a Turbo Stream',
        ],
        'money-input' => [
            'source' => 'resources/js/controllers/money_input_controller.js',
            'docs' => 'docs/controllers/money-input.md',
            'category' => 'forms',
            'description' => 'Formats locale-aware money entry and exposes canonical minor-unit values',
        ],
        'multi-select' => [
            'source' => 'resources/js/controllers/multi_select_controller.js',
            'docs' => 'docs/controllers/multi-select.md',
            'category' => 'forms',
            'description' => 'Enhances a native multiple select with search, limits, select-all and floating positioning',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'oembed' => [
            'source' => 'resources/js/controllers/oembed_controller.js',
            'docs' => 'docs/controllers/oembed.md',
            'category' => 'display',
            'description' => 'Embeds YouTube and Vimeo URLs as responsive frames and other providers as links',
            'styling' => [
                'slots' => $slots(['oembed', 'oembed-frame', 'oembed-link']),
            ],
        ],
        'optimistic--dispatch' => [
            'source' => 'resources/js/controllers/optimistic/dispatch_controller.js',
            'docs' => 'docs/controllers/optimistic/dispatch.md',
            'category' => 'turbo',
            'description' => 'Applies optimistic Turbo Stream templates from arbitrary custom triggers',
        ],
        'optimistic--form' => [
            'source' => 'resources/js/controllers/optimistic/form_controller.js',
            'docs' => 'docs/controllers/optimistic/form.md',
            'category' => 'turbo',
            'description' => 'Applies optimistic Turbo Stream templates before a form request begins',
        ],
        'optimistic--link' => [
            'source' => 'resources/js/controllers/optimistic/link_controller.js',
            'docs' => 'docs/controllers/optimistic/link.md',
            'category' => 'turbo',
            'description' => 'Applies optimistic Turbo Stream templates when Turbo handles a link click',
        ],
        'pagination' => [
            'source' => 'resources/js/controllers/pagination_controller.js',
            'docs' => 'docs/controllers/pagination.md',
            'category' => 'navigation',
            'description' => 'Appends server-rendered paginator pages manually or when the fallback link enters view',
        ],
        'password-visibility' => [
            'source' => 'resources/js/controllers/password_visibility_controller.js',
            'docs' => 'docs/controllers/password-visibility.md',
            'category' => 'forms',
            'description' => 'Toggles password visibility and synchronizes the trigger ARIA state',
        ],
        'popover' => [
            'source' => 'resources/js/controllers/popover_controller.js',
            'docs' => 'docs/controllers/popover.md',
            'category' => 'overlay',
            'description' => 'Controls an interactive floating panel with focus and dismissal management',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'read-more' => [
            'source' => 'resources/js/controllers/read_more_controller.js',
            'docs' => 'docs/controllers/read-more.md',
            'category' => 'display',
            'description' => 'Measures overflow and synchronizes static, collapsed and expanded preview states',
        ],
        'remote-form' => [
            'source' => 'resources/js/controllers/remote_form_controller.js',
            'docs' => 'docs/controllers/remote-form.md',
            'category' => 'forms',
            'description' => 'Submits a form from a decoupled trigger while preserving submitter metadata',
        ],
        'reset-files' => [
            'source' => 'resources/js/controllers/reset_files_controller.js',
            'docs' => 'docs/controllers/reset-files.md',
            'category' => 'forms',
            'description' => 'Clears opted-in file inputs after successful form submission and a render without validation errors',
        ],
        'reveal' => [
            'source' => 'resources/js/controllers/reveal_controller.js',
            'docs' => 'docs/controllers/reveal.md',
            'category' => 'display',
            'description' => 'Coordinates load- or viewport-triggered reveal cascades and emits when content appears',
        ],
        'rich-text' => [
            'source' => 'resources/js/controllers/rich_text_controller.js',
            'docs' => 'docs/controllers/rich-text.md',
            'category' => 'forms',
            'description' => 'Runs a Tiptap editor with form sync, read-only mode and optional image-upload events',
            'npm' => [
                '@tiptap/core' => '3.31.3',
                '@tiptap/starter-kit' => '3.31.3',
                '@tiptap/extensions' => '3.31.3',
                '@tiptap/extension-link' => '3.31.3',
                '@tiptap/extension-underline' => '3.31.3',
                '@tiptap/pm' => '3.31.3',
            ],
        ],
        'rich-text-toolbar' => [
            'source' => 'resources/js/controllers/rich_text_toolbar_controller.js',
            'docs' => 'docs/controllers/rich-text-toolbar.md',
            'category' => 'forms',
            'description' => 'Formats a linked Tiptap editor and synchronizes toolbar ARIA pressed states',
        ],
        'scroll-progress' => [
            'source' => 'resources/js/controllers/scroll_progress_controller.js',
            'docs' => 'docs/controllers/scroll-progress.md',
            'category' => 'utility',
            'description' => 'Updates a progress bar from the document scroll position with optional throttling',
        ],
        'sheet' => [
            'source' => 'resources/js/controllers/sheet_controller.js',
            'docs' => 'docs/controllers/sheet.md',
            'category' => 'overlay',
            'description' => 'Controls an accessible sheet overlay with optional Turbo Frame content',
        ],
        'side-panel' => [
            'source' => 'resources/js/controllers/side_panel_controller.js',
            'docs' => 'docs/controllers/side-panel.md',
            'category' => 'navigation',
            'description' => 'Controls nested inline panel state with ARIA synchronization and optional persistence',
        ],
        'sidebar' => [
            'source' => 'resources/js/controllers/sidebar_controller.js',
            'docs' => 'docs/controllers/sidebar.md',
            'category' => 'navigation',
            'description' => 'Controls persisted desktop sidebar state and an accessible mobile overlay',
        ],
        'slider' => [
            'source' => 'resources/js/controllers/slider_controller.js',
            'docs' => 'docs/controllers/slider.md',
            'category' => 'forms',
            'description' => 'Synchronizes a native range input visual fill after input, reset and Turbo morphs',
        ],
        'slug' => [
            'source' => 'resources/js/controllers/slug_controller.js',
            'docs' => 'docs/controllers/slug.md',
            'category' => 'forms',
            'description' => 'Autofills and sanitizes a slug from a source field until manual editing, with optional length limits',
        ],
        'tabs' => [
            'source' => 'resources/js/controllers/tabs_controller.js',
            'docs' => 'docs/controllers/tabs.md',
            'category' => 'display',
            'description' => 'Implements ARIA tabs with automatic activation and orientation-aware keyboard navigation',
        ],
        'timeago' => [
            'source' => 'resources/js/controllers/timeago_controller.js',
            'docs' => 'docs/controllers/timeago.md',
            'category' => 'utility',
            'description' => 'Formats localized relative times with optional suffixes and automatic refresh',
        ],
        'toast' => [
            'source' => 'resources/js/controllers/toast_controller.js',
            'docs' => 'docs/controllers/toast.md',
            'category' => 'feedback',
            'description' => 'Emits one configured notification through a Toaster',
        ],
        'toaster' => [
            'source' => 'resources/js/controllers/toaster_controller.js',
            'docs' => 'docs/controllers/toaster.md',
            'category' => 'feedback',
            'description' => 'Manages an accessible toast stack with timed dismissal and Turbo Drive persistence',
        ],
        'toggle' => [
            'source' => 'resources/js/controllers/toggle_controller.js',
            'docs' => 'docs/controllers/toggle.md',
            'category' => 'forms',
            'description' => 'Synchronizes a two-state button ARIA state and optional hidden form input',
        ],
        'toggle-group' => [
            'source' => 'resources/js/controllers/toggle_group_controller.js',
            'docs' => 'docs/controllers/toggle-group.md',
            'category' => 'forms',
            'description' => 'Coordinates single or multiple pressed-button groups and synchronizes form inputs',
        ],
        'tooltip' => [
            'source' => 'resources/js/controllers/tooltip_controller.js',
            'docs' => 'docs/controllers/tooltip.md',
            'category' => 'overlay',
            'description' => 'Creates non-interactive ARIA tooltips from templates with anchored positioning',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'turbo--frame-src' => [
            'source' => 'resources/js/controllers/turbo/frame_src_controller.js',
            'docs' => 'docs/controllers/turbo/frame-src.md',
            'category' => 'turbo',
            'description' => 'Sends the Turbo Frame source URL with same-frame form submissions',
        ],
        'turbo--morph-guard' => [
            'source' => 'resources/js/controllers/turbo/morph_guard_controller.js',
            'docs' => 'docs/controllers/turbo/morph-guard.md',
            'category' => 'turbo',
            'description' => 'Preserves the nearest Turbo Frame during outer morphs while the user is editing',
        ],
        'turbo--polling' => [
            'source' => 'resources/js/controllers/turbo/polling_controller.js',
            'docs' => 'docs/controllers/turbo/polling.md',
            'category' => 'turbo',
            'description' => 'Reloads a Turbo Frame at configurable intervals with pause and refresh controls',
        ],
        'turbo--preserve-scroll' => [
            'source' => 'resources/js/controllers/turbo/preserve_scroll_controller.js',
            'docs' => 'docs/controllers/turbo/preserve-scroll.md',
            'category' => 'turbo',
            'description' => 'Preserves page scroll across Turbo Frame renders that replace focused content',
        ],
        'turbo--progress' => [
            'source' => 'resources/js/controllers/turbo/progress_controller.js',
            'docs' => 'docs/controllers/turbo/progress.md',
            'category' => 'turbo',
            'description' => 'Shows the Turbo progress bar for Turbo Frame and Turbo Stream requests',
        ],
        'turbo--view-transition' => [
            'source' => 'resources/js/controllers/turbo/view_transition_controller.js',
            'docs' => 'docs/controllers/turbo/view-transition.md',
            'category' => 'turbo',
            'description' => 'Wraps Turbo Frame renders in View Transitions when supported',
        ],
        'unsaved-changes' => [
            'source' => 'resources/js/controllers/unsaved_changes_controller.js',
            'docs' => 'docs/controllers/unsaved-changes.md',
            'category' => 'forms',
            'description' => 'Confirms Turbo Drive navigation away from forms with unsaved changes',
        ],
    ],
];
