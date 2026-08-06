<?php

namespace Emaia\LaravelHotwire\Registry;

/**
 * The `data-slot` hooks a catalog entry emits. The values each varies by are deliberately absent —
 * only a stylesheet knows them; see `Support\PresetAxes`.
 */
final readonly class Styling
{
    /**
     * @param  array<string, 'visual'|'structural'>  $slots
     */
    public function __construct(
        public array $slots = [],
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

    /** @return string[] */
    private function slotsOfKind(string $kind): array
    {
        return array_keys(array_filter($this->slots, fn (string $slotKind): bool => $slotKind === $kind));
    }
}
