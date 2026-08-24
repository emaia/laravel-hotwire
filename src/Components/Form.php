<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Form extends Component
{
    public function __construct(
        public bool $autoSubmit = false,
        public bool $unsavedChanges = false,
        public bool $errorScroll = false,
        public bool $cleanQueryParams = false,
        public bool $conditionalFields = false,
        public bool $trackFrameSrc = false,
        public int|string|null $autoSubmitDelay = null,
        public string|object|bool|null $frame = null,
        public ?string $enctype = null,
        public mixed $state = null,
        public ?Htmlable $stimulus = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.form');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();
        $data['formRoot'] = $this;
        $data['conditionalFieldState'] = $this->state;
        $data['compute'] = $this->computeResolved(...);

        unset(
            $data['autoSubmit'],
            $data['unsavedChanges'],
            $data['errorScroll'],
            $data['cleanQueryParams'],
            $data['conditionalFields'],
            $data['trackFrameSrc'],
            $data['autoSubmitDelay'],
            $data['frame'],
            $data['enctype'],
            $data['state'],
            $data['stimulus'],
        );

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        $method = strtolower($attributes->get('method', 'post'));
        $isSpoofMethod = in_array($method, ['put', 'patch', 'delete']);

        $controller = trim(implode(' ', array_filter([
            $this->autoSubmit ? 'auto-submit' : null,
            $this->unsavedChanges ? 'unsaved-changes' : null,
            $this->errorScroll ? 'error-scroll' : null,
            $this->cleanQueryParams ? 'clean-query-params' : null,
            $this->conditionalFields ? 'conditional-fields' : null,
        ])));

        return [
            'controller' => $controller,
            'method' => $method,
            'isSpoofMethod' => $isSpoofMethod,
        ];
    }
}
