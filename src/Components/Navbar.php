<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Stringable;

class Navbar extends Component
{
    /**
     * @param  array<int, array{label: string|int|Stringable|Htmlable, href?: string|Stringable|null, current?: bool, disabled?: bool, as?: string|null, type?: string, frame?: string|object|bool|null}>  $items
     */
    public function __construct(
        public string $variant = 'line',
        public string $orientation = 'horizontal',
        public string $overflow = 'scroll',
        public bool $sticky = false,
        public string $stickySide = 'top',
        public string|int|float $stickyOffset = 0,
        public array $items = [],
    ) {
        $this->guardItems($items);
        $this->orientation = in_array($this->orientation, ['horizontal', 'vertical'], true) ? $this->orientation : 'horizontal';
        $this->stickySide = in_array($this->stickySide, ['top', 'bottom'], true) ? $this->stickySide : 'top';
    }

    public function render()
    {
        return view('hotwire::component-views.navbar');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['navbarItems'] = $this->items;
        unset($data['items']);

        return $data;
    }

    /** @param  array<int, mixed>  $items */
    private function guardItems(array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item) || ! array_key_exists('label', $item)) {
                throw new InvalidArgumentException('Navbar items must define [label].');
            }

            if (! is_string($item['label'])
                && ! is_int($item['label'])
                && ! $item['label'] instanceof Stringable
                && ! $item['label'] instanceof Htmlable) {
                throw new InvalidArgumentException('Navbar item [label] must be a string, integer, Stringable or Htmlable.');
            }

            if (array_key_exists('href', $item)
                && ! is_string($item['href'])
                && ! $item['href'] instanceof Stringable
                && $item['href'] !== null) {
                throw new InvalidArgumentException('Navbar item [href] must be a string, Stringable or null.');
            }

            if ((array_key_exists('current', $item) && ! is_bool($item['current']))
                || (array_key_exists('disabled', $item) && ! is_bool($item['disabled']))) {
                throw new InvalidArgumentException('Navbar item [current] and [disabled] must be booleans.');
            }

            if (array_key_exists('as', $item) && ! is_string($item['as']) && $item['as'] !== null) {
                throw new InvalidArgumentException('Navbar item [as] must be a string or null.');
            }

            if (array_key_exists('type', $item) && ! is_string($item['type'])) {
                throw new InvalidArgumentException('Navbar item [type] must be a string.');
            }

            if (array_key_exists('frame', $item)
                && ! is_string($item['frame'])
                && ! is_object($item['frame'])
                && ! is_bool($item['frame'])
                && $item['frame'] !== null) {
                throw new InvalidArgumentException('Navbar item [frame] must be a string, object, boolean or null.');
            }
        }
    }
}
