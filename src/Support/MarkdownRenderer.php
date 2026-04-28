<?php

namespace Emaia\LaravelHotwire\Support;

use Symfony\Component\Console\Formatter\OutputFormatter;

class MarkdownRenderer
{
    /** @return string[] */
    public function render(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $output = [];
        $inCodeBlock = false;

        foreach ($lines as $line) {
            if (preg_match('/^```/', $line)) {
                $inCodeBlock = ! $inCodeBlock;
                $output[] = '';

                continue;
            }

            if ($inCodeBlock) {
                $output[] = '  <fg=gray>'.OutputFormatter::escape($line).'</>';

                continue;
            }

            if (str_starts_with($line, '# ')) {
                $title = $this->inline(substr($line, 2));
                $output[] = '';
                $output[] = "<options=bold;fg=white>{$title}</>";
                $output[] = '';

                continue;
            }

            if (str_starts_with($line, '## ')) {
                $output[] = '';
                $output[] = '<options=bold>'.$this->inline(substr($line, 3)).'</>';

                continue;
            }

            if (str_starts_with($line, '### ')) {
                $output[] = $this->inline(substr($line, 4));

                continue;
            }

            if (str_starts_with($line, '> ')) {
                $output[] = '<fg=gray>│</> '.$this->inline(substr($line, 2));

                continue;
            }

            // Table separator row — skip
            if (preg_match('/^\|[\s\-|:]+\|$/', $line)) {
                continue;
            }

            $output[] = $this->inline($line);
        }

        return $output;
    }

    private function inline(string $text): string
    {
        // Escape first so raw < > in content won't be parsed as Symfony tags
        $text = OutputFormatter::escape($text);

        // **bold**
        $text = preg_replace('/\*\*(.+?)\*\*/', '<options=bold>$1</>', $text);

        // `inline code`
        $text = preg_replace('/`([^`]+)`/', '<fg=yellow>$1</>', $text);

        // [link text](url) → just the text
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

        return $text;
    }
}
