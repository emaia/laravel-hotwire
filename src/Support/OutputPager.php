<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class OutputPager
{
    /** @param  string[]  $lines */
    public function page(array $lines, OutputInterface $output, bool $interactive, bool $force = false): bool
    {
        if ((! $interactive && ! $force) || ! function_exists('proc_open')) {
            return false;
        }

        $output = $this->resolveOutput($output);

        if (! $output instanceof StreamOutput) {
            return false;
        }

        $stream = $output->getStream();

        if (! @stream_isatty($stream)) {
            return false;
        }

        $command = $this->resolvePagerCommand();

        if ($command === null) {
            return false;
        }

        $content = implode(PHP_EOL, array_map(
            fn (string $line) => $output->getFormatter()->format($line),
            $lines,
        )).PHP_EOL;

        $tmpFile = tempnam(sys_get_temp_dir(), 'hotwire-docs-');

        if ($tmpFile === false) {
            return false;
        }

        file_put_contents($tmpFile, $content);

        $process = @proc_open(
            $this->commandLine($command, $tmpFile),
            [
                0 => STDIN,
                1 => STDOUT,
                2 => STDERR,
            ],
            $pipes,
        );

        if (! is_resource($process)) {
            @unlink($tmpFile);

            return false;
        }

        $status = proc_close($process) === 0;
        @unlink($tmpFile);

        return $status;
    }

    public function resolvePagerCommand(): ?string
    {
        $pager = getenv('PAGER');

        if (is_string($pager) && trim($pager) !== '') {
            return $pager;
        }

        if ($this->findExecutable('less') !== null) {
            return 'less -R';
        }

        if ($this->findExecutable('more') !== null) {
            return 'more';
        }

        return null;
    }

    private function resolveOutput(OutputInterface $output): OutputInterface
    {
        if ($output instanceof OutputStyle) {
            return $output->getOutput();
        }

        return $output;
    }

    protected function commandLine(string $command, string $file): string
    {
        return $command.' '.escapeshellarg($file);
    }

    private function findExecutable(string $command): ?string
    {
        $path = getenv('PATH');

        if (! is_string($path) || $path === '') {
            return null;
        }

        $extensions = [''];

        if (DIRECTORY_SEPARATOR === '\\') {
            $pathext = getenv('PATHEXT');
            $extensions = is_string($pathext) && $pathext !== ''
                ? array_merge([''], explode(';', $pathext))
                : ['', '.exe', '.bat', '.cmd'];
        }

        foreach (explode(PATH_SEPARATOR, $path) as $dir) {
            foreach ($extensions as $extension) {
                $candidate = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$command.$extension;

                if (is_file($candidate) && is_executable($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
