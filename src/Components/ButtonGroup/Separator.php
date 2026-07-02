<?php

namespace Emaia\LaravelHotwire\Components\ButtonGroup;

use Emaia\LaravelHotwire\Components\Separator as SeparatorComponent;

class Separator extends SeparatorComponent
{
    public function __construct(string $orientation = 'vertical')
    {
        parent::__construct($orientation, 'button-group-separator');
    }
}
