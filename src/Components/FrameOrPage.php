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

    public ?string $layoutComponent;

    public function __construct(
        public string|object $frame,
        public ?string $layout = null,
    ) {
        $this->frameId = is_object($frame) ? dom_id($frame) : $frame;

        if (trim($this->frameId) === '') {
            throw new InvalidArgumentException('The frame prop must be a non-empty string or an object resolvable via dom_id().');
        }

        $this->layoutComponent = $this->resolveLayoutComponent($layout);
    }

    public function render()
    {
        return view('hotwire::component-views.frame-or-page');
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
