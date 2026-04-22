<?php

namespace Emaia\LaravelHotwire\Contracts;

interface HasStimulusControllers
{
    /** @return string[] Stimulus controller identifiers (e.g. ['dialog']) */
    public static function stimulusControllers(): array;
}
