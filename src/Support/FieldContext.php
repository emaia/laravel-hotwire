<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldContext
{
    /** @var array<int, array{id: string, name: ?string, kind: 'control'|'radio'|'checkbox'}> */
    private array $controls = [];

    /** @var array<int, array{labelId: ?string, usesAutomaticLabel: bool}> */
    private array $selections = [];

    private ?string $resolvedLabelId;

    public function __construct(
        private readonly ?string $name,
        private readonly ?string $id,
        private readonly ?string $label,
        private readonly ?string $set,
        ?string $labelId,
    ) {
        $this->resolvedLabelId = $labelId;
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
        ?string $id,
        ?string $name,
        ?string $localLabelId,
        bool $hasExplicitAccessibleName,
    ): ?string {
        $usesAutomaticLabel = false;
        $labelId = $localLabelId;

        if (
            $labelId === null
            && ! $hasExplicitAccessibleName
            && $this->hasAutomaticLabel()
            && $this->selections === []
        ) {
            $labelId = $this->resolveLabelId($id, $name);
            $usesAutomaticLabel = true;
        }

        $this->selections[] = [
            'labelId' => $labelId,
            'usesAutomaticLabel' => $usesAutomaticLabel,
        ];

        return $labelId;
    }

    /**
     * Resolve the Field wrapper and automatic label after its slot has rendered.
     *
     * @return array{renderLabel: bool, labelFor: ?string, labelId: ?string, labelSet: bool, role: ?string, ariaLabelledby: ?string}
     */
    public function resolve(): array
    {
        if (count($this->selections) === 1 && $this->controls === []) {
            $selection = $this->selections[0];

            return $this->resolution(
                renderLabel: $this->hasAutomaticLabel() && $selection['usesAutomaticLabel'],
                labelId: $selection['labelId'],
                labelSet: true,
            );
        }

        if ($this->selections !== []) {
            return $this->resolution(renderLabel: false);
        }

        $setRole = $this->set ?? $this->inferredSetRole();

        if ($setRole !== null) {
            $labelId = $this->hasAutomaticLabel() ? $this->resolveLabelId() : null;

            return $this->resolution(
                renderLabel: $this->hasAutomaticLabel(),
                labelId: $labelId,
                labelSet: true,
                role: $setRole,
                ariaLabelledby: $labelId,
            );
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
            return $this->resolution(
                renderLabel: $this->hasAutomaticLabel(),
                labelId: $this->resolvedLabelId,
                role: 'group',
            );
        }

        $fallbackFor = $this->id ?: ($this->name ? FieldKey::toId($this->name) : null);

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

    private function resolveLabelId(?string $fallbackId = null, ?string $fallbackName = null): string
    {
        if ($this->resolvedLabelId !== null && $this->resolvedLabelId !== '') {
            return $this->resolvedLabelId;
        }

        $base = $this->id
            ?: ($this->name ? FieldKey::toId($this->name) : null)
            ?: $fallbackId
            ?: ($fallbackName ? FieldKey::toId($fallbackName) : null);

        return $this->resolvedLabelId = $base
            ? $base.'-label'
            : 'hw-field-label-'.uniqid();
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
