<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\SessionToast;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

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
        // An empty string is absent, not a message: the values routinely come from request input
        // or a macro argument, and rendering one produces a card with no text in it.
        $message = $this->message !== null && trim($this->message) !== '' ? $this->message : null;

        // An explicit message is the caller's own, so it must not spend the session's — the toaster
        // (or a later <hw:toast />) still has to render that one. The type keeps falling back to it.
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
