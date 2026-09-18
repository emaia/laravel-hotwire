<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\Facades\File;

/*
 * The shadcn corpus map is study data, not a runtime registry. These tests keep it honest:
 * every upstream class carries a decision, every decision points at a slot the package really
 * declares, and every visual slot answers the comparison in the opposite direction.
 */

const SHADCN_UPSTREAM_DECISIONS = ['equivalent', 'renamed', 'divergent', 'not-applicable'];
const SHADCN_SLOT_DECISIONS = ['equivalent', 'renamed', 'divergent', 'hotwire-only'];

function shadcnCorpus(): array
{
    return require __DIR__.'/../Fixtures/shadcn/corpus.php';
}

function shadcnCorpusMap(): array
{
    return require __DIR__.'/../Fixtures/shadcn/map.php';
}

function shadcnRegistrySlots(): array
{
    $slots = [];

    foreach ([...array_values(HotwireRegistry::make()->components()), ...array_values(HotwireRegistry::make()->controllers())] as $definition) {
        $slots = [...$slots, ...$definition->styling->slots];
    }

    return $slots;
}

function shadcnVisualSlots(): array
{
    return array_keys(array_filter(shadcnRegistrySlots(), fn (string $kind): bool => $kind === 'visual'));
}

// --- References and method ---

it('pins the upstream revision the corpus was extracted from', function () {
    $corpus = shadcnCorpus();

    expect($corpus['reference']['repository'])->toBe('shadcn-ui/ui')
        ->and($corpus['reference']['commit'])->toMatch('/^[0-9a-f]{40}$/')
        ->and($corpus['reference']['sources'])->toHaveCount(8)
        ->and($corpus['reference']['extraction'])->toBeString()->not->toBeEmpty();
});

/**
 * Re-extract the inventory from a real checkout of the pinned revision.
 *
 * The upstream sources are not vendored, so this is the only place the fixture is checked against the corpus it
 * claims to describe rather than against itself. It skips when no checkout is reachable, and refuses to pass on a
 * checkout that drifted from the pinned commit.
 */
