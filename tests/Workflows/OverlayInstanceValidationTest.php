<?php

use Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

class OverlayInstanceCustomerRequest extends TurboFormRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string']];
    }
}

it('redirects a failed Laravel validation to the same concrete overlay frame', function () {
    Route::middleware('web')->get('/overlay-customer/create', fn () => Blade::render(
        '<x-hw::frame-or-page frame="modal"><x-hw::form method="post" action="/overlay-customer" track-frame-src><input name="name">Customer form</x-hw::form></x-hw::frame-or-page>',
    ));
    Route::middleware('web')->post('/overlay-customer', function (OverlayInstanceCustomerRequest $request) {
        return response('Saved '.$request->validated()['name']);
    });

    $token = str_repeat('a', 32);
    $frame = 'modal--'.$token;
    $headers = [
        'Turbo-Frame' => $frame,
        'X-Hotwire-Overlay-Host' => 'modal',
        'X-Hotwire-Overlay-Instance' => $token,
    ];

    $this->withHeaders($headers)->get('/overlay-customer/create')
        ->assertOk()
        ->assertSee('<turbo-frame id="'.$frame.'"', false)
        ->assertSee('name="_turbo_frame_src"', false);

    $this->withHeaders($headers)->post('/overlay-customer', [
        'name' => '',
        '_turbo_frame_src' => '/overlay-customer/create',
    ])->assertRedirect('/overlay-customer/create');

    $this->withHeaders($headers)->get('/overlay-customer/create')
        ->assertOk()
        ->assertSee('<turbo-frame id="'.$frame.'"', false)
        ->assertSee('Customer form');
});
