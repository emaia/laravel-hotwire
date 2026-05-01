<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Form extends Component
{
    public function __construct(
        public bool $autoSubmit = false,
        public bool $unsavedChanges = false,
        public bool $cleanQueryParams = false,
        public bool $remote = false,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.form');
    }
}
