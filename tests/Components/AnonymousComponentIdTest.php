<?php

use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
});

it('keeps anonymous field component ids stable across request scopes', function (string $template) {
    app()->forgetScopedInstances();
    $first = (string) $this->blade($template);

    app()->forgetScopedInstances();
    $second = (string) $this->blade($template);

    expect($second)->toBe($first);
})->with([
    'checkbox' => '<x-hw::checkbox />',
    'file' => '<x-hw::file />',
    'file upload' => '<x-hw::file-upload url="/uploads" />',
    'input' => '<x-hw::input />',
    'multi select' => '<x-hw::multi-select />',
    'rich text' => '<x-hw::rich-text />',
    'select' => '<x-hw::select />',
    'slider' => '<x-hw::slider />',
    'switch' => '<x-hw::switch />',
    'textarea' => '<x-hw::textarea />',
    'visible field error' => '<x-hw::field.error :messages="[\'Invalid\']" />',
]);
