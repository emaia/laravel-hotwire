<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Csrf extends Component
{
    public string $name = 'csrf-token';

    public string $content;

    public function __construct()
    {
        $request = request();
        $this->content = $request->hasSession() ? (string) $request->session()->token() : '';
    }

    /** Omit the tag when the request has no usable session token. */
    public function shouldRender(): bool
    {
        return $this->content !== '';
    }

    public function render()
    {
        return view('hotwire::component-views.meta-tag');
    }
}
