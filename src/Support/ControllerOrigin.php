<?php

namespace Emaia\LaravelHotwire\Support;

enum ControllerOrigin: string
{
    case Application = 'application';
    case Package = 'package';
}
