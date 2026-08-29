<?php

namespace Emaia\LaravelHotwire\Components\AlertDialog;

use Emaia\LaravelHotwire\Components\AlertDialog;

class Host extends AlertDialog
{
    /** @return array<string, mixed> */
    public function data(): array
    {
        if (trim($this->title) === '') {
            $this->title = 'Confirm action';
        }

        if ($this->overlayLabelContext->titleId() === null) {
            $this->overlayLabelContext->register('alert-dialog-title');
        }

        if ($this->overlayLabelContext->descriptionId() === null) {
            $this->overlayLabelContext->register('alert-dialog-description');
        }

        return [
            ...parent::data(),
            'alertDialogHost' => true,
            'alertDialogShared' => true,
        ];
    }
}
