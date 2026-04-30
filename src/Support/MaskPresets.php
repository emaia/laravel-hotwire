<?php

namespace Emaia\LaravelHotwire\Support;

final class MaskPresets
{
    /** @var array<string, string> */
    private const PRESETS = [
        'cpf' => '###.###.###-##',
        'cnpj' => '##.###.###/####-##',
        'phone-br' => '["(##) ####-####", "(##) #####-####"]',
        'cep' => '#####-###',
        'date' => '##/##/####',
        'time' => '##:##',
    ];

    public static function resolve(string $maskOrPreset): string
    {
        return self::PRESETS[$maskOrPreset] ?? $maskOrPreset;
    }
}
