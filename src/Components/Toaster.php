<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\SessionToast;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Toaster extends Component
{
    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public ?string $flashDescription = null;

    public ?string $flashPosition = null;

    public function __construct(
        public string $id = 'toaster',
        public string $position = 'bottom-center',
        public int $duration = 4000,
        public int $visibleToasts = 3,
        public bool $closeButton = true,
        public bool $expand = false,
        public bool $autoDisconnect = false,
        public bool $turboPermanent = true,
        public bool $flash = true,
        public string $class = '',
        public ?string $className = null,
        public ?string $containerAriaLabel = null,
        public ?Htmlable $stimulus = null,
    ) {
        if (! $this->flash) {
            return;
        }

        $toast = app(SessionToast::class)->consume();

        $this->flashMessage = $toast['message'] ?? null;
        $this->flashType = $toast['type'] ?? null;
        $this->flashDescription = $toast['description'] ?? null;
        $this->flashPosition = $toast['position'] ?? null;
    }

    public function render()
    {
        return view('hotwire::component-views.toaster');
    }
}
