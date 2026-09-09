<x-hw::button :slot-name="$slotName" :variant="$variant" :size="$size" :type="$type" :frame="$frame" {{ $attributes->except('frame') }}>{{ $slot }}</x-hw::button>
