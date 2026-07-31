<?php

use Emaia\LaravelHotwire\Components\Attachment\Trigger as AttachmentTrigger;
use Emaia\LaravelHotwire\Components\Badge;
use Emaia\LaravelHotwire\Components\Button;
use Emaia\LaravelHotwire\Components\ButtonGroup\Text as ButtonGroupText;
use Emaia\LaravelHotwire\Components\ConditionalField;
use Emaia\LaravelHotwire\Components\HoverCard\Trigger as HoverCardTrigger;
use Emaia\LaravelHotwire\Components\Item;
use Emaia\LaravelHotwire\Components\Modal\Close as ModalClose;
use Emaia\LaravelHotwire\Components\Modal\Trigger as ModalTrigger;
use Emaia\LaravelHotwire\Components\Navbar\Item as NavbarItem;
use Emaia\LaravelHotwire\Components\Sticky;

it('normalizes every public polymorphic tag prop', function () {
    expect((new Button(as: ' A '))->as)->toBe('a')
        ->and((new Badge(as: ' A '))->as)->toBe('a')
        ->and((new Item(as: ' BUTTON '))->as)->toBe('button')
        ->and((new Sticky(as: ' FOOTER '))->as)->toBe('footer')
        ->and((new ButtonGroupText(as: ' SPAN '))->as)->toBe('span')
        ->and((new HoverCardTrigger(as: ' A '))->as)->toBe('a')
        ->and((new NavbarItem(as: ' SPAN '))->tag)->toBe('span')
        ->and((new ModalTrigger(as: ' A '))->as)->toBe('a')
        ->and((new ModalClose(as: ' A '))->as)->toBe('a')
        ->and((new AttachmentTrigger(as: ' SPAN '))->as)->toBe('span')
        ->and((new ConditionalField(when: 'plan=pro', as: ' DIV '))->as)->toBe('div');
});

it('rejects unsupported tags on every polymorphic component', function () {
    $factories = [
        fn () => new Button(as: 'script'),
        fn () => new Badge(as: 'script'),
        fn () => new Item(as: 'script'),
        fn () => new Sticky(as: 'script'),
        fn () => new ButtonGroupText(as: 'script'),
        fn () => new HoverCardTrigger(as: 'script'),
        fn () => new NavbarItem(as: 'script'),
        fn () => new ModalTrigger(as: 'script'),
        fn () => new ModalClose(as: 'script'),
        fn () => new AttachmentTrigger(as: 'script'),
        fn () => new ConditionalField(when: 'plan=pro', as: 'script'),
    ];

    foreach ($factories as $factory) {
        expect($factory)->toThrow(InvalidArgumentException::class);
    }
});

it('rejects explicit falsey navbar item tags', function (string $tag) {
    new NavbarItem(as: $tag);
})->with(['', '0'])->throws(InvalidArgumentException::class, 'Unsupported navbar item tag');

it('keeps conditional field tag as a validated compatibility input', function () {
    expect((new ConditionalField(when: 'plan=pro', tag: ' DIV '))->as)->toBe('div')
        ->and(fn () => new ConditionalField(when: 'plan=pro', tag: 'script'))
        ->toThrow(InvalidArgumentException::class);
});
