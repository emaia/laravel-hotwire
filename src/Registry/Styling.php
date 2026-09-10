<?php

namespace Emaia\LaravelHotwire\Registry;

final readonly class Styling
{
    /**
     * @param  array<string, 'visual'|'structural'>  $slots
     * @param  array<string, class-string|null>  $slotOwners
     */
    public function __construct(
        public array $slots = [],
        private array $slotOwners = [],
    ) {}

    /**
     * Slots a preset must style; the structural ones are containers or controller-owned geometry.
     *
     * @return string[]
     */
    public function visualSlots(): array
    {
        return $this->slotsOfKind('visual');
    }

    /** @return string[] */
    public function structuralSlots(): array
    {
        return $this->slotsOfKind('structural');
    }

    /** Return the component family that declares a projected slot, if it came from a family reference. */
    public function slotOwner(string $slot): ?string
    {
        return $this->slotOwners[$slot] ?? null;
    }

    /** @return string[] */
    private function slotsOfKind(string $kind): array
    {
        return array_keys(array_filter($this->slots, fn (string $slotKind): bool => $slotKind === $kind));
    }
}
