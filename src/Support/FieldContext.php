<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldContext
{
    /** @var array<int, array{id: string, name: ?string, kind: 'control'|'radio'|'checkbox', errorId: ?string, errorKey: ?string, required: ?bool}> */
    private array $controls = [];

    /** @var array<int, array{labelId: ?string, usesAutomaticLabel: bool, hasLocalLabel: bool, name: ?string, errorId: ?string, errorKey: ?string}> */
    private array $selections = [];

    private ?string $resolvedLabelId;

    public function __construct(
        private readonly ?string $name,
        private readonly ?string $id,
        private readonly ?string $label,
        private readonly ?string $set,
        ?string $labelId,
        private readonly ?string $errorKey = null,
        private readonly bool $required = false,
    ) {
        $this->resolvedLabelId = $labelId !== '' ? $labelId : null;
    }

    /** Return component data that prevents Field and selection-owner context from crossing a boundary. */
    public static function boundaryData(): array
    {
        return [
            'fieldName' => null,
            'fieldId' => null,
            'fieldErrorKey' => null,
            'fieldRequired' => false,
            'fieldContext' => null,
            'fieldControlContext' => null,
            'fieldOwner' => false,
            'fieldOwnerName' => null,
            'fieldOwnerId' => null,
            'fieldOwnerErrorKey' => null,
            'fieldOwnerSet' => false,
            'fieldOwnerContext' => null,
        ];
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
    public function registerControl(
        string $id,
        ?string $name,
        string $kind = 'control',
        ?string $errorId = null,
        ?string $errorKey = null,
        ?bool $required = null,
    ): void {
        $this->controls[] = [
            'id' => $id,
            'name' => $name,
            'kind' => in_array($kind, ['radio', 'checkbox'], true) ? $kind : 'control',
            'errorId' => $errorId !== '' ? $errorId : null,
            'errorKey' => $errorKey !== '' ? $errorKey : null,
            'required' => $required,
        ];
    }

    /** Return an error id only when a Field owns the validation identity. */
    public function errorReference(?string $id, ?string $name, ?string $errorKey): ?string
    {
        $hasValidationIdentity = ($name !== null && $name !== '') || ($errorKey !== null && $errorKey !== '');

        return $hasValidationIdentity && $id !== null && $id !== '' ? $id : null;
    }

    /**
     * Register a selection owner and return the label id it should reference.
     *
     * @param  string|null  $id  Base id used to derive the selection's error node id.
     */
    public function registerSelection(
        ?string $localLabelId,
        bool $hasExplicitLabelledby,
        ?string $name = null,
        ?string $id = null,
        ?string $errorKey = null,
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
            'name' => $name,
            'errorId' => $id !== null && $id !== '' ? $id.'-error' : null,
            'errorKey' => $errorKey !== '' ? $errorKey : null,
        ];

        return $labelId;
    }

    /** Resolve the id base a direct selection owner inherits from this Field. */
    public function selectionId(?string $id, ?string $name): ?string
    {
        return FieldKey::resolveId($id, $name, $this->id, $this->name);
    }

    /** Determine whether this Field explicitly owns set semantics. */
    public function ownsSet(): bool
    {
        return $this->set !== null;
    }

    /**
     * Resolve the Field wrapper and automatic label after its slot has rendered.
     *
     * @return array{renderLabel: bool, labelFor: ?string, labelId: ?string, labelSet: bool, labelRequired: bool, role: ?string, ariaLabelledby: ?string, errorName: ?string, errorId: ?string, errorKey: ?string}
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
                labelId: $selection['labelId'] ?? $this->resolvedLabelId,
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

        return $this->resolution(
            renderLabel: $this->hasAutomaticLabel(),
            labelFor: '',
            labelId: $this->resolvedLabelId,
            role: 'group',
        );
    }

    private function hasAutomaticLabel(): bool
    {
        return $this->label !== null && $this->label !== '';
    }

    /** @return array{renderLabel: bool, labelFor: ?string, labelId: ?string, labelSet: bool, labelRequired: bool, role: ?string, ariaLabelledby: ?string, errorName: ?string, errorId: ?string, errorKey: ?string} */
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

        $baseLabelId = $base ? $base.'-label' : app(ComponentId::class)->next('hw-field-label');
        $labelId = FieldLabel::uniqueId($baseLabelId, array_column($this->selections, 'labelId'));

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

    /** @return array{renderLabel: bool, labelFor: ?string, labelId: ?string, labelSet: bool, labelRequired: bool, role: ?string, ariaLabelledby: ?string, errorName: ?string, errorId: ?string, errorKey: ?string} */
    private function resolution(
        bool $renderLabel,
        ?string $labelFor = null,
        ?string $labelId = null,
        bool $labelSet = false,
        ?string $role = null,
        ?string $ariaLabelledby = null,
    ): array {
        $error = $this->resolveErrorIdentity();

        return [
            'renderLabel' => $renderLabel,
            'labelFor' => $labelFor,
            'labelId' => $labelId,
            'labelSet' => $labelSet,
            'labelRequired' => $this->resolveRequired(),
            'role' => $role,
            'ariaLabelledby' => $ariaLabelledby,
            ...$error,
        ];
    }

    private function resolveRequired(): bool
    {
        if (count($this->controls) === 1 && $this->controls[0]['required'] !== null) {
            return $this->controls[0]['required'];
        }

        return $this->required;
    }

    /** @return array{errorName: ?string, errorId: ?string, errorKey: ?string} */
    private function resolveErrorIdentity(): array
    {
        $identities = array_map(
            static fn (array $identity): array => [
                'errorName' => $identity['name'],
                'errorId' => $identity['errorId'],
                'errorKey' => $identity['errorKey'],
            ],
            [...$this->controls, ...$this->selections],
        );
        $first = $identities[0] ?? null;

        if (
            $first !== null
            && count(array_filter($identities, static fn (array $identity): bool => $identity !== $first)) === 0
        ) {
            return $first;
        }

        $baseId = FieldKey::resolveId($this->id, $this->name, null, null);

        return [
            'errorName' => $this->name,
            'errorId' => $baseId ? $baseId.'-error' : null,
            'errorKey' => $this->errorKey,
        ];
    }
}
