@foreach ($personalities as $personality)
    <section data-preset-fixture="{{ $personality }}" aria-label="{{ ucfirst($personality) }} preset fixture">
        @include('preset-expressiveness::subject', ['personality' => $personality])
    </section>
@endforeach
