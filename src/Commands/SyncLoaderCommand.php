<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Support\LoaderSync;
use Illuminate\Console\Command;

class SyncLoaderCommand extends Command
{
    protected $signature = 'hotwire:sync-loader';

    protected $description = 'Sync the controller loader index with the package version (CI/production)';

    public function handle(LoaderSync $sync): int
    {
        $synced = $sync->syncIfStale();

        if ($synced) {
            $this->info('Loader index synced.');
        } else {
            $this->info('Loader index already up to date.');
        }

        return self::SUCCESS;
    }
}
