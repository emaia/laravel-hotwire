<?php

use Emaia\LaravelHotwire\Components\FrameOrPage;
use Emaia\LaravelHotwire\Components\FrameOrPage\Frame as FrameBranch;
use Emaia\LaravelHotwire\Components\FrameOrPage\Page as PageBranch;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

class FrameOrPageMessage extends Model
{
    protected $guarded = [];
}

beforeEach(function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');
});

afterEach(function () {
    request()->headers->remove('Turbo-Frame');
    request()->headers->remove('X-Hotwire-Overlay-Host');
    request()->headers->remove('X-Hotwire-Overlay-Instance');
    request()->headers->remove('X-Hotwire-Overlay-Origin');
});

// --- Overlay instance frames ---

it('matches a concrete overlay instance without teaching the view its generated frame id', function () {
    $token = str_repeat('a', 32);
    request()->headers->set('Turbo-Frame', 'modal--'.$token);
    request()->headers->set('X-Hotwire-Overlay-Host', 'modal');
    request()->headers->set('X-Hotwire-Overlay-Instance', $token);

    $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal" layout="dashboard-shell">
            <x-hw::frame-or-page.frame target="modal">Instance content</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.page>Page content</x-hw::frame-or-page.page>
        </x-hw::frame-or-page>
    BLADE)
        ->assertSee('<turbo-frame id="modal--'.$token.'"', false)
        ->assertSee('Instance content')
        ->assertDontSee('Page content')
        ->assertDontSee('data-test-layout', false);
});

it('keeps canonical frame-only mode for ordinary requests and uses the instance when correlated', function () {
    $token = str_repeat('b', 32);
    request()->headers->set('Turbo-Frame', 'sheet--'.$token);
    request()->headers->set('X-Hotwire-Overlay-Host', 'sheet');
    request()->headers->set('X-Hotwire-Overlay-Instance', $token);

    $this->blade('<x-hw::frame-or-page frame="sheet">Panel</x-hw::frame-or-page>')
        ->assertSee('<turbo-frame id="sheet--'.$token.'"', false);

    request()->headers->remove('X-Hotwire-Overlay-Host');
    request()->headers->remove('X-Hotwire-Overlay-Instance');
    $this->blade('<x-hw::frame-or-page frame="sheet">Panel</x-hw::frame-or-page>')
        ->assertSee('<turbo-frame id="sheet"', false)
        ->assertDontSee('sheet--'.$token, false);
});

it('selects the canonical branch for one of several hosts even though the response uses its instance id', function () {
    $token = str_repeat('1', 32);
    request()->headers->set('Turbo-Frame', 'drawer--'.$token);
    request()->headers->set('X-Hotwire-Overlay-Host', 'drawer');
    request()->headers->set('X-Hotwire-Overlay-Instance', $token);

    $this->blade(<<<'BLADE'
        <x-hw::frame-or-page :frames="['modal', 'drawer']" layout="dashboard-shell">
            Shared content
            <x-hw::frame-or-page.frame target="modal">Modal only</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.frame target="drawer">Drawer only</x-hw::frame-or-page.frame>
        </x-hw::frame-or-page>
    BLADE)
        ->assertSee('<turbo-frame id="drawer--'.$token.'"', false)
        ->assertSee('Shared content')
        ->assertSee('Drawer only')
        ->assertDontSee('Modal only');
});

it('keeps a nested frame separate from its owning overlay while validating the origin context', function () {
    $token = str_repeat('3', 32);
    request()->headers->set('Turbo-Frame', 'customer-details');
    request()->headers->set('X-Hotwire-Overlay-Origin', 'modal--'.$token);

    $this->blade('<x-hw::frame-or-page frame="customer-details" layout="dashboard-shell">Nested content</x-hw::frame-or-page>')
        ->assertSee('<turbo-frame id="customer-details"', false)
        ->assertSee('Nested content')
        ->assertDontSee('data-test-layout', false);
});

