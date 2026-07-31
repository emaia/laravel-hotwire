<x-hw::button slot-name="attachment-action" :variant="$variant" :size="$size" :type="$type" :frame="$frame" {{ $attributes->except('frame') }}>{{ $slot }}</x-hw::button>
