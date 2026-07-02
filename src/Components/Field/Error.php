<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;

class Error extends Component
{
    use StripsNullProps;

    /** @var string[]|null */
    public ?array $explicitMessages;

    /** @param  array<int, string>|string|null  $messages */
    public function __construct(
        public ?string $name = null,
        public ?string $errorKey = null,
        array|string|null $messages = null,
        public ?string $id = null,
        public string $class = '',
    ) {
        $this->explicitMessages = match (true) {
            $messages === null => null,
            is_string($messages) => [$messages],
            default => $messages,
        };
    }

    public function render()
    {
        return view('hotwire::component-views.field-error');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'errorKey', 'id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $errorKey,
        ?string $id,
        ViewErrorBag $errorsBag,
    ): array {
        $hasName = $name !== null && $name !== '';

        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : null);
        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name).'-error' : 'hwc-error-'.uniqid());

        $messages = $this->explicitMessages ?? ($resolvedErrorKey ? $errorsBag->get($resolvedErrorKey) : []);
        $isEmpty = empty($messages);

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'messages' => $messages,
            'isEmpty' => $isEmpty,
        ];
    }
}
