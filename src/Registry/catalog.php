<?php

use Emaia\LaravelHotwire\Components\Carousel;
use Emaia\LaravelHotwire\Components\CheckboxGroup;
use Emaia\LaravelHotwire\Components\ConfirmDialog;
use Emaia\LaravelHotwire\Components\Description;
use Emaia\LaravelHotwire\Components\Dropdown;
use Emaia\LaravelHotwire\Components\Error;
use Emaia\LaravelHotwire\Components\Field;
use Emaia\LaravelHotwire\Components\File;
use Emaia\LaravelHotwire\Components\FlashContainer;
use Emaia\LaravelHotwire\Components\FlashMessage;
use Emaia\LaravelHotwire\Components\Form;
use Emaia\LaravelHotwire\Components\Input;
use Emaia\LaravelHotwire\Components\Label;
use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\Components\Optimistic;
use Emaia\LaravelHotwire\Components\ScrollProgress;
use Emaia\LaravelHotwire\Components\Select;
use Emaia\LaravelHotwire\Components\Spinner;
use Emaia\LaravelHotwire\Components\Textarea;
use Emaia\LaravelHotwire\Components\Timeago;

return [
    'components' => [
        'carousel' => [
            'class' => Carousel::class,
            'view' => 'hotwire::component-views.carousel',
            'docs' => 'docs/components/carousel.md',
            'category' => 'utility',
            'description' => 'Carousel/slider (Embla) with navigation, dots, responsive options and CSS-variable sizing',
            'controllers' => ['carousel'],
        ],
        'confirm-dialog' => [
            'class' => ConfirmDialog::class,
            'view' => 'hotwire::component-views.confirm-dialog',
            'docs' => 'docs/components/confirm-dialog.md',
            'category' => 'overlay',
            'description' => 'Accessible confirmation dialog that intercepts clicks before proceeding',
            'controllers' => ['confirm-dialog'],
        ],
        'form' => [
            'class' => Form::class,
            'view' => 'hotwire::component-views.form',
            'docs' => 'docs/components/form.md',
            'category' => 'forms',
            'description' => 'Form wrapper with optional Stimulus behaviors, CSRF, and Turbo Frame redirect support',
            'controllers' => ['auto-submit', 'unsaved-changes', 'error-scroll', 'clean-query-params'],
        ],
        'checkbox-group' => [
            'class' => CheckboxGroup::class,
            'view' => 'hotwire::component-views.checkbox-group',
            'docs' => 'docs/components/checkbox-group.md',
            'category' => 'forms',
            'description' => 'Checkbox group with optional select-all master checkbox',
            'controllers' => ['checkbox-select-all'],
        ],
        'description' => [
            'class' => Description::class,
            'view' => 'hotwire::component-views.description',
            'docs' => 'docs/components/description.md',
            'category' => 'forms',
            'description' => 'Helper text for a form field — paragraph with the hwc-description hook',
            'controllers' => [],
        ],
        'error' => [
            'class' => Error::class,
            'view' => 'hotwire::component-views.error',
            'docs' => 'docs/components/error.md',
            'category' => 'forms',
            'description' => 'Always-present error container bound to a form field via name/errorKey',
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
        'file' => [
            'class' => File::class,
            'view' => 'hotwire::component-views.file',
            'docs' => 'docs/components/file.md',
            'category' => 'forms',
            'description' => 'File input with auto id/errorKey, ARIA, optional current file display and Turbo morph reset',
            'controllers' => ['file-preserve', 'reset-files'],
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
        'input' => [
            'class' => Input::class,
            'view' => 'hotwire::component-views.input',
            'docs' => 'docs/components/input.md',
            'category' => 'forms',
            'description' => 'Form input with auto id/errorKey, ARIA, optional mask/clear/auto-select',
            'controllers' => ['auto-select', 'clear-input', 'input-mask'],
        ],
        'label' => [
            'class' => Label::class,
            'view' => 'hotwire::component-views.label',
            'docs' => 'docs/components/label.md',
            'category' => 'forms',
            'description' => 'Form label with auto-derived for/id and optional required marker',
            'controllers' => [],
        ],
        'select' => [
            'class' => Select::class,
            'view' => 'hotwire::component-views.select',
            'docs' => 'docs/components/select.md',
            'category' => 'forms',
            'description' => 'Select dropdown with auto id/errorKey, ARIA, old() merge and placeholder support',
            'controllers' => [],
        ],
        'textarea' => [
            'class' => Textarea::class,
            'view' => 'hotwire::component-views.textarea',
            'docs' => 'docs/components/textarea.md',
            'category' => 'forms',
            'description' => 'Textarea with auto-resize and optional char counter',
            'controllers' => ['auto-resize', 'char-counter'],
        ],
        'spinner' => [
            'class' => Spinner::class,
            'view' => 'hotwire::component-views.spinner',
            'docs' => 'docs/components/spinner.md',
            'category' => 'feedback',
            'description' => 'Animated SVG spinner — no JavaScript required',
            'controllers' => [],
        ],
        'dropdown' => [
            'class' => Dropdown::class,
            'view' => 'hotwire::component-views.dropdown',
            'docs' => 'docs/components/dropdown.md',
            'category' => 'overlay',
            'description' => 'Accessible disclosure dropdown — a trigger toggles a menu, with outside-click/Escape dismissal',
            'controllers' => ['dropdown'],
        ],
        'modal' => [
            'class' => Modal::class,
            'view' => 'hotwire::component-views.modal',
            'docs' => 'docs/components/modal.md',
            'category' => 'overlay',
            'description' => 'Accessible modal with backdrop, animations, focus trap and Turbo integration',
            'controllers' => ['modal'],
        ],
        'optimistic' => [
            'class' => Optimistic::class,
            'view' => 'hotwire::component-views.optimistic',
            'docs' => 'docs/components/optimistic.md',
            'category' => 'turbo',
            'description' => 'Declares an inline optimistic Turbo Stream action for any Turbo trigger',
            'controllers' => [],
        ],
        'scroll-progress' => [
            'class' => ScrollProgress::class,
            'view' => 'hotwire::component-views.scroll-progress',
            'docs' => 'docs/components/scroll-progress.md',
            'category' => 'utility',
            'description' => 'Fixed scroll progress bar that fills as the page scrolls',
            'controllers' => ['scroll-progress'],
        ],
        'timeago' => [
            'class' => Timeago::class,
            'view' => 'hotwire::component-views.timeago',
            'docs' => 'docs/components/timeago.md',
            'category' => 'utility',
            'description' => 'Self-refreshing relative timestamp element wrapping the timeago controller',
            'controllers' => ['timeago'],
        ],
    ],
    'controllers' => [
        'animated-number' => [
            'source' => 'resources/js/controllers/animated_number_controller.ts',
            'docs' => 'docs/controllers/animated-number.md',
            'category' => 'utility',
            'description' => 'Animates a number from start to end value, with scroll-triggered lazy mode',
        ],
        'auto-save' => [
            'source' => 'resources/js/controllers/auto_save_controller.js',
            'docs' => 'docs/controllers/auto-save.md',
            'category' => 'forms',
            'description' => 'Automatically saves a form after changes, with debounce and status feedback',
        ],
        'auto-submit' => [
            'source' => 'resources/js/controllers/auto_submit_controller.js',
            'docs' => 'docs/controllers/auto-submit.md',
            'category' => 'forms',
            'description' => 'Submits a form automatically on input or change events, with debounce support',
        ],
        'auto-resize' => [
            'source' => 'resources/js/controllers/auto_resize_controller.js',
            'docs' => 'docs/controllers/auto-resize.md',
            'category' => 'forms',
            'description' => 'Expands a textarea to fit its content as the user types',
        ],
        'auto-select' => [
            'source' => 'resources/js/controllers/auto_select_controller.js',
            'docs' => 'docs/controllers/auto-select.md',
            'category' => 'forms',
            'description' => 'Selects all text in an input when it receives focus',
        ],
        'carousel' => [
            'source' => 'resources/js/controllers/carousel_controller.js',
            'docs' => 'docs/controllers/carousel.md',
            'category' => 'utility',
            'description' => 'Carousel/slider — wraps Embla Carousel with navigation, dots and Turbo-friendly lifecycle',
            'npm' => ['embla-carousel' => '^8.6.0'],
        ],
        'char-counter' => [
            'source' => 'resources/js/controllers/char_counter_controller.ts',
            'docs' => 'docs/controllers/char-counter.md',
            'category' => 'forms',
            'description' => 'Shows a live character count with count-up or countdown mode',
        ],
        'checkbox-select-all' => [
            'source' => 'resources/js/controllers/checkbox_select_all_controller.ts',
            'docs' => 'docs/controllers/checkbox-select-all.md',
            'category' => 'forms',
            'description' => 'Select-all checkbox that controls a group, with indeterminate state',
        ],
        'file-preserve' => [
            'source' => 'resources/js/controllers/file_preserve_controller.js',
            'docs' => 'docs/controllers/file-preserve.md',
            'category' => 'forms',
            'description' => 'Captures and restores file input selection across Turbo morphs and frame navigations',
        ],
        'clean-query-params' => [
            'source' => 'resources/js/controllers/clean_query_params_controller.js',
            'docs' => 'docs/controllers/clean-query-params.md',
            'category' => 'forms',
            'description' => 'Strips empty fields from the query string before submitting a GET form',
        ],
        'error-scroll' => [
            'source' => 'resources/js/controllers/error_scroll_controller.js',
            'docs' => 'docs/controllers/error-scroll.md',
            'category' => 'forms',
            'description' => 'Scrolls to the first validation error inside a container after frame render or full-page render',
        ],
        'clear-input' => [
            'source' => 'resources/js/controllers/clear_input_controller.js',
            'docs' => 'docs/controllers/clear-input.md',
            'category' => 'forms',
            'description' => 'Adds a clear button that appears when the input has a value',
        ],
        'confirm-dialog' => [
            'source' => 'resources/js/controllers/confirm_dialog_controller.js',
            'docs' => 'docs/controllers/confirm-dialog.md',
            'category' => 'overlay',
            'description' => 'Intercepts clicks and requires user confirmation before proceeding',
        ],
        'dropdown' => [
            'source' => 'resources/js/controllers/dropdown_controller.js',
            'docs' => 'docs/controllers/dropdown.md',
            'category' => 'overlay',
            'description' => 'Accessible disclosure dropdown with outside-click/Escape dismissal and optional transitions',
        ],
        'copy-to-clipboard' => [
            'source' => 'resources/js/controllers/copy_to_clipboard_controller.ts',
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
        'gtm' => [
            'source' => 'resources/js/controllers/gtm_controller.js',
            'docs' => 'docs/controllers/gtm.md',
            'category' => 'utility',
            'description' => 'Loads Google Tag Manager lazily and fires custom events via data-action',
        ],
        'hotkey' => [
            'source' => 'resources/js/controllers/hotkey_controller.ts',
            'docs' => 'docs/controllers/hotkey.md',
            'category' => 'utility',
            'description' => 'Binds keyboard shortcuts to click or focus an element',
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
        'scroll-progress' => [
            'source' => 'resources/js/controllers/scroll_progress_controller.js',
            'docs' => 'docs/controllers/scroll-progress.md',
            'category' => 'utility',
            'description' => 'Displays a progress bar that follows the scroll position',
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
            'source' => 'resources/js/controllers/timeago_controller.ts',
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
        'tooltip' => [
            'source' => 'resources/js/controllers/tooltip_controller.js',
            'docs' => 'docs/controllers/tooltip.md',
            'category' => 'utility',
            'description' => 'Adds Tippy.js tooltips to any element via data attributes',
            'npm' => ['tippy.js' => '^6.3.7'],
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
