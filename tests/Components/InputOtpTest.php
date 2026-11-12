<?php

use Emaia\LaravelHotwire\Components\InputOtp;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
    session()->forget('_old_input');
});

// --- Native input and presentation ---

it('renders one native OTP control and decorative server-rendered slots', function () {
    $html = (string) $this->blade('<x-hw::input-otp name="code" />');

    expect(substr_count($html, '<input'))->toBe(1)
        ->and(substr_count($html, 'data-slot="input-otp-slot"'))->toBe(6);

    $this->blade('<x-hw::input-otp name="code" />')
        ->assertSee('type="text"', false)
        ->assertSee('name="code"', false)
        ->assertSee('id="code"', false)
        ->assertSee('inputmode="numeric"', false)
        ->assertSee('autocomplete="one-time-code"', false)
        ->assertSee('pattern="[0-9]{6}"', false)
        ->assertSee('minlength="6"', false)
        ->assertSee('maxlength="6"', false)
        ->assertSee('change->input-otp#input', false)
        ->assertSee('keydown->input-otp#navigate', false)
        ->assertSee('aria-hidden="true" hidden', false)
        ->assertDontSee('type="hidden"', false);
});

it('preserves leading zeroes and supports a configurable length', function () {
    $view = $this->blade('<x-hw::input-otp name="code" :length="4" value="0012" />');

    $view->assertSee('value="0012"', false)
        ->assertSee('pattern="[0-9]{4}"', false)
        ->assertSee('maxlength="4"', false);
    expect(substr_count((string) $view, 'data-slot="input-otp-slot"'))->toBe(4);
});

it('rejects invalid OTP lengths', function (int $length) {
    expect(fn () => new InputOtp(length: $length))->toThrow(InvalidArgumentException::class);
})->with([0, -1, 33]);

it('keeps the native numeric contract protected and composes user controllers', function () {
    $view = $this->blade('<x-hw::input-otp name="code" type="number" maxlength="2" pattern=".*" data-controller="audit" data-audit-mode-value="safe" data-input-otp-length-value="2" data-action="change->audit#record" aria-label="Verification code" disabled readonly form="challenge" />');

    $view->assertSee('data-controller="input-otp audit"', false)
        ->assertSee('data-audit-mode-value="safe"', false)
        ->assertSee('change->audit#record', false)
        ->assertDontSee('data-input-otp-length-value="2"', false)
        ->assertSee('type="text"', false)
        ->assertSee('maxlength="6"', false)
        ->assertSee('pattern="[0-9]{6}"', false)
        ->assertSee('aria-label="Verification code"', false)
        ->assertSee('disabled', false)
        ->assertSee('readonly', false)
        ->assertSee('form="challenge"', false);
});

// --- Laravel field integration ---

it('does not restore flashed OTP values unless explicitly enabled', function () {
    session()->put('_old_input', ['challenge' => ['code' => '987654']]);

    $this->blade('<x-hw::input-otp name="challenge[code]" value="001234" />')
        ->assertSee('value="001234"', false)
        ->assertDontSee('987654');
    $this->blade('<x-hw::input-otp name="challenge[code]" :old="true" />')
        ->assertSee('value="987654"', false);
});

it('inherits field identity required state and error relationships', function () {
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag(['challenge.code' => ['Invalid code.']]));
    view()->share('errors', $bag);

    $this->blade('<x-hw::field name="challenge[code]" label="Code" description="Use your authenticator." required><x-hw::input-otp /></x-hw::field>')
        ->assertSee('id="challenge-code"', false)
        ->assertSee('name="challenge[code]"', false)
        ->assertSee('for="challenge-code"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby=', false)
        ->assertSee('challenge-code-error', false)
        ->assertSee('required', false)
        ->assertSee('Invalid code.');
});

it('does not invent standalone error references and respects explicit ids', function () {
    $this->blade('<x-hw::input-otp name="challenge[code]" id="verification" />')
        ->assertSee('id="verification"', false)
        ->assertDontSee('aria-describedby', false);
});

it('wires opt-in auto-submit to completion rather than individual input events', function () {
    $this->blade('<x-hw::input-otp name="code" auto-submit />')
        ->assertSee('input-otp:complete->auto-submit#submit', false)
        ->assertDontSee('input->auto-submit', false);
    $this->blade('<x-hw::input-otp name="code" />')->assertDontSee('->auto-submit', false);
});

it('registers the OTP family and controller', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('input-otp')->class)->toBe(InputOtp::class)
        ->and($registry->component('input-otp')->controllers)->toContain('input-otp')
        ->and($registry->controller('input-otp')->source)->toBe('resources/js/controllers/input_otp_controller.js');
});
