<?php

use Emaia\LaravelHotwire\Support\FieldKey;

// --- toErrorKey ---

it('preserves simple names', function () {
    expect(FieldKey::toErrorKey('email'))->toBe('email');
});

it('converts bracket notation to dot notation', function () {
    expect(FieldKey::toErrorKey('variables[0][name]'))->toBe('variables.0.name');
});

it('preserves dot notation already in the name', function () {
    expect(FieldKey::toErrorKey('address.street'))->toBe('address.street');
});

it('handles empty brackets deterministically as double dots', function () {
    expect(FieldKey::toErrorKey('users[][email]'))->toBe('users..email');
});

it('handles deeply nested arrays', function () {
    expect(FieldKey::toErrorKey('a[b][c][d]'))->toBe('a.b.c.d');
});

it('returns empty string for empty input', function () {
    expect(FieldKey::toErrorKey(''))->toBe('');
});

// --- toId ---

it('preserves simple names as ids', function () {
    expect(FieldKey::toId('email'))->toBe('email');
});

it('converts bracket notation to dash notation for ids', function () {
    expect(FieldKey::toId('variables[0][name]'))->toBe('variables-0-name');
});

it('converts dot notation to dash notation for ids', function () {
    expect(FieldKey::toId('address.street'))->toBe('address-street');
});

it('handles empty brackets deterministically as double dashes', function () {
    expect(FieldKey::toId('users[][email]'))->toBe('users--email');
});

it('handles deeply nested arrays for ids', function () {
    expect(FieldKey::toId('a[b][c][d]'))->toBe('a-b-c-d');
});
