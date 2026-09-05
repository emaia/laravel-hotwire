<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Stringable;

class Breadcrumb extends Component
{
    /**
     * @param  array<int, array{label?: string|int|Stringable|Htmlable, href?: string|Stringable|null, current?: bool, type?: string, frame?: string|object|bool|null}>  $items
     */
    public function __construct(
        public string $label = 'Breadcrumb',
        public array $items = [],
        public string $ellipsisLabel = 'More pages',
        public string|object|bool|null $frame = null,
    ) {
        $this->guardItems($items);

        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.breadcrumb');
    }

    /** Reject the ambiguous combination of generated items and manual slot composition. */
    public function guardComposition(mixed $slot): void
    {
        if ($this->items !== [] && trim((string) $slot) !== '') {
            throw new InvalidArgumentException('Breadcrumb cannot combine [items] with slot composition. Use one or the other.');
        }
    }

    /**
     * @return array<int, array{label: mixed, href: string|null, current: bool, type: string, frame: string|null}>
     */
    public function normalizedItems(): array
    {
        $items = array_values($this->items);
        $lastIndex = array_key_last($items);

        return array_map(function (array $item, int $index) use ($lastIndex): array {
            $type = $item['type'] ?? 'item';
            $href = $this->normalizeHref($item);

            return [
                'label' => $type === 'ellipsis'
                    ? ($item['label'] ?? $this->ellipsisLabel)
                    : $item['label'],
                'href' => $href,
                'current' => (bool) ($item['current'] ?? ($index === $lastIndex && $href === null && $type !== 'ellipsis')),
                'type' => $type,
                'frame' => array_key_exists('frame', $item)
                    ? FrameTarget::normalize($item['frame'])
                    : $this->frame,
            ];
        }, $items, array_keys($items));
    }

    /** @param  array<int, mixed>  $items */
    private function guardItems(array $items): void
    {
        $pages = 0;

        foreach (array_values($items) as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Breadcrumb items must be item descriptor arrays.');
            }

            $type = $item['type'] ?? 'item';

            if (! is_string($type) || ! in_array($type, ['item', 'ellipsis'], true)) {
                throw new InvalidArgumentException('Breadcrumb item [type] must be item or ellipsis.');
            }

            if ($type !== 'ellipsis' && ! array_key_exists('label', $item)) {
                throw new InvalidArgumentException('Breadcrumb items must define [label].');
            }

            if (array_key_exists('label', $item) && ! $this->isItemContent($item['label'])) {
                throw new InvalidArgumentException('Breadcrumb item [label] must be a string, integer, Stringable or Htmlable.');
            }

            if (array_key_exists('href', $item)
                && ! is_string($item['href'])
                && ! $item['href'] instanceof Stringable
                && $item['href'] !== null) {
                throw new InvalidArgumentException('Breadcrumb item [href] must be a string, Stringable or null.');
            }

            if (array_key_exists('current', $item) && ! is_bool($item['current'])) {
                throw new InvalidArgumentException('Breadcrumb item [current] must be a boolean.');
            }

            if ($type !== 'ellipsis' && (($item['current'] ?? false) || $this->normalizeHref($item) === null)) {
                $pages++;
            }
        }

        if ($pages > 1) {
            throw new InvalidArgumentException('Breadcrumb items resolve more than one current page. Give intermediate items an [href] or compose the trail manually.');
        }
    }

    /** @param  array<string, mixed>  $item */
    private function normalizeHref(array $item): ?string
    {
        $href = $item['href'] ?? null;

        return $href === null ? null : (string) $href;
    }

    private function isItemContent(mixed $content): bool
    {
        return is_string($content)
            || is_int($content)
            || $content instanceof Stringable
            || $content instanceof Htmlable;
    }
}
