@if ($prefetch !== null)
<x-hw::meta.prefetch :enabled="$prefetch" />
@endif
@if ($asked($refresh) || $asked($scroll))
<x-hw::meta.refresh :method="$given($refresh) ?? 'morph'" :scroll="$given($scroll) ?? 'preserve'" />
@endif
@if ($asked($cache))
<x-hw::meta.cache :control="$given($cache) ?? 'no-preview'" />
@endif
@if ($asked($visitControl))
<x-hw::meta.visit-control :control="$given($visitControl) ?? 'reload'" />
@endif
@if ($asked($root))
<x-hw::meta.root :path="$given($root) ?? '/'" />
@endif
@if ($asked($viewTransition))
<x-hw::meta.view-transition :scope="$given($viewTransition) ?? 'same-origin'" />
@endif
@if ($asked($csrf))
<x-hw::meta.csrf />
@endif
@if ($asked($colorScheme))
<x-hw::meta.color-scheme :schemes="$given($colorScheme) ?? 'light dark'" />
@endif
