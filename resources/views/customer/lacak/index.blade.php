@extends('layouts.customer')

@section('title', 'Lacak Paket')

@push('styles')
<style>
    /* Animasi untuk ikon status saat ini */
    .pulse-icon {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: .7;
            transform: scale(1.1);
        }
    }
    /* Styling untuk item terbaru/aktif */
    .timeline-item-active .timeline-icon-dot {
        background-color: #f59e0b; /* Amber */
        border: 2px solid #f59e0b;
        transform: scale(1.2);
    }
</style>
@endpush

@section('content')

{{-- 🔑 Pastikan Anda memiliki fungsi helper getStatusIcon() di controller atau helper Anda. --}}
@php
    // Fungsi dummy getStatusIcon untuk menghindari error sintaks di Blade
    if (!function_exists('getStatusIcon')) {
        function getStatusIcon($status) {
            $status = strtolower($status);
            if (str_contains($status, 'diterima')) return 'fas fa-truck-loading';
            if (str_contains($status, 'kirim')) return 'fas fa-shipping-fast';
            if (str_contains($status, 'dibuat')) return 'fas fa-clipboard-list';
            if (str_contains($status, 'cabang')) return 'fas fa-warehouse';
            return 'fas fa-map-marker-alt';
        }
    }
    // Asumsi: $result['histories'] sudah diurutkan dari TERBARU ke TERLAMA di controller.
    // Kita akan membalik loop untuk menampilkan TERLAMA di atas.
    $histories = isset($result['histories']) ? $result['histories']->reverse() : collect();
    $latestStatus = $result['status'] ?? null;
@endphp

<div class="bg-slate-50 min-h-screen">
    <div class="container mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-lg">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Lacak Kiriman Anda</h1>
            <p class="mt-2 text-slate-600">Masukkan nomor resi untuk melihat status pengiriman paket Anda.</p>
            <form action="{{ route('customer.lacak.index') }}" method="GET" class="mt-6">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <input type="text" name="search" id="search" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-lg" placeholder="Contoh: SCK..." value="{{ $resi ?? '' }}" required>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-8 py-3 text-base font-medium text-white shadow-sm hover:bg-red-700">
                        <i class="fas fa-search mr-2"></i>
                        Lacak
                    </button>
                </div>
            </form>
        </div>

        @if(isset($resi))
            <div class="mt-8">
                @if($result)
                    <div class="space-y-6">
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-lg flex justify-between items-center">
                            <div>
                                <h2 class="text-xl font-bold text-slate-800">Hasil untuk Resi: {{ $result['resi'] }}</h2>
                                <p class="mt-1 text-sm text-slate-500">Status Terakhir: <span class="font-semibold text-red-600">{{ $result['status'] }}</span></p>
                            </div>
                            @if($result['resi_aktual'])
    <a href="{{ url($result['resi_aktual'] . '/cetak_thermal') }}" 
       target="_blank" 
       class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
        <i class="fas fa-print mr-2"></i>
        Cetak Resi
    </a>
@endif
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-lg grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <h3 class="font-semibold text-slate-900">Pengirim</h3>
                                <p class="text-slate-600">{{ $result['pengirim'] }}</p>
                                <p class="text-sm text-slate-500">{{ $result['alamat_pengirim'] }}</p>
                                <p class="text-sm text-slate-500">No. HP: {{ $result['no_pengirim'] }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900">Penerima</h3>
                                <p class="text-slate-600">{{ $result['penerima'] }}</p>
                                <p class="text-sm text-slate-500">{{ $result['alamat_penerima'] }}</p>
                                <p class="text-sm text-slate-500">No. HP: {{ $result['no_penerima'] }}</p>
                            </div>
                        </div>

                        @if($histories->count() > 0)
                            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-lg">
                                <h3 class="text-lg font-semibold text-slate-800 mb-4">Riwayat Perjalanan</h3>
                                <ol class="relative border-l border-slate-200 ml-4">
                                    {{-- Loop diurutkan dari TERLAMA ke TERBARU --}}
                                    @foreach($histories as $history)
                                        @php
                                            // Tentukan apakah ini status TERAKHIR (Aktif)
                                            $isActive = ($history->status === $latestStatus);
                                        @endphp
                                        <li class="mb-6 ml-6 {{ $isActive ? 'timeline-item-active' : '' }}">
                                            <span class="absolute -left-3.5 flex h-4 w-4 items-center justify-center rounded-full bg-white timeline-icon-dot {{ $isActive ? 'pulse-icon' : '' }}">
                                                <i class="fas fa-circle text-xs text-red-400"></i>
                                            </span>
                                            <h4 class="font-semibold {{ $isActive ? 'text-red-600' : 'text-slate-900' }}">{{ $history->status }}</h4>
                                            <p class="text-sm text-slate-700">{{ $history->lokasi }}</p>
                                            <p class="text-sm text-slate-500">{{ $history->keterangan }}</p>
                                            <time class="block text-sm text-slate-400">{{ \Carbon\Carbon::parse($history->created_at)->format('d M Y, H:i') }}</time>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif

                    </div>
                @else
                    <div class="text-center rounded-xl border border-dashed border-slate-300 bg-white p-12 shadow-sm">
                        <i class="fas fa-box-open fa-4x text-slate-400"></i>
                        <h3 class="mt-4 text-sm font-medium text-slate-900">Paket tidak ditemukan</h3>
                        <p class="mt-1 text-sm text-slate-500">Nomor resi '{{ $resi }}' tidak ditemukan.</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

@endsection