it('selects the internal Frame branch even when its owning overlay host is also configured', function () {
    request()->headers->set('Turbo-Frame', 'customer-details');
    request()->headers->set('X-Hotwire-Overlay-Origin', 'modal--'.str_repeat('a', 32));

    expect(request()->wasFromOverlay(host: 'modal'))->toBeTrue();

    $this->blade(<<<'BLADE'
        <x-hw::frame-or-page :frames="['modal', 'customer-details']" layout="dashboard-shell">
            <x-hw::frame-or-page.frame target="modal">Outer content</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.frame target="customer-details">Inner content</x-hw::frame-or-page.frame>
        </x-hw::frame-or-page>
    BLADE)
        ->assertSee('<turbo-frame id="customer-details"', false)
        ->assertSee('Inner content')
        ->assertDontSee('Outer content');
});

it('rejects forged or inconsistent overlay origin headers', function (string $frame, string $origin, ?string $host, ?string $instance) {
    request()->headers->set('Turbo-Frame', $frame);
    request()->headers->set('X-Hotwire-Overlay-Origin', $origin);
    if ($host !== null) {
        request()->headers->set('X-Hotwire-Overlay-Host', $host);
    }
    if ($instance !== null) {
        request()->headers->set('X-Hotwire-Overlay-Instance', $instance);
    }

    expect(fn () => $this->blade('<x-hw::frame-or-page frame="customer-details" layout="dashboard-shell">Content</x-hw::frame-or-page>'))
        ->toThrow(ViewException::class, 'Invalid overlay origin context');
})->with([
    'invalid nested origin' => ['customer-details', '../../modal', null, null],
    'mismatched primary origin' => ['modal--'.str_repeat('4', 32), 'sheet--'.str_repeat('4', 32), 'modal', str_repeat('4', 32)],
]);

it('rejects an unrelated instance when a frame-only view has no page layout', function () {
    $token = str_repeat('2', 32);
    request()->headers->set('Turbo-Frame', 'drawer--'.$token);
    request()->headers->set('X-Hotwire-Overlay-Host', 'drawer');
    request()->headers->set('X-Hotwire-Overlay-Instance', $token);

    expect(fn () => $this->blade('<x-hw::frame-or-page frame="modal">Content</x-hw::frame-or-page>'))
        ->toThrow(ViewException::class, 'not configured for the requested overlay host');
});

it('rejects an unmatched overlay context instead of emitting a frame for another instance', function (string $frame, string $host) {
    $token = str_repeat('c', 32);
    request()->headers->set('Turbo-Frame', $frame.'--'.$token);
    request()->headers->set('X-Hotwire-Overlay-Host', $host);
    request()->headers->set('X-Hotwire-Overlay-Instance', $token);

    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="dashboard"', false)
        ->assertDontSee('<turbo-frame id="modal--'.$token.'"', false);
})->with([
    'other host' => ['drawer', 'drawer'],
    'unlisted host' => ['sheet', 'sheet'],
]);

it('rejects invalid instance transport before rendering', function (string $host, string $instance) {
    $token = str_repeat('d', 32);
    request()->headers->set('Turbo-Frame', 'modal--'.$token);
    request()->headers->set('X-Hotwire-Overlay-Host', $host);
    request()->headers->set('X-Hotwire-Overlay-Instance', $instance);

    expect(fn () => $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>'))
        ->toThrow(ViewException::class, 'Invalid overlay instance context');
})->with([
    'missing host' => ['', str_repeat('d', 32)],
    'invalid instance' => ['modal', '../../evil'],
    'mismatched instance' => ['modal', str_repeat('e', 32)],
]);

it('renders only the frame when no layout is given', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal">Content</x-hw::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Content');
    $view->assertDontSee('data-test-layout', false);
});

it('wraps the slot directly in the layout when no Turbo-Frame header is present', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('Content');
    // The layout typically hosts its own <turbo-frame id="modal"> (modal host);
    // wrapping the slot in another frame with the same id would duplicate ids.
    $view->assertDontSee('<turbo-frame id="modal"', false);
});

it('renders shared and matching lazy frame content for Turbo Frame requests', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page :frames="['modal', 'settings-panel']" layout="dashboard-shell">
            Shared form
            <x-hw::frame-or-page.frame>Any frame controls</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.frame target="modal">Modal controls</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.frame target="settings-panel">Settings controls</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.page>Page controls</x-hw::frame-or-page.page>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Shared form');
    $view->assertSee('Any frame controls');
    $view->assertSee('Modal controls');
    $view->assertDontSee('Settings controls');
    $view->assertDontSee('Page controls');
    $view->assertDontSee('data-test-layout', false);
});

