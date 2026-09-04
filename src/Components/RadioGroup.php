<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\FieldContext;
use Emaia\LaravelHotwire\Support\FieldKey;
use Emaia\LaravelHotwire\Support\FieldOwnerContext;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;

class RadioGroup extends Component
{
    private FieldOwnerContext $ownerContext;

    /** @param array<int|string, string> $options */
    public function __construct(
        public ?string $name = null,
        public array $options = [],
        public mixed $selected = null,
        public bool $disabled = false,
        public string $orientation = 'vertical',
        public string $class = '',
        public string $wrapperClass = '',
        public string $labelClass = '',
        public bool $old = true,
        public ?string $id = null,
        public ?string $errorKey = null,
        public ?Htmlable $stimulus = null,
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
    ) {
        $this->ownerContext = new FieldOwnerContext;

        if ($options !== [] && array_keys($options) === range(0, count($options) - 1)) {
            $this->options = array_combine($options, $options);
        }

        $this->orientation = in_array($this->orientation, ['horizontal', 'vertical'], true)
            ? $this->orientation
            : 'vertical';
    }

    public function render()
    {
        return view('hotwire::component-views.radio-group');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['radioGroupContext'] = true;
        $data['radioGroupName'] = $this->name;
        $data['radioGroupOptions'] = $this->options;
        $data['radioGroupSelected'] = $this->selected;
        $data['radioGroupDisabled'] = $this->disabled;
        $data['radioGroupOrientation'] = $this->orientation;
        $data['radioGroupClass'] = $this->class;
        $data['radioGroupWrapperClass'] = $this->wrapperClass;
        $data['radioGroupLabelClass'] = $this->labelClass;
        $data['radioGroupOld'] = $this->old;
        $data['radioGroupId'] = $this->id;
        $data['radioGroupErrorKey'] = $this->errorKey;
        $data['radioGroupStimulus'] = $this->stimulus;
        $data['radioGroupAutoSubmit'] = $this->autoSubmit;
        $data['radioGroupAutoSubmitDelay'] = $this->autoSubmitDelay;

        // A selection group owns a name, an id base and an error key, so field.label and
        // field.error nested in it must resolve against the group rather than a Field far
        // above. This is deliberately not the fieldName/fieldId/fieldErrorKey protocol:
        // group items end their fallback chain on those keys, and reusing them here would
        // let an outer group's name leak into a nameless inner group.
        //
        // The three keys always move together so this root blocks another group's owner
        // context. fieldOwner selects this group's values when it has any identity of its
        // own; otherwise field.label and field.error fall back to the separate Field keys.
        // Always a set: there is no single labelable control, so a nested field.label must
        // drop `for` even when the identity itself comes from a surrounding Field.
        $data['fieldOwnerSet'] = true;

        $ownsFieldIdentity = ($this->name !== null && $this->name !== '')
            || ($this->id !== null && $this->id !== '')
            || ($this->errorKey !== null && $this->errorKey !== '');

        $data['fieldOwner'] = $ownsFieldIdentity;
        $data['fieldOwnerName'] = $this->name;
        $fieldContext = FieldContext::consume();
        $data['fieldOwnerId'] = $fieldContext?->selectionId($this->id, $this->name)
            ?? ($this->id ?: ($this->name ? FieldKey::toId($this->name) : null));
        $data['radioGroupId'] = $data['fieldOwnerId'];
        $data['fieldOwnerErrorKey'] = $this->errorKey;
        $data['fieldOwnerContext'] = $this->ownerContext;
        $data['radioGroupFieldContext'] = $fieldContext;
        $data['fieldContext'] = null;
        $data['fieldControlContext'] = null;
        $data['internalPrefixes'] = array_filter([
            AutoSubmit::enabled($this->autoSubmit) ? 'data-auto-submit-' : null,
        ]);
        $data['compute'] = $this->computeResolved(...);

        unset(
            $data['name'],
            $data['options'],
            $data['selected'],
            $data['disabled'],
            $data['orientation'],
            $data['class'],
            $data['wrapperClass'],
            $data['labelClass'],
            $data['old'],
            $data['id'],
            $data['errorKey'],
            $data['stimulus'],
            $data['autoSubmit'],
            $data['autoSubmitDelay'],
        );

        return $data;
    }

    /** @return array<string, mixed> */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';
        $baseId = $id ?: ($hasName ? FieldKey::toId($name) : null);
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $baseId ? $baseId.'-error' : '';
        $resolvedSelected = $this->old && $resolvedErrorKey !== ''
            ? old($resolvedErrorKey, $this->selected)
            : $this->selected;
        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        return [
            'name' => $name,
            'baseId' => $baseId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'resolvedSelected' => $resolvedSelected,
            'hasErrors' => $hasErrors,
            'elementAction' => AutoSubmit::action($this->autoSubmit, 'change', 'submit'),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'submit'),
        ];
    }
}
