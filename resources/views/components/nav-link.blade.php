@props(['active' => false, 'icon' => ''])

@php
$classes = ($active)
            ? 'flex items-center space-x-3 px-4 py-2.5 bg-emerald-600 text-white rounded-lg transition duration-200 shadow-md shadow-emerald-900/20 group'
            : 'flex items-center space-x-3 px-4 py-2.5 text-emerald-100/70 hover:bg-emerald-800 hover:text-white rounded-lg transition duration-200 group';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <div class="flex-shrink-0 text-current">
        {{-- If you use Heroicons or similar, they would go here. For now, a sleek dot --}}
        <div class="w-1.5 h-1.5 rounded-full {{ $active ? 'bg-white' : 'bg-emerald-500 group-hover:bg-emerald-400' }}"></div>
    </div>
    <span class="font-medium text-sm tracking-wide">{{ $slot }}</span>
</a>