it('renders shared and lazy page content for direct navigation', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page :frames="['modal', 'settings-panel']" layout="dashboard-shell">
            Shared form
            <x-hw::frame-or-page.frame>
                @php(throw new RuntimeException('Frame branch was evaluated.'))
            </x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.page>Page controls</x-hw::frame-or-page.page>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('Shared form');
    $view->assertSee('Page controls');
    $view->assertDontSee('<turbo-frame id="modal"', false);
});

it('does not evaluate discarded page or targeted frame branches', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page :frames="['modal', 'settings-panel']" layout="dashboard-shell">
            Shared form
            <x-hw::frame-or-page.frame target="modal">Modal controls</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.frame target="settings-panel">
                @php(throw new RuntimeException('Other frame branch was evaluated.'))
            </x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.page>
                @php(throw new RuntimeException('Page branch was evaluated.'))
            </x-hw::frame-or-page.page>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Shared form');
    $view->assertSee('Modal controls');
});

it('selects the matching frame from multiple configured frames', function () {
    request()->headers->set('Turbo-Frame', 'settings-panel');

    $view = $this->blade('<x-hw::frame-or-page :frames="[\'modal\', \'settings-panel\']" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('<turbo-frame id="settings-panel"', false);
    $view->assertDontSee('<turbo-frame id="modal"', false);
});

it('renders the page for unknown Turbo Frame headers', function () {
    request()->headers->set('Turbo-Frame', 'unknown');

    $view = $this->blade('<x-hw::frame-or-page :frames="[\'modal\', \'settings-panel\']" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertDontSee('<turbo-frame', false);
});

it('resolves simple layout names to layouts components when they exist', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="nested-dashboard"', false);
    $view->assertSee('Content');
});

it('preserves existing simple layout aliases before trying layouts components', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="direct-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="direct-shell"', false);
    $view->assertDontSee('data-test-layout="nested-direct-shell"', false);
});

it('renders only the frame when the Turbo-Frame header matches', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Content');
    $view->assertDontSee('data-test-layout', false);
});

it('normalizes the Turbo-Frame header through the Turbo request macro', function () {
    request()->headers->set('Turbo-Frame', ' modal ');

    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertDontSee('data-test-layout', false);
});

it('treats a blank layout as frame-only mode', function (string $layout) {
    $view = $this->blade(
        '<x-hw::frame-or-page frame="modal" :layout="$layout">Content</x-hw::frame-or-page>',
        ['layout' => $layout],
    );

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertDontSee('data-test-layout', false);
})->with(['empty' => '', 'whitespace' => '   ']);

it('keeps one configured frame in frame-only mode without evaluating the page branch', function (string $props) {
    $view = $this->blade(<<<BLADE
        <x-hw::frame-or-page {$props}>
            <x-hw::frame-or-page.frame>Frame content</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.page>
                @php(throw new RuntimeException('Page branch was evaluated.'))
            </x-hw::frame-or-page.page>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Frame content');
})->with([
    'frame prop' => 'frame="modal"',
    'single-item frames prop' => ':frames="[\'modal\']"',
]);

it('wraps the slot directly in the layout when the Turbo-Frame header is for a different frame', function () {
    request()->headers->set('Turbo-Frame', 'sidebar');

    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('Content');
    $view->assertDontSee('<turbo-frame id="modal"', false);
});

it('resolves the frame from a Model via dom_id', function () {
    $model = new FrameOrPageMessage;
    $model->id = 42;

    $view = $this->blade(
        '<x-hw::frame-or-page :frame="$model">Content</x-hw::frame-or-page>',
        ['model' => $model],
    );

    $view->assertSee('<turbo-frame id="frame_or_page_message_42"', false);
});

it('resolves models in the frames list and selects the requested id', function () {
    $model = new FrameOrPageMessage;
    $model->id = 43;
    request()->headers->set('Turbo-Frame', 'frame_or_page_message_43');

    $view = $this->blade(
        '<x-hw::frame-or-page :frames="[\'modal\', $model]" layout="dashboard-shell">Content</x-hw::frame-or-page>',
        ['model' => $model],
    );

    $view->assertSee('<turbo-frame id="frame_or_page_message_43"', false);
});

it('accepts a Collection as the frames list', function () {
    request()->headers->set('Turbo-Frame', 'settings-panel');

    $view = $this->blade(
        '<x-hw::frame-or-page :frames="$frames" layout="dashboard-shell">Content</x-hw::frame-or-page>',
        ['frames' => collect(['modal', 'settings-panel'])],
    );

    $view->assertSee('<turbo-frame id="settings-panel"', false);
});

it('forwards extra attributes to the inner turbo-frame', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" src="/edit" loading="lazy">Content</x-hw::frame-or-page>');

    $view->assertSee('src="/edit"', false);
    $view->assertSee('loading="lazy"', false);
});

