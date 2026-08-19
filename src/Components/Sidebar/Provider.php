<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Provider extends Component
{
    public string $sidebarIdentifier;

    public string $sidebarState;

    public bool $resolvedOpen;

    public function __construct(
        public ?bool $defaultOpen = null,
        public string $width = '16rem',
        public string $mobileWidth = '18rem',
        public string $iconWidth = '3rem',
        public string $cookieName = 'sidebar_state',
        public string $controller = 'sidebar',
        public ?Htmlable $stimulus = null,
    ) {
        $this->sidebarIdentifier = $controller;
        $this->resolvedOpen = $this->resolveOpen();
        $this->sidebarState = $this->resolvedOpen ? 'expanded' : 'collapsed';
    }

    public function render()
    {
        return view('hotwire::component-views.sidebar-provider');
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
        return [
            'providerAttributes' => StimulusAttributes::merge([
                'data-slot' => 'sidebar-wrapper',
                'data-controller' => $this->sidebarIdentifier,
                'data-state' => $this->sidebarState,
                "data-{$this->sidebarIdentifier}-open-value" => $this->resolvedOpen ? 'true' : 'false',
                "data-{$this->sidebarIdentifier}-cookie-name-value" => $this->cookieName,
                "data-{$this->sidebarIdentifier}-lock-scroll-class" => 'overflow-hidden',
                'data-action' => "keydown@window->{$this->sidebarIdentifier}#shortcut turbo:before-cache@window->{$this->sidebarIdentifier}#closeForCache turbo:before-render@window->{$this->sidebarIdentifier}#preserveStateForRender",
                'style' => "--sidebar-width: {$this->width}; --sidebar-width-mobile: {$this->mobileWidth}; --sidebar-width-icon: {$this->iconWidth}",
            ], $attributes, $this->stimulus, protectedPrefixes: ["data-{$this->sidebarIdentifier}-open-"]),
        ];
    }

    private function resolveOpen(): bool
    {
        if ($this->defaultOpen !== null) {
            return $this->defaultOpen;
        }

        $value = request()->cookie($this->cookieName);
        if (! is_string($value)) {
            $value = $this->rawCookieValue($this->cookieName);
        }

        return match ($value) {
            'false' => false,
            'true' => true,
            default => true,
        };
    }

    private function rawCookieValue(string $name): ?string
    {
        $header = request()->headers->get('cookie') ?? request()->server('HTTP_COOKIE');
        if (! is_string($header) || $header === '') {
            return null;
        }

        foreach (explode(';', $header) as $cookie) {
            [$cookieName, $cookieValue] = array_pad(explode('=', trim($cookie), 2), 2, null);
            if ($cookieName === $name && is_string($cookieValue)) {
                return rawurldecode($cookieValue);
            }
        }

        return null;
    }
}
