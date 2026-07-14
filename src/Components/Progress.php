<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Progress extends Component
{
    public string $formattedValue;

    public string $formattedMax;

    public string $formattedPercentage;

    public function __construct(
        public int|float|string|null $value = 0,
        public int|float|string $max = 100,
    ) {
        $numericMax = $this->number($this->max, 100);
        $numericMax = $numericMax > 0 ? $numericMax : 100;

        $numericValue = $this->number($this->value, 0);
        $numericValue = min(max($numericValue, 0), $numericMax);

        $this->formattedValue = $this->formatNumber($numericValue);
        $this->formattedMax = $this->formatNumber($numericMax);
        $this->formattedPercentage = $this->formatNumber(($numericValue / $numericMax) * 100);
    }

    public function render()
    {
        return view('hotwire::component-views.progress');
    }

    private function number(int|float|string|null $value, float $default): float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $default;
        }

        return (float) $value;
    }

    private function formatNumber(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');

        return $formatted === '-0' || $formatted === '' ? '0' : $formatted;
    }
}
