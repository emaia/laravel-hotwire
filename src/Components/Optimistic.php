<?php

namespace Emaia\LaravelHotwire\Components;

class Optimistic extends HotwireComponent
{
    public function __construct(
        public string $target = '',
        public string $targets = '',
        public string $action = 'replace',
    ) {}

    public function render()
    {
        return $this->renderComponentView('optimistic');
    }
}
