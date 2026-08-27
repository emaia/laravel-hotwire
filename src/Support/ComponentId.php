<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Http\Request;
use InvalidArgumentException;

final class ComponentId
{
    /** @var array<string, int> */
    private array $counters = [];

    public function __construct(private readonly Request $request) {}

    /** Resolve an explicit string or model identity, falling back to the current render sequence. */
    public function resolve(string|object|null $id, string $prefix, ?string $modelPrefix = null): string
    {
        if (is_object($id)) {
            return $this->modelId($id, $modelPrefix ?? $prefix);
        }

        return $id !== null && $id !== '' ? $id : $this->next($prefix);
    }

    /** Allocate the next deterministic id for this prefix in the current render scope. */
    public function next(string $prefix): string
    {
        $scope = $this->renderScope();
        $counter = $scope."\0".$prefix;
        $ordinal = $this->counters[$counter] = ($this->counters[$counter] ?? 0) + 1;

        return "{$prefix}-{$scope}-{$ordinal}";
    }

    private function renderScope(): string
    {
        $frame = $this->request->turboFrameId();

        if ($frame === null) {
            return 'page';
        }

        $scope = preg_replace('/\s+/u', '-', trim($frame));

        return 'frame-'.rawurlencode($scope ?? $frame);
    }

    private function modelId(object $model, string $prefix): string
    {
        $key = match (true) {
            method_exists($model, 'getKey') => $model->getKey(),
            property_exists($model, 'id') => $model->id,
            default => null,
        };

        if ($key === null || $key === '' || $key === false) {
            throw new InvalidArgumentException('Component id models must have a stable key.');
        }

        return dom_class($model, $prefix).'_'.rawurlencode((string) $key);
    }
}
