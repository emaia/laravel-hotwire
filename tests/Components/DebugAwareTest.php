<?php

it('debug aware in nested', function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);

    // class-based parent: scroll-progress is class-based
    $view = $this->blade('
<x-hwc::field name="email">
<x-hwc::input type="email" />
</x-hwc::field>
');

    dump(['rendered' => $view->__toString()]);
});

it('debug field as anon receives name', function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);

    // simple anonymous parent + anonymous child to confirm aware works
    $view = $this->blade('<x-hwc::field name="x">slot</x-hwc::field>');
    dump(['anon' => $view->__toString()]);
});
