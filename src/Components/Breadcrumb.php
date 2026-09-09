<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Stringable;

class Breadcrumb extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'breadcrumb', 'kind' => 'visual'],
        'list' => ['name' => 'breadcrumb-list', 'kind' => 'visual'],
        'item' => ['name' => 'breadcrumb-item', 'kind' => 'visual'],
        'link' => ['name' => 'breadcrumb-link', 'kind' => 'visual'],
        'page' => ['name' => 'breadcrumb-page', 'kind' => 'visual'],
        'separator' => ['name' => 'breadcrumb-separator', 'kind' => 'visual'],
        'ellipsis' => ['name' => 'breadcrumb-ellipsis', 'kind' => 'visual'],
    ];

    /**
     * @param  array<int, array{label: string|int|Stringable|Htmlable, href?: string|Stringable|null, current?: bool, type?: 'item', frame?: string|object|bool|null}|array{type: 'ellipsis', label?: string|int|Stringable}>  $items
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
        return view('hotwire::component-views.breadcrumb', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }

    /** Reject the ambiguous combination of generated items and manual slot composition. */
    public function guardComposition(mixed $slot): void
    {
        if ($this->items !== [] && trim((string) $slot) !== '') {
            throw new InvalidArgumentException('Breadcrumb cannot combine [items] with slot composition. Use one or the other.');
        }
    }

    /**
     * @return array<int, array{label: string|int|Stringable|Htmlable, href: string|null, current: bool, type: 'item'|'ellipsis', frame: string|null}>
     */
    public function normalizedItems(): array
    {
        $items = array_values($this->items);
        $lastIndex = array_key_last($items);

        return array_map(function (array $item, int $index) use ($lastIndex): array {
            $type = $item['type'] ?? 'item';
            $href = $this->normalizeHref($item);
            $current = $this->isCurrentItem($item, $index, $lastIndex, $href, $type);

            return [
                'label' => $type === 'ellipsis'
                    ? (string) ($item['label'] ?? $this->ellipsisLabel)
                    : $item['label'],
                'href' => $href,
                'current' => $current,
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
        $items = array_values($items);
        $lastIndex = array_key_last($items);

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Breadcrumb items must be item descriptor arrays.');
            }

            $unsupportedKeys = array_values(array_diff(array_keys($item), ['label', 'href', 'current', 'type', 'frame']));

            if ($unsupportedKeys !== []) {
                throw new InvalidArgumentException(sprintf('Breadcrumb item contains unsupported key [%s].', $unsupportedKeys[0]));
            }

            $type = $item['type'] ?? 'item';

            if (! is_string($type) || ! in_array($type, ['item', 'ellipsis'], true)) {
                throw new InvalidArgumentException('Breadcrumb item [type] must be item or ellipsis.');
            }

            if ($type === 'ellipsis') {
                foreach (['href', 'current', 'frame'] as $unsupportedKey) {
                    if (array_key_exists($unsupportedKey, $item)) {
                        throw new InvalidArgumentException(sprintf('Breadcrumb ellipsis items do not support [%s].', $unsupportedKey));
                    }
                }
            }

            if ($type !== 'ellipsis' && ! array_key_exists('label', $item)) {
                throw new InvalidArgumentException('Breadcrumb items must define [label].');
            }

            if (array_key_exists('label', $item) && ! $this->isItemContent($item['label'])) {
                throw new InvalidArgumentException('Breadcrumb item [label] must be a string, integer, Stringable or Htmlable.');
            }

            if ($type === 'ellipsis'
                && array_key_exists('label', $item)
                && ! is_string($item['label'])
                && ! is_int($item['label'])
                && ! $item['label'] instanceof Stringable) {
                throw new InvalidArgumentException('Breadcrumb ellipsis [label] must be a string, integer or Stringable.');
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

            if (array_key_exists('frame', $item)) {
                if (! is_string($item['frame'])
                    && ! is_object($item['frame'])
                    && ! is_bool($item['frame'])
                    && $item['frame'] !== null) {
                    throw new InvalidArgumentException('Breadcrumb item [frame] must be a string, object, boolean or null.');
                }

                FrameTarget::normalize($item['frame']);
            }

            $href = $this->normalizeHref($item);
            $current = $this->isCurrentItem($item, $index, $lastIndex, $href, $type);

            if ($type !== 'ellipsis' && $href === null && ! $current) {
                throw new InvalidArgumentException('Breadcrumb non-current items must define [href].');
            }

            if ($current) {
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

    /** @param  array<string, mixed>  $item */
    private function isCurrentItem(array $item, int $index, ?int $lastIndex, ?string $href, string $type): bool
    {
        return $type !== 'ellipsis'
            && (bool) ($item['current'] ?? ($index === $lastIndex && $href === null));
    }

    private function isItemContent(mixed $content): bool
    {
        return is_string($content)
            || is_int($content)
            || $content instanceof Stringable
            || $content instanceof Htmlable;
    }
}
