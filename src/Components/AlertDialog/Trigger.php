<?php

namespace Emaia\LaravelHotwire\Components\AlertDialog;

use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public bool $asChild = false,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $confirmLabel = null,
        public ?string $cancelLabel = null,
        public ?string $confirmVariant = null,
        public ?string $cancelVariant = null,
    ) {
        $this->title = $this->nonEmpty($this->title);
        $this->confirmLabel = $this->nonEmpty($this->confirmLabel);
        $this->cancelLabel = $this->nonEmpty($this->cancelLabel);
        $this->confirmVariant = $this->nonEmpty($this->confirmVariant);
        $this->cancelVariant = $this->nonEmpty($this->cancelVariant);
    }

    public function render()
    {
        return view('hotwire::component-views.alert-dialog-trigger');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();
        $data['alertDialogTriggerAsChild'] = $this->asChild;
        $data['alertDialogTriggerTitle'] = $this->title;
        $data['alertDialogTriggerDescription'] = $this->description;
        $data['alertDialogTriggerConfirmLabel'] = $this->confirmLabel;
        $data['alertDialogTriggerCancelLabel'] = $this->cancelLabel;
        $data['alertDialogTriggerConfirmVariant'] = $this->confirmVariant;
        $data['alertDialogTriggerCancelVariant'] = $this->cancelVariant;

        unset(
            $data['asChild'],
            $data['title'],
            $data['description'],
            $data['confirmLabel'],
            $data['cancelLabel'],
            $data['confirmVariant'],
            $data['cancelVariant'],
        );

        return $data;
    }

    private function nonEmpty(?string $value): ?string
    {
        return $value !== null && trim($value) !== '' ? $value : null;
    }
}
