<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

class Frame extends Component
{
    public string $frameId;

    public function __construct(
        public string|object $id,
        public ?string $src = null,
        public ?string $loading = null,
        public ?string $target = null,
        public bool|string|null $autoscroll = null,
        public bool $lazy = false,
        public ?string $action = null,
        public bool $advance = false,
        public bool $replace = false,
    ) {
        $this->frameId = is_object($id) ? dom_id($id) : $id;

        if (trim($this->frameId) === '') {
            throw new InvalidArgumentException('The id prop must be a non-empty string or an object resolvable via dom_id().');
        }

        if ($this->loading === '') {
            $this->loading = null;
        }

        if ($this->action === '') {
            $this->action = null;
        }
    }

    public function render()
    {
        return view('hotwire::component-views.frame');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /** @return array<string, mixed> */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        $hasRawAction = $attributes->has('data-turbo-action') && $attributes->get('data-turbo-action') !== false;

        if ($this->advance && $this->replace && $this->action === null && ! $hasRawAction) {
            throw new InvalidArgumentException('The advance and replace props cannot be used together unless action or data-turbo-action is set.');
        }

        return [
            'frameAttributes' => StimulusAttributes::merge([
                'id' => $this->frameId,
                'src' => $this->src,
                'loading' => $this->resolvedLoading(),
                'target' => $this->target,
                'autoscroll' => $this->autoscroll,
                'data-turbo-action' => $this->resolvedAction(),
            ], $attributes, except: [
                'id',
                'src',
                'loading',
                'target',
                'autoscroll',
                'lazy',
                'action',
                'advance',
                'replace',
            ]),
        ];
    }

    private function resolvedLoading(): ?string
    {
        return $this->loading ?? ($this->lazy ? 'lazy' : null);
    }

    private function resolvedAction(): ?string
    {
        return $this->action ?? match (true) {
            $this->advance => 'advance',
            $this->replace => 'replace',
            default => null,
        };
    }
}
