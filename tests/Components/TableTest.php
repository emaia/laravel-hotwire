<?php

it('renders a responsive table container and table slot', function () {
    $view = $this->blade('<x-hwc::table><tbody><tr><td>Jane</td></tr></tbody></x-hwc::table>');

    $view->assertSee('data-slot="table-container"', false)
        ->assertSee('<table', false)
        ->assertSee('data-slot="table"', false)
        ->assertSeeText('Jane');
});

it('renders table subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hwc::table>
            <x-hwc::table.caption>Users</x-hwc::table.caption>
            <x-hwc::table.header>
                <x-hwc::table.row>
                    <x-hwc::table.head>Name</x-hwc::table.head>
                </x-hwc::table.row>
            </x-hwc::table.header>
            <x-hwc::table.body>
                <x-hwc::table.row data-state="selected">
                    <x-hwc::table.cell>Jane</x-hwc::table.cell>
                </x-hwc::table.row>
            </x-hwc::table.body>
            <x-hwc::table.footer>
                <x-hwc::table.row>
                    <x-hwc::table.cell>Total</x-hwc::table.cell>
                </x-hwc::table.row>
            </x-hwc::table.footer>
        </x-hwc::table>
    BLADE);

    $view->assertSee('data-slot="table-caption"', false)
        ->assertSee('data-slot="table-header"', false)
        ->assertSee('data-slot="table-row"', false)
        ->assertSee('data-slot="table-head"', false)
        ->assertSee('data-slot="table-body"', false)
        ->assertSee('data-slot="table-cell"', false)
        ->assertSee('data-slot="table-footer"', false)
        ->assertSee('data-state="selected"', false)
        ->assertSeeText('Users')
        ->assertSeeText('Jane');
});

it('does not emit package Tailwind classes inline', function () {
    $view = $this->blade('<x-hwc::table><x-hwc::table.body /></x-hwc::table>');

    $view->assertDontSee('overflow-x-auto', false)
        ->assertDontSee('caption-bottom', false)
        ->assertDontSee('border-b', false);
});

it('passes through classes and arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::table id="users" class="min-w-lg" data-test="users"><x-hwc::table.body /></x-hwc::table>');

    $view->assertSee('id="users"', false)
        ->assertSee('class="min-w-lg"', false)
        ->assertSee('data-test="users"', false);
});
