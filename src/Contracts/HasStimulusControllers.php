<?php

namespace Emaia\LaravelHotwire\Contracts;

interface HasStimulusControllers
{
    /** @return string[] Stimulus controller identifiers (e.g. ['dialog--modal']) */
    public static function stimulusControllers(): array;
}
