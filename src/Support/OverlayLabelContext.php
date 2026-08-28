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

    /** @var array<string, list<array{id: string, slot: string, overlay: ?string, insideTemplate: bool}>> */
    private array $inspectionCache = [];

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
            'modalAriaLabel' => null,
            'modalAriaLabelledby' => null,
            'modalAriaDescription' => null,
            'modalAriaDescribedby' => null,
            'sheetAriaLabel' => null,
            'sheetAriaLabelledby' => null,
            'sheetAriaDescription' => null,
            'sheetAriaDescribedby' => null,
            'drawerAriaLabel' => null,
            'drawerAriaLabelledby' => null,
            'drawerAriaDescription' => null,
            'drawerAriaDescribedby' => null,
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

        $this->inspectionCache = [];

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

    /** Return the first registered title id. */
    public function titleId(): ?string
    {
        return $this->titleIds[0] ?? null;
    }

    /** Return the first registered description id. */
    public function descriptionId(): ?string
    {
        return $this->descriptionIds[0] ?? null;
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

        $reachable = $this->registeredIdsIn($contents);

        return [
            'title' => $this->firstReachableId($this->titleIds, $reachable),
            'description' => $this->firstReachableId($this->descriptionIds, $reachable),
        ];
    }

    /**
     * Validate rendered content and return its reachable title and description ids.
     *
     * @return array{title: ?string, description: ?string}
     */
    public function resolveReferences(Htmlable $contents): array
    {
        $this->assertNoIdCollisions($contents);

        return $this->referencesFor($contents);
    }

    /** Report whether rendered content contains a registered overlay label. */
    public function hasRegisteredLabels(Htmlable $contents): bool
    {
        foreach ($this->inspect($contents) as $element) {
            if (! $element['insideTemplate'] && $this->isRegisteredLabel($element)) {
                return true;
            }
        }

        return false;
    }

    /** Reject authored elements that reuse an id owned by an overlay label. */
    public function assertNoIdCollisions(Htmlable $contents, string $scope = 'content'): void
    {
        $this->assertNoIdCollisionsIn($this->inspect($contents), $scope);
    }

    /** Validate label placement and ids across an overlay's complete rendered slot. */
    public function validateRoot(Htmlable $contents): void
    {
        $elements = $this->inspect($contents);
        $this->assertNoIdCollisionsIn($elements, 'root');

        foreach ($elements as $element) {
            if (! $element['insideTemplate'] && $this->isSemanticLabel($element) && $element['overlay'] === null) {
                $component = ucfirst($this->slotPrefix);

                throw new InvalidArgumentException(
                    "{$component} title and description subcomponents must be rendered in {$this->slotPrefix}.content.",
                );
            }
        }
    }

    /** @return array<string, true> */
    private function registeredIdsIn(Htmlable $contents): array
    {
        $registered = array_fill_keys([...$this->titleIds, ...$this->descriptionIds], true);
        $found = [];
        foreach ($this->inspect($contents) as $element) {
            if (
                $element['id'] !== ''
                && isset($registered[$element['id']])
                && $element['overlay'] === null
                && ! $element['insideTemplate']
            ) {
                $found[$element['id']] = true;
            }
        }

        return $found;
    }

    /** @return list<array{id: string, slot: string, overlay: ?string, insideTemplate: bool}> */
    private function inspect(Htmlable $contents): array
    {
        $html = $contents->toHtml();
        if (isset($this->inspectionCache[$html])) {
            return $this->inspectionCache[$html];
        }

        if (
            $this->titleIds === []
            && $this->descriptionIds === []
            && ! str_contains($html, "{$this->slotPrefix}-title")
            && ! str_contains($html, "{$this->slotPrefix}-description")
        ) {
            return $this->inspectionCache[$html] = [];
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
            return $this->inspectionCache[$html] = [];
        }

        $nodes = (new DOMXPath($document))->query('//*[@id or @data-slot]');
        if ($nodes === false) {
            return $this->inspectionCache[$html] = [];
        }

        $elements = [];
        foreach ($nodes as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            $elements[] = [
                'id' => $element->getAttribute('id'),
                'slot' => $element->getAttribute('data-slot'),
                'overlay' => $this->nearestOverlaySlot($element),
                'insideTemplate' => $this->insideTemplate($element),
            ];
        }

        return $this->inspectionCache[$html] = $elements;
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

    /** @param array{id: string, slot: string, overlay: ?string, insideTemplate: bool} $element */
    private function isRegisteredLabel(array $element): bool
    {
        return ($element['slot'] === "{$this->slotPrefix}-title" && in_array($element['id'], $this->titleIds, true))
            || ($element['slot'] === "{$this->slotPrefix}-description" && in_array($element['id'], $this->descriptionIds, true));
    }

    /** @param array{id: string, slot: string, overlay: ?string, insideTemplate: bool} $element */
    private function isSemanticLabel(array $element): bool
    {
        return $element['slot'] === "{$this->slotPrefix}-title"
            || $element['slot'] === "{$this->slotPrefix}-description";
    }

    /** @param list<array{id: string, slot: string, overlay: ?string, insideTemplate: bool}> $elements */
    private function assertNoIdCollisionsIn(array $elements, string $scope): void
    {
        $registered = array_fill_keys([...$this->titleIds, ...$this->descriptionIds], true);
        $counts = [];
        foreach ($elements as $element) {
            if ($element['insideTemplate'] || $element['id'] === '' || ! isset($registered[$element['id']])) {
                continue;
            }

            $counts[$element['id']] = ($counts[$element['id']] ?? 0) + 1;
            if ($counts[$element['id']] > 1 || ! $this->isRegisteredLabel($element)) {
                throw new InvalidArgumentException(
                    "Overlay label id [{$element['id']}] conflicts with another element in its {$scope}.",
                );
            }
        }
    }

    private function nearestOverlaySlot(DOMElement $element): ?string
    {
        for ($node = $element->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
            $slot = $node->getAttribute('data-slot');
            if (in_array($slot, ['modal-overlay', 'sheet-overlay', 'drawer-overlay', 'alert-dialog-overlay'], true)) {
                return $slot;
            }

            $role = $node->getAttribute('role');
            if (in_array($role, ['dialog', 'alertdialog'], true)) {
                return "role:{$role}";
            }
        }

        return null;
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
