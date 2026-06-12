<?php

use Emaia\LaravelHotwire\Components\Chart;
use Illuminate\Support\Facades\Log;

// --- Rendering ---

it('renders a div with the chart controller and option data attr', function () {
    $view = $this->blade('<x-hwc::chart :option="[\'title\' => [\'text\' => \'x\']]" />');

    $view->assertSee('data-controller="chart"', false);
    $view->assertSee('data-chart-option-value=', false);
    $view->assertSee('{&quot;title&quot;:{&quot;text&quot;:&quot;x&quot;}}', false);
});

it('renders the url data attr when url prop is set', function () {
    $view = $this->blade('<x-hwc::chart url="/api/charts/sales" />');

    $view->assertSee('data-chart-url-value="/api/charts/sales"', false);
    $view->assertDontSee('data-chart-option-value', false);
});

it('renders the theme data attr when theme prop is set', function () {
    $view = $this->blade('<x-hwc::chart :option="[\'title\' => [\'text\' => \'x\']]" theme="dark" />');

    $view->assertSee('data-chart-theme-value="dark"', false);
});

it('omits the theme attr when theme is not provided', function () {
    $view = $this->blade('<x-hwc::chart :option="[\'title\' => [\'text\' => \'x\']]" />');

    $view->assertDontSee('data-chart-theme-value', false);
});

it('renders the poll data attr when poll prop is set', function () {
    $view = $this->blade('<x-hwc::chart url="/api/charts/sales" :poll="30000" />');

    $view->assertSee('data-chart-poll-value="30000"', false);
});

it('omits the poll attr when poll is 0 (default)', function () {
    $view = $this->blade('<x-hwc::chart url="/api/charts/sales" />');

    $view->assertDontSee('data-chart-poll-value', false);
});

// --- Sizing ---

it('emits inline style with the default 400px height and 100% width', function () {
    $view = $this->blade('<x-hwc::chart :option="[\'title\' => [\'text\' => \'x\']]" />');

    $view->assertSee('style="width: 100%; height: 400px"', false);
});

it('honors custom height and width props', function () {
    $view = $this->blade('<x-hwc::chart :option="[\'title\' => [\'text\' => \'x\']]" height="320px" width="640px" />');

    $view->assertSee('style="width: 640px; height: 320px"', false);
});

// --- Validation ---

it('throws when neither option nor url is provided', function () {
    expect(fn () => new Chart)->toThrow(InvalidArgumentException::class);
});

it('does not throw when only option is provided', function () {
    expect(fn () => new Chart(option: ['title' => ['text' => 'x']]))->not->toThrow(InvalidArgumentException::class);
});

it('does not throw when only url is provided', function () {
    expect(fn () => new Chart(url: '/api/charts/sales'))->not->toThrow(InvalidArgumentException::class);
});

// --- Controller swap (subclass extensibility) ---

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade('<x-hwc::chart controller="sales-chart" url="/api/charts/sales" />');

    $view->assertSee('data-controller="sales-chart"', false);
    $view->assertSee('data-sales-chart-url-value="/api/charts/sales"', false);
});

it('prefixes the option data attr with the swapped identifier', function () {
    $view = $this->blade('<x-hwc::chart controller="sales-chart" :option="[\'title\' => [\'text\' => \'x\']]" />');

    $view->assertSee('data-sales-chart-option-value', false);
    $view->assertDontSee('data-chart-option-value', false);
});

// --- Attribute passthrough ---

it('forwards extra attributes to the wrapper element', function () {
    $view = $this->blade('<x-hwc::chart :option="[\'x\' => 1]" class="rounded border" id="sales-chart" />');

    $view->assertSee('id="sales-chart"', false);
    $view->assertSee('class="rounded border"', false);
});

it('merges user-provided data-controller alongside the chart identifier', function () {
    $view = $this->blade('<x-hwc::chart :option="[\'x\' => 1]" data-controller="extra-behavior" />');

    $view->assertSee('data-controller="chart extra-behavior"', false);
});

// --- Dev-mode warning ---

it('logs a warning when inline option exceeds 500KB in local environment', function () {
    Log::spy();
    app()['env'] = 'local';

    // 200KB doesn't trigger
    new Chart(option: ['data' => str_repeat('a', 200_000)]);
    Log::shouldNotHaveReceived('warning');

    // 600KB triggers
    new Chart(option: ['data' => str_repeat('a', 600_000)]);
    Log::shouldHaveReceived('warning')->once();
});

it('does not log the size warning outside the local environment', function () {
    Log::spy();
    app()['env'] = 'production';

    new Chart(option: ['data' => str_repeat('a', 600_000)]);

    Log::shouldNotHaveReceived('warning');
});
