@if ($attributes->isNotEmpty())
    @php($attributeNames = implode(', ', array_keys($attributes->getAttributes())))
    @php(throw new InvalidArgumentException("The frame-or-page.{$branchName} component does not accept HTML attributes [{$attributeNames}]."))
@endif

{{ $slot }}
