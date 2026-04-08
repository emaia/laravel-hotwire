<?php

namespace Emaia\LaravelHotwireComponents\Commands;

use Illuminate\Console\Command;

class LaravelHotwireComponentsCommand extends Command
{
    public $signature = 'laravel-hotwire-components';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
