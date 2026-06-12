<div data-test-layout="dashboard-with-modal">
    <main>{{ $slot }}</main>
    {{-- Simulates a real dashboard layout that hosts a modal frame globally. --}}
    <turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame>
</div>
