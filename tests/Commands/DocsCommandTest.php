<?php

// --- Lookup ---

it('displays docs for a top-level controller', function () {
    $this->artisan('hotwire:docs auto-submit')
        ->expectsOutputToContain('Auto Submit')
        ->assertSuccessful();
});

it('displays docs for a substrate controller using slash notation', function () {
    $this->artisan('hotwire:docs turbo/progress')
        ->expectsOutputToContain('Progress')
        ->assertSuccessful();
});

it('displays docs for a component', function () {
    $this->artisan('hotwire:docs flash-message --component')
        ->expectsOutputToContain('Flash Message')
        ->assertSuccessful();
});

it('fails with an error for an unknown name', function () {
    $this->artisan('hotwire:docs nonexistent')
        ->expectsOutputToContain('not found')
        ->assertFailed();
});

// --- Ambiguity ---

it('prompts when name exists in both controllers and components', function () {
    $this->artisan('hotwire:docs modal')
        ->expectsChoice(
            'Found in both controllers and components. Which would you like to view?',
            'controller',
            ['controller', 'component'],
        )
        ->assertSuccessful();
});

it('shows controller docs directly with --controller when name is ambiguous', function () {
    $this->artisan('hotwire:docs modal --controller')
        ->expectsOutputToContain('Modal')
        ->assertSuccessful();
});

it('shows component docs directly with --component when name is ambiguous', function () {
    $this->artisan('hotwire:docs modal --component')
        ->expectsOutputToContain('Modal')
        ->assertSuccessful();
});

it('fails with an error when no argument is given in non-interactive mode', function () {
    $this->artisan('hotwire:docs --no-interaction')
        ->expectsOutputToContain('interactive mode')
        ->assertFailed();
});
