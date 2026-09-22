<?php

namespace Emaia\LaravelHotwire\Support;

final class RevealContext
{
    private int $index = 0;

    /** Return the stable owner identity for this render context. */
    public function owner(): int
    {
        return spl_object_id($this);
    }

    /** Claim and advance the next server-rendered item index. */
    public function nextIndex(): int
    {
        return $this->index++;
    }
}
