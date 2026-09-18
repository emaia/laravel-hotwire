<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

function documentedCommandSection(string $documentation, string $name): string
{
    $section = explode("## `{$name}`", $documentation, 2)[1] ?? '';

    return explode("\n## `", $section, 2)[0];
}

function commandDefault(mixed $default): string
{
    return match (true) {
        $default === null => 'null',
        $default === false => 'false',
        $default === true => 'true',
        $default === [] => '[]',
        default => (string) $default,
    };
}

function argumentToken(InputArgument $argument): string
{
    return $argument->getName()
        .($argument->isRequired() ? '' : '?')
        .($argument->isArray() ? '*' : '');
}

function argumentDefault(InputArgument $argument): string
{
    return $argument->isRequired() ? 'required' : commandDefault($argument->getDefault());
}

function optionToken(InputOption $option): string
{
    return '--'.$option->getName()
        .($option->acceptValue() ? '=' : '')
        .($option->isArray() ? '*' : '');
}

function commandTableRow(string $token, string $default): string
{
    return '/^\|\s*`'.preg_quote($token, '/').'`\s*\|\s*`'.preg_quote($default, '/').'`\s*\|/m';
}

/** @return string[] */
function documentedControllerNames(string $contents): array
{
    $contents = preg_replace('/\\\\\R[ \t]*/', ' ', $contents) ?? $contents;
    preg_match_all('/hotwire:make-controller\b([^`\r\n]*)/', $contents, $matches);

    $names = [];

    foreach ($matches[1] as $arguments) {
        $tokens = preg_split('/[ \t]+/', trim($arguments), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            if (! str_starts_with($token, '-')) {
                $names[] = $token;

                break;
            }
        }
    }

    return $names;
}

/** @return array<string, Command> */
function packageCommands(): array
{
    return collect(app(Kernel::class)->all())
        ->filter(fn (Command $command, string $name): bool => str_starts_with($name, 'hotwire:'))
        ->sortKeys()
        ->all();
}

it('documents the complete public command API from the registered definitions', function () {
    $documentation = str_replace(["\r\n", "\r"], "\n", File::get(__DIR__.'/../../docs/commands.md'));
    preg_match_all('/^## `(hotwire:[^`]+)`$/m', $documentation, $matches);

    $commands = packageCommands();
    $documented = $matches[1];
    sort($documented);

    expect($documented)->toBe(array_keys($commands));

    foreach ($commands as $name => $command) {
        $section = documentedCommandSection($documentation, $name);
        $definition = $command->getNativeDefinition();

        expect($section)->toContain("php artisan {$name}");

        foreach ($definition->getArguments() as $argument) {
            $token = argumentToken($argument);
            $default = argumentDefault($argument);

            expect($section)->toMatch(commandTableRow($token, $default));
        }

        foreach ($definition->getOptions() as $option) {
            $token = optionToken($option);
            $default = commandDefault($option->getDefault());

            expect($section)->toMatch(commandTableRow($token, $default));
        }
    }
});

it('links the command API from the main documentation index', function () {
    expect(File::get(__DIR__.'/../../README.md'))
        ->toContain('[**Artisan commands**](docs/commands.md)');
});

it('uses namespaced names in documented make-controller invocations', function () {
    foreach (File::allFiles(__DIR__.'/../../docs') as $file) {
        foreach (documentedControllerNames($file->getContents()) as $name) {
            expect($name)->toContain('/');
        }
    }
});

it('finds make-controller names after command options', function () {
    $contents = <<<'MARKDOWN'
        `php artisan hotwire:make-controller --ts form/autosave`

        ```shell
        php artisan hotwire:make-controller --force \
            admin/report
        ```
        MARKDOWN;

    expect(documentedControllerNames($contents))->toBe(['form/autosave', 'admin/report']);
});
