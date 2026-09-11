<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use InvalidArgumentException;

class Textarea extends Component
{
    use StripsNullProps;

    public const array SLOTS = [
        'wrapper' => ['name' => 'textarea-wrapper', 'kind' => 'visual'],
        'root' => ['name' => 'textarea', 'kind' => 'visual'],
        'counter' => ['name' => 'textarea-counter', 'kind' => 'visual'],
    ];

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public mixed $value = null,
        public ?string $errorKey = null,
        public bool $old = true,
        public bool $autoResize = false,
        public ?int $counter = null,
        public bool $countdown = false,
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
        public string $class = '',
        public string $wrapperClass = '',
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.textarea', [
            'wrapperSlotName' => self::SLOTS['wrapper']['name'],
            'slotName' => self::SLOTS['root']['name'],
            'counterSlotName' => self::SLOTS['counter']['name'],
        ]);
    }

    public function data(): array
    {
        $data = parent::data();
        $data['needsWrapper'] = $this->counter !== null;
        $data['internalPrefixes'] = array_values(array_filter([
            $this->counter !== null ? 'data-char-counter-' : null,
            $this->autoResize ? 'data-auto-resize-' : null,
            AutoSubmit::enabled($this->autoSubmit) ? 'data-auto-submit-' : null,
        ]));
        $data['compute'] = $this->computeResolved(...);
        $data['guardCounter'] = $this->guardCounter(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    private function guardCounter(mixed $counter, ?ComponentSlot $counterSlot): ?int
    {
        if ($counter !== null && ! is_int($counter)) {
            throw new InvalidArgumentException(
                'Textarea [counter] must be an integer or null. Use <x-slot:counter-slot> to customize counter content.'
            );
        }

        if ($counterSlot !== null && $counter === null) {
            throw new InvalidArgumentException('Textarea [counter-slot] requires the [counter] prop.');
        }

        return $counter;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        bool $required,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';

        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : app(ComponentId::class)->next('hw-textarea'));
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $resolvedId.'-error';

        $resolvedValue = ($this->old && $resolvedErrorKey !== '')
            ? old($resolvedErrorKey, $this->value)
            : $this->value;

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = $attributes->has('required')
            ? $attributes->get('required') !== false
            : $required;

        $elementController = trim(implode(' ', array_filter([
            $this->autoResize ? 'auto-resize' : null,
        ])));

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'resolvedValue' => $resolvedValue,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'elementController' => $elementController,
            'elementAction' => AutoSubmit::action($this->autoSubmit, 'input', 'debounced'),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'debounced'),
        ];
    }
}
