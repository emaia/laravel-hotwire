<?php

namespace Emaia\LaravelHotwire\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

final class OverlayLabelContext
{
    /** @var string[] */
    private array $titleIds = [];

    /** @var string[] */
    private array $descriptionIds = [];

    /** @var array<string, true> */
    private array $usedIds;

    /** @param string[] $reservedIds */
    public function __construct(
        private readonly string $rootId,
        private readonly string $slotPrefix,
        array $reservedIds = [],
    ) {
        $this->usedIds = array_fill_keys([$rootId, ...$reservedIds], true);
    }

    /** Return component data that prevents label ownership from crossing an overlay root. */
    public static function boundaryData(): array
    {
        return [
            'overlayLabelOwnerContext' => null,
            'modalOverlayLabelContext' => null,
            'sheetOverlayLabelContext' => null,
            'drawerOverlayLabelContext' => null,
            'alertDialogOverlayLabelContext' => null,
        ];
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
        $elements = $this->registeredElementsIn($contents, excludeTemplates: false);

        if ($elements === null) {
            return false;
        }

        foreach ($elements as $element) {
            if ($this->isRegisteredLabel($element)) {
                return true;
            }
        }

        return false;
    }

    /** Reject authored elements that reuse an id owned by an overlay label. */
    public function assertNoIdCollisions(Htmlable $contents): void
    {
        $elements = $this->registeredElementsIn($contents, excludeTemplates: false);

        if ($elements === null) {
            return;
        }

        $counts = [];
        foreach ($elements as $element) {
            $id = $element->getAttribute('id');
            $counts[$id] = ($counts[$id] ?? 0) + 1;

            if ($counts[$id] > 1 || ! $this->isRegisteredLabel($element)) {
                throw new InvalidArgumentException("Overlay label id [{$id}] conflicts with another element in its content.");
            }
        }
    }

    /**
     * @return array<string, true>|null null when the fragment cannot be parsed
     */
    private function registeredIdsIn(Htmlable $contents, bool $excludeTemplates): ?array
    {
        $elements = $this->registeredElementsIn($contents, $excludeTemplates);
        if ($elements === null) {
            return null;
        }

        $found = [];
        foreach ($elements as $element) {
            $found[$element->getAttribute('id')] = true;
        }

        return $found;
    }

    /** @return DOMElement[]|null null when the fragment cannot be parsed */
    private function registeredElementsIn(Htmlable $contents, bool $excludeTemplates): ?array
    {
        $registered = array_fill_keys([...$this->titleIds, ...$this->descriptionIds], true);
        if ($registered === []) {
            return [];
        }

        $html = $contents->toHtml();
        if (preg_match('/\sid\s*=/i', $html) !== 1) {
            return [];
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $nodes = (new DOMXPath($document))->query('//*[@id]');
        if ($nodes === false) {
            return null;
        }

        $elements = [];
        foreach ($nodes as $element) {
            if (
                $element instanceof DOMElement
                && isset($registered[$element->getAttribute('id')])
                && (! $excludeTemplates || ! $this->insideTemplate($element))
            ) {
                $elements[] = $element;
            }
        }

        return $elements;
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

    private function isRegisteredLabel(DOMElement $element): bool
    {
        $id = $element->getAttribute('id');
        $slot = $element->getAttribute('data-slot');

        return ($slot === "{$this->slotPrefix}-title" && in_array($id, $this->titleIds, true))
            || ($slot === "{$this->slotPrefix}-description" && in_array($id, $this->descriptionIds, true));
    }

    private function resolveId(?string $explicitId, string $kind, int $count): string
    {
        if ($explicitId !== null && $explicitId !== '') {
            if (isset($this->usedIds[$explicitId])) {
                throw new InvalidArgumentException("Overlay label id [{$explicitId}] is already in use.");
            }

            $this->usedIds[$explicitId] = true;

            return $explicitId;
        }

        do {
            $suffix = $count === 1 ? '' : "-{$count}";
            $id = "{$this->rootId}-{$kind}{$suffix}";
            $count++;
        } while (isset($this->usedIds[$id]));

        $this->usedIds[$id] = true;

        return $id;
    }
}
