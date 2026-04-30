<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->cssPath = resource_path('css/app.css');
    $this->uiJsPath = resource_path('js/libs/ui.js');
    $this->indexJsPath = resource_path('js/libs/index.js');
    $this->packageJsonPath = base_path('package.json');

    File::deleteDirectory(resource_path('css'));
    File::deleteDirectory(resource_path('js'));

    $this->originalPackageJson = File::exists($this->packageJsonPath)
        ? File::get($this->packageJsonPath)
        : null;
});

afterEach(function () {
    File::deleteDirectory(resource_path('css'));
    File::deleteDirectory(resource_path('js'));

    if ($this->originalPackageJson !== null) {
        File::put($this->packageJsonPath, $this->originalPackageJson);
    } elseif (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }

    foreach (['bun.lock', 'pnpm-lock.yaml', 'yarn.lock', 'package-lock.json'] as $lock) {
        $path = base_path($lock);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

// --- Helpers ---

function ensureJsScaffolding(): void
{
    File::makeDirectory(resource_path('js/libs'), 0755, true);
    File::put(resource_path('js/libs/index.js'), "import \"./turbo\";\nimport \"./stimulus\";\nimport \"../controllers\";\n");
    File::put(resource_path('js/app.js'), "import \"./libs\";\n");
}

// --- Default behavior (both CSS + JS) ---

it('injects basecoat import into app.css', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    $css = File::get($this->cssPath);
    expect($css)->toContain('@import "tailwindcss";')
        ->and($css)->toContain('@import "basecoat-css";');
});

it('creates resources/js/libs/ui.js with basecoat import', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->uiJsPath))->toBeTrue();
    expect(File::get($this->uiJsPath))->toContain("import 'basecoat-css/all';");
});

it('adds import ./ui to libs/index.js', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->indexJsPath))->toContain('import "./ui";');
});

// --- Ordering: basecoat import goes after tailwindcss ---

it('places basecoat import after tailwindcss import', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n@custom-variant turbo-frame (turbo-frame[src] &);\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    $css = File::get($this->cssPath);
    $tailwindPos = strpos($css, '@import "tailwindcss";');
    $basecoatPos = strpos($css, '@import "basecoat-css";');
    expect($basecoatPos)->toBeGreaterThan($tailwindPos);
});

// --- Idempotence ---

it('does not duplicate basecoat css import if already present', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n@import \"basecoat-css\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    $css = File::get($this->cssPath);
    $count = mb_substr_count($css, '@import "basecoat-css";');
    expect($count)->toBe(1);
});

it('does not duplicate ui.js content if already present', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    File::makeDirectory(resource_path('js/libs'), 0755, true);
    File::put($this->uiJsPath, "import 'basecoat-css/all';\n");
    File::put($this->indexJsPath, "import \"./turbo\";\nimport \"./ui\";\n");

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->uiJsPath))->toBe("import 'basecoat-css/all';\n");
});

it('does not duplicate ./ui import in index.js if already present', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    File::makeDirectory(resource_path('js/libs'), 0755, true);
    File::put($this->uiJsPath, "import 'basecoat-css/all';\n");
    File::put($this->indexJsPath, "import \"./turbo\";\nimport \"./ui\";\n");

    $before = File::get($this->indexJsPath);

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->indexJsPath))->toBe($before);
});

it('is fully idempotent when run twice', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')->assertSuccessful();
    $firstCss = File::get($this->cssPath);
    $firstUiJs = File::get($this->uiJsPath);
    $firstIndexJs = File::get($this->indexJsPath);

    $this->artisan('hotwire:ui --no-interaction')->assertSuccessful();
    $secondCss = File::get($this->cssPath);
    $secondUiJs = File::get($this->uiJsPath);
    $secondIndexJs = File::get($this->indexJsPath);

    expect($secondCss)->toBe($firstCss)
        ->and($secondUiJs)->toBe($firstUiJs)
        ->and($secondIndexJs)->toBe($firstIndexJs);
});

// --- File creation when missing ---

it('creates app.css when it does not exist', function () {
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->cssPath))->toBeTrue();
    $css = File::get($this->cssPath);
    expect($css)->toContain('@import "tailwindcss";')
        ->and($css)->toContain('@import "basecoat-css";');
});

it('injects basecoat import when tailwindcss is not found in app.css', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "/* custom css */\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    $css = File::get($this->cssPath);
    expect($css)->toContain('@import "basecoat-css";');
});

it('creates libs/index.js when it does not exist', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->uiJsPath))->toBeTrue();
    expect(File::exists($this->indexJsPath))->toBeTrue();
    expect(File::get($this->indexJsPath))->toContain('import "./ui";');
});

// --- --css-only flag ---

it('only injects css with --css-only flag', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --css-only --no-interaction')
        ->assertSuccessful();

    $css = File::get($this->cssPath);
    expect($css)->toContain('@import "basecoat-css";');

    expect(File::get($this->indexJsPath))->not->toContain('import "./ui";');
    expect(File::exists($this->uiJsPath))->toBeFalse();
});

it('does not create or modify js scaffolding with --css-only', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");

    $this->artisan('hotwire:ui --css-only --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->uiJsPath))->toBeFalse();
    expect(File::exists($this->indexJsPath))->toBeFalse();
});

// --- --js-only flag ---

it('only installs js with --js-only flag', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n@import \"basecoat-css\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --js-only --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->uiJsPath))->toContain("import 'basecoat-css/all';");
    expect(File::get($this->indexJsPath))->toContain('import "./ui";');

    // CSS should remain unchanged
    expect(File::get($this->cssPath))->toBe("@import \"tailwindcss\";\n@import \"basecoat-css\";\n");
});

// --- npm dependency ---

it('adds basecoat-css to devDependencies in package.json', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);
    expect($json['devDependencies'])->toHaveKey('basecoat-css');
});

it('does not duplicate basecoat-css in package.json if already present', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => [
            'basecoat-css' => '^0.3.11',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $before = File::get($this->packageJsonPath);

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->packageJsonPath))->toBe($before);
});

it('preserves existing dependencies when adding basecoat-css', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => [
            'vite' => '^5.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);
    expect($json['devDependencies']['vite'])->toBe('^5.0.0')
        ->and($json['devDependencies'])->toHaveKey('basecoat-css');
});

it('warns when package.json does not exist but still sets up css and js', function () {
    if (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }

    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->cssPath))->toContain('@import "basecoat-css";');
    expect(File::get($this->indexJsPath))->toContain('import "./ui";');
});

// --- Post-install instructions ---

it('shows post-install instructions', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->expectsOutputToContain('Basecoat UI installed successfully')
        ->assertSuccessful();
});

it('shows summary of steps taken', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();

    $this->artisan('hotwire:ui --no-interaction')
        ->expectsOutputToContain('Injected @import "basecoat-css"; into resources/css/app.css')
        ->expectsOutputToContain('Created resources/js/libs/ui.js with Basecoat JS import')
        ->assertSuccessful();
});

it('detects package manager in post-install instructions', function () {
    File::makeDirectory(resource_path('css'), 0755, true);
    File::put($this->cssPath, "@import \"tailwindcss\";\n");
    ensureJsScaffolding();
    File::put(base_path('bun.lock'), '');

    $this->artisan('hotwire:ui --no-interaction')
        ->expectsOutputToContain('bun install')
        ->assertSuccessful();
});
