<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class Pagination extends Component
{
    public function __construct(
        public ?LengthAwarePaginator $paginator = null,
        public string $label = 'Pagination',
        public ?string $turboFrame = null,
        public string $previousLabel = 'Previous',
        public string $nextLabel = 'Next',
        public string $ellipsisLabel = 'More pages',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.pagination', [
            'links' => $this->normalizedLinks(),
        ]);
    }

    public function shouldRender(): bool
    {
        return $this->paginator === null || $this->paginator->hasPages();
    }

    /**
     * @return array<int, array{type: string, label: string, url: string|null, active: bool, disabled: bool}>
     */
    public function normalizedLinks(): array
    {
        if ($this->paginator === null) {
            return [];
        }

        $links = $this->paginator->linkCollection()->values();
        $lastIndex = $links->count() - 1;

        return $links->map(function (array $link, int $index) use ($lastIndex): array {
            $url = $link['url'] ?? null;
            $active = (bool) ($link['active'] ?? false);
            $label = (string) ($link['label'] ?? '');

            if ($index === 0) {
                return [
                    'type' => 'previous',
                    'label' => $this->previousLabel,
                    'url' => is_string($url) ? $url : null,
                    'active' => false,
                    'disabled' => ! is_string($url),
                ];
            }

            if ($index === $lastIndex) {
                return [
                    'type' => 'next',
                    'label' => $this->nextLabel,
                    'url' => is_string($url) ? $url : null,
                    'active' => false,
                    'disabled' => ! is_string($url),
                ];
            }

            if (! is_string($url) && $label === '...') {
                return [
                    'type' => 'ellipsis',
                    'label' => $this->ellipsisLabel,
                    'url' => null,
                    'active' => false,
                    'disabled' => true,
                ];
            }

            return [
                'type' => 'page',
                'label' => $label,
                'url' => is_string($url) ? $url : null,
                'active' => $active,
                'disabled' => ! is_string($url) && ! $active,
            ];
        })->all();
    }
}
