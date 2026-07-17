<?php

use Emaia\LaravelHotwire\Components\Accordion;
use Emaia\LaravelHotwire\Components\Alert;
use Emaia\LaravelHotwire\Components\AlertDialog;
use Emaia\LaravelHotwire\Components\AspectRatio;
use Emaia\LaravelHotwire\Components\Avatar;
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
use Emaia\LaravelHotwire\Components\ConditionalField;
use Emaia\LaravelHotwire\Components\Drawer;
use Emaia\LaravelHotwire\Components\Dropdown;
use Emaia\LaravelHotwire\Components\EmptyState;
use Emaia\LaravelHotwire\Components\Field;
use Emaia\LaravelHotwire\Components\Field\Error as FieldError;
use Emaia\LaravelHotwire\Components\Field\Group as FieldGroup;
use Emaia\LaravelHotwire\Components\Field\Label as FieldLabel;
use Emaia\LaravelHotwire\Components\File;
use Emaia\LaravelHotwire\Components\FileUpload;
use Emaia\LaravelHotwire\Components\FlashContainer;
use Emaia\LaravelHotwire\Components\FlashMessage;
use Emaia\LaravelHotwire\Components\Form;
use Emaia\LaravelHotwire\Components\Frame;
use Emaia\LaravelHotwire\Components\FrameOrPage;
use Emaia\LaravelHotwire\Components\HoverCard;
use Emaia\LaravelHotwire\Components\Icon;
use Emaia\LaravelHotwire\Components\Input;
use Emaia\LaravelHotwire\Components\Item;
use Emaia\LaravelHotwire\Components\Kbd;
use Emaia\LaravelHotwire\Components\Map;
use Emaia\LaravelHotwire\Components\Marker;
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
use Emaia\LaravelHotwire\Components\RichText;
use Emaia\LaravelHotwire\Components\ScrollProgress;
use Emaia\LaravelHotwire\Components\Select;
use Emaia\LaravelHotwire\Components\Separator;
use Emaia\LaravelHotwire\Components\Sheet;
use Emaia\LaravelHotwire\Components\Sidebar;
use Emaia\LaravelHotwire\Components\Skeleton;
use Emaia\LaravelHotwire\Components\Spinner;
use Emaia\LaravelHotwire\Components\Sticky;
use Emaia\LaravelHotwire\Components\SwitchInput;
use Emaia\LaravelHotwire\Components\Table;
use Emaia\LaravelHotwire\Components\Tabs;
use Emaia\LaravelHotwire\Components\Textarea;
use Emaia\LaravelHotwire\Components\Timeago;
use Emaia\LaravelHotwire\Components\Toggle;
use Emaia\LaravelHotwire\Components\ToggleGroup;
use Emaia\LaravelHotwire\Components\ToggleGroup\Item as ToggleGroupItem;

