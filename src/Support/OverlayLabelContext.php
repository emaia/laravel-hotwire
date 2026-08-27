<?php

namespace Emaia\LaravelHotwire\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Contracts\Support\Htmlable;

final class OverlayLabelContext
{
    /** @var string[] */
    private array $titleIds = [];

    /** @var string[] */
    private array $descriptionIds = [];

    public function __construct(
        private readonly string $rootId,
        private readonly string $slotPrefix,
    ) {}

    /** Return component data that prevents label ownership from crossing an overlay root. */
    public static function boundaryData(): array
    {
        return ['overlayLabelOwnerContext' => null];
    }

    /** Return component data that lets an overlay content component own its labels. */
    public static function ownerData(string $contextKey): array
    {
        $context = app('view')->getConsumableComponentData($contextKey);

        return ['overlayLabelOwnerContext' => $context instanceof self ? $context : null];
    }

    /** Register an owned title or description and return its resolved id. */
    public function register(string $slotName, ?string $explicitId = null): ?string
    {
        $kind = match ($slotName) {
            "{$this->slotPrefix}-title" => 'title',
            "{$this->slotPrefix}-description" => 'description',
            default => null,
        };

        if ($kind === null) {
            return null;
        }

        if ($kind === 'title') {
            $count = count($this->titleIds) + 1;
            $id = $this->resolveId($explicitId, $kind, $count);
            $this->titleIds[] = $id;

            return $id;
        }

        $count = count($this->descriptionIds) + 1;
        $id = $this->resolveId($explicitId, $kind, $count);
        $this->descriptionIds[] = $id;

        return $id;
    }

    /** Return the first registered title id reachable in the optional rendered content. */
    public function titleId(?Htmlable $contents = null): ?string
    {
        if ($contents === null) {
            return $this->titleIds[0] ?? null;
        }

        return $this->referencesFor($contents)['title'];
    }

    /** Return the first registered description id reachable in the optional rendered content. */
    public function descriptionId(?Htmlable $contents = null): ?string
    {
        if ($contents === null) {
            return $this->descriptionIds[0] ?? null;
        }

        return $this->referencesFor($contents)['description'];
    }

    /**
     * Return the first reachable title and description ids in rendered content.
     *
     * @return array{title: ?string, description: ?string}
     */
    public function referencesFor(Htmlable $contents): array
    {
        if ($this->titleIds === [] && $this->descriptionIds === []) {
            return ['title' => null, 'description' => null];
        }

        $reachable = $this->registeredIdsIn($contents, excludeTemplates: true);
        if ($reachable === null) {
            return ['title' => null, 'description' => null];
        }

        return [
            'title' => $this->firstReachableId($this->titleIds, $reachable),
            'description' => $this->firstReachableId($this->descriptionIds, $reachable),
        ];
    }

    /** Report whether rendered content contains a registered overlay label. */
    public function hasRegisteredLabels(Htmlable $contents): bool
    {
        $ids = $this->registeredIdsIn($contents, excludeTemplates: false);

        return $ids !== null && $ids !== [];
    }

    /**
     * @return array<string, true>|null null when the fragment cannot be parsed
     */
    private function registeredIdsIn(Htmlable $contents, bool $excludeTemplates): ?array
    {
        $registered = array_fill_keys([...$this->titleIds, ...$this->descriptionIds], true);
        if ($registered === []) {
            return [];
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><div>'.$contents->toHtml().'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $elements = (new DOMXPath($document))->query('//*[@id]');
        if ($elements === false) {
            return null;
        }

        $found = [];
        foreach ($elements as $element) {
            if (! $element instanceof DOMElement || ($excludeTemplates && $this->insideTemplate($element))) {
                continue;
            }

            $id = $element->getAttribute('id');
            if (isset($registered[$id])) {
                $found[$id] = true;
            }
        }

        return $found;
    }

    /**
     * @param  string[]  $ids
     * @param  array<string, true>  $reachable
     */
    private function firstReachableId(array $ids, array $reachable): ?string
    {
        foreach ($ids as $id) {
            if (isset($reachable[$id])) {
                return $id;
            }
        }

        return null;
    }

    private function insideTemplate(DOMElement $element): bool
    {
        for ($node = $element->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
            if ($node->tagName === 'template') {
                return true;
            }
        }

        return false;
    }

    private function resolveId(?string $explicitId, string $kind, int $count): string
    {
        if ($explicitId !== null && $explicitId !== '') {
            return $explicitId;
        }

        $suffix = $count === 1 ? '' : "-{$count}";

        return "{$this->rootId}-{$kind}{$suffix}";
    }
}
