<?php

use Emaia\LaravelHotwire\Components\Accordion;
use Emaia\LaravelHotwire\Components\Accordion\Item as AccordionItem;
use Emaia\LaravelHotwire\Components\Carousel;
use Emaia\LaravelHotwire\Components\Chart;
use Emaia\LaravelHotwire\Components\FileUpload;
use Emaia\LaravelHotwire\Components\Map;
use Emaia\LaravelHotwire\Components\ReadMore;
use Emaia\LaravelHotwire\Components\RichText;
use Emaia\LaravelHotwire\Components\Sidebar;
use Emaia\LaravelHotwire\Components\Sidebar\Provider;
use Emaia\LaravelHotwire\Components\SidePanel;
use Emaia\LaravelHotwire\Components\SidePanel\Panel as SidePanelPanel;
use Emaia\LaravelHotwire\Components\SidePanel\Trigger as SidePanelTrigger;
use Emaia\LaravelHotwire\Components\Tabs;
use Emaia\LaravelHotwire\Components\Tabs\Panel as TabsPanel;
use Emaia\LaravelHotwire\Components\Tabs\TabList;
use Emaia\LaravelHotwire\Components\Tabs\Trigger as TabsTrigger;
use Emaia\LaravelHotwire\Support\StimulusIdentifier;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Illuminate\View\ViewException;

// Each factory takes the `controller` prop, so one dataset drives both the reject
// and the accept case. Wrapped in arrays so Pest passes the closure as the
// argument instead of treating it as a lazy dataset provider.
dataset('identifier swapping components', [
    'accordion' => [fn (string $c) => new Accordion(controller: $c)],
    'carousel' => [fn (string $c) => new Carousel(controller: $c)],
    'chart' => [fn (string $c) => new Chart(url: '/api/charts', controller: $c)],
    'file-upload' => [fn (string $c) => new FileUpload(url: '/uploads', controller: $c)],
    'map' => [fn (string $c) => new Map(center: [0, 0], controller: $c)],
    'read-more' => [fn (string $c) => new ReadMore(controller: $c)],
    'rich-text' => [fn (string $c) => new RichText(controller: $c)],
    'side-panel' => [fn (string $c) => new SidePanel(name: 'nav', controller: $c)],
    'sidebar' => [fn (string $c) => new Provider(controller: $c)],
    'tabs' => [fn (string $c) => new Tabs(controller: $c)],
]);

dataset('identifier aware subcomponents', [
    'accordion item' => [fn (string $c) => (new AccordionItem(value: 'one'))->data()['compute']($c, [], new ComponentAttributeBag)],
    'side panel panel' => [fn (string $c) => (new SidePanelPanel)->data()['compute']('panel', $c, 'expanded', new ComponentAttributeBag)],
    'side panel trigger' => [fn (string $c) => (new SidePanelTrigger)->data()['compute']('panel', $c, 'expanded', new ComponentAttributeBag)],
    'tabs list' => [fn (string $c) => (new TabList)->data()['compute']($c, 'horizontal', new ComponentAttributeBag)],
    'tabs panel' => [fn (string $c) => (new TabsPanel(value: 'one'))->data()['compute']('tabs', 'one', $c, new ComponentAttributeBag)],
    'tabs trigger' => [fn (string $c) => (new TabsTrigger(value: 'one'))->data()['compute']('tabs', 'one', $c, new ComponentAttributeBag)],
]);

dataset('controller named slot components', [
    'carousel' => ['<x-hw::carousel><x-slot:controller>foo onmouseover=alert(1) x</x-slot:controller></x-hw::carousel>'],
    'chart' => ['<x-hw::chart url="/chart"><x-slot:controller>foo onmouseover=alert(1) x</x-slot:controller></x-hw::chart>'],
    'file upload' => ['<x-hw::file-upload url="/upload"><x-slot:controller>foo onmouseover=alert(1) x</x-slot:controller></x-hw::file-upload>'],
    'map' => ['<x-hw::map url="/map"><x-slot:controller>foo onmouseover=alert(1) x</x-slot:controller></x-hw::map>'],
    'read more' => ['<x-hw::read-more><x-slot:controller>foo onmouseover=alert(1) x</x-slot:controller></x-hw::read-more>'],
    'rich text' => ['<x-hw::rich-text><x-slot:controller>foo onmouseover=alert(1) x</x-slot:controller></x-hw::rich-text>'],
]);

// --- Guard ---

it('returns the identifier when it is a valid stimulus identifier', function (string $identifier) {
    expect(StimulusIdentifier::guard($identifier, 'chart'))->toBe($identifier);
})->with([
    'chart',
    'sales-chart',
    'chart2',
    'my_chart',
    'turbo--progress',
    'optimistic--form--field',
]);

it('rejects an identifier that cannot survive interpolation into an attribute name', function (string $identifier) {
    expect(fn () => StimulusIdentifier::guard($identifier, 'chart'))
        ->toThrow(InvalidArgumentException::class, 'Invalid chart controller identifier.');
})->with([
    'empty' => '',
    'space' => 'sales chart',
    'double quote' => 'foo" onmouseover="alert(1)',
    'single quote' => "foo' onmouseover='alert(1)",
    'angle bracket' => 'foo><script>',
    'equals' => 'foo=bar',
    'slash' => 'foo/bar',
    'uppercase' => 'salesChart',
    'leading dash' => '-chart',
    'trailing newline' => "chart\n",
]);

it('names the offending component in the message', function () {
    expect(fn () => StimulusIdentifier::guard('bad id', 'rich-text'))
        ->toThrow(InvalidArgumentException::class, 'Invalid rich-text controller identifier.');
});

// --- Component props ---

it('rejects a hostile controller prop on every component that swaps its identifier', function (Closure $make) {
    expect(fn () => $make('foo" onmouseover="alert(1)'))
        ->toThrow(InvalidArgumentException::class, 'controller identifier');
})->with('identifier swapping components');

it('accepts a swapped kebab-case controller prop on every component', function (Closure $make) {
    expect(fn () => $make('my-widget'))->not->toThrow(InvalidArgumentException::class);
})->with('identifier swapping components');

it('rejects a hostile identifier inherited by every identifier-aware subcomponent', function (Closure $compute) {
    expect(fn () => $compute('foo" onmouseover="alert(1)'))
        ->toThrow(InvalidArgumentException::class, 'controller identifier');
})->with('identifier aware subcomponents');

it('validates the final controller value after named slots are merged into view data', function (string $blade) {
    view()->share('errors', new ViewErrorBag);

    expect(fn () => $this->blade($blade))
        ->toThrow(ViewException::class, 'controller identifier');
})->with('controller named slot components');

it('rejects a hostile identifier inherited by the Sidebar view', function () {
    $views = view();
    $bufferLevel = ob_get_level();
    $views->startComponent(fn () => new HtmlString, [
        'sidebarIdentifier' => 'foo" onmouseover="alert(1)',
    ]);

    $data = (new Sidebar)->data();
    $data['attributes'] = new ComponentAttributeBag;
    $data['slot'] = new ComponentSlot;

    $exception = null;

    try {
        view('hotwire::component-views.sidebar', $data)->render();
    } catch (ViewException $caught) {
        $exception = $caught;
    } finally {
        $views->flushState();
        while (ob_get_level() > $bufferLevel) {
            ob_end_clean();
        }
    }

    expect($exception)->toBeInstanceOf(ViewException::class)
        ->and($exception?->getMessage())->toContain('controller identifier');
});
