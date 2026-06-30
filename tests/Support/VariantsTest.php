<?php

use Emaia\LaravelHotwire\Support\Variants;

// --- Base classes ---

it('returns base classes when no variants are configured', function () {
    $v = Variants::make('px-4 py-2 rounded-md');

    expect($v->classes([]))->toBe('px-4 py-2 rounded-md');
});

it('returns empty string when no base and no props', function () {
    $v = Variants::make();

    expect($v->classes([]))->toBe('');
});

// --- Single variant group ---

it('picks the matching variant class', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'size' => [
                'sm' => 'text-sm px-2',
                'lg' => 'text-lg px-4',
            ],
        ],
    );

    expect($v->classes(['size' => 'sm']))->toBe('btn text-sm px-2')
        ->and($v->classes(['size' => 'lg']))->toBe('btn text-lg px-4');
});

it('uses the default variant when prop is not provided', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'size' => [
                'sm' => 'text-sm px-2',
                'lg' => 'text-lg px-4',
            ],
        ],
        defaults: ['size' => 'sm'],
    );

    expect($v->classes([]))->toBe('btn text-sm px-2');
});

it('uses the default variant for missing prop and explicit for provided', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'variant' => [
                'default' => 'bg-primary',
                'outline' => 'border',
            ],
            'size' => [
                'sm' => 'text-sm',
                'lg' => 'text-lg',
            ],
        ],
        defaults: ['variant' => 'default', 'size' => 'sm'],
    );

    expect($v->classes(['variant' => 'outline']))
        ->toBe('btn border text-sm');
});

// --- Multiple variant groups ---

it('combines multiple variant groups', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'variant' => [
                'default' => 'bg-primary text-primary-foreground',
                'outline' => 'border border-input bg-background',
            ],
            'size' => [
                'default' => 'h-10 px-4',
                'sm' => 'h-9 px-3',
            ],
        ],
        defaults: ['variant' => 'default', 'size' => 'default'],
    );

    expect($v->classes([]))
        ->toBe('btn bg-primary text-primary-foreground h-10 px-4')
        ->and($v->classes(['variant' => 'outline', 'size' => 'sm']))
        ->toBe('btn border border-input bg-background h-9 px-3');
});

// --- Compound variants ---

it('applies compound variant when conditions match', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'variant' => [
                'outline' => 'border',
                'default' => 'bg-primary',
            ],
            'size' => [
                'icon' => 'h-10 w-10',
                'default' => 'h-10 px-4',
            ],
        ],
        compound: [
            ['when' => ['variant' => 'outline', 'size' => 'icon'], 'class' => 'p-0'],
        ],
    );

    expect($v->classes(['variant' => 'outline', 'size' => 'icon']))
        ->toBe('btn border h-10 w-10 p-0');
});

it('does not apply compound variant when conditions do not match', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'variant' => [
                'outline' => 'border',
                'default' => 'bg-primary',
            ],
        ],
        compound: [
            ['when' => ['variant' => 'outline', 'size' => 'icon'], 'class' => 'p-0'],
        ],
    );

    expect($v->classes(['variant' => 'outline', 'size' => 'default']))
        ->toBe('btn border');
});

// --- Edge cases ---

it('ignores unknown variant keys', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'size' => ['sm' => 'text-sm'],
        ],
    );

    expect($v->classes(['size' => 'unknown']))->toBe('btn');
});

it('ignores unrecognised prop keys not in variant groups', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'size' => ['sm' => 'text-sm'],
        ],
    );

    expect($v->classes(['colour' => 'red', 'size' => 'sm']))
        ->toBe('btn text-sm');
});

it('does not crash when a variant group has no matching entry and no default', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'size' => ['sm' => 'text-sm'],
        ],
    );

    expect($v->classes([]))->toBe('btn');
});

it('trims whitespace from the final string', function () {
    $v = Variants::make('  ');

    expect($v->classes([]))->toBe('');
});

it('handles empty compound class', function () {
    $v = Variants::make(
        base: 'btn',
        compound: [
            ['when' => ['variant' => 'outline'], 'class' => ''],
        ],
    );

    expect($v->classes(['variant' => 'outline']))->toBe('btn');
});

it('handles multiple compound rules', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'variant' => [
                'outline' => 'border',
                'ghost' => '',
            ],
            'size' => [
                'icon' => 'h-10 w-10',
                'default' => 'h-10 px-4',
            ],
        ],
        compound: [
            ['when' => ['variant' => 'outline', 'size' => 'icon'], 'class' => 'p-0'],
            ['when' => ['variant' => 'ghost', 'size' => 'icon'], 'class' => 'rounded-full'],
        ],
    );

    expect($v->classes(['variant' => 'outline', 'size' => 'icon']))
        ->toBe('btn border h-10 w-10 p-0')
        ->and($v->classes(['variant' => 'ghost', 'size' => 'icon']))
        ->toBe('btn h-10 w-10 rounded-full');
});

// --- Immutability ---

it('make returns a Variants instance', function () {
    $v = Variants::make('btn');

    expect($v)->toBeInstanceOf(Variants::class);
});

it('classes can be called multiple times with different props', function () {
    $v = Variants::make(
        base: 'btn',
        variants: [
            'size' => ['sm' => 'text-sm', 'lg' => 'text-lg'],
        ],
    );

    expect($v->classes(['size' => 'sm']))->toBe('btn text-sm')
        ->and($v->classes(['size' => 'lg']))->toBe('btn text-lg')
        ->and($v->classes(['size' => 'sm']))->toBe('btn text-sm');
});
