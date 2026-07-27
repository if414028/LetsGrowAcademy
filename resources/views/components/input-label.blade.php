@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1.5 block text-sm font-bold text-slate-700']) }}>
    {{ $value ?? $slot }}
</label>
