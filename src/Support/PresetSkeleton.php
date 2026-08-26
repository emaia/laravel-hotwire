<?php

namespace Emaia\LaravelHotwire\Support;

/**
 * Renders the shipped presets as empty rules: every selector they define, grouped by the component
 * that owns it, with nothing between the braces. The selector is the documentation — a scaffolded
 * `[data-slot="accordion-item"][open] > [data-slot="accordion-trigger"] {}` says what the state is
 * and which element carries it, which no summary above the base rule can.
 *
 * A rule naming no visual slot has no group to land in and is left out. That is a statement about
 * the preset rather than a gap here: styling keyed on a technical hook alone belongs beside the
 * controller that sets it, and `MakePresetCommandTest` fails on any shipped rule that goes missing.
 */
final readonly class PresetSkeleton
{
    public function __construct(private CssRules $rules = new CssRules) {}

    /**
     * @param  string[]  $stylesheets
     * @param  array<string, string[]>  $groups  Ordered label => the visual slots the group owns.
     * @return string[]
     */
    public function render(array $stylesheets, array $groups): array
    {
        $owner = [];

        foreach ($groups as $label => $slots) {
            foreach ($slots as $slot) {
                $owner[$slot] ??= $label;
            }
        }

        $buckets = array_fill_keys(array_keys($groups), []);
        $seen = [];

        foreach ($stylesheets as $css) {
            foreach ($this->rules->parse($this->rules->stripComments($css)) as ['chain' => $chain]) {
                if (array_filter($chain, fn (string $block): bool => str_starts_with($block, '@keyframes')) !== []) {
                    continue;
                }

                $selector = (string) array_pop($chain);
                $preludes = array_values(array_filter(array_map(
                    $this->normalizePrelude(...),
                    $chain,
                )));
                $key = implode('|', [...$preludes, $selector]);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $label = $this->owner($selector, $owner);

                if ($label === null) {
                    continue;
                }

                $buckets[$label][] = [$preludes, $selector];
            }
        }

        $lines = $this->layerOrders($stylesheets);

        foreach ($buckets as $label => $rules) {
            if ($rules === []) {
                continue;
            }

            $lines = [...$lines, '', "    /* $label */", ...$this->block($rules)];
        }

        return $lines;
    }

    /**
     * Preserve explicit component sublayer order before grouping rules by catalog owner.
     *
     * @param  string[]  $stylesheets
     * @return string[]
     */
    private function layerOrders(array $stylesheets): array
    {
        $lines = [];
        $seen = [];

        foreach ($stylesheets as $css) {
            preg_match_all('/@layer\s+([^;{}]+);/', $this->rules->stripComments($css), $matches);

            foreach ($matches[1] as $declaration) {
                $layers = array_map('trim', explode(',', $declaration));

                if (array_filter(
                    $layers,
                    fn (string $layer): bool => ! str_starts_with($layer, 'components.'),
                ) !== []) {
                    continue;
                }

                $order = implode(', ', array_map(
                    fn (string $layer): string => substr($layer, strlen('components.')),
                    $layers,
                ));

                if (! isset($seen[$order])) {
                    $lines[] = "    @layer {$order};";
                    $seen[$order] = true;
                }
            }
        }

        return $lines;
    }

    /**
     * The group of the first slot the selector names. A rule reaching several slots belongs with the
     * outermost one, which is where a reader looks for it: `[data-slot="table-header"] tr` is Table's.
     *
     * @param  array<string, string>  $owner
     */
    private function owner(string $selector, array $owner): ?string
    {
        preg_match_all('/\[data-slot\s*=\s*["\']?([a-z0-9-]+)["\']?\]/', $selector, $matches);

        foreach ($matches[1] as $slot) {
            if (isset($owner[$slot])) {
                return $owner[$slot];
            }
        }

        return null;
    }

    /**
     * @param  list<array{0: string[], 1: string}>  $rules
     * @return string[]
     */
    private function block(array $rules): array
    {
        $lines = [];
        $open = [];

        foreach ($rules as [$preludes, $selector]) {
            $depth = 0;

            while ($depth < count($open) && $depth < count($preludes) && $open[$depth] === $preludes[$depth]) {
                $depth++;
            }

            $lines = [...$lines, ...$this->close($open, $depth)];
            $open = array_slice($open, 0, $depth);

            foreach (array_slice($preludes, $depth) as $prelude) {
                $lines[] = '';
                $lines[] = $this->indent(count($open) + 1).$prelude.' {';
                $open[] = $prelude;
            }

            $lines[] = $this->indent(count($open) + 1).$selector.' {}';
        }

        return [...$lines, ...$this->close($open, 0)];
    }

    /**
     * @param  string[]  $open
     * @return string[]
     */
    private function close(array $open, int $depth): array
    {
        $lines = [];

        for ($level = count($open); $level > $depth; $level--) {
            $lines[] = $this->indent($level).'}';
        }

        return $lines === [] ? [] : [...$lines, ''];
    }

    private function indent(int $level): string
    {
        return str_repeat(' ', 4 * $level);
    }

    private function normalizePrelude(string $prelude): ?string
    {
        if ($prelude === '@layer components') {
            return null;
        }

        if (str_starts_with($prelude, '@layer components.')) {
            return '@layer '.substr($prelude, strlen('@layer components.'));
        }

        return $prelude;
    }
}
