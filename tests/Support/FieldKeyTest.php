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

it('strips trailing [] so checkbox-group names map to bare error key', function () {
    expect(FieldKey::toErrorKey('roles[]'))->toBe('roles');
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

it('strips trailing [] so checkbox-group names map to bare id', function () {
    expect(FieldKey::toId('roles[]'))->toBe('roles');
});

it('handles deeply nested arrays for ids', function () {
    expect(FieldKey::toId('a[b][c][d]'))->toBe('a-b-c-d');
});

// --- resolveId ---

it('resolves one identity precedence for controls labels and errors', function (
    ?string $id,
    ?string $name,
    ?string $ownerId,
    ?string $ownerName,
    ?string $expected,
) {
    expect(FieldKey::resolveId($id, $name, $ownerId, $ownerName))->toBe($expected);
})->with([
    'explicit id' => ['custom', 'child', 'owner-id', 'owner', 'custom'],
    'divergent explicit name' => [null, 'child', 'owner-id', 'owner', 'child'],
    'matching explicit name' => [null, 'owner', 'owner-id', 'owner', 'owner-id'],
    'inherited owner id' => [null, null, 'owner-id', 'owner', 'owner-id'],
    'inherited owner name' => [null, null, null, 'owner[field]', 'owner-field'],
    'no identity' => [null, null, null, null, null],
]);
