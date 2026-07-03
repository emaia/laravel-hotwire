<?php

use Emaia\LaravelHotwire\Support\LaravelIdeaMetadata;

require __DIR__.'/../vendor/autoload.php';

$metadata = LaravelIdeaMetadata::make();

file_put_contents(
    __DIR__.'/../ide.json',
    json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
);