// --- Overlay presentation ---

it('emits validated presentation metadata only for a matching frame response', function () {
    request()->headers->set('Turbo-Frame', 'sheet');

    $this->blade('<x-hw::frame-or-page frame="sheet" layout="dashboard-shell" overlay="sheet" overlay-side="left" overlay-size="28rem">Settings</x-hw::frame-or-page>')
        ->assertSee('data-overlay-side="left"', false)
        ->assertSee('data-overlay-size="28rem"', false)
        ->assertSee('data-overlay-family="sheet"', false);

    request()->headers->remove('Turbo-Frame');

    $this->blade('<x-hw::frame-or-page frame="sheet" layout="dashboard-shell" overlay="sheet" overlay-side="left" overlay-size="28rem">Settings</x-hw::frame-or-page>')
        ->assertDontSee('data-overlay-side=', false)
        ->assertDontSee('data-overlay-size=', false)
        ->assertDontSee('data-overlay-family=', false);
});

it('accepts a modal size without a side and drawer directions with their aliases', function () {
    $this->blade('<x-hw::frame-or-page frame="modal" overlay="modal" overlay-size="xl">Content</x-hw::frame-or-page>')
        ->assertSee('data-overlay-size="xl"', false)
        ->assertDontSee('data-overlay-side=', false);

    $this->blade('<x-hw::frame-or-page frame="drawer" overlay="drawer" overlay-side="top" overlay-size="30vh">Content</x-hw::frame-or-page>')
        ->assertSee('data-overlay-side="up"', false)
        ->assertSee('data-overlay-size="30vh"', false);
});

it('rejects ambiguous or incompatible overlay presentation', function (string $props, string $message) {
    expect(fn () => $this->blade("<x-hw::frame-or-page frame=\"modal\" {$props}>Content</x-hw::frame-or-page>"))
        ->toThrow(ViewException::class, $message);
})->with([
    ['overlay="modal" overlay-side="left"', 'Modal does not support overlay-side'],
    ['overlay-side="left"', 'Modal does not support overlay-side'],
    ['overlay="sheet" overlay-side="up"', 'Sheet overlay-side must be one of'],
    ['overlay="drawer" overlay-side="rigth"', 'Drawer overlay-side must be one of'],
    ['overlay="bogus" overlay-size="lg"', 'Overlay family must be one of'],
    ['overlay="modal" overlay-size=" "', 'Overlay size must be a non-empty'],
    ['overlay="sheet" overlay-size="28rem; color:red"', 'Overlay size must be a non-empty'],
]);

it('infers the family from a canonical host frame and requires it for a custom host', function () {
    $this->blade('<x-hw::frame-or-page frame="sheet" overlay-side="left">Content</x-hw::frame-or-page>')
        ->assertSee('data-overlay-family="sheet"', false)
        ->assertSee('data-overlay-side="left"', false);

    $this->blade('<x-hw::frame-or-page frame="cart-panel" overlay="drawer" overlay-side="bottom">Content</x-hw::frame-or-page>')
        ->assertSee('data-overlay-family="drawer"', false)
        ->assertSee('data-overlay-side="down"', false);

    expect(fn () => $this->blade('<x-hw::frame-or-page frame="cart-panel" overlay-size="lg">Content</x-hw::frame-or-page>'))
        ->toThrow(ViewException::class, 'Overlay presentation requires an overlay family');
});

it('supports frame component aliases when rendering as a frame', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" lazy advance>Content</x-hw::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('data-turbo-action="advance"', false)
        ->assertDontSee(' lazy', false)
        ->assertDontSee(' advance', false);
});

