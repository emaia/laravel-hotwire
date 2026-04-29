<?php

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::timeago datetime="2026-04-29 12:00:00">Apr 29</x-hwc::timeago>');

    $view->assertSee('data-controller="timeago"', false);
    $view->assertSee('data-timeago-datetime-value=', false);
    $view->assertSee('data-timeago-add-suffix-value="true"', false);
    $view->assertSee('data-timeago-include-seconds-value="false"', false);
    $view->assertSee('Apr 29');
});

it('keeps the timeago controller fixed when arbitrary attributes are forwarded', function () {
    $view = $this->blade('<x-hwc::timeago datetime="2026-04-29 12:00:00" data-controller="custom" class="text-sm" />');

    $view->assertSee('data-controller="timeago"', false);
    $view->assertDontSee('data-controller="custom"', false);
    $view->assertSee('class="text-sm"', false);
});

it('renders refresh interval when configured', function () {
    $view = $this->blade('<x-hwc::timeago datetime="2026-04-29 12:00:00" :refresh-interval="60" />');

    $view->assertSee('data-timeago-refresh-interval-value="60"', false);
});
