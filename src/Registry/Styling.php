<?php

namespace Emaia\LaravelHotwire\Registry;

/**
 * The styling surface a catalog entry contributes: which `data-slot` hooks it emits and, per slot,
 * the `data-variant` / `data-size` values its appearance varies by. Controllers declare it too —
 * `tooltip` builds its own DOM, so its slots exist even though no Blade view emits them.
 */
final readonly class Styling
{
    /**
     * @param  array<string, 'visual'|'structural'>  $slots
     * @param  array<string, string[]>  $variants  slot => supported `data-variant` values
     * @param  array<string, string[]>  $sizes  slot => supported `data-size` values
     */
    public function __construct(
        public array $slots = [],
        public array $variants = [],
        public array $sizes = [],
    ) {}

    /**
     * Slots a preset must style. Structural slots are containers, assistive nodes or geometry the
     * controller stylesheet already owns, so requiring a preset rule for them would be noise.
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

    /**
     * Attribute values a single slot varies by, keyed by attribute name and empty axes dropped.
     *
     * @return array<string, string[]>
     */
    public function axesFor(string $slot): array
    {
        return array_filter([
            'data-variant' => $this->variants[$slot] ?? [],
            'data-size' => $this->sizes[$slot] ?? [],
        ]);
    }

    /** @return string[] */
    private function slotsOfKind(string $kind): array
    {
        return array_keys(array_filter($this->slots, fn (string $slotKind): bool => $slotKind === $kind));
    }
}
