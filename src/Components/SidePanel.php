<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Emaia\LaravelHotwire\Support\StimulusIdentifier;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;

class SidePanel extends Component
{
    private const SIDES = ['left', 'right'];

    public string $cookieName;

    public string $sidePanelIdentifier;

    public string $sidePanelPanelId;

    public bool $resolvedOpen;

    public string $sidePanelState;

    public function __construct(
        public string $name,
        ?string $panelId = null,
        public ?bool $defaultOpen = null,
        public string $width = '16rem',
        public string $side = 'left',
        public bool $persist = true,
        public string $controller = 'side-panel',
        public ?Htmlable $stimulus = null,
    ) {
        StimulusIdentifier::guard($controller, 'side-panel');

        $key = $this->key($name);
        if (! in_array($this->side, self::SIDES, true)) {
            throw new \InvalidArgumentException('Side Panel side must be one of: '.implode(', ', self::SIDES).". Got: {$this->side}");
        }

        $this->cookieName = "side_panel_{$key}_state";
        $this->sidePanelIdentifier = $controller;
        $this->sidePanelPanelId = $panelId !== null && $panelId !== '' ? $panelId : "{$key}-panel";
        $this->resolvedOpen = $this->resolveOpen();
        $this->sidePanelState = $this->resolvedOpen ? 'expanded' : 'collapsed';
    }

    public function render()
    {
        return view('hotwire::component-views.side-panel');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /** @return array<string, mixed> */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        $style = trim("--side-panel-width: {$this->width}; ".(string) $attributes->get('style'));

        return [
            'sidePanelAttributes' => StimulusAttributes::merge([
                'data-slot' => 'side-panel',
                'data-controller' => $this->sidePanelIdentifier,
                'data-state' => $this->sidePanelState,
                'data-side' => $this->side,
                "data-{$this->sidePanelIdentifier}-name-value" => $this->name,
                "data-{$this->sidePanelIdentifier}-open-value" => $this->resolvedOpen ? 'true' : 'false',
                "data-{$this->sidePanelIdentifier}-persist-value" => $this->persist ? 'true' : 'false',
                "data-{$this->sidePanelIdentifier}-cookie-name-value" => $this->cookieName,
                'data-action' => "turbo:before-render@window->{$this->sidePanelIdentifier}#preserveStateForRender",
                'style' => $style,
            ], $attributes, $this->stimulus, except: [
                'data-slot',
                'data-state',
                'data-side',
                "data-{$this->sidePanelIdentifier}-name-value",
                "data-{$this->sidePanelIdentifier}-open-value",
                "data-{$this->sidePanelIdentifier}-persist-value",
                "data-{$this->sidePanelIdentifier}-cookie-name-value",
                'style',
            ]),
        ];
    }

    private function key(string $name): string
    {
        $key = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $name), '-');

        return $key !== '' ? $key : substr(md5($name), 0, 8);
    }

    private function resolveOpen(): bool
    {
        if ($this->defaultOpen !== null) {
            return $this->defaultOpen;
        }

        if (! $this->persist) {
            return true;
        }

        // Laravel drops unencrypted package cookies, so read the raw header as a fallback.
        $value = request()->cookie($this->cookieName);
        if (! is_string($value)) {
            $value = $this->rawCookieValue();
        }

        return $value !== 'false';
    }

    private function rawCookieValue(): ?string
    {
        $header = request()->headers->get('cookie') ?? request()->server('HTTP_COOKIE');
        if (! is_string($header) || $header === '') {
            return null;
        }

        foreach (explode(';', $header) as $cookie) {
            [$name, $value] = array_pad(explode('=', trim($cookie), 2), 2, null);
            if ($name === $this->cookieName && is_string($value)) {
                return rawurldecode($value);
            }
        }

        return null;
    }
}
