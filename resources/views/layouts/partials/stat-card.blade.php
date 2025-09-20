@php
    $colors = [
        'green' => 'bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400',
        'blue' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400',
        'indigo' => 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400',
        'yellow' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400',
    ];
@endphp
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
    <div class="flex items-center">
        <div class="p-3 rounded-full {{ $colors[$color] ?? $colors['blue'] }}">
            <i class="fas {{ $icon ?? 'fa-info-circle' }} w-6 h-6 text-center leading-6"></i>
        </div>
        <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $title }}</p>
            <p id="{{ $id }}" class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $value }}</p>
        </div>
    </div>
</div>
