<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Component Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used for all Blade components provided by this package.
    | For example, with prefix "hw", the modal component is used as:
    |
    |     <hw:modal> ... </hw:modal>
    |
    */

    'prefix' => 'hw',

    /*
    |--------------------------------------------------------------------------
    | Controller Loading
    |--------------------------------------------------------------------------
    |
    | Preloaded controllers stay in separate chunks but begin downloading from
    | the document head. Eager controllers join the application's entry graph.
    | Both lists accept package and conventional application controllers.
    | Run `php artisan hotwire:check --fix` after changing either list to
    | regenerate resources/js/controllers/index.js.
    |
    */

    'controllers' => [
        'preload' => [],
        'eager' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Messages
    |--------------------------------------------------------------------------
    |
    | Override native File Upload copy for every instance. Per-component
    | `messages` props take precedence over these values.
    |
    */

    'file_upload' => [
        'messages' => [],
    ],

];
