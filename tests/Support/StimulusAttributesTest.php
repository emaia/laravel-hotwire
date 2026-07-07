<?php

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\View\ComponentAttributeBag;

it('merges and de-duplicates controller tokens', function () {
    $bag = StimulusAttributes::merge(
        ['data-controller' => 'tabs'],
        new ComponentAttributeBag(['data-controller' => 'tabs analytics']),
        stimulus()->controller('tab-url')->controller('analytics'),
    );

    expect((string) $bag)->toContain('data-controller="tabs analytics tab-url"');
});

it('merges and de-duplicates action descriptors', function () {
    $bag = StimulusAttributes::merge(
        ['data-action' => 'click->tabs#select keydown->tabs#navigate'],
        new ComponentAttributeBag(['data-action' => 'click->tabs#select mouseenter->analytics#track']),
        stimulus()->action('tab-url', 'update', 'tabs:change'),
    );

    expect((string) $bag)->toContain('data-action="click->tabs#select keydown->tabs#navigate mouseenter->analytics#track tabs:change->tab-url#update"');
});

it('merges targets for the same controller', function () {
    $bag = StimulusAttributes::merge(
        ['data-tabs-target' => 'tab'],
        new ComponentAttributeBag(['data-tabs-target' => 'tab panel']),
        stimulus()->target('tabs', 'panel extra'),
    );

    expect((string) $bag)->toContain('data-tabs-target="tab panel extra"');
});

it('keeps internal attributes when a protected prefix is passed', function () {
    $bag = StimulusAttributes::merge(
        [
            'data-controller' => 'tabs',
            'data-tabs-target' => 'tab',
            'data-tabs-id-value' => 'internal',
        ],
        new ComponentAttributeBag([
            'data-controller' => 'analytics',
            'data-tabs-target' => 'override',
            'data-tabs-id-value' => 'external',
            'data-analytics-id-value' => 'external',
        ]),
        protectedPrefixes: ['data-tabs-'],
    );

    $html = (string) $bag;

    expect($html)->toContain('data-controller="tabs analytics"')
        ->and($html)->toContain('data-tabs-target="tab"')
        ->and($html)->toContain('data-tabs-id-value="internal"')
        ->and($html)->toContain('data-analytics-id-value="external"');
});

it('keeps internal target attributes when a protected prefix is passed to stimulus', function () {
    $bag = StimulusAttributes::merge(
        [
            'data-controller' => 'tabs',
            'data-tabs-target' => 'tab',
        ],
        stimulus: stimulus()->target('tabs', 'override')->target('analytics', 'panel'),
        protectedPrefixes: ['data-tabs-'],
    );

    $html = (string) $bag;

    expect($html)->toContain('data-controller="tabs"')
        ->and($html)->toContain('data-tabs-target="tab"')
        ->and($html)->toContain('data-analytics-target="panel"');
});

it('lets later non-protected scalar attributes replace earlier ones', function () {
    $bag = StimulusAttributes::merge(
        ['aria-label' => 'Internal'],
        new ComponentAttributeBag(['aria-label' => 'User']),
    );

    expect((string) $bag)->toContain('aria-label="User"');
});
