<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldOwnerContext
{
    private ?string $labelId = null;

    /** @var array<string, true> */
    private array $usedLabelIds = [];

    /** Reserve a unique rendered id while retaining the first as the owner's reference. */
    public function registerLabel(?string $id): string
    {
        $baseId = $id !== null && $id !== '' ? $id : 'hw-field-label-'.uniqid();
        $resolvedId = $baseId;
        $suffix = 2;

        while (isset($this->usedLabelIds[$resolvedId])) {
            $resolvedId = $baseId.'-'.$suffix;
            $suffix++;
        }

        $this->usedLabelIds[$resolvedId] = true;
        $this->labelId ??= $resolvedId;

        return $resolvedId;
    }

    /** Return the id registered by this owner's label. */
    public function labelId(): ?string
    {
        return $this->labelId;
    }
}
