<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('returns the configured prefix followed by permanent aliases', function () {
    expect(ComponentAliases::prefixes('ui'))->toBe(['ui', 'hw'])
        ->and(ComponentAliases::prefixes('hw'))->toBe(['hw']);
});

it('builds component tags from the shared prefix list', function () {
    $component = HotwireRegistry::make()->component('modal');

    expect($component->tags(ComponentAliases::prefixes('ui')))
        ->toBe(['<x-ui::modal>', '<x-hw::modal>']);
});
