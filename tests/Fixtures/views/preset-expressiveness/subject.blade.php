<x-hw::button>Save changes</x-hw::button>
<x-hw::button variant="outline" size="sm">Cancel</x-hw::button>
<x-hw::button variant="outline" disabled>Disabled</x-hw::button>
<x-hw::button variant="outline" aria-invalid="true">Invalid</x-hw::button>

<x-hw::card size="sm">
    <x-hw::card.header>
        <x-hw::card.title>Account</x-hw::card.title>
        <x-hw::card.description>Shared semantic markup.</x-hw::card.description>
        <x-hw::card.action>
            <x-hw::button variant="ghost" size="icon-sm" aria-label="More options">...</x-hw::button>
        </x-hw::card.action>
    </x-hw::card.header>
    <x-hw::card.content>
        <x-hw::input id="fixture-{{ $personality }}-email" type="email" value="person@example.com" />
        <x-hw::select
            id="fixture-{{ $personality }}-role"
            :options="['admin' => 'Administrator', 'member' => 'Member']"
            selected="member"
        />
    </x-hw::card.content>
    <x-hw::card.footer>Last updated today.</x-hw::card.footer>
</x-hw::card>

<x-hw::alert variant="destructive">
    <x-hw::alert.title>Payment failed</x-hw::alert.title>
    <x-hw::alert.description>Review the billing details and try again.</x-hw::alert.description>
    <x-hw::alert.action>Retry</x-hw::alert.action>
</x-hw::alert>

<x-hw::modal id="fixture-{{ $personality }}-modal">
    <x-hw::modal.trigger>Preview modal</x-hw::modal.trigger>
    <x-hw::modal.content>
        <x-hw::modal.header>
            <x-hw::modal.title>Confirm changes</x-hw::modal.title>
            <x-hw::modal.description>The overlay uses the same component tree.</x-hw::modal.description>
        </x-hw::modal.header>
        <x-hw::modal.footer>
            <x-hw::button variant="outline">Cancel</x-hw::button>
            <x-hw::button>Confirm</x-hw::button>
        </x-hw::modal.footer>
    </x-hw::modal.content>
</x-hw::modal>

<x-hw::toaster id="fixture-{{ $personality }}-toaster" :flash="false" :turbo-permanent="false" />
