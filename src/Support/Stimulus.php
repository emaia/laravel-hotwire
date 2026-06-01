<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use JsonException;
use Stringable;

/**
 * Fluent builder for Stimulus data attributes, usable from Blade.
 *
 * @implements Arrayable<string, string>
 */
final class Stimulus implements Arrayable, Htmlable, Stringable
{
    /** @var list<string> */
    private array $controllers = [];

    /** @var array<string, array<string, mixed>> */
    private array $values = [];

    /** @var array<string, array<string, string>> */
    private array $classes = [];

    /** @var array<string, array<string, string>> */
    private array $outlets = [];

    /** @var array<string, list<string>> */
    private array $targets = [];

    /** @var list<string> */
    private array $actions = [];

    /** @var array<string, array<string, mixed>> */
    private array $params = [];

    public static function make(): self
    {
        return new self;
    }

    /**
     * Register several controllers at once (no per-controller config). For
     * values/classes/outlets on a controller, use controller() instead — the two
     * compose freely and both deduplicate.
     */
    public function controllers(string ...$names): self
    {
        foreach ($names as $name) {
            $this->controller($name);
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $values  keys are kebab-cased → data-{name}-{key}-value
     * @param  array<string, string>  $classes  → data-{name}-{key}-class
     * @param  array<string, string>  $outlets  → data-{name}-{key}-outlet (value is a CSS selector)
     */
    public function controller(string $name, array $values = [], array $classes = [], array $outlets = []): self
    {
        if (! in_array($name, $this->controllers, true)) {
            $this->controllers[] = $name;
        }

        $this->values[$name] = array_merge($this->values[$name] ?? [], $this->withoutNull($values));
        $this->classes[$name] = array_merge($this->classes[$name] ?? [], $this->withoutNull($classes));
        $this->outlets[$name] = array_merge($this->outlets[$name] ?? [], $this->withoutNull($outlets));

        return $this;
    }

    /**
     * → data-action="{event}->{controller}#{method}" (no event → "{controller}#{method}")
     *
     * @param  array<string, mixed>  $params  → data-{controller}-{key}-param
     */
    public function action(string $controller, string $method, ?string $event = null, array $params = []): self
    {
        $descriptor = "$controller#$method";
        $action = $event !== null ? "$event->$descriptor" : $descriptor;

        if (! in_array($action, $this->actions, true)) {
            $this->actions[] = $action;
        }

        $params = $this->withoutNull($params);

        if ($params !== []) {
            $this->params[$controller] = array_merge($this->params[$controller] ?? [], $params);
        }

        return $this;
    }

    /** $target may contain several space-separated names → data-{controller}-target */
    public function target(string $controller, string $target): self
    {
        foreach (preg_split('/\s+/', trim($target)) ?: [] as $name) {
            if ($name !== '' && ! in_array($name, $this->targets[$controller] ?? [], true)) {
                $this->targets[$controller][] = $name;
            }
        }

        return $this;
    }

    /**
     * Raw (unescaped) attribute map — the ComponentAttributeBag escapes on merge.
     *
     * @return array<string, string>
     *
     * @throws JsonException
     */
    public function toArray(): array
    {
        $attributes = [];

        if ($this->controllers !== []) {
            $attributes['data-controller'] = implode(' ', $this->controllers);
        }

        foreach ($this->controllers as $controller) {
            foreach ($this->values[$controller] ?? [] as $key => $value) {
                $attributes["data-$controller-".Str::kebab($key).'-value'] = $this->encode($value);
            }

            foreach ($this->classes[$controller] ?? [] as $key => $value) {
                $attributes["data-$controller-".Str::kebab($key).'-class'] = (string) $value;
            }

            foreach ($this->outlets[$controller] ?? [] as $key => $value) {
                $attributes["data-$controller-".Str::kebab($key).'-outlet'] = (string) $value;
            }
        }

        foreach ($this->targets as $controller => $names) {
            $attributes["data-$controller-target"] = implode(' ', $names);
        }

        if ($this->actions !== []) {
            $attributes['data-action'] = implode(' ', $this->actions);
        }

        foreach ($this->params as $controller => $params) {
            foreach ($params as $key => $value) {
                $attributes["data-$controller-".Str::kebab($key).'-param'] = $this->encode($value);
            }
        }

        return $attributes;
    }

    /** Escaped attribute string — safe for direct `{{ }}` output via Htmlable.
     * @throws JsonException
     */
    public function toHtml(): string
    {
        return implode(' ', array_map(
            fn (string $name, string $value): string => sprintf('%s="%s"', $name, $this->escapeAttr($value)),
            array_keys($this->toArray()),
            array_values($this->toArray()),
        ));
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * Escape for a double-quoted attribute value. Unlike e()/htmlspecialchars, we
     * leave `>` intact so action arrows (`click->c#m`) and child-combinator outlet
     * selectors (`.a > .b`) survive; only `&`, `<` and `"` can break the context.
     */
    private function escapeAttr(string $value): string
    {
        return strtr($value, ['&' => '&amp;', '<' => '&lt;', '"' => '&quot;']);
    }

    /**
     * @template TValue
     *
     * @param  array<string, TValue|null>  $values
     * @return array<string, TValue>
     */
    private function withoutNull(array $values): array
    {
        return array_filter($values, fn ($value) => $value !== null);
    }

    /**
     * @throws JsonException
     */
    private function encode(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value) || is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }
}
