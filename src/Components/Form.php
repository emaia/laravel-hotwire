<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Form extends Component
{
    public function __construct(
        public bool $autoSubmit = false,
        public bool $unsavedChanges = false,
        public bool $errorScroll = false,
        public bool $cleanQueryParams = false,
        public bool $trackFrameSrc = false,
        public ?string $enctype = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.form');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        $userController = trim($attributes->get('data-controller', ''));
        $method = strtolower($attributes->get('method', 'post'));
        $isSpoofMethod = in_array($method, ['put', 'patch', 'delete']);

        $controller = trim(implode(' ', array_filter([
            $userController,
            $this->autoSubmit ? 'auto-submit' : null,
            $this->unsavedChanges ? 'unsaved-changes' : null,
            $this->errorScroll ? 'error-scroll' : null,
            $this->cleanQueryParams ? 'clean-query-params' : null,
        ])));

        return [
            'controller' => $controller,
            'method' => $method,
            'isSpoofMethod' => $isSpoofMethod,
        ];
    }
}
