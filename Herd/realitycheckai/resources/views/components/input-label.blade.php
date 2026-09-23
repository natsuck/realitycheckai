@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-primary-container']) }}>
    {{ $value ?? $slot }}
</label>
