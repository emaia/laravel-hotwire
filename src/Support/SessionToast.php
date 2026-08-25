<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ViewErrorBag;

class SessionToast
{
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
     * Claim the flashed toast; every later call returns null so it renders exactly once.
     *
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

    /** The bag is never forgotten: @error and ViewErrorBag still need it downstream. */
    private function firstError(): ?string
    {
        $bag = Session::get('errors');

        // ViewErrorBag::first() reaches the default bag alone, so named bags would resolve to nothing.
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

    /** ':message' overrides the bag's own format, which would decorate the text and ride into the card. */
    private function firstMessage(MessageBag $bag): ?string
    {
        foreach ($bag->all(':message') as $message) {
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
