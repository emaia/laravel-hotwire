<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Emaia\LaravelHotwire\Support\MetaValue;
use Illuminate\View\Component;

class ColorScheme extends Component
{
    public string $name = 'color-scheme';

    public string $content;

    public function __construct(string $schemes = 'light dark')
    {
        $this->content = MetaValue::enum(
            preg_replace('/\s+/', ' ', $schemes) ?? $schemes,
            ['light', 'dark', 'light dark', 'dark light', 'normal', 'only light'],
            'meta.color-scheme',
        );
    }

    public function render()
    {
        return view('hotwire::component-views.meta-tag');
    }
}
