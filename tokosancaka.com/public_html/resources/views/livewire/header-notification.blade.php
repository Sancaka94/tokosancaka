<div x-data="{ open: false }" class="relative">
    {{-- Tombol Lonceng --}}
    <button @click="open = !open" wire:click="loadNotifications"
            class="p-2 rounded-full text-gray-500 hover:bg-red-700 focus:outline-none relative">
        <span class="sr-only">Lihat notifikasi</span>
        <i class="fas fa-bell text-lg text-white"></i>

        @if($unreadCount > 0)
        <span class="absolute top-1 right-1 flex items-center justify-center text-[10px] text-white bg-red-600 rounded-full w-4 h-4">
            {{ $unreadCount }}
        </span>
        @endif
    </button>

    {{-- Dropdown Body --}}
    <div x-show="open" @click.away="open = false" x-cloak
         class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">

        <div class="py-1">
            <div class="px-4 py-2 text-sm font-semibold text-gray-900 border-b flex justify-between items-center">
                <span>Notifikasi</span>
                @if($unreadCount > 0)
                    <button wire:click="markAllAsRead" class="text-xs text-blue-600 hover:text-blue-800 font-normal">
                        Tandai baca semua
                    </button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-100 table-fixed">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($notifications as $notification)
                            <tr class="hover:bg-gray-50 {{ $notification->read_at ? '' : 'bg-blue-50' }}">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <p class="font-bold text-xs text-gray-800">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                                    <p class="text-gray-500 text-[10px] mt-0.5">{{Str::limit($notification->data['message'] ?? '-', 80)}}</p>
                                    <p class="text-gray-400 text-[9px] mt-1 text-right">{{ $notification->created_at->diffForHumans() }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-10 text-sm text-gray-500 text-center">
                                    <i class="fas fa-bell-slash text-gray-300 text-2xl mb-2"></i><br>
                                    Tidak ada notifikasi baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('admin.notifications.index') }}"
               class="block text-center px-4 py-2 text-sm text-indigo-600 hover:bg-gray-50 rounded-b-xl border-t font-medium">
                Lihat semua notifikasi
            </a>
        </div>
    </div>
</div>