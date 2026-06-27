<?php

$stubPath = realpath(__DIR__.'/../../stubs/resources/css/app.css');

// --- Token system ---

it('contains @theme inline block', function () use ($stubPath) {
    $css = file_get_contents($stubPath);
    expect($css)->toContain('@theme inline');
});

it('declares all semantic color tokens', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    $required = [
        '--color-background',
        '--color-foreground',
        '--color-card',
        '--color-card-foreground',
        '--color-popover',
        '--color-popover-foreground',
        '--color-primary',
        '--color-primary-foreground',
        '--color-secondary',
        '--color-secondary-foreground',
        '--color-muted',
        '--color-muted-foreground',
        '--color-accent',
        '--color-accent-foreground',
        '--color-destructive',
        '--color-destructive-foreground',
        '--color-border',
        '--color-input',
        '--color-ring',
    ];

    foreach ($required as $token) {
        expect($css)->toContain("{$token}: var(");
    }
});

it('declares all radius tokens', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    $required = [
        '--radius-sm',
        '--radius-md',
        '--radius-lg',
        '--radius-xl',
    ];

    foreach ($required as $token) {
        expect($css)->toContain($token);
    }
});

// --- Light mode ---

it('contains :root with OKLCH values', function () use ($stubPath) {
    $css = file_get_contents($stubPath);
    expect($css)->toContain(':root');

    $requiredVars = [
        '--background:',
        '--foreground:',
        '--primary:',
        '--primary-foreground:',
        '--secondary:',
        '--secondary-foreground:',
        '--muted:',
        '--muted-foreground:',
        '--accent:',
        '--accent-foreground:',
        '--destructive:',
        '--destructive-foreground:',
        '--border:',
        '--input:',
        '--ring:',
        '--radius:',
    ];

    foreach ($requiredVars as $var) {
        expect($css)->toContain($var);
    }

    expect($css)->toContain('oklch(');
});

// --- Dark mode ---

it('contains data-theme dark with overrides', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    expect($css)->toContain('[data-theme="dark"]');

    $darkVars = [
        '--background:',
        '--foreground:',
        '--primary:',
        '--primary-foreground:',
        '--destructive:',
    ];

    foreach ($darkVars as $var) {
        expect($css)->toContain("{$var} ");
    }
});

// --- Preserve existing features ---

it('preserves existing @source directives', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    expect($css)->toContain("@source '../../vendor/emaia/laravel-hotwire/resources/views/**/*.blade.php'");
    expect($css)->toContain("@source '../../vendor/emaia/laravel-hotwire/src/Components/**/*.php'");
});

it('preserves existing @custom-variant rules', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    $variants = [
        'turbo-preview',
        'turbo-visit',
        'form-busy',
        'frame-busy',
        'in-turbo-frame',
        'in-remote-turbo-frame',
        'modal',
    ];

    foreach ($variants as $variant) {
        expect($css)->toContain("@custom-variant {$variant}");
    }
});
