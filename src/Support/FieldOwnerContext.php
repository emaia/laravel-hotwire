<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldOwnerContext
{
    private ?string $labelId = null;

    /** Register the exact id rendered by the first label belonging to this owner. */
    public function registerLabel(?string $id): string
    {
        $id = $id !== null && $id !== '' ? $id : 'hw-field-label-'.uniqid();
        $this->labelId ??= $id;

        return $id;
    }

    /** Return the id registered by this owner's label. */
    public function labelId(): ?string
    {
        return $this->labelId;
    }
}
