<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ViewErrorBag;

class SessionToast
{
    /** Priority order, and the toast type each key maps to. */
    private const array KEYS = [
        'success' => 'success',
        'error' => 'error',
        'errors' => 'error',
        'warning' => 'warning',
        'info' => 'info',
    ];

    private bool $claimed = false;

    /**
     * Read the flashed toast without claiming it.
     *
     * @return array{type: string, message: string, description: ?string, position: ?string}|null
     */
    public function resolve(): ?array
    {
        return $this->structured() ?? $this->simple();
    }

    /**
     * @return array{type: string, message: string, description: ?string, position: ?string}|null
     */
    public function consume(): ?array
    {
        if ($this->claimed || ($toast = $this->resolve()) === null) {
            return null;
        }

        $this->claimed = true;

        return $toast;
    }

    /** @return array{type: string, message: string, description: ?string, position: ?string}|null */
    private function structured(): ?array
    {
        $payload = Session::get('toast');

        if (is_string($payload) || is_int($payload) || is_float($payload)) {
            $payload = ['message' => $payload];
        }

        if (! is_array($payload) || ($message = $this->text($payload['message'] ?? null)) === null) {
            return null;
        }

        return [
            'type' => $this->text($payload['type'] ?? null) ?? 'default',
            'message' => $message,
            'description' => $this->text($payload['description'] ?? null),
            'position' => $this->text($payload['position'] ?? null),
        ];
    }

    /** @return array{type: string, message: string, description: ?string, position: ?string}|null */
    private function simple(): ?array
    {
        foreach (self::KEYS as $key => $type) {
            $message = $key === 'errors' ? $this->firstError() : Session::get($key);

            if (($text = $this->text($message)) !== null) {
                return [
                    'type' => $type,
                    'message' => $text,
                    'description' => null,
                    'position' => null,
                ];
            }
        }

        return null;
    }

    /**
     * The bag is never forgotten — @error and ViewErrorBag still need it downstream, and the claim
     * this class hands out is in-memory only so an aborted render doesn't burn the flash.
     */
    private function firstError(): ?string
    {
        $bag = Session::get('errors');

        // ViewErrorBag::first() delegates to the default bag alone, so validateWithBag() and
        // withErrors(..., 'login') would resolve to nothing. Every bag it holds is fair game, and a
        // bag whose first message is blank must not shadow the ones after it.
        if ($bag instanceof ViewErrorBag) {
            foreach ($bag->getBags() as $messageBag) {
                if (($text = $this->firstMessage($messageBag)) !== null) {
                    return $text;
                }
            }

            return null;
        }

        return $bag instanceof MessageBag ? $this->firstMessage($bag) : null;
    }

    /** first() only reaches the first message of the first key, which may itself be blank. */
    private function firstMessage(MessageBag $bag): ?string
    {
        foreach ($bag->all() as $message) {
            if (($text = $this->text($message)) !== null) {
                return $text;
            }
        }

        return null;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        return trim((string) $value) === '' ? null : (string) $value;
    }
}
