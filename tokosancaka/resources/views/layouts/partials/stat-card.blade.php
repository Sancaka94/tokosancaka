@php
    $colors = [
        'green' => 'bg-green-100 text-green-600',
        'blue' => 'bg-blue-100 text-blue-600',
        'indigo' => 'bg-indigo-100 text-indigo-600',
        'yellow' => 'bg-yellow-100 text-yellow-600',
    ];
@endphp
<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex items-center">
        <div class="p-3 rounded-full {{ $colors[$color] ?? $colors['blue'] }}">
            <i class="fas {{ $icon ?? 'fa-info-circle' }} w-6 h-6 text-center leading-6"></i>
        </div>
        <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p id="{{ $id }}" class="text-2xl font-bold text-gray-800">{{ $value }}</p>
        </div>
    </div>
</div>
