<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Error extends Component
{
    /** @var string[]|null */
    public ?array $explicitMessages;

    /** @param  array<int, string>|string|null  $messages */
    public function __construct(
        public ?string $name = null,
        public ?string $errorKey = null,
        array|string|null $messages = null,
        public ?string $id = null,
        public string $class = '',
    ) {
        $this->explicitMessages = match (true) {
            $messages === null => null,
            is_string($messages) => [$messages],
            default => $messages,
        };
    }

    public function render()
    {
        return view('hotwire::component-views.error');
    }

    public function data(): array
    {
        $data = parent::data();

        foreach (['name', 'errorKey', 'id'] as $key) {
            if (($data[$key] ?? null) === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
