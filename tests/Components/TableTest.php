<?php

it('renders a responsive table container and table slot', function () {
    $view = $this->blade('<x-hw::table><tbody><tr><td>Jane</td></tr></tbody></x-hw::table>');

    $view->assertSee('data-slot="table-container"', false)
        ->assertSee('<table', false)
        ->assertSee('data-slot="table"', false)
        ->assertSeeText('Jane');
});

it('renders table subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::table>
            <x-hw::table.caption>Users</x-hw::table.caption>
            <x-hw::table.header>
                <x-hw::table.row>
                    <x-hw::table.head>Name</x-hw::table.head>
                </x-hw::table.row>
            </x-hw::table.header>
            <x-hw::table.body>
                <x-hw::table.row data-state="selected">
                    <x-hw::table.cell>Jane</x-hw::table.cell>
                </x-hw::table.row>
            </x-hw::table.body>
            <x-hw::table.footer>
                <x-hw::table.row>
                    <x-hw::table.cell>Total</x-hw::table.cell>
                </x-hw::table.row>
            </x-hw::table.footer>
        </x-hw::table>
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
    $view = $this->blade('<x-hw::table><x-hw::table.body /></x-hw::table>');

    $view->assertDontSee('overflow-x-auto', false)
        ->assertDontSee('caption-bottom', false)
        ->assertDontSee('border-b', false);
});

it('passes through classes and arbitrary attributes', function () {
    $view = $this->blade('<x-hw::table id="users" class="min-w-lg" data-test="users"><x-hw::table.body /></x-hw::table>');

    $view->assertSee('id="users"', false)
        ->assertSee('class="min-w-lg"', false)
        ->assertSee('data-test="users"', false);
});
