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
        public bool|string $lazy = false,
        public bool|string|null $action = null,
        public bool|string $advance = false,
        public bool|string $replace = false,
        public bool|string $poll = false,
        public ?int $pollInterval = null,
        public bool|string $viewTransition = false,
        public bool|string $preserveScroll = false,
    ) {
        $this->frameId = trim(is_object($id) ? dom_id($id) : $id);

        if ($this->frameId === '') {
            throw new InvalidArgumentException('The id prop must be a non-empty string or an object resolvable via dom_id().');
        }

        $this->src = $this->normalizeOptional($this->src);
        $this->loading = $this->normalizeOptional($this->loading);
        $this->target = $this->normalizeOptional($this->target);
        $this->autoscroll = $this->normalizeBooleanAttribute($this->autoscroll);
        $this->action = $this->normalizeOptionalAction($this->action);
        $this->lazy = $this->normalizeBooleanAlias($this->lazy);
        $this->advance = $this->normalizeBooleanAlias($this->advance);
        $this->replace = $this->normalizeBooleanAlias($this->replace);
        $this->poll = $this->normalizeBooleanAlias($this->poll);
        $this->viewTransition = $this->normalizeBooleanAlias($this->viewTransition);
        $this->preserveScroll = $this->normalizeBooleanAlias($this->preserveScroll);
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
        $protectedPrefixes = $this->poll ? ['data-turbo--polling-'] : [];

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
                'data-controller' => $this->resolvedController() ?: null,
                'data-turbo--polling-timeout-value' => $this->poll ? $this->pollInterval : null,
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
                'poll',
                'poll-interval',
                'view-transition',
                'preserve-scroll',
            ], protectedPrefixes: $protectedPrefixes),
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

    private function resolvedController(): string
    {
        return trim(implode(' ', array_filter([
            $this->poll ? 'turbo--polling' : null,
            $this->viewTransition ? 'turbo--view-transition' : null,
            $this->preserveScroll ? 'turbo--preserve-scroll' : null,
        ])));
    }

    private function normalizeOptional(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeBooleanAttribute(bool|string|null $value): bool|string|null
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, ['false', '0', 'off', 'no'], true) ? null : $value;
    }

    private function normalizeBooleanAlias(bool|string $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return ! in_array(strtolower(trim($value)), ['false', '0', 'off', 'no'], true);
    }

    private function normalizeOptionalAction(bool|string|null $value): ?string
    {
        if ($value === false || $value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' || in_array(strtolower($value), ['false', '0', 'off', 'no'], true) ? null : $value;
    }
}
