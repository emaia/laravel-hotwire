<?php

declare(strict_types=1);

use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

require dirname(__DIR__).'/vendor/autoload.php';

$testCase = new class('runTest') extends TestCase
{
    /** Render Textarea browser cases with real Blade components. */
    public function renderFixture(): string
    {
        $this->setUp();
        request()->setLaravelSession($this->app['session.store']);

        return Blade::render(file_get_contents(dirname(__DIR__).'/tests/Fixtures/views/textarea.blade.php'));
    }

    public function runTest(): void {}
};

try {
    fwrite(STDOUT, $testCase->renderFixture());
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}
