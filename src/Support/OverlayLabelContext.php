<?php

namespace Emaia\LaravelHotwire\Support;

final class OverlayLabelContext
{
    private int $titleCount = 0;

    private int $descriptionCount = 0;

    private ?string $titleId = null;

    private ?string $descriptionId = null;

    public function __construct(
        private readonly string $rootId,
        private readonly string $slotPrefix,
    ) {}

    /** Return component data that prevents label ownership from crossing an overlay root. */
    public static function boundaryData(): array
    {
        return ['overlayLabelOwnerContext' => null];
    }

    /** Return component data that lets an overlay content component own its labels. */
    public static function ownerData(string $contextKey): array
    {
        $context = app('view')->getConsumableComponentData($contextKey);

        return ['overlayLabelOwnerContext' => $context instanceof self ? $context : null];
    }

    /** Register an owned title or description and return its resolved id. */
    public function register(string $slotName, ?string $explicitId = null): ?string
    {
        $kind = match ($slotName) {
            "{$this->slotPrefix}-title" => 'title',
            "{$this->slotPrefix}-description" => 'description',
            default => null,
        };

        if ($kind === null) {
            return null;
        }

        if ($kind === 'title') {
            $count = ++$this->titleCount;
            $id = $this->resolveId($explicitId, $kind, $count);
            $this->titleId ??= $id;

            return $id;
        }

        $count = ++$this->descriptionCount;
        $id = $this->resolveId($explicitId, $kind, $count);
        $this->descriptionId ??= $id;

        return $id;
    }

    /** Return the first registered title id. */
    public function titleId(): ?string
    {
        return $this->titleId;
    }

    /** Return the first registered description id. */
    public function descriptionId(): ?string
    {
        return $this->descriptionId;
    }

    private function resolveId(?string $explicitId, string $kind, int $count): string
    {
        if ($explicitId !== null && $explicitId !== '') {
            return $explicitId;
        }

        $suffix = $count === 1 ? '' : "-{$count}";

        return "{$this->rootId}-{$kind}{$suffix}";
    }
}