it('forwards polling and view transition integrations to the inner frame', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" poll view-transition data-controller="analytics">Content</x-hw::frame-or-page>');

    $view->assertSee('data-controller="turbo--polling turbo--view-transition analytics"', false);
});

it('does NOT emit a duplicate frame id when the layout already hosts a frame with the same id', function () {
    // The dashboard-with-modal fixture renders its own <turbo-frame id="modal"> (the modal host).
    // The component must not wrap the slot in another <turbo-frame id="modal"> on direct nav,
    // or the page ends up with duplicated ids and Turbo aims content at the wrong frame.
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-with-modal">Content</x-hw::frame-or-page>');

    expect(substr_count($view->__toString(), 'id="modal"'))->toBe(1);
    $view->assertSee('data-test-layout="dashboard-with-modal"', false);
    $view->assertSee('Content');
});

it('does not forward turbo-frame attributes to the layout slot on direct nav', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell" src="/edit" loading="lazy">Content</x-hw::frame-or-page>');

    // The slot is rendered directly inside the layout; frame-specific attrs have nowhere to go
    // and must not leak onto the layout wrapper or be inlined next to the content.
    $view->assertDontSee('src="/edit"', false);
    $view->assertDontSee('loading="lazy"', false);
});

it('rejects an empty string frame id', function () {
    $this->blade('<x-hw::frame-or-page frame="">Content</x-hw::frame-or-page>')->render();
})->throws(ViewException::class, 'The frame prop must be a non-empty string');

it('requires exactly one of frame or frames', function (string $template) {
    $this->blade($template);
})->with([
    'neither prop' => '<x-hw::frame-or-page>Content</x-hw::frame-or-page>',
    'both props' => '<x-hw::frame-or-page frame="modal" :frames="[\'modal\']">Content</x-hw::frame-or-page>',
])->throws(ViewException::class, 'Exactly one of the frame or frames props must be provided.');

it('rejects an empty or associative frames list', function (string $frames) {
    $this->blade("<x-hw::frame-or-page :frames=\"{$frames}\" layout=\"dashboard-shell\">Content</x-hw::frame-or-page>");
})->with([
    'empty' => '[]',
    'associative' => "['modal' => 'modal']",
])->throws(ViewException::class, 'The frames prop must be a non-empty list of strings or objects resolvable via dom_id().');

it('requires a layout when more than one frame is configured', function () {
    $this->blade('<x-hw::frame-or-page :frames="[\'modal\', \'settings-panel\']">Content</x-hw::frame-or-page>');
})->throws(ViewException::class, 'The layout prop is required when more than one frame is configured.');

it('rejects targets not declared by the parent', function () {
    $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal" layout="dashboard-shell">
            <x-hw::frame-or-page.frame target="settings-panel">Content</x-hw::frame-or-page.frame>
        </x-hw::frame-or-page>
    BLADE);
})->throws(ViewException::class, 'The frame-or-page.frame target [settings-panel] is not declared in the parent frame or frames prop.');

it('rejects attributes on renderless contextual subcomponents', function (string $template, string $branch, string $attribute) {
    expect(fn () => $this->blade($template))->toThrow(
        ViewException::class,
        "The frame-or-page.{$branch} component does not accept HTML attributes [{$attribute}].",
    );
})->with([
    'frame class' => [
        '<x-hw::frame-or-page frame="modal"><x-hw::frame-or-page.frame class="hidden">Content</x-hw::frame-or-page.frame></x-hw::frame-or-page>',
        'frame',
        'class',
    ],
    'page class' => [
        '<x-hw::frame-or-page frame="modal" layout="dashboard-shell"><x-hw::frame-or-page.page class="hidden">Content</x-hw::frame-or-page.page></x-hw::frame-or-page>',
        'page',
        'class',
    ],
    'page target typo' => [
        '<x-hw::frame-or-page frame="modal" layout="dashboard-shell"><x-hw::frame-or-page.page target="modal">Content</x-hw::frame-or-page.page></x-hw::frame-or-page>',
        'page',
        'target',
    ],
]);

it('does not mistake attributes from an enclosing component for page branch attributes', function () {
    $view = $this->blade('<x-frame-or-page-card target="modal" />');

    $view->assertSee('target="modal"', false);
    $view->assertSee('Page content');
});

