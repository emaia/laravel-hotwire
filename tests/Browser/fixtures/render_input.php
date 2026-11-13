<?php

use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$testCase = new class('runTest') extends TestCase
{
    /** Render the real package components for browser contract tests. */
    public function renderFixture(): string
    {
        $this->setUp();
        view()->share('errors', new ViewErrorBag);
        request()->setLaravelSession($this->app['session.store']);

        return Blade::render(file_get_contents(__DIR__.'/input.blade.php'));
    }

    public function runTest(): void {}
};

echo $testCase->renderFixture();
