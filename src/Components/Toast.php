<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\SessionToast;
use Illuminate\Contracts\Support\Htmlable;

class Toast extends Component
{
    public string $finalType;

    public ?string $finalMessage;

    public ?string $finalDescription;

    public ?string $finalPosition;

    public function __construct(
        public ?string $message = null,
        public ?string $description = null,
        public ?string $type = null,
        public ?string $position = null,
        public ?string $className = null,
        public ?Htmlable $stimulus = null,
    ) {
        $message = $this->message !== null && trim($this->message) !== '' ? $this->message : null;

        // An explicit message must not spend the session's; the toaster still has to render that one.
        $claims = $message === null;
        $session = app(SessionToast::class);
        $flash = $claims ? $session->consume() : $session->resolve();

        $this->finalType = $this->type ?? $flash['type'] ?? 'default';
        $this->finalMessage = $message ?? $flash['message'] ?? null;
        $this->finalDescription = $this->description ?? ($claims ? $flash['description'] ?? null : null);
        $this->finalPosition = $this->position ?? ($claims ? $flash['position'] ?? null : null);
    }

    public function shouldRender(): bool
    {
        return $this->finalMessage !== null;
    }

    public function render()
    {
        return view('hotwire::component-views.toast');
    }
}
