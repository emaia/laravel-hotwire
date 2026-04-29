<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Support\Facades\Session;

class FlashMessage extends HotwireComponent
{
    public string $finalType;

    public ?string $finalMessage;

    public function __construct(
        public ?string $message = null,
        public ?string $description = null,
        public ?string $type = null,
    ) {
        $sessionType = match (true) {
            Session::has('success') => 'success',
            Session::has('error') => 'error',
            Session::has('errors') => 'error',
            Session::has('warning') => 'warning',
            Session::has('info') => 'info',
            default => null,
        };

        $sessionMessage = match ($sessionType) {
            'success' => Session::get('success'),
            'error' => Session::get('error') ?: Session::get('errors')?->first(),
            'warning' => Session::get('warning'),
            'info' => Session::get('info'),
            default => null,
        };

        $this->finalType = $this->type ?? $sessionType ?? 'default';
        $this->finalMessage = $this->message ?? $sessionMessage;
    }

    public function shouldRender(): bool
    {
        return $this->finalMessage !== null;
    }

    public function render()
    {
        return $this->renderComponentView('flash-message');
    }
}
