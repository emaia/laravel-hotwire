<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\Component;
use InvalidArgumentException;

class FrameOrPage extends Component
{
    public string $frameId;

    /** @var list<string> */
    public array $frameIds;

    public ?string $activeFrameId;

    public ?string $layoutComponent;

    /** @param iterable<array-key, string|object>|null $frames */
    public function __construct(
        public string|object|null $frame = null,
        public ?string $layout = null,
        public ?iterable $frames = null,
    ) {
        if (($frame === null) === ($frames === null)) {
            throw new InvalidArgumentException('Exactly one of the frame or frames props must be provided.');
        }

        $frames = $frames === null ? null : (is_array($frames) ? $frames : iterator_to_array($frames));
        $this->frames = $frames;
        $layout = $layout !== null && trim($layout) === '' ? null : $layout;
        $this->layout = $layout;

        if ($frames !== null && ($frames === [] || ! array_is_list($frames))) {
            throw new InvalidArgumentException('The frames prop must be a non-empty list of strings or objects resolvable via dom_id().');
        }

        $this->frameIds = array_map($this->resolveFrameId(...), $frames ?? [$frame]);
        $this->frameId = $this->frameIds[0];

        if (count($this->frameIds) > 1 && $layout === null) {
            throw new InvalidArgumentException('The layout prop is required when more than one frame is configured.');
        }

        $this->layoutComponent = $this->resolveLayoutComponent($layout);
        $requestedFrame = request()->turboFrameId();
        $this->activeFrameId = $requestedFrame !== null && in_array($requestedFrame, $this->frameIds, true)
            ? $requestedFrame
            : ($layout === null ? $this->frameId : null);
    }

    public function render()
    {
        return view('hotwire::component-views.frame-or-page');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return [
            ...parent::data(),
            'frameOrPageContext' => $this,
        ];
    }

    private function resolveFrameId(mixed $frame): string
    {
        if (! is_string($frame) && ! is_object($frame)) {
            throw new InvalidArgumentException('The frames prop must be a non-empty list of strings or objects resolvable via dom_id().');
        }

        $frameId = is_object($frame) ? dom_id($frame) : $frame;

        if (trim($frameId) === '') {
            if ($this->frames === null) {
                throw new InvalidArgumentException('The frame prop must be a non-empty string or an object resolvable via dom_id().');
            }

            throw new InvalidArgumentException('The frames prop must contain non-empty strings or objects resolvable via dom_id().');
        }

        return $frameId;
    }

    private function resolveLayoutComponent(?string $layout): ?string
    {
        if ($layout === null || str_contains($layout, '.') || str_contains($layout, '::') || str_contains($layout, '\\')) {
            return $layout;
        }

        if ($this->componentExists($layout)) {
            return $layout;
        }

        $candidate = 'layouts.'.$layout;

        if ($this->componentExists($candidate)) {
            return $candidate;
        }

        return $layout;
    }

    private function componentTagCompiler(): ComponentTagCompiler
    {
        /** @var BladeCompiler $compiler */
        $compiler = Blade::getFacadeRoot();

        return new ComponentTagCompiler(
            $compiler->getClassComponentAliases(),
            $compiler->getClassComponentNamespaces(),
            $compiler,
        );
    }

    private function componentExists(string $component): bool
    {
        try {
            $this->componentTagCompiler()->componentClass($component);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
