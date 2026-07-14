<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class AspectRatio extends Component
{
    public string $resolvedRatio;

    public function __construct(
        public string $ratio = '16/9',
        public int|string|null $width = null,
        public int|string|null $height = null,
    ) {
        $this->resolvedRatio = $this->resolveRatio();
    }

    public function render()
    {
        return view('hotwire::component-views.aspect-ratio');
    }

    private function resolveRatio(): string
    {
        $width = trim((string) $this->width);
        $height = trim((string) $this->height);

        if ($width !== '' && $height !== '') {
            return $width.'/'.$height;
        }

        return trim($this->ratio) !== '' ? trim($this->ratio) : '16/9';
    }
}
