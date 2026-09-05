<?php

use Emaia\LaravelHotwire\Support\OptionPairs;

it('normalizes flat options into value label pairs', function () {
    expect(OptionPairs::normalize(['bold', 'italic']))->toBe([
        'bold' => 'bold',
        'italic' => 'italic',
    ]);
});

it('preserves associative and empty options', function () {
    expect(OptionPairs::normalize(['b' => 'Bold', 'i' => 'Italic']))->toBe([
        'b' => 'Bold',
        'i' => 'Italic',
    ])->and(OptionPairs::normalize([]))->toBe([]);
});

it('preserves array combine key coercion used by existing option components', function () {
    expect(OptionPairs::normalize([1.2, 1.8]))->toBe([
        '1.2' => 1.2,
        '1.8' => 1.8,
    ]);
});
