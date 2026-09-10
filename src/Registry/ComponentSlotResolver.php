<?php

namespace Emaia\LaravelHotwire\Registry;

use InvalidArgumentException;
use ReflectionClass;

/** @internal */
final class ComponentSlotResolver
{
    /**
     * Project literal slots or component family references into the registry styling shape.
     *
     * @param  array<mixed>  $metadata
     * @return array{slots: array<string, 'visual'|'structural'>, owners: array<string, class-string|null>}
     */
    public static function resolve(array $metadata): array
    {
        if (! array_is_list($metadata)) {
            $slots = self::validateSlots($metadata, 'Catalog');

            return ['slots' => $slots, 'owners' => array_fill_keys(array_keys($slots), null)];
        }

        $slots = [];
        $owners = [];

        foreach ($metadata as $reference) {
            if (! is_array($reference)
                || array_diff(array_keys($reference), ['class', 'only']) !== []
                || ! is_string($reference['class'] ?? null)
                || $reference['class'] === '') {
                throw new InvalidArgumentException('Component slot references must define [class] and optional [only] keys.');
            }

            $class = $reference['class'];

            if (! class_exists($class)) {
                throw new InvalidArgumentException("Component slot reference class [{$class}] does not exist.");
            }

            $reflection = new ReflectionClass($class);
            $constant = $reflection->getReflectionConstant('SLOTS');

            if ($constant === false
                || ! $constant->isPublic()
                || $constant->getDeclaringClass()->getName() !== $class) {
                throw new InvalidArgumentException("Component slot reference [{$class}] must declare its own public SLOTS constant.");
            }

            $declaration = self::declaration($class, $constant->getValue());
            $only = array_key_exists('only', $reference) ? $reference['only'] : array_keys($declaration);

            if (! is_array($only) || ! array_is_list($only)) {
                throw new InvalidArgumentException("Component slot reference [{$class}] must define [only] as a list of local slot keys.");
            }

            $seen = [];

            foreach ($only as $localKey) {
                if (! is_string($localKey) || isset($seen[$localKey])) {
                    throw new InvalidArgumentException("Component slot reference [{$class}] must contain unique local slot keys.");
                }

                $seen[$localKey] = true;

                if (! isset($declaration[$localKey])) {
                    throw new InvalidArgumentException("Component slot reference [{$class}] references undefined local slot [{$localKey}].");
                }

                ['name' => $name, 'kind' => $kind] = $declaration[$localKey];

                if (isset($slots[$name]) && $slots[$name] !== $kind) {
                    throw new InvalidArgumentException(
                        "Component slot reference [{$class}] classifies slot [{$name}] as [{$kind}], already classified as [{$slots[$name]}]."
                    );
                }

                $slots[$name] ??= $kind;
                $owners[$name] ??= $class;
            }
        }

        return ['slots' => $slots, 'owners' => $owners];
    }

    /**
     * @return array<string, array{name: string, kind: 'visual'|'structural'}>
     */
    private static function declaration(string $class, mixed $declaration): array
    {
        if (! is_array($declaration)) {
            throw new InvalidArgumentException("Component slot declaration [{$class}::SLOTS] must be an array.");
        }

        $slots = [];
        $publicNames = [];

        foreach ($declaration as $localKey => $slot) {
            if (! is_string($localKey) || preg_match('/^[a-z][a-z0-9-]*$/', $localKey) !== 1) {
                throw new InvalidArgumentException("Component slot declaration [{$class}::SLOTS] contains an invalid local key.");
            }

            if (! is_array($slot)) {
                throw new InvalidArgumentException("Component slot declaration [{$class}::SLOTS.{$localKey}] must define exactly [name] and [kind].");
            }

            $keys = array_keys($slot);
            sort($keys);

            if ($keys !== ['kind', 'name']) {
                throw new InvalidArgumentException("Component slot declaration [{$class}::SLOTS.{$localKey}] must define exactly [name] and [kind].");
            }

            $name = $slot['name'];
            $kind = $slot['kind'];

            if (! is_string($name) || preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1) {
                throw new InvalidArgumentException("Component slot declaration [{$class}::SLOTS.{$localKey}] contains an invalid public name.");
            }

            if (! is_string($kind) || ! in_array($kind, ['visual', 'structural'], true)) {
                throw new InvalidArgumentException("Component slot declaration [{$class}::SLOTS.{$localKey}] must be [visual] or [structural].");
            }

            if (isset($publicNames[$name])) {
                throw new InvalidArgumentException("Component slot declaration [{$class}::SLOTS] repeats public name [{$name}].");
            }

            $publicNames[$name] = true;
            $slots[$localKey] = ['name' => $name, 'kind' => $kind];
        }

        return $slots;
    }

    /**
     * @param  array<mixed>  $slots
     * @return array<string, 'visual'|'structural'>
     */
    private static function validateSlots(array $slots, string $owner): array
    {
        foreach ($slots as $name => $kind) {
            if (! is_string($name) || preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1) {
                throw new InvalidArgumentException("{$owner} styling contains an invalid slot name.");
            }

            if (! is_string($kind) || ! in_array($kind, ['visual', 'structural'], true)) {
                throw new InvalidArgumentException("{$owner} slot [{$name}] must be [visual] or [structural].");
            }
        }

        return $slots;
    }
}
