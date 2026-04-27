<?php

use Emaia\LaravelHotwire\Components\ConfirmDialog;
use Emaia\LaravelHotwire\Components\FlashContainer;
use Emaia\LaravelHotwire\Components\FlashMessage;
use Emaia\LaravelHotwire\Components\Loader;
use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\Components\Optimistic;
use Emaia\LaravelHotwire\Components\Timeago;

return [
    'components' => [
        'confirm-dialog' => [
            'class' => ConfirmDialog::class,
            'view' => 'hotwire::components.confirm-dialog.confirm-dialog',
            'docs' => 'docs/components/confirm-dialog/readme.md',
            'category' => 'overlay',
            'controllers' => ['confirm-dialog'],
        ],
        'flash-container' => [
            'class' => FlashContainer::class,
            'view' => 'hotwire::components.flash-container.flash-container',
            'docs' => 'docs/components/flash-container/readme.md',
            'category' => 'feedback',
            'controllers' => ['toaster'],
        ],
        'flash-message' => [
            'class' => FlashMessage::class,
            'view' => 'hotwire::components.flash-message.flash-message',
            'docs' => 'docs/components/flash-message/readme.md',
            'category' => 'feedback',
            'controllers' => ['toast'],
        ],
        'loader' => [
            'class' => Loader::class,
            'view' => 'hotwire::components.loader.loader',
            'docs' => 'docs/components/loader/readme.md',
            'category' => 'feedback',
            'controllers' => [],
        ],
        'modal' => [
            'class' => Modal::class,
            'view' => 'hotwire::components.modal.modal',
            'docs' => 'docs/components/modal/readme.md',
            'category' => 'overlay',
            'controllers' => ['modal'],
        ],
        'optimistic' => [
            'class' => Optimistic::class,
            'view' => 'hotwire::components.optimistic.optimistic',
            'docs' => 'docs/components/optimistic/readme.md',
            'category' => 'turbo',
            'controllers' => [],
        ],
        'timeago' => [
            'class' => Timeago::class,
            'view' => 'hotwire::components.timeago.timeago',
            'docs' => 'docs/components/timeago/readme.md',
            'category' => 'utility',
            'controllers' => ['timeago'],
        ],
    ],
    'controllers' => [
        'animated-number' => [
            'source' => 'resources/js/controllers/animated_number_controller.ts',
            'docs' => 'docs/controllers/animated-number.md',
            'category' => 'utility',
        ],
        'auto-save' => [
            'source' => 'resources/js/controllers/auto_save_controller.js',
            'docs' => 'docs/controllers/auto-save.md',
            'category' => 'forms',
        ],
        'auto-submit' => [
            'source' => 'resources/js/controllers/auto_submit_controller.js',
            'docs' => 'docs/controllers/auto-submit.md',
            'category' => 'forms',
        ],
        'autoresize' => [
            'source' => 'resources/js/controllers/autoresize_controller.js',
            'docs' => 'docs/controllers/autoresize.md',
            'category' => 'forms',
        ],
        'autoselect' => [
            'source' => 'resources/js/controllers/autoselect_controller.js',
            'docs' => 'docs/controllers/autoselect.md',
            'category' => 'utility',
        ],
        'char-counter' => [
            'source' => 'resources/js/controllers/char_counter_controller.ts',
            'docs' => 'docs/controllers/char-counter.md',
            'category' => 'forms',
        ],
        'checkbox-select-all' => [
            'source' => 'resources/js/controllers/checkbox_select_all_controller.ts',
            'docs' => 'docs/controllers/checkbox-select-all.md',
            'category' => 'forms',
        ],
        'clean-query-params' => [
            'source' => 'resources/js/controllers/clean_query_params_controller.js',
            'docs' => 'docs/controllers/clean-query-params.md',
            'category' => 'forms',
        ],
        'clear-input' => [
            'source' => 'resources/js/controllers/clear_input_controller.js',
            'docs' => 'docs/controllers/clear-input.md',
            'category' => 'forms',
        ],
        'confirm-dialog' => [
            'source' => 'resources/js/controllers/confirm_dialog_controller.js',
            'docs' => 'docs/controllers/confirm-dialog.md',
            'category' => 'overlay',
        ],
        'copy-to-clipboard' => [
            'source' => 'resources/js/controllers/copy_to_clipboard_controller.ts',
            'docs' => 'docs/controllers/copy-to-clipboard.md',
            'category' => 'utility',
        ],
        'dev--log' => [
            'source' => 'resources/js/controllers/dev/log_controller.js',
            'docs' => 'docs/controllers/dev/log.md',
            'category' => 'dev',
        ],
        'gtm' => [
            'source' => 'resources/js/controllers/gtm_controller.js',
            'docs' => 'docs/controllers/gtm.md',
            'category' => 'utility',
        ],
        'hotkey' => [
            'source' => 'resources/js/controllers/hotkey_controller.ts',
            'docs' => 'docs/controllers/hotkey.md',
            'category' => 'utility',
        ],
        'input-mask' => [
            'source' => 'resources/js/controllers/input_mask_controller.js',
            'docs' => 'docs/controllers/input-mask.md',
            'category' => 'forms',
            'npm' => ['maska' => '^3.2.0'],
        ],
        'lazy-image' => [
            'source' => 'resources/js/controllers/lazy_image_controller.js',
            'docs' => 'docs/controllers/lazy-image.md',
            'category' => 'utility',
        ],
        'modal' => [
            'source' => 'resources/js/controllers/modal_controller.js',
            'docs' => 'docs/controllers/modal.md',
            'category' => 'overlay',
        ],
        'modal-auto-close' => [
            'source' => 'resources/js/controllers/modal_auto_close_controller.js',
            'docs' => 'docs/controllers/modal-auto-close.md',
            'category' => 'overlay',
        ],
        'oembed' => [
            'source' => 'resources/js/controllers/oembed_controller.js',
            'docs' => 'docs/controllers/oembed.md',
            'category' => 'utility',
        ],
        'optimistic--dispatch' => [
            'source' => 'resources/js/controllers/optimistic/dispatch_controller.js',
            'docs' => 'docs/controllers/optimistic/dispatch.md',
            'category' => 'turbo',
        ],
        'optimistic--form' => [
            'source' => 'resources/js/controllers/optimistic/form_controller.js',
            'docs' => 'docs/controllers/optimistic/form.md',
            'category' => 'turbo',
        ],
        'optimistic--link' => [
            'source' => 'resources/js/controllers/optimistic/link_controller.js',
            'docs' => 'docs/controllers/optimistic/link.md',
            'category' => 'turbo',
        ],
        'remote-form' => [
            'source' => 'resources/js/controllers/remote_form_controller.js',
            'docs' => 'docs/controllers/remote-form.md',
            'category' => 'forms',
        ],
        'reset-files' => [
            'source' => 'resources/js/controllers/reset_files_controller.js',
            'docs' => 'docs/controllers/reset-files.md',
            'category' => 'forms',
        ],
        'timeago' => [
            'source' => 'resources/js/controllers/timeago_controller.ts',
            'docs' => 'docs/controllers/timeago.md',
            'category' => 'utility',
            'npm' => ['date-fns' => '^4.1.0'],
        ],
        'toast' => [
            'source' => 'resources/js/controllers/toast_controller.js',
            'docs' => 'docs/controllers/toast.md',
            'category' => 'feedback',
            'npm' => ['@emaia/sonner' => '^2.1.0'],
        ],
        'toaster' => [
            'source' => 'resources/js/controllers/toaster_controller.js',
            'docs' => 'docs/controllers/toaster.md',
            'category' => 'feedback',
            'npm' => ['@emaia/sonner' => '^2.1.0'],
        ],
        'tooltip' => [
            'source' => 'resources/js/controllers/tooltip_controller.js',
            'docs' => 'docs/controllers/tooltip.md',
            'category' => 'utility',
            'npm' => ['tippy.js' => '^6.3.7'],
        ],
        'turbo--polling' => [
            'source' => 'resources/js/controllers/turbo/polling_controller.js',
            'docs' => 'docs/controllers/turbo/polling.md',
            'category' => 'turbo',
        ],
        'turbo--progress' => [
            'source' => 'resources/js/controllers/turbo/progress_controller.js',
            'docs' => 'docs/controllers/turbo/progress.md',
            'category' => 'turbo',
        ],
        'turbo--view-transition' => [
            'source' => 'resources/js/controllers/turbo/view_transition_controller.js',
            'docs' => 'docs/controllers/turbo/view-transition.md',
            'category' => 'turbo',
        ],
        'unsaved-changes' => [
            'source' => 'resources/js/controllers/unsaved_changes_controller.js',
            'docs' => 'docs/controllers/unsaved-changes.md',
            'category' => 'forms',
        ],
    ],
];
