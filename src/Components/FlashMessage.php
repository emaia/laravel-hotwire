<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Contracts\HasStimulusControllers;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;

class FlashMessage extends Component implements HasStimulusControllers
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

    public static function stimulusControllers(): array
    {
        return ['notification--toast', 'notification--toaster'];
    }

    public function shouldRender(): bool
    {
        return $this->finalMessage !== null;
    }

    public function render()
    {
        return view('hotwire::components.flash-message.flash-message');
    }
}
