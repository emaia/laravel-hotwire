<?php

use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$testCase = new class('runTest') extends TestCase
{
    /** Isolate compiled views so constructor changes cannot reuse another browser worker's cache. */
    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        config()->set('view.compiled', sys_get_temp_dir().'/hotwire-input-browser-'.bin2hex(random_bytes(8)));
    }

    /** Render the real package components for browser contract tests. */
    public function renderFixture(): string
    {
        $this->setUp();
        view()->share('errors', new ViewErrorBag);
        request()->setLaravelSession($this->app['session.store']);

        try {
            return Blade::render(file_get_contents(__DIR__.'/input.blade.php'));
        } finally {
            File::deleteDirectory(config('view.compiled'));
        }
    }

    public function runTest(): void {}
};

echo $testCase->renderFixture();
