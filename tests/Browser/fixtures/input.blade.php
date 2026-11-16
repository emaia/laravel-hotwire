<hw:input id="plain-input" aria-label="Plain text" />
<div id="default-flex-layout" class="fixture-wide-flex">
    <hw:input id="default-flex-input" aria-label="Default flexible" />
    <button class="fixture-sibling">Next</button>
</div>
<hw:field name="horizontal" label="Name" orientation="horizontal" class="fixture-horizontal">
    <hw:input id="horizontal-input" />
</hw:field>
<div class="fixture-wide-flex">
    <hw:input id="fixed-width-input" aria-label="Fixed width" wrapper-class="w-40" class="h-10" />
</div>
<div id="flex-layout" class="fixture-flex">
    <hw:input id="flex-input" aria-label="Flexible" wrapper-class="flex-1" />
    <button class="fixture-sibling">Next</button>
</div>
<div id="grid-layout" class="fixture-grid">
    <hw:input id="grid-input" type="search" aria-label="Grid search" wrapper-class="col-span-2" value="Trees" />
    <button>Next</button>
</div>
<hw:form id="filters" method="get" auto-submit auto-submit-delay="150">
    <hw:field name="q" id="query" label="Search parks">
        <hw:input type="search" value="Trees" auto-submit />
    </hw:field>
    <button id="after-query" type="button">After search</button>
</hw:form>
<hw:input id="native-search" type="search" :clearable="false" aria-label="Native search" value="Trees" />
<hw:input id="disabled-search" type="search" disabled aria-label="Disabled search" value="Trees" />
<hw:input id="readonly-search" type="search" readonly aria-label="Readonly search" value="Trees" />
<fieldset id="disabled-fieldset" disabled>
    <hw:input id="fieldset-search" type="search" aria-label="Fieldset search" value="Trees" />
</fieldset>
<hw:input-group id="group">
    <hw:input id="group-search" type="search" aria-label="Grouped search" value="Trees" />
    <hw:input-group.addon align="inline-start">Find</hw:input-group.addon>
    <hw:input-group.addon align="inline-end"><hw:button type="button">Go</hw:button></hw:input-group.addon>
</hw:input-group>
<hw:multi-select id="species" :options="['oak' => 'Oak', 'pine' => 'Pine']" />
<hw:modal id="search-modal" motion="none">
    <hw:modal.content>
        <hw:modal.header><hw:modal.title>Search dialog</hw:modal.title></hw:modal.header>
        <hw:input id="modal-search" type="search" aria-label="Modal search" value="Trees" />
    </hw:modal.content>
</hw:modal>
<hw:drawer id="search-drawer" motion="none">
    <hw:drawer.content>
        <hw:drawer.header><hw:drawer.title>Search drawer</hw:drawer.title></hw:drawer.header>
        <hw:input id="drawer-search" type="search" aria-label="Drawer search" value="Trees" />
    </hw:drawer.content>
</hw:drawer>
<hw:alert-dialog id="search-alert" title="Search confirmation" motion="none">
    <button type="button" id="search-alert-trigger">Open search confirmation</button>
    <x-slot:content>
        <hw:input id="alert-search" type="search" aria-label="Alert search" value="Trees" />
    </x-slot:content>
</hw:alert-dialog>
<hw:sidebar.provider id="search-sidebar" class="md:hidden">
    <hw:sidebar aria-label="Search navigation" motion="none">
        <hw:sidebar.content>
            <hw:input id="sidebar-search" type="search" aria-label="Sidebar search" value="Trees" />
        </hw:sidebar.content>
    </hw:sidebar>
</hw:sidebar.provider>
<hw:dropdown id="search-dropdown" :close-on-select="false" motion="none">
    <hw:dropdown.trigger>Search menu</hw:dropdown.trigger>
    <hw:dropdown.content><hw:input id="dropdown-search" type="search" aria-label="Dropdown search" value="Trees" /></hw:dropdown.content>
</hw:dropdown>
<turbo-frame id="search-frame">
    <hw:input id="frame-search" type="search" aria-label="Frame search" value="Trees" />
</turbo-frame>
<span id="missing-button" data-controller="clear-input">
    <input data-clear-input-target="input" value="Trees" />
</span>
<span id="missing-input" data-controller="clear-input">
    <button type="button" data-clear-input-target="clearButton">Clear missing input</button>
</span>
