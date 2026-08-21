<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class CheckboxGroup extends Component
{
    /** @param array<int|string, string> $options */
    public function __construct(
        public ?string $name = null,
        public array $options = [],
        public array $selected = [],
        public bool $selectAll = false,
        public ?string $selectAllLabel = null,
        public string $orientation = 'vertical',
        public bool $disabled = false,
        public string $class = '',
        public string $wrapperClass = '',
        public string $labelClass = '',
        public bool $old = true,
        public ?string $id = null,
        public ?string $errorKey = null,
        public ?Htmlable $stimulus = null,
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
        public bool $disableIndeterminate = false,
    ) {
        if ($options !== [] && array_keys($options) === range(0, count($options) - 1)) {
            $this->options = array_combine($options, $options);
        }

        $this->orientation = in_array($this->orientation, ['horizontal', 'vertical'], true)
            ? $this->orientation
            : 'vertical';
    }

    public function render()
    {
        return view('hotwire::component-views.checkbox-group');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['checkboxGroupContext'] = true;
        $data['checkboxGroupName'] = $this->name;
        $data['checkboxGroupOptions'] = $this->options;
        $data['checkboxGroupSelected'] = $this->selected;
        $data['checkboxGroupSelectAll'] = $this->selectAll;
        $data['checkboxGroupSelectAllLabel'] = $this->selectAllLabel;
        $data['checkboxGroupOrientation'] = $this->orientation;
        $data['checkboxGroupDisabled'] = $this->disabled;
        $data['checkboxGroupClass'] = $this->class;
        $data['checkboxGroupWrapperClass'] = $this->wrapperClass;
        $data['checkboxGroupLabelClass'] = $this->labelClass;
        $data['checkboxGroupOld'] = $this->old;
        $data['checkboxGroupId'] = $this->id;
        $data['checkboxGroupErrorKey'] = $this->errorKey;
        $data['checkboxGroupStimulus'] = $this->stimulus;
        $data['checkboxGroupAutoSubmit'] = $this->autoSubmit;
        $data['checkboxGroupAutoSubmitDelay'] = $this->autoSubmitDelay;
        $data['checkboxGroupDisableIndeterminate'] = $this->disableIndeterminate;
        $data['internalPrefixes'] = array_values(array_filter([
            $this->selectAll ? 'data-checkbox-select-all-' : null,
            AutoSubmit::enabled($this->autoSubmit) ? 'data-auto-submit-' : null,
        ]));
        $data['compute'] = $this->computeResolved(...);

        unset(
            $data['name'],
            $data['options'],
            $data['selected'],
            $data['selectAll'],
            $data['selectAllLabel'],
            $data['orientation'],
            $data['disabled'],
            $data['class'],
            $data['wrapperClass'],
            $data['labelClass'],
            $data['old'],
            $data['id'],
            $data['errorKey'],
            $data['stimulus'],
            $data['autoSubmit'],
            $data['autoSubmitDelay'],
            $data['disableIndeterminate'],
        );

        return $data;
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
                    "<hw:checkbox-group name=\"$name\">: appended [] for array submission. Use name=\"{$name}[]\" explicitly to silence this notice.",
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

        $wrapperController = $this->selectAll
            ? 'checkbox-select-all'
            : '';

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        return [
            'name' => $name,
            'baseId' => $baseId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'resolvedSelected' => $resolvedSelected,
            'wrapperController' => $wrapperController,
            'hasErrors' => $hasErrors,
            'elementAction' => AutoSubmit::action($this->autoSubmit, 'change', 'submit'),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'submit'),
        ];
    }
}
