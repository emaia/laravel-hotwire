<?php

use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$testCase = new class('runTest') extends TestCase
{
    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        config()->set('view.compiled', sys_get_temp_dir().'/hotwire-breadcrumb-browser-'.bin2hex(random_bytes(8)));
    }

    public function renderFixture(): string
    {
        $this->setUp();

        try {
            return Blade::render(<<<'BLADE'
                @foreach (['sm', 'md', 'lg'] as $breakpoint)
                    <header>
                        <x-hw::breadcrumb :id="$breakpoint" :collapse-below="$breakpoint" frame="results" :items="[
                            ['label' => 'Dashboard', 'href' => '#dashboard', 'frame' => false],
                            ['label' => 'Projects', 'href' => '#projects'],
                            ['label' => 'A long current page title that needs to fit next to the header controls'],
                        ]" />
                        <button type="button">Settings</button>
                    </header>
                @endforeach
                BLADE);
        } finally {
            File::deleteDirectory(config('view.compiled'));
        }
    }

    public function runTest(): void {}
};

echo $testCase->renderFixture();
