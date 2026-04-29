<?php

use Emaia\LaravelHotwire\Support\OutputPager;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

it('does not page when the output is not interactive', function () {
    $stream = fopen('php://memory', 'w+');
    $output = new StreamOutput($stream, decorated: true, formatter: new OutputFormatter(true));

    expect((new OutputPager)->page(['<fg=yellow>hello</>'], $output, false))->toBeFalse();
});

it('does not page when the output is not interactive unless forced', function () {
    $stream = fopen('php://memory', 'w+');
    $output = new StreamOutput($stream, decorated: true, formatter: new OutputFormatter(true));

    expect((new OutputPager)->page(['<fg=yellow>hello</>'], $output, false, true))->toBeFalse();
});

it('supports laravel output style wrappers', function () {
    $stream = fopen('php://memory', 'w+');
    $wrapped = new StreamOutput($stream, decorated: true, formatter: new OutputFormatter(true));
    $output = new OutputStyle(new ArrayInput([]), $wrapped);

    expect((new OutputPager)->page(['<fg=yellow>hello</>'], $output, false))->toBeFalse();
});

it('prefers the pager from the environment', function () {
    $originalPager = getenv('PAGER');

    putenv('PAGER=most -s');

    expect((new OutputPager)->resolvePagerCommand())->toBe('most -s');

    putenv($originalPager === false ? 'PAGER' : "PAGER={$originalPager}");
});

it('returns null when no pager is configured or available in path', function () {
    $originalPager = getenv('PAGER');
    $originalPath = getenv('PATH');

    putenv('PAGER');
    putenv('PATH=');

    expect((new OutputPager)->resolvePagerCommand())->toBeNull();

    putenv($originalPager === false ? 'PAGER' : "PAGER={$originalPager}");
    putenv($originalPath === false ? 'PATH' : "PATH={$originalPath}");
});

it('builds a shell command line with the target file', function () {
    $pager = new class extends OutputPager
    {
        public function command(string $pager, string $file): string
        {
            return $this->commandLine($pager, $file);
        }
    };

    $file = '/tmp/docs file.txt';

    expect($pager->command('less -R', $file))->toBe('less -R '.escapeshellarg($file));
});
