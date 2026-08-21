<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\FieldContext;
use Emaia\LaravelHotwire\Support\FieldOwnerContext;
use Illuminate\View\Component;
use InvalidArgumentException;

class Field extends Component
{
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

        $this->context = new FieldContext($this->name, $this->id, $this->label, $this->set, $this->labelId, $this->errorKey);
        $this->ownerContext = new FieldOwnerContext;
    }

    public function render()
    {
        return view('hotwire::component-views.field');
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
