<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class MultiSelect extends Component
{
    use StripsNullProps;

    /** @param array<int|string, string> $options */
    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public array $options = [],
        public array $selected = [],
        public ?string $errorKey = null,
        public bool $old = true,
        public string $placeholder = 'Select options',
        public bool $search = true,
        public string $searchPlaceholder = 'Search...',
        public string $emptyText = 'No options found.',
        public bool $selectAll = false,
        public string $selectAllText = 'Select all',
        public string $deselectAllText = 'Clear all',
        public int|string|null $max = null,
        public bool $listAll = false,
        public int|string|null $listAllLimit = 3,
        public bool $sortSelected = false,
        public bool $closeListOnItemSelect = false,
        public string $side = 'bottom',
        public string $align = 'start',
        public int|float|string $sideOffset = 4,
        public int|float|string $alignOffset = 0,
        public string $strategy = 'fixed',
        public bool $flip = true,
        public bool $shift = true,
        public string $width = '',
        public string $triggerClass = '',
        public string $contentClass = '',
        public ?Htmlable $stimulus = null,
    ) {
        if ($options !== [] && array_keys($options) === range(0, count($options) - 1)) {
            $this->options = array_combine($options, $options);
        }

        $this->side = $this->oneOf($this->side, ['top', 'right', 'bottom', 'left'], 'bottom');
        $this->align = $this->oneOf($this->align, ['start', 'center', 'end'], 'start');
        $this->strategy = $this->oneOf($this->strategy, ['absolute', 'fixed'], 'fixed');
        $this->sideOffset = $this->number($this->sideOffset, 4);
        $this->alignOffset = $this->number($this->alignOffset, 0);
        $this->listAllLimit = $this->wholeNumber($this->listAllLimit, 3);
    }

    public function render()
    {
        return view('hotwire::component-views.multi-select');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        bool $required,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';
        $submissionName = $hasName && ! str_ends_with($name, '[]') ? $name.'[]' : $name;
        $fieldName = $hasName ? $submissionName : null;

        $resolvedId = $id ?: ($hasName ? FieldKey::toId($submissionName) : 'hw-multi-select-'.uniqid());
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($submissionName) : '');
        $errorId = $resolvedId.'-error';
        $contentId = $resolvedId.'-content';

        $resolvedSelected = $this->old && $resolvedErrorKey !== ''
            ? old($resolvedErrorKey, $this->selected)
            : $this->selected;

        if (! is_array($resolvedSelected)) {
            $resolvedSelected = $resolvedSelected !== null ? [$resolvedSelected] : [];
        }

        $selectedSet = array_map('strval', $resolvedSelected);
        $selectedLabels = [];

        foreach ($this->options as $value => $label) {
            if (in_array((string) $value, $selectedSet, true)) {
                $selectedLabels[] = $label;
            }
        }

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        return [
            'resolvedId' => $resolvedId,
            'submissionName' => $fieldName,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'contentId' => $contentId,
            'selectedSet' => $selectedSet,
            'selectedLabels' => $selectedLabels,
            'selectedSummary' => $this->summary($selectedLabels),
            'selectedFullSummary' => $this->fullSummary($selectedLabels),
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
        ];
    }

    /** @param string[] $allowed */
    private function oneOf(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function number(int|float|string $value, int|float $default): string
    {
        if (! is_numeric($value)) {
            return (string) $default;
        }

        $formatted = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

        return $formatted === '-0' || $formatted === '' ? '0' : $formatted;
    }

    private function wholeNumber(int|string|null $value, int $default): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return max(0, (int) $value);
    }

    /** @param string[] $labels */
    private function summary(array $labels): string
    {
        $count = count($labels);

        if ($count === 0) {
            return $this->placeholder;
        }

        if ($this->listAll) {
            $limit = (int) $this->listAllLimit;

            if ($limit > 0 && $count > $limit) {
                return implode(', ', array_slice($labels, 0, $limit)).', +'.($count - $limit).' more';
            }

            return $this->fullSummary($labels);
        }

        return $count.' selected';
    }

    /** @param string[] $labels */
    private function fullSummary(array $labels): string
    {
        return $labels === [] ? $this->placeholder : implode(', ', $labels);
    }
}
