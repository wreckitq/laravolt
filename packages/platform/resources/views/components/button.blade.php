<button type="{{ $type }}" {{ $attributes->merge(['class' => 'ui button rounded-full '.$class]) }} themed>
    @if($icon)
        <i class="icon {{ $icon }}"></i>
    @endif
    {{ $label ?? $slot }}
</button>
