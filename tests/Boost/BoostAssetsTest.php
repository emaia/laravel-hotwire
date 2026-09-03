<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

function boostAssetsPath(string $path = ''): string
{
    return __DIR__.'/../../resources/boost'.($path === '' ? '' : '/'.$path);
}

function renderBoostAsset(string $path): string
{
    return Blade::render(File::get($path));
}

/** @return array<string, mixed> */
function parseBoostFrontmatter(string $content): array
{
    $content = preg_replace('/^(\s*<!--.*?-->\s*)+/s', '', $content);

    if (! preg_match('/^\s*---\s*\n(.*?)\n---\s*\n/s', (string) $content, $matches)) {
        return [];
    }

    try {
        return Yaml::parse($matches[1]) ?? [];
    } catch (Throwable) {
        return [];
    }
}

function isValidBoostSkillName(string $name): bool
{
    if (str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0")) {
        return false;
    }

    return trim($name, ". \t\n\r\0\x0B") !== '';
}

it('ships the Boost guideline and four focused skills', function () {
    expect(__DIR__.'/../../docs/boost.md')->toBeFile();
    expect(File::get(__DIR__.'/../../README.md'))->toContain('[**Laravel Boost**](docs/boost.md)');
    expect(boostAssetsPath('guidelines/core.blade.php'))->toBeFile();
    expect(boostAssetsPath('skills/laravel-hotwire-forms/references/controls.md'))->toBeFile();
    expect(boostAssetsPath('skills/laravel-hotwire-ui-development/references/styling.md'))->toBeFile();

    $skillDirectories = collect(File::directories(boostAssetsPath('skills')))
        ->map(fn (string $path): string => basename($path))
        ->sort()
        ->values()
        ->all();

    expect($skillDirectories)->toBe([
        'laravel-hotwire-forms',
        'laravel-hotwire-stimulus-controllers',
        'laravel-hotwire-turbo-workflows',
        'laravel-hotwire-ui-development',
    ]);

    foreach ($skillDirectories as $skill) {
        $directory = boostAssetsPath("skills/{$skill}");

        expect(
            File::exists("{$directory}/SKILL.blade.php") || File::exists("{$directory}/SKILL.md")
        )->toBeTrue("Skill [{$skill}] has no SKILL.blade.php or SKILL.md file.");
    }
});

it('uses frontmatter that Boost can parse and install safely', function () {
    foreach (File::directories(boostAssetsPath('skills')) as $directory) {
        $path = File::exists("{$directory}/SKILL.blade.php")
            ? "{$directory}/SKILL.blade.php"
            : "{$directory}/SKILL.md";
        $content = str_ends_with($path, '.blade.php') ? renderBoostAsset($path) : File::get($path);
        $frontmatter = parseBoostFrontmatter($content);
        $directoryName = basename($directory);

        expect($frontmatter)
            ->toHaveKeys(['name', 'description'])
            ->and($frontmatter['name'])->toBe($directoryName)
            ->and($frontmatter['description'])->toBeString()->not->toBeEmpty()
            ->and(isValidBoostSkillName($frontmatter['name']))->toBeTrue();
    }
});

it('renders every Boost Blade asset with the configured component prefix', function () {
    config()->set('hotwire.prefix', 'hot');

    foreach (File::allFiles(boostAssetsPath()) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        expect(fn () => renderBoostAsset($file->getPathname()))
            ->not->toThrow(Throwable::class);
    }

    expect(renderBoostAsset(boostAssetsPath('guidelines/core.blade.php')))
        ->toContain('<hot:form>')
        ->not->toContain('<hw:form>');

    $rawFormsSkill = Blade::render(File::get(boostAssetsPath('skills/laravel-hotwire-forms/SKILL.blade.php')));
    $rawFrontmatter = parseBoostFrontmatter($rawFormsSkill);

    expect($rawFrontmatter['description'])
        ->toContain('<hot:form>')
        ->toContain('<hot:field>')
        ->not->toContain('&lt;');
});

