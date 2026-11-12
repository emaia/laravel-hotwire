<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;

class InputOtp extends BaseComponent
{
    use StripsNullProps;

    public const array SLOTS = [
        'root' => ['name' => 'input-otp', 'kind' => 'visual'],
        'input' => ['name' => 'input-otp-input', 'kind' => 'visual'],
        'slots' => ['name' => 'input-otp-slots', 'kind' => 'structural'],
        'slot' => ['name' => 'input-otp-slot', 'kind' => 'visual'],
    ];

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public ?string $value = null,
        public int $length = 6,
        public ?string $errorKey = null,
        public bool $old = false,
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
        public string $class = '',
        public string $wrapperClass = '',
        public ?Htmlable $stimulus = null,
    ) {
        if ($length < 1 || $length > 32) {
            throw new InvalidArgumentException('Input OTP length must be between 1 and 32.');
        }
    }

    /** Render the native control and its decorative segments. */
    public function render(): View
    {
        return view('hotwire::component-views.input-otp', [
            'slotNames' => array_map(static fn (array $slot): string => $slot['name'], self::SLOTS),
        ]);
    }

    /** Expose field-aware resolution without replacing inherited Field props with null. */
    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);
        $data['internalPrefixes'] = ['data-input-otp-'];
        if (AutoSubmit::enabled($this->autoSubmit)) {
            $data['internalPrefixes'][] = 'data-auto-submit-';
        }
        $data['completionAction'] = AutoSubmit::action($this->autoSubmit, 'input-otp:complete', 'submit');
        $data['autoSubmitDelayParam'] = AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'submit');

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /** @return array<string, mixed> */
    private function computeResolved(?string $name, ?string $id, ?string $errorKey, ViewErrorBag $errorsBag): array
    {
        $resolvedId = $id ?: ($name ? FieldKey::toId($name) : app(ComponentId::class)->next('hw-input-otp'));
        $resolvedErrorKey = $errorKey ?: ($name ? FieldKey::toErrorKey($name) : '');
        $value = $this->old && $resolvedErrorKey !== '' ? old($resolvedErrorKey, $this->value) : $this->value;

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'resolvedValue' => is_scalar($value) ? (string) $value : null,
            'errorId' => $resolvedId.'-error',
            'hasErrors' => $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey),
        ];
    }
}
