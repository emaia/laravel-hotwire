<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Emaia\LaravelHotwire\Components\Separator as SeparatorComponent;

class Separator extends SeparatorComponent
{
    public function __construct(string $orientation = 'horizontal')
    {
        parent::__construct($orientation, 'item-separator');
    }
}
