<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

beforeEach(function () {
    RegistrySlotFamilyFixture::$instances = 0;
});

it('projects complete and partial family references without constructing components', function () {
    $registry = HotwireRegistry::fromCatalog(slotReferenceCatalog([
        ['class' => RegistrySlotFamilyFixture::class, 'only' => ['root', 'title']],
        ['class' => RegistrySharedSlotFamilyFixture::class],
    ]), '/tmp');

    expect($registry->component('fixture')->styling->slots)->toBe([
        'fixture' => 'structural',
        'fixture-title' => 'visual',
        'shared-action' => 'visual',
    ])->and(RegistrySlotFamilyFixture::$instances)->toBe(0);
});

it('rejects malformed family slot declarations', function () {
    expect(fn () => HotwireRegistry::fromCatalog(slotReferenceCatalog([
        ['class' => RegistryMalformedSlotFamilyFixture::class],
    ]), '/tmp'))->toThrow(InvalidArgumentException::class, 'must define exactly [name] and [kind]');
});

it('rejects references to undeclared local slot keys', function () {
    expect(fn () => HotwireRegistry::fromCatalog(slotReferenceCatalog([
        ['class' => RegistrySlotFamilyFixture::class, 'only' => ['missing']],
    ]), '/tmp'))->toThrow(InvalidArgumentException::class, 'references undefined local slot [missing]');
});

it('rejects malformed reference subsets', function () {
    expect(fn () => HotwireRegistry::fromCatalog(slotReferenceCatalog([
        ['class' => RegistrySlotFamilyFixture::class, 'only' => null],
    ]), '/tmp'))->toThrow(InvalidArgumentException::class, 'must define [only] as a list');
});

it('rejects references to classes without a slot declaration', function () {
    expect(fn () => HotwireRegistry::fromCatalog(slotReferenceCatalog([
        ['class' => RegistrySlotlessFamilyFixture::class],
    ]), '/tmp'))->toThrow(InvalidArgumentException::class, 'must declare its own public SLOTS constant');
});

it('rejects references to classes that do not exist', function () {
    expect(fn () => HotwireRegistry::fromCatalog(slotReferenceCatalog([
        ['class' => 'Missing\\SlotFamily'],
    ]), '/tmp'))->toThrow(InvalidArgumentException::class, 'class [Missing\\SlotFamily] does not exist');
});

it('rejects conflicting classifications across family references', function () {
    expect(fn () => HotwireRegistry::fromCatalog(slotReferenceCatalog([
        ['class' => RegistrySharedSlotFamilyFixture::class],
        ['class' => RegistryConflictingSlotFamilyFixture::class],
    ]), '/tmp'))->toThrow(InvalidArgumentException::class, 'classifies slot [shared-action] as [structural], already classified as [visual]');
});

/** @param list<array{class: class-string, only?: list<string>}> $references */
function slotReferenceCatalog(array $references): array
{
    return [
        'components' => [
            'fixture' => [
                'class' => RegistrySlotFamilyFixture::class,
                'view' => 'fixture',
                'docs' => 'fixture.md',
                'category' => 'display',
                'styling' => ['slots' => $references],
            ],
        ],
        'controllers' => [],
    ];
}

final class RegistrySlotFamilyFixture
{
    public const array SLOTS = [
        'root' => ['name' => 'fixture', 'kind' => 'structural'],
        'title' => ['name' => 'fixture-title', 'kind' => 'visual'],
        'unused' => ['name' => 'fixture-unused', 'kind' => 'visual'],
    ];

    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }
}

final class RegistrySharedSlotFamilyFixture
{
    public const array SLOTS = [
        'title' => ['name' => 'fixture-title', 'kind' => 'visual'],
        'action' => ['name' => 'shared-action', 'kind' => 'visual'],
    ];
}

final class RegistryConflictingSlotFamilyFixture
{
    public const array SLOTS = [
        'action' => ['name' => 'shared-action', 'kind' => 'structural'],
    ];
}

final class RegistryMalformedSlotFamilyFixture
{
    public const array SLOTS = [
        'root' => ['name' => 'fixture'],
    ];
}

final class RegistrySlotlessFamilyFixture {}
