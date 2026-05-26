<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class CheckboxGroup extends Component
{
    use StripsNullProps;

    /** @param  array<int|string, string>  $options */
    public function __construct(
        public ?string $name = null,
        public array $options = [],
        public array $selected = [],
        public bool $selectAll = false,
        public ?string $selectAllLabel = null,
        public string $class = '',
        public bool $old = true,
        public ?string $id = null,
        public ?string $errorKey = null,
    ) {
        if ($options !== [] && array_keys($options) === range(0, count($options) - 1)) {
            $this->options = array_combine($options, $options);
        }
    }

    public function render()
    {
        return view('hotwire::component-views.checkbox-group');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = ['data-checkbox-select-all-'];
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';

        if ($hasName && ! str_ends_with($name, '[]')) {
            if (config('app.debug', false) && ! app()->environment('testing')) {
                trigger_error(
                    "<x-hwc::checkbox-group name=\"{$name}\">: appended [] for array submission. Use name=\"{$name}[]\" explicitly to silence this notice.",
                    E_USER_NOTICE
                );
            }
            $name = $name.'[]';
        }

        $baseId = $id ?: ($hasName ? FieldKey::toId($name) : null);

        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $baseId ? $baseId.'-error' : '';

        $resolvedSelected = $this->old && $resolvedErrorKey !== ''
            ? old($resolvedErrorKey, $this->selected)
            : $this->selected;

        if (! is_array($resolvedSelected)) {
            $resolvedSelected = $resolvedSelected !== null ? [$resolvedSelected] : [];
        }

        $userController = trim($attributes->get('data-controller', ''));

        $wrapperController = $this->selectAll
            ? trim(implode(' ', array_filter([$userController, 'checkbox-select-all'])))
            : $userController;

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        return [
            'name' => $name,
            'baseId' => $baseId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'resolvedSelected' => $resolvedSelected,
            'wrapperController' => $wrapperController,
            'hasErrors' => $hasErrors,
        ];
    }
}
