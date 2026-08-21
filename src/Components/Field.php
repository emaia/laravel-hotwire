<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Field extends Component
{
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
    ) {}

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
        );

        return $data;
    }
}
