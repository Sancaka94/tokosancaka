@extends('layouts.admin')

@section('title', 'Log Aktivitas')
@section('page-title', 'Log Aktivitas')

@section('content')
<div x-data="{ isModalOpen: false, modalData: {} }" class="container mx-auto px-4 sm:px-8 py-8">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight text-gray-800">Log Aktivitas Sistem</h2>
            <span class="text-sm text-gray-600">Menampilkan semua aktivitas terbaru dari admin dan pelanggan.</span>
        </div>

        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                 class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 my-6 rounded-md shadow-md" role="alert">
                <div class="flex justify-between items-center">
                    <p>{{ session('success') }}</p>
                    <button @click="show = false" class="text-green-800 hover:text-green-900">&times;</button>
                </div>
            </div>
        @endif

        <!-- Filter Buttons -->
        <div class="my-6 flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.activity-log.index') }}" class="px-4 py-2 text-sm font-medium rounded-md {{ !$currentFilter ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Semua</a>
            <a href="{{ route('admin.activity-log.index', ['filter' => 'user']) }}" class="px-4 py-2 text-sm font-medium rounded-md {{ $currentFilter == 'user' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Pendaftaran</a>
            <a href="{{ route('admin.activity-log.index', ['filter' => 'order']) }}" class="px-4 py-2 text-sm font-medium rounded-md {{ $currentFilter == 'order' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Pesanan</a>
            <a href="{{ route('admin.activity-log.index', ['filter' => 'topup']) }}" class="px-4 py-2 text-sm font-medium rounded-md {{ $currentFilter == 'topup' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Top Up</a>
            <a href="{{ route('admin.activity-log.index', ['filter' => 'scan']) }}" class="px-4 py-2 text-sm font-medium rounded-md {{ $currentFilter == 'scan' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Scan SPX</a>
        </div>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pengguna</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Aktivitas</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail</th>
                            {{-- ======================= KOLOM BARU ======================= --}}
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($activities as $activity)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><p class="font-semibold text-gray-800">{{ $activity['user'] }}</p></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($activity['type'] == 'user') <i class="fas fa-user-plus text-blue-500 mr-3"></i>
                                        @elseif($activity['type'] == 'order') <i class="fas fa-shopping-cart text-green-500 mr-3"></i>
                                        @elseif($activity['type'] == 'topup') <i class="fas fa-wallet text-purple-500 mr-3"></i>
                                        @elseif($activity['type'] == 'scan') <i class="fas fa-barcode text-yellow-500 mr-3"></i> @endif
                                        <span>{{ $activity['description'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="font-mono text-gray-700">{{ $activity['details'] }}</span></td>
                                {{-- ======================= DATA BARU ======================= --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $status = strtolower($activity['status'] ?? '');
                                        $badgeColor = 'bg-gray-100 text-gray-700'; // Default
                                        if (in_array($status, ['success', 'paid', 'selesai', 'terkirim'])) {
                                            $badgeColor = 'bg-green-100 text-green-700';
                                        } elseif (in_array($status, ['pending', 'diproses', 'proses pickup'])) {
                                            $badgeColor = 'bg-yellow-100 text-yellow-700';
                                        } elseif (in_array($status, ['failed', 'gagal', 'expired', 'cancelled'])) {
                                            $badgeColor = 'bg-red-100 text-red-700';
                                        }
                                    @endphp
                                    <span class="px-3 py-1 font-semibold leading-tight rounded-full {{ $badgeColor }}">
                                        {{ ucfirst($activity['status'] ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ \Carbon\Carbon::parse($activity['timestamp'])->isoFormat('dddd, D MMM YYYY - HH:mm:ss') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="modalData = {{ json_encode($activity) }}; isModalOpen = true" class="px-3 py-1 text-xs font-medium text-indigo-600 bg-indigo-100 rounded-full hover:bg-indigo-200">
                                        Lihat Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            {{-- ======================= PERBAIKAN COLSPAN ======================= --}}
                            <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada aktivitas yang tercatat untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $activities->links() }}
            </div>
        </div>
    </div>

    {{-- Modal (tidak ada perubahan di sini) --}}
    <div x-show="isModalOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
        <div @click.away="isModalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 p-6">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Detail Teknis Aktivitas</h3>
                <button @click="isModalOpen = false" class="text-gray-500 hover:text-gray-800">&times;</button>
            </div>
            
            <div class="space-y-4 text-sm">
                <div>
                    <h4 class="font-bold text-gray-600">Perangkat</h4>
                    <div class="flex items-center mt-1">
                        <i class="fas text-gray-400 mr-3 fa-lg" 
                           :class="{
                               'fa-mobile-alt': modalData.device && (modalData.device.toLowerCase().includes('android') || modalData.device.toLowerCase().includes('ios') || modalData.device.toLowerCase().includes('iphone')),
                               'fa-desktop': !modalData.device || !(modalData.device.toLowerCase().includes('android') || modalData.device.toLowerCase().includes('ios') || modalData.device.toLowerCase().includes('iphone'))
                           }"></i>
                        <div>
                            <p class="font-medium text-gray-900" x-text="modalData.device || 'Tidak Diketahui'"></p>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-gray-600">Alamat IP</h4>
                    <div class="flex items-center mt-1">
                        <i class="fas fa-network-wired text-gray-400 mr-3 fa-lg"></i>
                        <div>
                            <p class="font-mono text-gray-900" x-text="modalData.ip_address || 'N/A'"></p>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-gray-600">Lokasi (Koordinat)</h4>
                    <div class="flex items-center mt-1">
                        <i class="fas fa-map-marker-alt text-gray-400 mr-3 fa-lg"></i>
                        <div>
                            <template x-if="modalData.latitude && modalData.longitude">
                                <a :href="`https://www.google.com/maps?q=${modalData.latitude},${modalData.longitude}`" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                    Lihat di Google Maps
                                </a>
                            </template>
                            <template x-if="!modalData.latitude || !modalData.longitude">
                                <span class="text-gray-500">Tidak tersedia</span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-right mt-6">
                <button @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
