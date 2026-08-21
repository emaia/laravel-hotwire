<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Emaia\LaravelHotwire\Support\FieldLabel;
use Illuminate\View\Component;

class Label extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $for = null,
        public ?bool $set = null,
        public ?string $name = null,
        public ?string $value = null,
        public ?bool $required = null,
        public string $requiredLabel = '*',
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.field-label');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'for', 'required', 'set']);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $id,
        mixed $slot,
        bool $labelsSet = false,
    ): array {
        $slotHtml = (string) $slot;
        $slotWrapsControl = preg_match('/<(input|select|textarea)\b/i', $slotHtml) === 1;

        // A set has no single labelable control, so `for` would dangle. The owner names
        // itself with aria-labelledby against the id emitted here instead.
        if ($this->for !== null) {
            $resolvedFor = $this->for;
        } elseif ($slotWrapsControl || $labelsSet) {
            $resolvedFor = null;
        } else {
            $resolvedFor = $id ?? ($name ? FieldKey::toId($name) : null);
        }

        return [
            'resolvedFor' => $resolvedFor,
            'resolvedId' => $labelsSet && $this->for === null ? FieldLabel::idFor($id, $name) : null,
            'slotHtml' => $slotHtml,
        ];
    }
}
