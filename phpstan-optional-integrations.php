<?php

use Spatie\ResponseCache\Middlewares\CacheResponse;

// Analyze the adapter against Spatie itself when that optional dependency is installed.
return class_exists(CacheResponse::class) ? [] : [
    'parameters' => [
        'excludePaths' => [
            'analyse' => [__DIR__.'/src/Http/Middleware/CachePublicResponse.php'],
        ],
    ],
];
