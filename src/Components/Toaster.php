<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Toaster extends Component
{
    public function __construct(
        public string $id = 'toaster',
        public string $position = 'bottom-center',
        public int $duration = 4000,
        public int $visibleToasts = 3,
        public bool $closeButton = true,
        public bool $expand = false,
        public bool $autoDisconnect = false,
        public bool $turboPermanent = true,
        public string $class = '',
        public ?string $className = null,
        public ?string $containerAriaLabel = null,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.toaster');
    }
}
