<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Breadcrumb extends Component
{
    /**
     * @param  array<int, array{label?: mixed, href?: string|null, current?: bool, type?: string}>|null  $items
     */
    public function __construct(
        public string $label = 'Breadcrumb',
        public ?array $items = null,
        public string $ellipsisLabel = 'More pages',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.breadcrumb');
    }

    public function hasItems(): bool
    {
        return is_array($this->items) && $this->items !== [];
    }

    /**
     * @return array<int, array{label: mixed, href: string|null, current: bool, type: string}>
     */
    public function normalizedItems(): array
    {
        $items = array_values($this->items ?? []);
        $lastIndex = array_key_last($items);

        return array_map(function (array $item, int $index) use ($lastIndex): array {
            $type = (string) ($item['type'] ?? 'item');
            $href = $item['href'] ?? null;

            return [
                'label' => $item['label'] ?? ($type === 'ellipsis' ? $this->ellipsisLabel : ''),
                'href' => is_string($href) ? $href : null,
                'current' => (bool) ($item['current'] ?? ($index === $lastIndex && $href === null && $type !== 'ellipsis')),
                'type' => $type,
            ];
        }, $items, array_keys($items));
    }
}