it('matches a checkout of the pinned upstream revision', function () {
    $root = getenv('HOTWIRE_SHADCN_REFERENCE') ?: __DIR__.'/../../../laravel-hotwire-references/ui';

    if (! is_dir($root.'/.git')) {
        $this->markTestSkipped('Set HOTWIRE_SHADCN_REFERENCE to a shadcn-ui/ui checkout to run this guard.');
    }

    $corpus = shadcnCorpus();
    $head = trim((string) shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD 2>/dev/null'));

    if ($head !== $corpus['reference']['commit']) {
        $this->markTestSkipped("Checkout is at [{$head}], not the pinned revision.");
    }

    $extracted = [];

    foreach ($corpus['reference']['sources'] as $source) {
        $style = preg_replace('/^style-|\.css$/', '', basename($source));
        $css = preg_replace('#/\*.*?\*/#s', '', File::get($root.'/'.$source));
        preg_match_all('/\.(cn-[a-zA-Z0-9_-]+)/', (string) $css, $matches);

        foreach (array_unique($matches[1]) as $class) {
            $extracted[$class][] = $style;
        }
    }

    ksort($extracted);

    expect($extracted)->toBe($corpus['classes']);
});

it('pins the package revision the decisions were verified against', function () {
    $map = shadcnCorpusMap();

    expect($map['reference']['commit'])->toMatch('/^[0-9a-f]{40}$/')
        ->and($map['reference']['upstream'])->toBe(shadcnCorpus()['reference']['commit']);
});

// --- Upstream direction ---

it('decides every class in the shadcn corpus', function () {
    $undecided = array_values(array_diff(array_keys(shadcnCorpus()['classes']), array_keys(shadcnCorpusMap()['upstream'])));

    expect($undecided)->toBe([]);
});

it('decides no class outside the shadcn corpus', function () {
    $unknown = array_values(array_diff(array_keys(shadcnCorpusMap()['upstream']), array_keys(shadcnCorpus()['classes'])));

    expect($unknown)->toBe([]);
});

it('shapes every upstream decision', function () {
    foreach (shadcnCorpusMap()['upstream'] as $class => $entry) {
        expect($entry['decision'] ?? null)->toBeIn(SHADCN_UPSTREAM_DECISIONS, "Class [{$class}] must carry a known decision.")
            ->and(array_diff(array_keys($entry), ['decision', 'slot', 'note']))->toBe([], "Class [{$class}] declares unknown keys.");

        if (in_array($entry['decision'], ['equivalent', 'renamed'], true)) {
            expect($entry['slot'] ?? null)->toBeString("Class [{$class}] must name the slot it maps to.");
        }

        if ($entry['decision'] === 'not-applicable') {
            expect(array_key_exists('slot', $entry))->toBeFalse("Class [{$class}] is not applicable and must not name a slot.");
        }

        if (in_array($entry['decision'], ['divergent', 'not-applicable'], true)) {
            expect($entry['note'] ?? '')->not->toBe('', "Class [{$class}] must explain its divergence.");
        }
    }
});

it('references only slots the registry declares', function () {
    $declared = array_keys(shadcnRegistrySlots());
    $dangling = [];

    foreach (shadcnCorpusMap()['upstream'] as $class => $entry) {
        if (isset($entry['slot']) && ! in_array($entry['slot'], $declared, true)) {
            $dangling[] = "{$class} -> {$entry['slot']}";
        }
    }

    foreach (shadcnCorpusMap()['slots'] as $slot => $entry) {
        if (! in_array($slot, $declared, true)) {
            $dangling[] = "slot {$slot}";
        }
    }

    expect($dangling)->toBe([]);
});

it('verifies equivalent decisions against the slot name rather than trusting it', function () {
    foreach (shadcnCorpusMap()['upstream'] as $class => $entry) {
        if ($entry['decision'] !== 'equivalent') {
            continue;
        }

        expect('cn-'.$entry['slot'])->toBe($class, "Class [{$class}] claims an equivalent slot under a different name.");
    }
});

// --- Inverse direction ---

it('decides every visual registry slot against the corpus', function () {
    $undecided = array_values(array_diff(shadcnVisualSlots(), array_keys(shadcnCorpusMap()['slots'])));

    expect($undecided)->toBe([]);
});

it('decides no slot the registry does not expose as visual', function () {
    $stale = array_values(array_diff(array_keys(shadcnCorpusMap()['slots']), shadcnVisualSlots()));

    expect($stale)->toBe([]);
});

it('leaves hotwire-only slots unreferenced by the corpus', function () {
    $referenced = [];

    foreach (shadcnCorpusMap()['upstream'] as $class => $entry) {
        if (isset($entry['slot'])) {
            $referenced[$entry['slot']][] = $class;
        }
    }

    $contradictions = [];

    foreach (shadcnCorpusMap()['slots'] as $slot => $entry) {
        if ($entry['decision'] === 'hotwire-only' && isset($referenced[$slot])) {
            $contradictions[] = "{$slot} <- ".implode(', ', $referenced[$slot]);
        }
    }

    expect($contradictions)->toBe([]);
});

it('shapes every slot decision', function () {
    foreach (shadcnCorpusMap()['slots'] as $slot => $entry) {
        expect($entry['decision'] ?? null)->toBeIn(SHADCN_SLOT_DECISIONS, "Slot [{$slot}] must carry a known decision.")
            ->and(array_diff(array_keys($entry), ['decision', 'class', 'note']))->toBe([], "Slot [{$slot}] declares unknown keys.");

        if (in_array($entry['decision'], ['equivalent', 'renamed'], true)) {
            expect($entry['class'] ?? null)->toBeString("Slot [{$slot}] must name the upstream class it maps to.");
            expect(array_key_exists($entry['class'], shadcnCorpus()['classes']))
                ->toBeTrue("Slot [{$slot}] names a class outside the corpus.");
        }

        if ($entry['decision'] === 'hotwire-only') {
            expect(array_key_exists('class', $entry))
                ->toBeFalse("Slot [{$slot}] has no upstream class and must not name one.");
        }

        if (in_array($entry['decision'], ['divergent', 'hotwire-only'], true)) {
            expect($entry['note'] ?? '')->not->toBe('', "Slot [{$slot}] must explain its divergence.");
        }
    }
});

it('agrees with itself in both directions', function () {
    $upstream = shadcnCorpusMap()['upstream'];
    $disagreements = [];

    foreach (shadcnCorpusMap()['slots'] as $slot => $entry) {
        if (! isset($entry['class'])) {
            continue;
        }

        $counterpart = $upstream[$entry['class']] ?? null;

        if ($counterpart === null
            || ($counterpart['slot'] ?? null) !== $slot
            || $counterpart['decision'] !== $entry['decision']) {
            $disagreements[] = "{$slot} <-> {$entry['class']}";
        }
    }

    expect($disagreements)->toBe([]);
});

it('records an inverse decision for every slot an upstream class maps onto', function () {
    $slots = shadcnCorpusMap()['slots'];
    $missing = [];

    foreach (shadcnCorpusMap()['upstream'] as $class => $entry) {
        if (! in_array($entry['decision'], ['equivalent', 'renamed'], true)) {
            continue;
        }

        if (($slots[$entry['slot']]['class'] ?? null) !== $class) {
            $missing[] = "{$class} -> {$entry['slot']}";
        }
    }

    expect($missing)->toBe([]);
});

// --- Study boundary ---

it('keeps the corpus map out of the package runtime', function () {
    $referencing = collect(File::allFiles(__DIR__.'/../../src'))
        ->merge(File::allFiles(__DIR__.'/../../resources'))
        ->filter(fn (SplFileInfo $file): bool => str_contains($file->getContents(), 'Fixtures/shadcn'))
        ->map(fn (SplFileInfo $file): string => $file->getRelativePathname())
        ->values()
        ->all();

    expect($referencing)->toBe([]);
});
