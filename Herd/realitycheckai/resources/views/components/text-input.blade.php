@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-surface-high bg-white text-ink placeholder:text-ink-faint focus:border-accent focus:ring-accent rounded-xl shadow-sm']) }}>