it('ignores attributes and content on a discarded frame branch', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal" layout="dashboard-shell">
            <x-hw::frame-or-page.frame class="hidden">Discarded frame content</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.page>Page content</x-hw::frame-or-page.page>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('Page content');
    $view->assertDontSee('Discarded frame content');
    $view->assertDontSee('class="hidden"', false);
});

it('ignores attributes and content on a discarded page branch', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal" layout="dashboard-shell">
            <x-hw::frame-or-page.frame>Frame content</x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.page class="hidden">Discarded page content</x-hw::frame-or-page.page>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Frame content');
    $view->assertDontSee('Discarded page content');
    $view->assertDontSee('class="hidden"', false);
});

it('rejects contextual subcomponents outside frame-or-page', function (string $component) {
    $this->blade("<x-hw::frame-or-page.{$component}>Content</x-hw::frame-or-page.{$component}>");
})->with(['frame', 'page'])
    ->throws(ViewException::class, 'FrameOrPage contextual components must be used inside <hw:frame-or-page>.');

it('rejects removed contextual slots with migration guidance', function () {
    $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal" layout="dashboard-shell">
            <x-slot:frameContent>Frame content</x-slot:frameContent>
            <x-slot:pageContent>Page content</x-slot:pageContent>
        </x-hw::frame-or-page>
    BLADE);
})->throws(
    ViewException::class,
    'The frameContent and pageContent slots were removed. Use <hw:frame-or-page.frame> and <hw:frame-or-page.page> instead.',
);

it('uses the nearest frame-or-page ancestor for nested contextual components', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="outer" layout="dashboard-shell">
            <x-hw::frame-or-page.frame>
                @php(throw new RuntimeException('Outer frame branch was evaluated.'))
            </x-hw::frame-or-page.frame>
            <x-hw::frame-or-page.page>
                <x-hw::frame-or-page frame="inner">
                    <x-hw::frame-or-page.frame target="inner">Inner frame content</x-hw::frame-or-page.frame>
                    <x-hw::frame-or-page.page>
                        @php(throw new RuntimeException('Inner page branch was evaluated.'))
                    </x-hw::frame-or-page.page>
                </x-hw::frame-or-page>
            </x-hw::frame-or-page.page>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('<turbo-frame id="inner"', false);
    $view->assertSee('Inner frame content');
});

it('registers contextual subcomponents for default and custom prefixes', function () {
    config()->set('hotwire.prefix', 'custom');

    (new LaravelHotwireServiceProvider($this->app))->bootBladeIntegration();

    expect(Blade::getClassComponentAliases())
        ->toHaveKeys([
            'hw::frame-or-page.frame',
            'hw::frame-or-page.page',
            'custom::frame-or-page.frame',
            'custom::frame-or-page.page',
        ]);

    request()->headers->set('Turbo-Frame', 'modal');

    $this->blade(<<<'BLADE'
        <x-custom::frame-or-page frame="modal" layout="dashboard-shell">
            <x-custom::frame-or-page.frame>Custom prefix frame</x-custom::frame-or-page.frame>
            <x-custom::frame-or-page.page>Custom prefix page</x-custom::frame-or-page.page>
        </x-custom::frame-or-page>
    BLADE)
        ->assertSee('Custom prefix frame')
        ->assertDontSee('Custom prefix page');
});

it('registers contextual subcomponents in the component catalog', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('frame-or-page')->controllers)
        ->toBe(['turbo--polling', 'turbo--view-transition'])
        ->and($registry->component('frame-or-page.frame'))
        ->class->toBe(FrameBranch::class)
        ->view->toBe('hotwire::component-views.frame-or-page-branch')
        ->and($registry->component('frame-or-page.page'))
        ->class->toBe(PageBranch::class)
        ->view->toBe('hotwire::component-views.frame-or-page-branch');
});

it('exposes the resolved frame id as a public property', function () {
    $component = new FrameOrPage(frame: 'modal');

    expect($component->frameId)->toBe('modal')
        ->and($component->frameIds)->toBe(['modal'])
        ->and($component->activeFrameId)->toBe('modal');
});

it('resolves a Model frame id eagerly in the constructor', function () {
    $model = new FrameOrPageMessage;
    $model->id = 7;

    $component = new FrameOrPage(frame: $model);

    expect($component->frameId)->toBe('frame_or_page_message_7');
});
