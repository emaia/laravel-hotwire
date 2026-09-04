<?php

namespace Emaia\LaravelHotwire\Components\Concerns;

use ReflectionClass;

trait ResolvesWithoutContainer
{
    /** @var array<class-string, array{list<string>, array<string, true>|null}> */
    private static array $fastResolveSpecs = [];

    /**
     * Resolve package components directly when all required data is present.
     *
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function resolve($data)
    {
        if (static::$componentsResolver) {
            return parent::resolve($data);
        }

        [$parameters, $required] = self::fastResolveSpec();

        if ($required === null || array_diff_key($required, $data) !== []) {
            return parent::resolve($data);
        }

        $class = static::class;

        return new $class(...array_intersect_key($data, array_flip($parameters)));
    }

    /** @return array{list<string>, array<string, true>|null} */
    private static function fastResolveSpec(): array
    {
        $class = static::class;

        if (isset(self::$fastResolveSpecs[$class])) {
            return self::$fastResolveSpecs[$class];
        }

        $constructor = (new ReflectionClass($class))->getConstructor();
        $required = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            if ($parameter->isVariadic()) {
                $required = null;

                break;
            }

            if (! $parameter->isDefaultValueAvailable()) {
                $required[$parameter->getName()] = true;
            }
        }

        return self::$fastResolveSpecs[$class] = [
            static::extractConstructorParameters(),
            $required,
        ];
    }
}
