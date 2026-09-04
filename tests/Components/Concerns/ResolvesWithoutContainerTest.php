<?php

use Emaia\LaravelHotwire\Components\BaseComponent;
use Emaia\LaravelHotwire\Components\Concerns\ResolvesWithoutContainer;
use Emaia\LaravelHotwire\Components\Icon;
use Emaia\LaravelHotwire\Components\Input;
use Emaia\LaravelHotwire\Components\Separator;
use Emaia\LaravelHotwire\Components\Timeago;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Container\Util;
use Illuminate\View\Component;

class VariadicResolutionComponent extends BaseComponent
{
    /** @var list<string> */
    public array $values;

    public function __construct(string ...$values)
    {
        $this->values = $values;
    }

    public function render(): string
    {
        return '';
    }
}

/** @return list<class-string> */
function fastResolutionComponentClasses(): array
{
    return collect(HotwireRegistry::make()->components())
        ->map(fn ($definition): string => $definition->class)
        ->merge(ComponentAliases::subComponents())
        ->unique()
        ->values()
        ->all();
}

afterEach(function () {
    Component::forgetComponentsResolver();
});

it('applies fast resolution to every registered package component', function () {
    foreach (fastResolutionComponentClasses() as $class) {
        expect(is_subclass_of($class, BaseComponent::class))
            ->toBeTrue("Component [{$class}] does not extend the package base component.")
            ->and(class_uses_recursive($class))
            ->toHaveKey(ResolvesWithoutContainer::class);
    }
});

it('keeps optional typed defaults equivalent to container resolution', function () {
    foreach (fastResolutionComponentClasses() as $class) {
        $constructor = (new ReflectionClass($class))->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            if (! $parameter->isDefaultValueAvailable()
                || ($dependency = Util::getParameterClassName($parameter)) === null) {
                continue;
            }

            // Mirror Container::resolveClass(): either binding replaces an optional typed default.
            $hasContextualBinding = array_key_exists(
                app()->getAlias($dependency),
                app()->contextual[$class] ?? [],
            );

            expect(app()->bound($dependency) || $hasContextualBinding)->toBeFalse(
                "Optional parameter [{$class}::\${$parameter->getName()}] has container-bound type [{$dependency}].",
            );
        }
    }
});

it('resolves optional-only components without invoking their container binding', function () {
    app()->bind(Separator::class, fn () => throw new RuntimeException('Container binding was invoked.'));

    $component = Separator::resolve([
        'orientation' => 'vertical',
        'ignored' => 'value',
    ]);

    expect($component->orientation)->toBe('vertical')
        ->and($component->slotName)->toBe('separator');
});

it('preserves omitted nullable interface defaults without invoking the container', function () {
    app()->bind(Input::class, fn () => throw new RuntimeException('Container binding was invoked.'));

    $component = Input::resolve(['name' => 'email']);

    expect($component->name)->toBe('email')
        ->and($component->stimulus)->toBeNull();
});

it('resolves components directly when all required parameters are supplied', function () {
    app()->bind(Timeago::class, fn () => throw new RuntimeException('Container binding was invoked.'));

    $component = Timeago::resolve(['datetime' => '2026-09-03 12:00:00']);

    expect($component->datetime)->toBe('2026-09-03 12:00:00')
        ->and($component->addSuffix)->toBeTrue()
        ->and($component->stimulus)->toBeNull();
});

it('delegates components with required parameters to Laravel resolution', function () {
    app()->bind(Icon::class, fn () => new Icon('container-resolved'));

    expect(Icon::resolve([])->name)->toBe('container-resolved');
});

it('delegates variadic components to Laravel resolution', function () {
    app()->bind(VariadicResolutionComponent::class, fn () => new VariadicResolutionComponent('container-resolved'));

    expect(VariadicResolutionComponent::resolve([])->values)->toBe(['container-resolved']);
});

it('preserves an application component resolver', function () {
    Component::resolveComponentsUsing(
        fn (string $class, array $data): Component => new $class(orientation: 'vertical'),
    );

    expect(Separator::resolve([])->orientation)->toBe('vertical');
});

it('renders byte-identical HTML with container and fast resolution', function () {
    $template = '<x-hw::input name="email" id="email" /><x-hw::separator orientation="vertical" />';
    Component::resolveComponentsUsing(
        fn (string $class, array $data): Component => app()->make($class, $data),
    );
    $containerHtml = (string) $this->blade($template);
    Component::forgetComponentsResolver();

    expect((string) $this->blade($template))->toBe($containerHtml);
});
