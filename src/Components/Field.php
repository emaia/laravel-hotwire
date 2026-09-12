<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldContext;
use Emaia\LaravelHotwire\Support\FieldKey;
use Emaia\LaravelHotwire\Support\FieldOwnerContext;
use InvalidArgumentException;

class Field extends Component
{
    public const array SLOTS = [
        'set' => ['name' => 'field-set', 'kind' => 'visual'],
        'legend' => ['name' => 'field-legend', 'kind' => 'visual'],
        'group' => ['name' => 'field-group', 'kind' => 'visual'],
        'root' => ['name' => 'field', 'kind' => 'visual'],
        'label' => ['name' => 'field-label', 'kind' => 'visual'],
        'content' => ['name' => 'field-content', 'kind' => 'visual'],
        'title' => ['name' => 'field-title', 'kind' => 'visual'],
        'description' => ['name' => 'field-description', 'kind' => 'visual'],
        'error' => ['name' => 'field-error', 'kind' => 'visual'],
        'separator' => ['name' => 'field-separator', 'kind' => 'visual'],
        'separator-line' => ['name' => 'field-separator-line', 'kind' => 'visual'],
        'separator-content' => ['name' => 'field-separator-content', 'kind' => 'visual'],
        'label-required' => ['name' => 'field-label-required', 'kind' => 'structural'],
    ];

    private FieldContext $context;

    private FieldOwnerContext $ownerContext;

    public function __construct(
        public ?string $name = null,
        public ?string $label = null,
        public ?string $description = null,
        public string $requiredLabel = '*',
        public ?string $errorKey = null,
        public ?bool $required = null,
        public bool $error = true,
        public string $orientation = 'vertical',
        public string $class = '',
        public bool $disabled = false,
        public bool $invalid = false,
        public ?string $id = null,
        public ?string $wrapperId = null,
        public ?string $set = null,
        public ?string $labelId = null,
    ) {
        if (! in_array($this->set, [null, 'group', 'radiogroup'], true)) {
            throw new InvalidArgumentException('The Field set prop must be group, radiogroup, or null.');
        }

        if ($this->id === null && $this->name !== null && $this->name !== '') {
            $scope = FieldKey::scope();

            if ($scope !== null) {
                $this->id = FieldKey::scopedToId($scope, $this->name);
                app(ComponentId::class)->claim($scope, $this->id.'-error');
            }
        }

        $this->context = new FieldContext(
            $this->name,
            $this->id,
            $this->label,
            $this->set,
            $this->labelId,
            $this->errorKey,
            (bool) $this->required,
        );
        $this->ownerContext = new FieldOwnerContext;
    }

    public function render()
    {
        return view('hotwire::component-views.field', [
            'slotName' => self::SLOTS['root']['name'],
            'labelSlotName' => self::SLOTS['label']['name'],
            'requiredSlotName' => self::SLOTS['label-required']['name'],
        ]);
    }

    public function data(): array
    {
        $data = parent::data();

        $data['fieldName'] = $this->name;
        $data['fieldId'] = $this->id;
        $data['fieldLabel'] = $this->label;
        $data['fieldDescription'] = $this->description;
        $data['fieldRequiredLabel'] = $this->requiredLabel;
        $data['fieldErrorKey'] = $this->errorKey;
        $data['fieldRequired'] = $this->required;
        $data['fieldError'] = $this->error;
        $data['fieldOrientation'] = $this->orientation;
        $data['fieldClass'] = $this->class;
        $data['fieldWrapperId'] = $this->wrapperId;
        $data['fieldDisabled'] = $this->disabled;
        $data['fieldInvalid'] = $this->invalid;
        $data['fieldSet'] = $this->set;
        $data['fieldLabelId'] = $this->labelId;
        $data['fieldContext'] = $this->context;
        $data['fieldControlContext'] = $this->context;

        // A Field always starts a fresh owner boundary, even when nested inside a group.
        $data['fieldOwner'] = false;
        $data['fieldOwnerName'] = null;
        $data['fieldOwnerId'] = null;
        $data['fieldOwnerErrorKey'] = null;
        $data['fieldOwnerSet'] = false;
        $data['fieldOwnerContext'] = $this->ownerContext;

        unset(
            $data['name'],
            $data['id'],
            $data['label'],
            $data['description'],
            $data['requiredLabel'],
            $data['errorKey'],
            $data['required'],
            $data['error'],
            $data['orientation'],
            $data['class'],
            $data['wrapperId'],
            $data['disabled'],
            $data['invalid'],
            $data['set'],
            $data['labelId'],
        );

        return $data;
    }
}
