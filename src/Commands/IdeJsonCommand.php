<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Support\LaravelIdeaMetadata;
use Emaia\LaravelHotwire\Support\LaravelIdeaMetadataFile;
use Emaia\LaravelHotwire\Support\StimulusControllerLocations;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\info;

class IdeJsonCommand extends Command
{
    public $signature = 'hotwire:ide-json';

    public $description = 'Generate Laravel Idea metadata for Hotwire components and Stimulus controllers';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $controllers = StimulusControllerLocations::discoverApp(
            $this->files,
            base_path(),
            resource_path('js/controllers'),
        );

        $metadata = LaravelIdeaMetadata::make(
            controllerLocations: $controllers,
            includeComponents: false,
            includeCompletions: true,
        );

        (new LaravelIdeaMetadataFile($this->files))->write(base_path('ide.json'), $metadata);

        info('Laravel Idea metadata updated: ide.json');

        return self::SUCCESS;
    }
}