it('documents the permanent hw alias alongside a configured component prefix', function () {
    config()->set('hotwire.prefix', 'hot');

    expect(renderBoostAsset(boostAssetsPath('guidelines/core.blade.php')))
        ->toContain('The configured component prefix is `hot`.')
        ->toContain('The `hw` prefix remains registered as an alias')
        ->toContain('follow the convention already used by the application.');
});

it('maps form controls to their initial value props', function () {
    $reference = File::get(boostAssetsPath('skills/laravel-hotwire-forms/references/controls.md'));

    expect($reference)
        ->toContain('| Submitted value or interaction | Prefer | Avoid confusing it with | Initial value prop |')
        ->toContain('| One native boolean/value pair | `<hw:checkbox>` | `<hw:toggle>` is pressed UI state; `<hw:switch>` is an on/off widget | `checked` |')
        ->toContain('| Several checkboxes sharing a name | `<hw:checkbox-group>` | Repeating standalone checkboxes loses group semantics and select-all support | `selected` |')
        ->toContain('| One choice from a visible list | `<hw:radio-group>` | `<hw:select>` is better for a long or compact list | `selected` |')
        ->toContain('| One compact native choice | `<hw:select>` | Native `multiple` requires an explicit `name="ids[]"` | `selected` |')
        ->toContain('| Searchable multiple selection | `<hw:multi-select>` | `<hw:select multiple>` has native behavior and a smaller API | `selected` |')
        ->toContain('| Plain multiline text | `<hw:textarea>` | `<hw:rich-text>` stores editor-generated rich content | `value` |')
        ->toContain('fallback beneath `old($errorKey, ...)`')
        ->toMatch('/Native file\s+inputs cannot be repopulated/');
});

it('warns that frame redirects need a toast inside the requested frame', function () {
    config()->set('hotwire.prefix', 'hot');
    $skill = renderBoostAsset(boostAssetsPath('skills/laravel-hotwire-turbo-workflows/SKILL.blade.php'));

    expect($skill)
        ->toContain('A redirect with `->toast()` loses its message when navigation stays inside a Frame')
        ->toContain('Render `<hot:toast />` inside the requested frame content')
        ->toMatch('/Flash is claimed only once per request,\s+so this does not duplicate the toast\./');
});

it('documents self-hosted frames when the layout has no frame host', function () {
    config()->set('hotwire.prefix', 'hot');
    $skill = renderBoostAsset(boostAssetsPath('skills/laravel-hotwire-turbo-workflows/SKILL.blade.php'));

    expect($skill)
        ->toContain("When a Frame is the page's main content and the layout does not already host it")
        ->toContain('render `<hot:frame>` in the full-page response')
        ->toMatch('/Turbo extracts the matching Frame during scoped\s+navigation/');
});

it('documents validation and prefill for multistep forms', function () {
    config()->set('hotwire.prefix', 'hot');
    $skill = renderBoostAsset(boostAssetsPath('skills/laravel-hotwire-forms/SKILL.blade.php'));
    $multistep = Str::between($skill, '## Multistep forms', '## Frames and modals');

    expect($multistep)
        ->toContain('->withErrors($validator)')
        ->toContain('->withInput();')
        ->toContain("session()->put('registration.contact'")
        ->toContain('return redirect()->route(\'registration.address\');')
        ->toContain('<hot:frame id="registration" advance>')
        ->toContain(':value="session(\'registration.contact.email\')"')
        ->toContain('`old($errorKey, $value)` automatically wins')
        ->toContain("session()->forget('registration');")
        ->toContain('track-frame-src')
        ->toContain('TurboFormRequest');
});

it('documents slots required by custom composed elements', function () {
    $guideline = renderBoostAsset(boostAssetsPath('guidelines/core.blade.php'));
    $alert = File::get(__DIR__.'/../../docs/components/alert.md');
    $item = File::get(__DIR__.'/../../docs/components/item.md');

    expect($guideline)
        ->toContain('carry the documented `data-slot`')
        ->toContain('<x-lucide-check data-slot="icon" />')
        ->and($alert)->toContain('- `data-slot="icon"`')
        ->and($item)->toContain('- `data-slot="icon"`');
});

