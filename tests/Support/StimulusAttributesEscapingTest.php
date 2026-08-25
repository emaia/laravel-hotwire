<?php

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\View\ComponentAttributeBag;

it('encodes a double quote so the value survives the attribute', function () {
    $bag = StimulusAttributes::merge(['data-toast-message-value' => 'He said "hello"']);

    expect((string) $bag)
        ->toContain('data-toast-message-value="He said &quot;hello&quot;"')
        ->not->toContain('\\"');
});

it('leaves everything but the quote alone', function () {
    $bag = StimulusAttributes::merge(['data-toast-message-value' => "Tom & Jerry <b> it's 5 > 3"]);

    expect((string) $bag)->toContain('data-toast-message-value="Tom & Jerry <b> it\'s 5 > 3"');
});

it('renders an already encoded value the same way', function () {
    $bag = StimulusAttributes::merge(['data-toast-message-value' => 'He said &quot;hello&quot;']);

    expect((string) $bag)->toContain('data-toast-message-value="He said &quot;hello&quot;"');
});

it('encodes user attributes too', function () {
    $bag = StimulusAttributes::merge([], new ComponentAttributeBag(['title' => 'He said "hello"']));

    expect((string) $bag)->toContain('title="He said &quot;hello&quot;"');
});

it('keeps boolean attributes rendering as their own name', function () {
    $bag = StimulusAttributes::merge(['data-turbo-temporary' => true]);

    expect((string) $bag)->toContain('data-turbo-temporary="data-turbo-temporary"');
});

it('leaves token lists untouched', function () {
    $bag = StimulusAttributes::merge(
        ['data-controller' => 'toast'],
        new ComponentAttributeBag(['data-controller' => 'analytics']),
    );

    expect((string) $bag)->toContain('data-controller="toast analytics"');
});
