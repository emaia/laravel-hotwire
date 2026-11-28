<?php

use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$testCase = new class('runTest') extends TestCase
{
    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        config()->set('view.compiled', sys_get_temp_dir().'/hotwire-navigation-menu-browser-'.bin2hex(random_bytes(8)));
    }

    public function renderFixture(string $surface): string
    {
        $this->setUp();

        try {
            $menu = Blade::render(<<<'BLADE'
                <hw:navigation-menu id="site" aria-label="Site" :anchor="$anchor" :hover="$hover">
                    <hw:navigation-menu.list>
                        <hw:navigation-menu.item><hw:navigation-menu.trigger value="products">Products</hw:navigation-menu.trigger></hw:navigation-menu.item>
                        <hw:navigation-menu.item><hw:navigation-menu.trigger value="company">Company</hw:navigation-menu.trigger></hw:navigation-menu.item>
                        <hw:navigation-menu.item><hw:navigation-menu.link href="/about">About</hw:navigation-menu.link></hw:navigation-menu.item>
                    </hw:navigation-menu.list>
                    <hw:navigation-menu.viewport>
                        <hw:navigation-menu.content value="products">
                            <div style="width: 230px; max-width: 100%">
                                <p><a href="/catalog">Catalog</a></p>
                                <p><a href="/featured">Featured</a></p>
                            </div>
                        </hw:navigation-menu.content>
                        <hw:navigation-menu.content value="company" frame="company-frame" src="/company-menu">
                            <p>Loading company</p>
                        </hw:navigation-menu.content>
                    </hw:navigation-menu.viewport>
                </hw:navigation-menu>
                BLADE, [
                'anchor' => $surface === 'menu' ? 'menu' : 'trigger',
                'hover' => ! in_array($surface, ['modal', 'dropdown'], true),
            ]);

            $wrapper = match ($surface) {
                'frame' => '<turbo-frame id="page-nav">{{ $menu }}</turbo-frame>',
                'modal' => <<<'BLADE'
                    <hw:modal initial-focus="dialog">
                        <hw:modal.trigger>Open modal</hw:modal.trigger>
                        <hw:modal.content aria-label="Menu dialog">{{ $menu }}</hw:modal.content>
                    </hw:modal>
                    BLADE,
                'dropdown' => <<<'BLADE'
                    <hw:dropdown :close-on-select="false">
                        <hw:dropdown.trigger>Open dropdown</hw:dropdown.trigger>
                        <hw:dropdown.content>{{ $menu }}</hw:dropdown.content>
                    </hw:dropdown>
                    BLADE,
                default => '{{ $menu }}',
            };

            return Blade::render($wrapper.' <button id="after">After navigation</button>', ['menu' => new HtmlString($menu)]);
        } finally {
            File::deleteDirectory(config('view.compiled'));
        }
    }

    public function runTest(): void {}
};

echo $testCase->renderFixture($argv[1] ?? '');
