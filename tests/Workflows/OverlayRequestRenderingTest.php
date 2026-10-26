<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('selects compact presentation in an HTTP action using the canonical overlay host', function (array $headers, bool $compact) {
    Route::get('/overlay-editor', function (Request $request) {
        return response()->json([
            'compact' => $request->wasFromOverlay(host: 'customer-editor'),
            'frame' => $request->turboFrameId(),
        ]);
    });

    $this->get('/overlay-editor', $headers)->assertOk()->assertExactJson([
        'compact' => $compact,
        'frame' => $headers['Turbo-Frame'] ?? null,
    ]);
})->with([
    'standalone page' => [[], false],
    'ordinary app Frame' => [['Turbo-Frame' => 'customer-details'], false],
    'ordinary Frame called modal' => [['Turbo-Frame' => 'modal'], false],
    'primary custom host' => [[
        'Turbo-Frame' => 'customer-editor--'.str_repeat('a', 32),
        'X-Hotwire-Overlay-Host' => 'customer-editor',
        'X-Hotwire-Overlay-Instance' => str_repeat('a', 32),
        'X-Hotwire-Overlay-Origin' => 'customer-editor--'.str_repeat('a', 32),
    ], true],
    'internal Frame owned by custom host' => [[
        'Turbo-Frame' => 'customer-details',
        'X-Hotwire-Overlay-Origin' => 'customer-editor--'.str_repeat('a', 32),
    ], true],
    'internal Frame owned by another host' => [[
        'Turbo-Frame' => 'customer-details',
        'X-Hotwire-Overlay-Origin' => 'modal--'.str_repeat('b', 32),
    ], false],
]);