return [
    'components' => [
        'accordion' => [
            'class' => Accordion::class,
            'view' => 'hotwire::component-views.accordion',
            'docs' => 'docs/components/accordion.md',
            'category' => 'display',
            'description' => 'Native details/summary accordion primitives with single or multiple item coordination',
            'controllers' => ['accordion'],
        ],
        'alert' => [
            'class' => Alert::class,
            'view' => 'hotwire::component-views.alert',
            'docs' => 'docs/components/alert.md',
            'category' => 'feedback',
            'description' => 'Inline alert with title, description, action and semantic variants',
            'controllers' => [],
        ],
        'alert-dialog' => [
            'class' => AlertDialog::class,
            'view' => 'hotwire::component-views.alert-dialog',
            'docs' => 'docs/components/alert-dialog.md',
            'category' => 'overlay',
            'description' => 'Accessible alert dialog that intercepts clicks before proceeding',
            'controllers' => ['alert-dialog'],
        ],
        'aspect-ratio' => [
            'class' => AspectRatio::class,
            'view' => 'hotwire::component-views.aspect-ratio',
            'docs' => 'docs/components/aspect-ratio.md',
            'category' => 'display',
            'description' => 'Static media wrapper that preserves a configurable aspect ratio',
            'controllers' => [],
        ],
        'avatar' => [
            'class' => Avatar::class,
            'view' => 'hotwire::component-views.avatar',
            'docs' => 'docs/components/avatar.md',
            'category' => 'display',
            'description' => 'User avatar with image, generated initials fallback, badge and grouped display primitives',
            'controllers' => [],
        ],
        'badge' => [
            'class' => Badge::class,
            'view' => 'hotwire::component-views.badge',
            'docs' => 'docs/components/badge.md',
            'category' => 'display',
            'description' => 'Compact status label with semantic variants and optional link rendering',
            'controllers' => [],
        ],
        'breadcrumb' => [
            'class' => Breadcrumb::class,
            'view' => 'hotwire::component-views.breadcrumb',
            'docs' => 'docs/components/breadcrumb.md',
            'category' => 'display',
            'description' => 'Semantic navigation trail with composed subcomponents and an items shortcut',
            'controllers' => [],
        ],
        'button' => [
            'class' => Button::class,
            'view' => 'hotwire::component-views.button',
            'docs' => 'docs/components/button.md',
            'category' => 'utility',
            'description' => 'Displays a button or a component that looks like a button.',
            'controllers' => ['hotkey', 'tooltip'],
        ],
        'button-group' => [
            'class' => ButtonGroup::class,
            'view' => 'hotwire::component-views.button-group',
            'docs' => 'docs/components/button-group.md',
            'category' => 'display',
            'description' => 'Groups related buttons and button-like controls with shared borders and orientation state',
            'controllers' => [],
        ],
        'card' => [
            'class' => Card::class,
            'view' => 'hotwire::component-views.card',
            'docs' => 'docs/components/card.md',
            'category' => 'display',
            'description' => 'Composable content container with header, action, content and footer slots',
            'controllers' => [],
        ],
        'carousel' => [
            'class' => Carousel::class,
            'view' => 'hotwire::component-views.carousel',
            'docs' => 'docs/components/carousel.md',
            'category' => 'utility',
            'description' => 'Carousel/slider (Embla) with navigation, dots, responsive options and CSS-variable sizing',
            'controllers' => ['carousel'],
        ],
        'chart' => [
            'class' => Chart::class,
            'view' => 'hotwire::component-views.chart',
            'docs' => 'docs/components/chart.md',
            'category' => 'utility',
            'description' => 'Apache ECharts wrapper — inline option or URL-fetched, theme + sizing props, controller swap for subclass extensibility',
            'controllers' => ['chart'],
        ],
        'checkbox' => [
            'class' => Checkbox::class,
            'view' => 'hotwire::component-views.checkbox',
            'docs' => 'docs/components/checkbox.md',
            'category' => 'forms',
            'description' => 'Standalone native checkbox with old input restore, unchecked hidden value and optional indeterminate state',
            'controllers' => ['checkbox', 'auto-submit'],
        ],
        'checkbox-group' => [
            'class' => CheckboxGroup::class,
            'view' => 'hotwire::component-views.checkbox-group',
            'docs' => 'docs/components/checkbox-group.md',
            'category' => 'forms',
            'description' => 'Checkbox group with options, rich item composition and optional select-all master checkbox',
            'controllers' => ['checkbox-select-all', 'auto-submit'],
        ],
        'checkbox-group.item' => [
            'class' => CheckboxGroupItem::class,
            'view' => 'hotwire::component-views.checkbox-group-item',
            'docs' => 'docs/components/checkbox-group.md',
            'category' => 'forms',
            'description' => 'Rich checkbox-group item that inherits name, selected state, validation and select-all wiring',
            'controllers' => ['checkbox-select-all', 'auto-submit'],
        ],
        'conditional-field' => [
            'class' => ConditionalField::class,
            'view' => 'hotwire::component-views.conditional-field',
            'docs' => 'docs/components/conditional-field.md',
            'category' => 'forms',
            'description' => 'Renders a dependent block for the conditional-fields controller — single source of truth for the show/hide rule on both client and server',
            'controllers' => ['conditional-fields'],
        ],
        'drawer' => [
            'class' => Drawer::class,
            'view' => 'hotwire::component-views.drawer',
            'docs' => 'docs/components/drawer.md',
            'category' => 'overlay',
            'description' => 'Off-canvas drawer with backdrop, focus trap and Escape/click-outside dismissal',
            'controllers' => ['drawer'],
        ],
        'dropdown' => [
            'class' => Dropdown::class,
            'view' => 'hotwire::component-views.dropdown',
            'docs' => 'docs/components/dropdown.md',
            'category' => 'overlay',
            'description' => 'Accessible disclosure dropdown — a trigger toggles a menu, with outside-click/Escape dismissal',
            'controllers' => ['dropdown'],
        ],
        'empty-state' => [
            'class' => EmptyState::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/empty-state.md',
            'category' => 'display',
            'description' => 'Composable empty state with media, title, description and action content slots',
            'controllers' => [],
        ],
        'field' => [
            'class' => Field::class,
            'view' => 'hotwire::component-views.field',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Wraps label, input, description and error — propagates name/errorKey/required via @aware',
            'controllers' => [],
        ],
        'field.error' => [
            'class' => FieldError::class,
            'view' => 'hotwire::component-views.field-error',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Always-present error container bound to a form field via name/errorKey',
            'controllers' => [],
        ],
        'field.group' => [
            'class' => FieldGroup::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Groups form fields and enables responsive field orientation layout',
            'controllers' => [],
        ],
        'field.label' => [
            'class' => FieldLabel::class,
            'view' => 'hotwire::component-views.field-label',
            'docs' => 'docs/components/field.md',
            'category' => 'forms',
            'description' => 'Form label with auto-derived for/id and optional required marker',
            'controllers' => [],
        ],
        'file' => [
            'class' => File::class,
            'view' => 'hotwire::component-views.file',
            'docs' => 'docs/components/file.md',
            'category' => 'forms',
            'description' => 'File input with auto id/errorKey, ARIA, optional current file display and Turbo morph reset',
            'controllers' => ['file-preserve', 'reset-files'],
        ],
        'file-upload' => [
            'class' => FileUpload::class,
            'view' => 'hotwire::component-views.file-upload',
            'docs' => 'docs/components/file-upload.md',
            'category' => 'forms',
            'description' => 'Dropzone wrapper — drag-drop, queue, progress, server-side endpoint, optional hidden input and DELETE',
            'controllers' => ['file-upload'],
        ],
        'flash-container' => [
            'class' => FlashContainer::class,
            'view' => 'hotwire::component-views.flash-container',
            'docs' => 'docs/components/flash-container.md',
            'category' => 'feedback',
            'description' => 'Hosts the Sonner toaster instance and persists it across Turbo Drive navigations',
            'controllers' => ['toaster'],
        ],
        'flash-message' => [
            'class' => FlashMessage::class,
            'view' => 'hotwire::component-views.flash-message',
            'docs' => 'docs/components/flash-message.md',
            'category' => 'feedback',
            'description' => 'Fires a toast notification from the Laravel session or from explicit props',
            'controllers' => ['toast'],
        ],
        'form' => [
            'class' => Form::class,
            'view' => 'hotwire::component-views.form',
            'docs' => 'docs/components/form.md',
            'category' => 'forms',
            'description' => 'Form wrapper with optional Stimulus behaviors, CSRF, and Turbo Frame redirect support',
            'controllers' => ['auto-submit', 'unsaved-changes', 'error-scroll', 'clean-query-params', 'conditional-fields'],
        ],
        'frame' => [
            'class' => Frame::class,
            'view' => 'hotwire::component-views.frame',
            'docs' => 'docs/components/frame.md',
            'category' => 'turbo',
            'description' => 'DX-friendly Turbo Frame wrapper with lazy, advance and replace aliases',
            'controllers' => ['turbo--polling', 'turbo--view-transition'],
        ],
        'frame-or-page' => [
            'class' => FrameOrPage::class,
            'view' => 'hotwire::component-views.frame-or-page',
            'docs' => 'docs/components/frame-or-page.md',
            'category' => 'turbo',
            'description' => 'Renders a view as a Turbo Frame payload or wrapped in a layout, based on the Turbo-Frame request header',
            'controllers' => [],
        ],
        'hover-card' => [
            'class' => HoverCard::class,
            'view' => 'hotwire::component-views.hover-card',
            'docs' => 'docs/components/hover-card.md',
            'category' => 'overlay',
            'description' => 'Anchored hover/focus preview card with delayed Floating UI positioning',
            'controllers' => ['hover-card'],
        ],
        'icon' => [
            'class' => Icon::class,
            'view' => 'hotwire::component-views.icon',
            'docs' => 'docs/components/icon.md',
            'category' => 'utility',
            'description' => 'Inline SVG icon from the embedded Lucide subset (~21 icons)',
            'controllers' => [],
        ],
        'input' => [
            'class' => Input::class,
            'view' => 'hotwire::component-views.input',
            'docs' => 'docs/components/input.md',
            'category' => 'forms',
            'description' => 'Form input with auto id/errorKey, ARIA, optional mask/clear/auto-select',
            'controllers' => ['auto-select', 'clear-input', 'input-mask', 'auto-submit'],
        ],
        'item' => [
            'class' => Item::class,
            'view' => 'hotwire::component-views.item',
            'docs' => 'docs/components/item.md',
            'category' => 'display',
            'description' => 'Composable list item primitive with media, content, actions, header, footer and separator slots',
            'controllers' => [],
        ],
        'kbd' => [
            'class' => Kbd::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/kbd.md',
            'category' => 'display',
            'description' => 'Keyboard input hint with optional grouped shortcut rendering',
            'controllers' => [],
        ],
        'map' => [
            'class' => Map::class,
            'view' => 'hotwire::component-views.map',
            'docs' => 'docs/components/map.md',
            'category' => 'utility',
            'description' => 'Leaflet wrapper — inline center/markers or GeoJSON URL, OSM tiles by default, subclass-friendly tile/handler hooks',
            'controllers' => ['map'],
        ],
        'marker' => [
            'class' => Marker::class,
            'view' => 'hotwire::component-views.marker',
            'docs' => 'docs/components/marker.md',
            'category' => 'display',
            'description' => 'Lightweight visual primitive for timelines, activity feeds and lists',
            'controllers' => [],
        ],
        'modal' => [
            'class' => Modal::class,
            'view' => 'hotwire::component-views.modal',
            'docs' => 'docs/components/modal.md',
            'category' => 'overlay',
            'description' => 'Accessible modal with backdrop, animations, focus trap and Turbo integration',
            'controllers' => ['modal'],
        ],
        'multi-select' => [
            'class' => MultiSelect::class,
            'view' => 'hotwire::component-views.multi-select',
            'docs' => 'docs/components/multi-select.md',
            'category' => 'forms',
            'description' => 'Searchable multi-value select with Floating UI positioning and native form submission',
            'controllers' => ['multi-select', 'clear-input'],
        ],
        'navbar' => [
            'class' => Navbar::class,
            'view' => 'hotwire::component-views.navbar',
            'docs' => 'docs/components/navbar.md',
            'category' => 'navigation',
            'description' => 'Horizontal or vertical navigation bar for real links with current-page state and optional sticky sugar',
            'controllers' => [],
        ],
        'navbar.item' => [
            'class' => NavbarItem::class,
            'view' => 'hotwire::component-views.navbar-item',
            'docs' => 'docs/components/navbar.md',
            'category' => 'navigation',
            'description' => 'Navbar item that renders as a link or button with current and disabled semantics',
            'controllers' => [],
        ],
        'optimistic' => [
            'class' => Optimistic::class,
            'view' => 'hotwire::component-views.optimistic',
            'docs' => 'docs/components/optimistic.md',
            'category' => 'turbo',
            'description' => 'Declares an inline optimistic Turbo Stream action for any Turbo trigger',
            'controllers' => [],
        ],
        'pagination' => [
            'class' => Pagination::class,
            'view' => 'hotwire::component-views.pagination',
            'docs' => 'docs/components/pagination.md',
            'category' => 'display',
            'description' => 'Pagination navigation primitives with Laravel paginator display modes and Turbo Frame support',
            'controllers' => [],
        ],
        'popover' => [
            'class' => Popover::class,
            'view' => 'hotwire::component-views.popover',
            'docs' => 'docs/components/popover.md',
            'category' => 'overlay',
            'description' => 'Anchored click-triggered popover for rich arbitrary content using Floating UI positioning',
            'controllers' => ['popover'],
        ],
        'progress' => [
            'class' => Progress::class,
            'view' => 'hotwire::component-views.progress',
            'docs' => 'docs/components/progress.md',
            'category' => 'display',
            'description' => 'Server-rendered progress primitive with label, value, track and indicator slots',
            'controllers' => [],
        ],
        'radio-group' => [
            'class' => RadioGroup::class,
            'view' => 'hotwire::component-views.radio-group',
            'docs' => 'docs/components/radio-group.md',
            'category' => 'forms',
            'description' => 'Native radio group with options, rich item composition, old input restore and validation wiring',
            'controllers' => ['auto-submit'],
        ],
        'radio-group.item' => [
            'class' => RadioGroupItem::class,
            'view' => 'hotwire::component-views.radio-group-item',
            'docs' => 'docs/components/radio-group.md',
            'category' => 'forms',
            'description' => 'Rich radio-group item that inherits name, selected state and validation wiring',
            'controllers' => ['auto-submit'],
        ],
        'rich-text' => [
            'class' => RichText::class,
            'view' => 'hotwire::component-views.rich-text',
            'docs' => 'docs/components/rich-text.md',
            'category' => 'forms',
            'description' => 'Tiptap-backed rich text editor with optional default toolbar, output as HTML or JSON, and image-upload event hook',
            'controllers' => ['rich-text', 'rich-text-toolbar'],
        ],
        'scroll-progress' => [
            'class' => ScrollProgress::class,
            'view' => 'hotwire::component-views.scroll-progress',
            'docs' => 'docs/components/scroll-progress.md',
            'category' => 'utility',
            'description' => 'Fixed scroll progress bar that fills as the page scrolls',
            'controllers' => ['scroll-progress'],
        ],
        'select' => [
            'class' => Select::class,
            'view' => 'hotwire::component-views.select',
            'docs' => 'docs/components/select.md',
            'category' => 'forms',
            'description' => 'Select dropdown with auto id/errorKey, ARIA, old() merge and placeholder support',
            'controllers' => ['auto-submit'],
        ],
        'separator' => [
            'class' => Separator::class,
            'view' => 'hotwire::component-views.separator',
            'docs' => 'docs/components/separator.md',
            'category' => 'display',
            'description' => 'Horizontal or vertical visual separator with semantic orientation hooks',
            'controllers' => [],
        ],
        'sheet' => [
            'class' => Sheet::class,
            'view' => 'hotwire::component-views.sheet',
            'docs' => 'docs/components/sheet.md',
            'category' => 'overlay',
            'description' => 'Off-canvas sheet with backdrop, close button, focus trap and side-aware slide transitions',
            'controllers' => ['sheet'],
        ],
        'sidebar' => [
            'class' => Sidebar::class,
            'view' => 'hotwire::component-views.sidebar',
            'docs' => 'docs/components/sidebar.md',
            'category' => 'utility',
            'description' => 'Composable app sidebar with provider state, trigger, rail, menu and content primitives',
            'controllers' => ['sidebar'],
        ],
        'skeleton' => [
            'class' => Skeleton::class,
            'view' => 'hotwire::component-views.slot',
            'docs' => 'docs/components/skeleton.md',
            'category' => 'feedback',
            'description' => 'Animated placeholder block for loading states',
            'controllers' => [],
        ],
        'spinner' => [
            'class' => Spinner::class,
            'view' => 'hotwire::component-views.spinner',
            'docs' => 'docs/components/spinner.md',
            'category' => 'feedback',
            'description' => 'Animated SVG spinner — no JavaScript required',
            'controllers' => [],
        ],
        'sticky' => [
            'class' => Sticky::class,
            'view' => 'hotwire::component-views.sticky',
            'docs' => 'docs/components/sticky.md',
            'category' => 'layout',
            'description' => 'Generic top or bottom sticky surface primitive with configurable offset and tag',
            'controllers' => [],
        ],
        'switch' => [
            'class' => SwitchInput::class,
            'view' => 'hotwire::component-views.switch',
            'docs' => 'docs/components/switch.md',
            'category' => 'forms',
            'description' => 'Native checkbox rendered as an accessible switch with old input restore and unchecked hidden value',
            'controllers' => ['auto-submit'],
        ],
        'table' => [
            'class' => Table::class,
            'view' => 'hotwire::component-views.table',
            'docs' => 'docs/components/table.md',
            'category' => 'display',
            'description' => 'Responsive table wrapper with semantic row, cell, header, footer and caption primitives',
            'controllers' => [],
        ],
        'tabs' => [
            'class' => Tabs::class,
            'view' => 'hotwire::component-views.tabs',
            'docs' => 'docs/components/tabs.md',
            'category' => 'display',
            'description' => 'Accessible tab primitives backed by the tabs controller, with server-rendered active state',
            'controllers' => ['tabs'],
        ],
        'textarea' => [
            'class' => Textarea::class,
            'view' => 'hotwire::component-views.textarea',
            'docs' => 'docs/components/textarea.md',
            'category' => 'forms',
            'description' => 'Textarea with auto-resize and optional char counter',
            'controllers' => ['auto-resize', 'char-counter', 'auto-submit'],
        ],
        'timeago' => [
            'class' => Timeago::class,
            'view' => 'hotwire::component-views.timeago',
            'docs' => 'docs/components/timeago.md',
            'category' => 'utility',
            'description' => 'Self-refreshing relative timestamp element wrapping the timeago controller',
            'controllers' => ['timeago'],
        ],
        'toggle' => [
            'class' => Toggle::class,
            'view' => 'hotwire::component-views.toggle',
            'docs' => 'docs/components/toggle.md',
            'category' => 'forms',
            'description' => 'Accessible two-state button with optional hidden input and auto-submit integration',
            'controllers' => ['toggle', 'auto-submit'],
        ],
        'toggle-group' => [
            'class' => ToggleGroup::class,
            'view' => 'hotwire::component-views.toggle-group',
            'docs' => 'docs/components/toggle-group.md',
            'category' => 'forms',
            'description' => 'Single or multiple pressed-button group with hidden-input form submission',
            'controllers' => ['toggle-group', 'toggle', 'auto-submit'],
        ],
        'toggle-group.item' => [
            'class' => ToggleGroupItem::class,
            'view' => 'hotwire::component-views.toggle-group-item',
            'docs' => 'docs/components/toggle-group.md',
            'category' => 'forms',
            'description' => 'Button item for toggle groups with aria-pressed and hidden-input synchronization',
            'controllers' => ['toggle-group', 'toggle', 'auto-submit'],
        ],
    ],
    'controllers' => [
        'accordion' => [
            'source' => 'resources/js/controllers/accordion_controller.js',
            'docs' => 'docs/controllers/accordion.md',
            'category' => 'utility',
            'description' => 'Coordinates native details/summary accordion items for single, multiple and disabled behavior',
        ],
        'alert-dialog' => [
            'source' => 'resources/js/controllers/alert_dialog_controller.js',
            'docs' => 'docs/controllers/alert-dialog.md',
            'category' => 'overlay',
            'description' => 'Intercepts clicks and requires user confirmation before proceeding',
        ],
        'animated-number' => [
            'source' => 'resources/js/controllers/animated_number_controller.js',
            'docs' => 'docs/controllers/animated-number.md',
            'category' => 'utility',
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
            'category' => 'utility',
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
            'category' => 'utility',
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
        'dev--log' => [
            'source' => 'resources/js/controllers/dev/log_controller.js',
            'docs' => 'docs/controllers/dev/log.md',
            'category' => 'dev',
            'description' => 'Logs Stimulus events to the browser console for debugging',
        ],
        'disclosure' => [
            'source' => 'resources/js/controllers/disclosure_controller.js',
            'docs' => 'docs/controllers/disclosure.md',
            'category' => 'utility',
            'description' => 'Show/hide collapsible content with aria-expanded sync — base primitive for FAQ items, read-more sections and accordions',
        ],
        'drawer' => [
            'source' => 'resources/js/controllers/drawer_controller.js',
            'docs' => 'docs/controllers/drawer.md',
            'category' => 'overlay',
            'description' => 'Off-canvas drawer with backdrop, focus trap and Escape/click-outside dismissal',
        ],
        'dropdown' => [
            'source' => 'resources/js/controllers/dropdown_controller.js',
            'docs' => 'docs/controllers/dropdown.md',
            'category' => 'overlay',
            'description' => 'Accessible disclosure dropdown with outside-click/Escape dismissal and optional transitions',
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
            'description' => 'Dropzone-backed multi file upload — drag-drop, queue, progress, emits success/error/progress events, optional hidden input and DELETE',
            'npm' => ['@deltablot/dropzone' => '^7.4.0'],
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
            'description' => 'Delayed hover/focus preview card with Escape dismissal, cleanup and Floating UI positioning',
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
            'category' => 'utility',
            'description' => 'Polls until an image URL becomes available, then displays it',
        ],
        'map' => [
            'source' => 'resources/js/controllers/map_controller.js',
            'docs' => 'docs/controllers/map.md',
            'category' => 'utility',
            'description' => 'Leaflet wrapper — center/zoom/markers values, GeoJSON URL fetch, ResizeObserver, subclass hooks for tile layer and event listeners',
            'npm' => ['leaflet' => '^1.9.4'],
        ],
        'modal' => [
            'source' => 'resources/js/controllers/modal_controller.js',
            'docs' => 'docs/controllers/modal.md',
            'category' => 'overlay',
            'description' => 'Accessible modal with backdrop, focus trap and Turbo integration',
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
            'description' => 'Searchable multi-value select with select-all, max selection and Floating UI positioning',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'oembed' => [
            'source' => 'resources/js/controllers/oembed_controller.js',
            'docs' => 'docs/controllers/oembed.md',
            'category' => 'utility',
            'description' => 'Transforms oembed tags into responsive iframes for YouTube, Vimeo and others',
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
            'description' => 'Anchored click-triggered popover with outside-click/Escape dismissal, focus return and Floating UI positioning',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
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
            'description' => 'Off-canvas sheet with backdrop, close button, focus trap and side-aware slide transitions',
        ],
        'sidebar' => [
            'source' => 'resources/js/controllers/sidebar_controller.js',
            'docs' => 'docs/controllers/sidebar.md',
            'category' => 'utility',
            'description' => 'Controls sidebar expanded/collapsed state, trigger clicks and the Cmd/Ctrl+B shortcut',
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
            'category' => 'utility',
            'description' => 'Accessible tabs with roving tabindex, arrow/Home/End keyboard navigation and automatic activation',
        ],
        'timeago' => [
            'source' => 'resources/js/controllers/timeago_controller.js',
            'docs' => 'docs/controllers/timeago.md',
            'category' => 'utility',
            'description' => 'Displays a self-refreshing relative timestamp (e.g. "3 minutes ago")',
            'npm' => ['date-fns' => '^4.1.0'],
        ],
        'toast' => [
            'source' => 'resources/js/controllers/toast_controller.js',
            'docs' => 'docs/controllers/toast.md',
            'category' => 'feedback',
            'description' => 'Fires a single Sonner toast from session flash or explicit props',
            'npm' => ['@emaia/sonner' => '^2.1.0'],
        ],
        'toaster' => [
            'source' => 'resources/js/controllers/toaster_controller.js',
            'docs' => 'docs/controllers/toaster.md',
            'category' => 'feedback',
            'description' => 'Initializes the Sonner toaster and persists it across Turbo Drive navigations',
            'npm' => ['@emaia/sonner' => '^2.1.0'],
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
            'category' => 'utility',
            'description' => 'Adds accessible hover/focus tooltips positioned with Floating UI',
            'npm' => ['@floating-ui/dom' => '^1.8.0'],
        ],
        'turbo--frame-src' => [
            'source' => 'resources/js/controllers/turbo/frame_src_controller.js',
            'docs' => 'docs/controllers/turbo/frame-src.md',
            'category' => 'turbo',
            'description' => 'Injects the X-Turbo-Frame-Src header on Turbo Frame requests for correct redirect resolution',
        ],
        'turbo--polling' => [
            'source' => 'resources/js/controllers/turbo/polling_controller.js',
            'docs' => 'docs/controllers/turbo/polling.md',
            'category' => 'turbo',
            'description' => 'Reloads a Turbo Frame at regular intervals without user interaction',
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
