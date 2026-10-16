<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\OverlayReturnRegion;
use Emaia\LaravelHotwire\Support\SameOriginUrl;
use InvalidArgumentException;

class OverlayReturn extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'overlay-return', 'kind' => 'structural'],
    ];

    /**
     * @param  list<string>  $fields  Enabled scalar controls of the opening form sent to the renderer as context.
     */
    public function __construct(
        public string $name,
        public ?string $src = null,
        public array $fields = [],
    ) {
        OverlayReturnRegion::name($name);
        if ($src !== null) {
            SameOriginUrl::assert($src, 'Overlay return renderer');
        }
        if ($fields !== [] && $src === null) {
            throw new InvalidArgumentException('Overlay return fields are only sent to a renderer; pass src as well.');
        }
        foreach ($fields as $field) {
            if (trim($field) === '' || preg_match('/\s/', $field)) {
                throw new InvalidArgumentException('Overlay return fields must be control names without whitespace.');
            }
        }
    }

    public function render()
    {
        return view('hotwire::component-views.overlay-return', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
