<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\View\Component;

class Label extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $for = null,
        public ?string $name = null,
        public ?string $value = null,
        public ?bool $required = null,
        public string $requiredLabel = '*',
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.label');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'for', 'required']);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $id,
        mixed $slot,
    ): array {
        $slotHtml = (string) $slot;
        $slotWrapsControl = preg_match('/<(input|select|textarea)\b/i', $slotHtml) === 1;

        if ($this->for !== null) {
            $resolvedFor = $this->for;
        } elseif ($slotWrapsControl) {
            $resolvedFor = null;
        } else {
            $resolvedFor = $id ?? ($name ? FieldKey::toId($name) : null);
        }

        return [
            'resolvedFor' => $resolvedFor,
            'slotHtml' => $slotHtml,
        ];
    }
}
