@extends('layouts.admin')

@section('page-title', 'Semua Notifikasi')

@section('content')
<div class="w-full max-w-7xl mx-auto"> {{-- Dibuat lebih lebar (max-w-7xl) untuk tabel --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        
        {{-- Header Halaman --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Semua Notifikasi
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Menampilkan semua notifikasi yang telah Anda terima.
                    </p>
                </div>
                
                @if($notifications->whereNull('read_at')->count() > 0)
                    <form action="{{ route('admin.notifications.markAllAsRead') }}" method="POST">
                        @csrf
                        <button type="submit"
    class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded font-medium">
    Tandai semua sudah dibaca
                        </button>

                    </form>
                @endif
            </div>
        </div>

        {{-- 
          Konten Tabel 
          Wrapper 'overflow-x-auto' sangat penting untuk responsivitas di layar kecil.
        --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                
                {{-- Table Header (thead) --}}
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="w-16 px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{-- Kolom Ikon (tidak perlu judul) --}}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Notifikasi
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Waktu
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>

                {{-- Table Body (tbody) --}}
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    
                    @forelse($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $icon = $data['icon'] ?? 'fas fa-bell';
                            $title = $data['judul'] ?? 'Notifikasi';
                            $message = $data['pesan_utama'] ?? 'Tidak ada detail.';
                            $url = $data['url'] ?? '#';
                            $isUnread = !$notification->read_at;
                        @endphp

                        {{-- 
                          Baris Tabel (tr)
                          - Dibuat 'cursor-pointer' dan 'hover:bg-gray-50'
                          - Diberi 'onclick' untuk navigasi.
                          - Jika sudah dibaca (!isUnread), teks akan dibuat redup (text-gray-500).
                        --}}
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer {{ !$isUnread ? 'text-gray-500 dark:text-gray-400' : 'text-gray-900 dark:text-gray-100' }}"
                            onclick="window.location.href='{{ $url }}';">
                            
                            {{-- Kolom 1: Ikon --}}
                            <td class="px-6 py-4">
                                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center {{ $isUnread ? 'bg-indigo-100 dark:bg-indigo-700' : 'bg-gray-100 dark:bg-gray-700' }} rounded-full">
                                    <i class="{{ $icon }} {{ $isUnread ? 'text-indigo-500 dark:text-indigo-200' : 'text-gray-500 dark:text-gray-400' }} text-lg"></i>
                                </div>
                            </td>

                            {{-- Kolom 2: Notifikasi (Judul, Pesan, Badge) --}}
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm {{ $isUnread ? 'font-semibold' : 'font-medium' }}">
                                        {{ $title }}
                                    </span>
                                    @if($isUnread)
                                        <span class="inline-block px-2 py-0.5 bg-red-500 text-white text-xs font-semibold rounded-full">
                                            BARU
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm mt-1 {{ $isUnread ? 'text-gray-600 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400' }} truncate">
                                    {{ $message }}
                                </p>
                            </td>

                            {{-- Kolom 3: Waktu --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm align-top">
                                {{ $notification->created_at->diffForHumans() }}
                            </td>

                            {{-- Kolom 4: Aksi (Lacak) --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm align-top">
                                @if(isset($data['latitude']) && isset($data['longitude']))
                                    <a href="https://www.google.com/maps?q={{ $data['latitude'] }},{{ $data['longitude'] }}"
                                       target="_blank"
                                       onclick="event.stopPropagation();" {{-- PENTING: Mencegah 'onclick' baris --}}
                                       class="inline-flex items-center gap-1.5 text-xs px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors font-medium">
                                        <i class="fas fa-map-marker-alt w-3 h-3"></i>
                                        Lacak
                                    </a>
                                @endif
                            </td>
                        </tr>

                    @empty
                        {{-- Tampilan jika tabel kosong --}}
                        <tr>
                            <td colspan="4" class="text-center p-10">
                                <i class="fas fa-bell-slash text-4xl text-gray-300 dark:text-gray-600"></i>
                                <p class="mt-4 text-gray-500 dark:text-gray-400">
                                    Tidak ada notifikasi untuk ditampilkan.
                                </p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- Footer Paginasi (jika ada) --}}
        @if($notifications->hasPages())
            <div class="p-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                {{ $notifications->links() }}
            </div>
        @endif

    </div>
</div>
@endsection