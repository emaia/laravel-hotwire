<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders class components with the short hw tag syntax', function () {
    $view = $this->blade('<hw:button variant="destructive">Delete</hw:button>');

    $view->assertSee('data-slot="button"', false)
        ->assertSee('data-variant="destructive"', false)
        ->assertSeeText('Delete');
});

it('renders self-closing components with the short hw tag syntax', function () {
    $view = $this->blade('<hw:spinner class="size-4" />');

    $view->assertSee('data-slot="spinner"', false)
        ->assertSee('class="size-4"', false);
});

it('renders subcomponents with the short hw tag syntax', function () {
    $view = $this->blade('
        <hw:field.set class="space-y-4">
            <hw:field.legend variant="label">Preferences</hw:field.legend>
            <hw:field.content>Content</hw:field.content>
        </hw:field.set>
    ');

    $view->assertSee('data-slot="field-set"', false)
        ->assertSee('class="space-y-4"', false)
        ->assertSee('data-slot="field-legend"', false)
        ->assertSee('data-slot="field-content"', false)
        ->assertSeeText('Preferences')
        ->assertSeeText('Content');
});

it('keeps the x-hw namespace working as a blade alias', function () {
    $view = $this->blade('<x-hw::button>Save</x-hw::button>');

    $view->assertSee('data-slot="button"', false)
        ->assertSeeText('Save');
});

it('does not register legacy namespaces', function () {
    expect(Blade::getClassComponentAliases())
        ->not->toHaveKey('hwc::button')
        ->not->toHaveKey('hotwire::button');
});

it('does not compile legacy short tag prefixes', function (string $prefix) {
    $view = $this->blade("<{$prefix}:button>Save</{$prefix}:button>");

    $view->assertSee("<{$prefix}:button>Save</{$prefix}:button>", false);
})->with(['hwc', 'hotwire']);

it('supports the configured tag prefix', function () {
    config()->set('hotwire.prefix', 'ui');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->bootBladeIntegration();

    $view = $this->blade('<ui:button>Save</ui:button>');

    $view->assertSee('data-slot="button"', false)
        ->assertSeeText('Save');
});

it('does not compile short tags inside native component attributes', function () {
    $view = $this->blade('<x-hw::button title="Press <hw:kbd>O</hw:kbd>">Save</x-hw::button>');

    $view->assertSee('title="Press <hw:kbd>O</hw:kbd>"', false)
        ->assertSeeText('Save');
});

it('does not compile short tags inside php strings', function () {
    $view = $this->blade(<<<'BLADE'
        <?php $markup = '<hw:badge>Example</hw:badge>'; ?>
        {{ $markup }}
    BLADE);

    $view->assertSee('&lt;hw:badge&gt;Example&lt;/hw:badge&gt;', false);
});

it('does not compile short tags inside php heredocs and nowdocs', function (string $declaration) {
    $view = $this->blade("<?php \$label = 'Example'; \$markup = {$declaration}; ?>\n{{ \$markup }}");

    $view->assertSee('&lt;hw:badge&gt;Example&lt;/hw:badge&gt;', false);
})->with([
    'heredoc' => '<<<BLADE'."\n".'<hw:badge>{$label}</hw:badge>'."\n".'BLADE',
    'nowdoc' => "<<<'BLADE'\n<hw:badge>Example</hw:badge>\nBLADE",
]);

it('still compiles short tags in markup between php blocks', function () {
    $view = $this->blade(<<<'BLADE'
        <?php $label = 'Save'; ?>
        <hw:button>{{ $label }}</hw:button>
    BLADE);

    $view->assertSee('data-slot="button"', false)
        ->assertSeeText('Save');
});

it('leaves short tags untouched in blade protected regions', function () {
    $view = $this->blade(<<<'BLADE'
        @php
            $markup = '<hw:badge>PHP</hw:badge>';
        @endphp
        {{ $markup }}
        @verbatim
            <hw:badge>Verbatim</hw:badge>
        @endverbatim
        {{-- <hw:badge>Comment</hw:badge> --}}
    BLADE);

    $view->assertSee('&lt;hw:badge&gt;PHP&lt;/hw:badge&gt;', false)
        ->assertSee('<hw:badge>Verbatim</hw:badge>', false)
        ->assertDontSee('Comment');
});

it('does not register implicit class namespaces that confuse completion', function () {
    expect(Blade::getClassComponentNamespaces())
        ->not->toHaveKey('hw')
        ->not->toHaveKey('hwc')
        ->not->toHaveKey('hotwire');
});
