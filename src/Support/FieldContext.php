<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldContext
{
    /** @var array<int, array{id: string, name: ?string, kind: 'control'|'radio'|'checkbox'}> */
    private array $controls = [];

    /** @var array<int, array{labelId: ?string, usesAutomaticLabel: bool, hasLocalLabel: bool}> */
    private array $selections = [];

    private ?string $resolvedLabelId;

    public function __construct(
        private readonly ?string $name,
        private readonly ?string $id,
        private readonly ?string $label,
        private readonly ?string $set,
        ?string $labelId,
    ) {
        $this->resolvedLabelId = $labelId !== '' ? $labelId : null;
    }

    /** Return the Field context visible to the component currently being created. */
    public static function consume(): ?self
    {
        $view = app('view');
        $context = $view->getConsumableComponentData('fieldContext');

        return $context instanceof self ? $context : null;
    }

    /** Return the control-registration context visible to the component currently being created. */
    public static function consumeControl(): ?self
    {
        $view = app('view');
        $context = $view->getConsumableComponentData('fieldControlContext');

        return $context instanceof self ? $context : null;
    }

    /**
     * Register the final identity of a labelable control rendered by the package.
     *
     * @param  'control'|'radio'|'checkbox'  $kind
     */
    public function registerControl(string $id, ?string $name, string $kind = 'control'): void
    {
        $this->controls[] = [
            'id' => $id,
            'name' => $name,
            'kind' => in_array($kind, ['radio', 'checkbox'], true) ? $kind : 'control',
        ];
    }

    /** Register a selection owner and return the label id it should reference. */
    public function registerSelection(
        ?string $localLabelId,
        bool $hasExplicitLabelledby,
    ): ?string {
        $usesAutomaticLabel = false;
        $labelId = $localLabelId;

        if (
            $labelId === null
            && ! $hasExplicitLabelledby
            && $this->set === null
            && ($this->hasAutomaticLabel() || $this->resolvedLabelId !== null)
        ) {
            $labelId = $this->resolveLabelId();
            $usesAutomaticLabel = true;
        }

        $this->selections[] = [
            'labelId' => $labelId,
            'usesAutomaticLabel' => $usesAutomaticLabel,
            'hasLocalLabel' => $localLabelId !== null,
        ];

        return $labelId;
    }

    /** Resolve the id base a direct selection owner inherits from this Field. */
    public function selectionId(?string $id, ?string $name): ?string
    {
        return $id
            ?: $this->id
            ?: ($name !== null && $name !== '' ? FieldKey::toId($name) : null);
    }

    /** Determine whether this Field explicitly owns set semantics. */
    public function ownsSet(): bool
    {
        return $this->set !== null;
    }

    /**
     * Resolve the Field wrapper and automatic label after its slot has rendered.
     *
     * @return array{renderLabel: bool, labelFor: ?string, labelId: ?string, labelSet: bool, role: ?string, ariaLabelledby: ?string}
     */
    public function resolve(): array
    {
        if ($this->set !== null) {
            return $this->setResolution($this->set);
        }

        if (count($this->selections) === 1 && $this->controls === []) {
            $selection = $this->selections[0];

            return $this->resolution(
                renderLabel: $this->hasAutomaticLabel() && ! $selection['hasLocalLabel'],
                labelFor: $selection['usesAutomaticLabel'] ? null : '',
                labelId: $selection['labelId'],
                labelSet: $selection['usesAutomaticLabel'],
            );
        }

        if ($this->selections !== []) {
            $labelId = $this->hasAutomaticLabel()
                ? $this->resolveLabelId()
                : $this->resolvedLabelId;
            $labelFor = count($this->controls) === 1 ? $this->controls[0]['id'] : null;

            return $this->resolution(
                renderLabel: $this->hasAutomaticLabel(),
                labelFor: $labelFor ?? ($labelId === null ? '' : null),
                labelId: $labelId,
                labelSet: $labelId !== null,
            );
        }

        $setRole = $this->inferredSetRole();

        if ($setRole !== null) {
            return $this->setResolution($setRole);
        }

        if (count($this->controls) === 1) {
            return $this->resolution(
                renderLabel: $this->hasAutomaticLabel(),
                labelFor: $this->controls[0]['id'],
                labelId: $this->resolvedLabelId,
                role: 'group',
            );
        }

        if (count($this->controls) > 1) {
            $labelId = $this->hasAutomaticLabel()
                ? $this->resolveLabelId()
                : $this->resolvedLabelId;

            return $this->resolution(
                renderLabel: $this->hasAutomaticLabel(),
                labelFor: '',
                labelId: $labelId,
                labelSet: $labelId !== null,
                role: 'group',
                ariaLabelledby: $labelId,
            );
        }

        $fallbackFor = FieldKey::resolveId($this->id, $this->name, null, null);

        return $this->resolution(
            renderLabel: $this->hasAutomaticLabel(),
            labelFor: $fallbackFor,
            labelId: $this->resolvedLabelId,
            role: 'group',
        );
    }

    private function hasAutomaticLabel(): bool
    {
        return $this->label !== null && $this->label !== '';
    }

    /** @return array{renderLabel: bool, labelFor: ?string, labelId: ?string, labelSet: bool, role: ?string, ariaLabelledby: ?string} */
    private function setResolution(string $role): array
    {
        $labelId = $this->resolvedLabelId;

        if ($labelId === null && $this->hasAutomaticLabel()) {
            $labelId = $this->resolveLabelId();
        }

        return $this->resolution(
            renderLabel: $this->hasAutomaticLabel(),
            labelId: $labelId,
            labelSet: true,
            role: $role,
            ariaLabelledby: $labelId,
        );
    }

    private function resolveLabelId(): string
    {
        if ($this->resolvedLabelId !== null && $this->resolvedLabelId !== '') {
            return $this->resolvedLabelId;
        }

        $base = FieldKey::resolveId($this->id, $this->name, null, null);

        $labelId = $base ? $base.'-label' : 'hw-field-label-'.uniqid();
        $claimedIds = array_column($this->selections, 'labelId');
        $suffix = 2;

        while (in_array($labelId, $claimedIds, true)) {
            $labelId = ($base ? $base.'-label' : $labelId).'-'.$suffix;
            $suffix++;
        }

        return $this->resolvedLabelId = $labelId;
    }

    private function inferredSetRole(): ?string
    {
        if (count($this->controls) < 2) {
            return null;
        }

        $names = array_unique(array_column($this->controls, 'name'));
        $kinds = array_unique(array_column($this->controls, 'kind'));

        if (count($names) !== 1 || $names[0] === null || count($kinds) !== 1) {
            return null;
        }

        return match ($kinds[0]) {
            'radio' => 'radiogroup',
            'checkbox' => 'group',
            default => null,
        };
    }

    /** @return array{renderLabel: bool, labelFor: ?string, labelId: ?string, labelSet: bool, role: ?string, ariaLabelledby: ?string} */
    private function resolution(
        bool $renderLabel,
        ?string $labelFor = null,
        ?string $labelId = null,
        bool $labelSet = false,
        ?string $role = null,
        ?string $ariaLabelledby = null,
    ): array {
        return [
            'renderLabel' => $renderLabel,
            'labelFor' => $labelFor,
            'labelId' => $labelId,
            'labelSet' => $labelSet,
            'role' => $role,
            'ariaLabelledby' => $ariaLabelledby,
        ];
    }
}
