<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->targetDir = resource_path('js/controllers');
    File::deleteDirectory($this->targetDir);
});

afterEach(function () {
    File::deleteDirectory($this->targetDir);
});

// --- Basic generation ---

it('creates a controller file in the app', function () {
    $this->artisan('hotwire:make-controller form/autosave --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/form/autosave_controller.js'))->toBeTrue();
});

it('generates correct filename converting hyphens to underscores', function () {
    $this->artisan('hotwire:make-controller form/auto-save --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/form/auto_save_controller.js'))->toBeTrue();
});

it('creates necessary subdirectories', function () {
    $this->artisan('hotwire:make-controller my-namespace/my-feature --no-interaction')
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir.'/my-namespace'))->toBeTrue();
});

it('generates typescript file with --ts', function () {
    $this->artisan('hotwire:make-controller form/autosave --ts --no-interaction')
        ->assertSuccessful();

    $file = $this->targetDir.'/form/autosave_controller.ts';
    expect(File::exists($file))->toBeTrue()
        ->and(File::get($file))->toContain('import { Controller } from "@hotwired/stimulus"');
});

// --- Validation ---

it('requires namespace/name format', function () {
    $this->artisan('hotwire:make-controller autosave --no-interaction')
        ->assertFailed();

    expect(File::isDirectory($this->targetDir))->toBeFalse();
});

it('rejects invalid characters in name', function () {
    $this->artisan('hotwire:make-controller Form/AutoSave --no-interaction')
        ->assertFailed();
});

// --- Conflicts ---

it('warns when controller already exists', function () {
    $this->artisan('hotwire:make-controller form/autosave --no-interaction')
        ->assertSuccessful();

    $this->artisan('hotwire:make-controller form/autosave --no-interaction')
        ->assertFailed();
});

it('overwrites with --force', function () {
    $this->artisan('hotwire:make-controller form/autosave --no-interaction')
        ->assertSuccessful();

    $file = $this->targetDir.'/form/autosave_controller.js';
    File::put($file, '// custom content');

    $this->artisan('hotwire:make-controller form/autosave --force --no-interaction')
        ->assertSuccessful();

    expect(File::get($file))->toContain('import { Controller }');
});

// --- Features (interactive) ---

it('generates controller with targets', function () {
    $this->artisan('hotwire:make-controller form/search')
        ->expectsChoice('Language?', 'js', ['js' => 'JavaScript', 'ts' => 'TypeScript'])
        ->expectsChoice('Which features would you like to include?', ['targets'], ['targets' => 'targets', 'values' => 'values', 'classes' => 'classes'])
        ->expectsQuestion('Controller targets (comma-separated, e.g. input,button):', 'input,button')
        ->assertSuccessful();

    $content = File::get($this->targetDir.'/form/search_controller.js');

    expect($content)->toContain('static targets = ["input", "button"]');
});

it('generates controller with values', function () {
    $this->artisan('hotwire:make-controller form/remote')
        ->expectsChoice('Language?', 'js', ['js' => 'JavaScript', 'ts' => 'TypeScript'])
        ->expectsChoice('Which features would you like to include?', ['values'], ['targets' => 'targets', 'values' => 'values', 'classes' => 'classes'])
        ->expectsQuestion('Value names (comma-separated, e.g. url,count):', 'url')
        ->expectsChoice('Type for "url"?', 'String', ['String', 'Number', 'Boolean', 'Array', 'Object'])
        ->assertSuccessful();

    $content = File::get($this->targetDir.'/form/remote_controller.js');

    expect($content)->toContain('static values = {')
        ->and($content)->toContain('url: { type: String, default: "" },');
});

it('generates controller with classes', function () {
    $this->artisan('hotwire:make-controller ui/toggle')
        ->expectsChoice('Language?', 'js', ['js' => 'JavaScript', 'ts' => 'TypeScript'])
        ->expectsChoice('Which features would you like to include?', ['classes'], ['targets' => 'targets', 'values' => 'values', 'classes' => 'classes'])
        ->expectsQuestion('CSS classes (comma-separated, e.g. active,hidden):', 'active,hidden')
        ->assertSuccessful();

    $content = File::get($this->targetDir.'/ui/toggle_controller.js');

    expect($content)->toContain('static classes = ["active", "hidden"]');
});

it('generates controller with multiple features', function () {
    $this->artisan('hotwire:make-controller form/filter')
        ->expectsChoice('Language?', 'js', ['js' => 'JavaScript', 'ts' => 'TypeScript'])
        ->expectsChoice('Which features would you like to include?', ['targets', 'values'], ['targets' => 'targets', 'values' => 'values', 'classes' => 'classes'])
        ->expectsQuestion('Controller targets (comma-separated, e.g. input,button):', 'input')
        ->expectsQuestion('Value names (comma-separated, e.g. url,count):', 'delay')
        ->expectsChoice('Type for "delay"?', 'Number', ['String', 'Number', 'Boolean', 'Array', 'Object'])
        ->assertSuccessful();

    $content = File::get($this->targetDir.'/form/filter_controller.js');

    expect($content)->toContain('static targets = ["input"]')
        ->and($content)->toContain('delay: { type: Number, default: 0 },');
});

it('generates typescript declarations for targets', function () {
    $this->artisan('hotwire:make-controller form/search')
        ->expectsChoice('Language?', 'ts', ['js' => 'JavaScript', 'ts' => 'TypeScript'])
        ->expectsChoice('Which features would you like to include?', ['targets'], ['targets' => 'targets', 'values' => 'values', 'classes' => 'classes'])
        ->expectsQuestion('Controller targets (comma-separated, e.g. input,button):', 'input,submitButton')
        ->assertSuccessful();

    $content = File::get($this->targetDir.'/form/search_controller.ts');

    expect($content)->toContain('declare readonly inputTarget: HTMLElement;')
        ->and($content)->toContain('declare readonly hasInputTarget: boolean;')
        ->and($content)->toContain('declare readonly submitButtonTarget: HTMLElement;')
        ->and($content)->toContain('declare readonly hasSubmitButtonTarget: boolean;');
});

// --- Non-interactive ---

it('generates basic controller in non-interactive mode', function () {
    $this->artisan('hotwire:make-controller form/autosave --no-interaction')
        ->assertSuccessful();

    $content = File::get($this->targetDir.'/form/autosave_controller.js');

    expect($content)->toContain('import { Controller } from "@hotwired/stimulus"')
        ->and($content)->toContain('export default class extends Controller')
        ->and($content)->toContain('connect() {}')
        ->and($content)->toContain('disconnect() {}')
        ->and($content)->not->toContain('static targets')
        ->and($content)->not->toContain('static values')
        ->and($content)->not->toContain('static classes');
});

// --- Output ---

it('shows stimulus identifier after creation', function () {
    $this->artisan('hotwire:make-controller form/autosave --no-interaction')
        ->expectsOutputToContain('form--autosave')
        ->assertSuccessful();
});

it('shows usage hint with data-controller', function () {
    $this->artisan('hotwire:make-controller form/autosave --no-interaction')
        ->expectsOutputToContain('data-controller="form--autosave"')
        ->assertSuccessful();
});
