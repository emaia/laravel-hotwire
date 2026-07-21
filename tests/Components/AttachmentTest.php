<?php

use Emaia\LaravelHotwire\Components\Attachment\Trigger;

it('renders an attachment with semantic state size and orientation', function () {
    $view = $this->blade('<x-hw::attachment state="uploading" size="sm" orientation="vertical">Upload</x-hw::attachment>');

    $view->assertSee('data-slot="attachment"', false)
        ->assertSee('data-state="uploading"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSee('data-orientation="vertical"', false)
        ->assertSeeText('Upload')
        ->assertDontSee('rounded-xl', false);
});

it('renders attachment subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::attachment.group>
            <x-hw::attachment state="done" size="xs">
                <x-hw::attachment.media variant="image"><img src="/avatar.png" alt="Avatar"></x-hw::attachment.media>
                <x-hw::attachment.content>
                    <x-hw::attachment.title>avatar.png</x-hw::attachment.title>
                    <x-hw::attachment.description>PNG · 12 KB</x-hw::attachment.description>
                </x-hw::attachment.content>
                <x-hw::attachment.actions>
                    <x-hw::attachment.action aria-label="Remove avatar.png"><x-hw::icon name="x" /></x-hw::attachment.action>
                </x-hw::attachment.actions>
                <x-hw::attachment.trigger aria-label="Open avatar.png" />
            </x-hw::attachment>
        </x-hw::attachment.group>
    BLADE);

    $view->assertSee('data-slot="attachment-group"', false)
        ->assertSee('role="list"', false)
        ->assertSee('data-slot="attachment"', false)
        ->assertSee('data-slot="attachment-media"', false)
        ->assertSee('data-variant="image"', false)
        ->assertSee('data-slot="attachment-content"', false)
        ->assertSee('data-slot="attachment-title"', false)
        ->assertSee('data-slot="attachment-description"', false)
        ->assertSee('<p data-slot="attachment-description"', false)
        ->assertSee('data-slot="attachment-actions"', false)
        ->assertSee('data-slot="attachment-action"', false)
        ->assertSee('data-slot="attachment-trigger"', false)
        ->assertSee('aria-label="Remove avatar.png"', false)
        ->assertSee('aria-label="Open avatar.png"', false)
        ->assertSeeText('avatar.png');
});

it('rejects unsafe attachment trigger tags', function () {
    expect(fn () => new Trigger(as: 'script'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported attachment trigger tag');
});

it('renders attachment trigger as a link when requested', function () {
    $view = $this->blade('<x-hw::attachment.trigger as="a" href="/files/report.pdf" aria-label="Open report" />');

    $view->assertSee('<a', false)
        ->assertSee('href="/files/report.pdf"', false)
        ->assertSee('data-slot="attachment-trigger"', false)
        ->assertDontSee('type="button"', false);
});

it('passes attributes through attachment parts', function () {
    $view = $this->blade('<x-hw::attachment id="file-a" class="custom" data-test="attachment">File</x-hw::attachment>');

    $view->assertSee('id="file-a"', false)
        ->assertSee('class="custom"', false)
        ->assertSee('data-test="attachment"', false);
});