it('documents Reveal stream target and shared-template boundaries', function () {
    $documentation = File::get(__DIR__.'/../../docs/controllers/reveal.md');

    expect($documentation)
        ->toContain('at least one Reveal controller is connected')
        ->toContain('`[data-controller~="reveal"]` root or its descendant')
        ->toContain('shared template')
        ->toContain('separate stream elements')
        ->toContain('targets all sit outside Reveal markup')
        ->toContain('include `data-reveal-skip` in that payload');
});

it('keeps reusable Reveal partials free of presentation state', function () {
    $skill = renderBoostAsset(boostAssetsPath('skills/laravel-hotwire-ui-development/SKILL.blade.php'));
    $documentation = File::get(__DIR__.'/../../docs/components/reveal.md');

    expect($skill)
        ->toContain('wrap them in `<hw:reveal>`')
        ->toContain('emits `data-reveal-children` automatically')
        ->toContain('`data-reveal-item` globally')
        ->toContain('Reveal composition site')
        ->and($documentation)
        ->toContain('let `<hw:reveal>` own direct-child mode')
        ->toContain('emits `data-reveal-children` automatically')
        ->toContain('`data-reveal-item` globally')
        ->toContain('`data-reveal-skip` to the stream payload')
        ->toContain('when its entrance should not replay');
});

it('keeps the copyable Stimulus example aligned with its controllers', function () {
    $skill = renderBoostAsset(boostAssetsPath('skills/laravel-hotwire-stimulus-controllers/SKILL.blade.php'));
    $clipboard = File::get(__DIR__.'/../../resources/js/controllers/copy_to_clipboard_controller.js');

    expect($skill)
        ->toContain("->controller('copy-to-clipboard', ['successContent' => 'Copied'])")
        ->toContain("->target('copy-to-clipboard', 'source')")
        ->toContain("->target('copy-to-clipboard', 'button')")
        ->toContain("->action('copy-to-clipboard', 'copy', 'click')")
        ->toContain('value="Text to copy"')
        ->and($clipboard)
        ->toContain('static targets = ["button", "source"]')
        ->toContain('successContent: String')
        ->toContain('copy(event)');
});

it('documents the generated controller filename and identifier exactly', function () {
    $skill = renderBoostAsset(boostAssetsPath('skills/laravel-hotwire-stimulus-controllers/SKILL.blade.php'));

    expect($skill)
        ->toContain('php artisan hotwire:make-controller form/auto-save')
        ->toContain('form/auto_save_controller.js')
        ->toContain('form--auto-save');
});

it('only cites registered components and Artisan commands', function () {
    config()->set('hotwire.prefix', 'hw');

    $content = collect(File::allFiles(boostAssetsPath()))
        ->map(function ($file): string {
            return str_ends_with($file->getFilename(), '.blade.php')
                ? renderBoostAsset($file->getPathname())
                : File::get($file->getPathname());
        })
        ->implode("\n");

    preg_match_all('/<hw:([a-z0-9.-]+)/', $content, $componentMatches);
    preg_match_all('/php artisan (hotwire:[a-z-]+)/', $content, $commandMatches);

    $components = array_merge(
        array_keys(HotwireRegistry::make()->components()),
        array_keys(ComponentAliases::subComponents()),
    );
    $commands = array_keys(app(Kernel::class)->all());
    $unknownComponents = array_values(array_diff(array_unique($componentMatches[1]), $components));
    $unknownCommands = array_values(array_diff(array_unique($commandMatches[1]), $commands));

    expect($unknownComponents)->toBe([], 'Boost assets cite components missing from the Hotwire registry.')
        ->and($unknownCommands)->toBe([], 'Boost assets cite Artisan commands the package does not register.');
});

it('keeps the upfront guideline within its context budget', function () {
    $guideline = renderBoostAsset(boostAssetsPath('guidelines/core.blade.php'));

    expect(str_word_count($guideline))->toBeLessThanOrEqual(900);
});
