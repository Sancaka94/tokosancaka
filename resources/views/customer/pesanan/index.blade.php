{{-- Halaman ini meng-extend layout utama customer --}}
@extends('layouts.customer')

@section('title', 'Data Pesanan Anda')

@section('content')
<div class="bg-slate-50 min-h-screen">
    <div class="container mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        
        <!-- Header Halaman -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Data Pesanan Anda</h1>
            <p class="mt-2 text-lg text-slate-600">Berikut adalah riwayat semua pesanan yang telah Anda buat.</p>
        </div>

        <!-- Konten Utama: Tabel Pesanan -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">No. Resi</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tujuan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Total Biaya</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        {{-- Loop melalui data pesanan yang sudah difilter oleh controller --}}
                        @forelse ($pesanans as $order)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="font-semibold text-indigo-600">{{ $order->resi ?? 'Belum Ada Resi' }}</div>
                                </td>
                                {{-- ✅ PERBAIKAN: Menggunakan kolom 'tanggal_pesanan' yang benar --}}
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ \Carbon\Carbon::parse($order->tanggal_pesanan)->format('d M Y, H:i') }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    {{-- ✅ PERBAIKAN: Menggunakan kolom 'nama_pembeli' dan 'alamat_pengiriman' yang benar --}}
                                    <div class="text-sm font-medium text-slate-900">{{ $order->nama_pembeli }}</div>
                                    <div class="text-xs text-slate-500">{{ Str::limit($order->alamat_pengiriman, 35) }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @php
                                        $status = $order->status_pesanan ?? 'Belum ada status';
                                    
                                        $statusClass = 'bg-slate-100 text-slate-800';
                                    
                                        switch ($status) {
                                            case 'Menunggu Pembayaran':
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                break;
                                    
                                            case 'Menunggu Pickup':
                                            case 'Sedang Dikirim':
                                                $statusClass = 'bg-blue-100 text-blue-800';
                                                break;
                                    
                                            case 'Dalam Proses Retur':
                                                $statusClass = 'bg-orange-100 text-orange-800';
                                                break;
                                    
                                            case 'Selesai':
                                            case 'Retur Selesai':
                                                $statusClass = 'bg-green-100 text-green-800';
                                                break;
                                    
                                            case 'Dibatalkan':
                                                $statusClass = 'bg-red-100 text-red-800';
                                                break;
                                        }
                                    @endphp
                                    
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-slate-800">
                                    {{-- ✅ PERBAIKAN: Menggunakan kolom 'total_harga_barang' yang benar --}}
                                    Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}
                                </td>
                               <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                   @php
                                        $method = strtoupper($order->payment_method);
                                        $url    = $order->payment_url;
                                        $virtualAccounts = [
                                            'PERMATAVA','BNIVA','BRIVA','MANDIRIVA','BCAVA','MUAMALATVA',
                                            'CIMBVA','BSIVA','OCBCVA','DANAMONVA','OTHERBANKVA'
                                        ];
                                    @endphp
                                    
                                    @if ($order->status === 'Menunggu Pembayaran')
                                        @if (str_contains($method, 'QRIS'))
                                            <div class="text-center">
                                                <p class="text-xs text-gray-600 mb-1">Bayar via QRIS:</p>
                                                <img src="{{ $url }}" alt="QRIS Payment" class="w-16 h-16 mx-auto rounded-md border">
                                            </div>
                                        @elseif (in_array($method, ['DANA', 'OVO', 'SHOPEEPAY']))
                                            <div class="text-center">
                                                <p class="text-xs text-gray-600 mb-1">Bayar dengan {{ ucfirst(strtolower($method)) }}</p>
                                                <a href="{{ $url }}" target="_blank" 
                                                   class="text-green-600 hover:text-green-900 font-semibold">
                                                   Bayar
                                                </a>
                                            </div>
                                        @elseif (in_array($method, $virtualAccounts))
                                            <div class="text-center">
                                                <p class="text-xs text-gray-600 mb-1">Virtual Account:</p>
                                                <span class="font-mono text-blue-600 text-sm">
                                                    {{ $url }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="text-center">
                                                <a href="{{ $url }}" 
                                                   class="text-green-600 hover:text-green-900 font-semibold"
                                                   target="_blank">
                                                   Bayar
                                                </a>
                                            </div>
                                        @endif
                                    @elseif ($order->status === 'Menunggu Pickup')
                                        <div class="text-center">
                                            <a href="{{ route('customer.lacak.index', ['resi' => $order->resi]) }}" 
                                               class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                               Lacak
                                            </a>
                                        </div>
                                    @endif

                                </td>
                            </tr>
                        @empty
                            {{-- Tampilan jika tidak ada pesanan sama sekali --}}
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="mx-auto max-w-md">
                                        <i class="fas fa-box-open fa-3x text-slate-400"></i>
                                        <h3 class="mt-2 text-sm font-medium text-slate-900">Tidak ada data pesanan</h3>
                                        <p class="mt-1 text-sm text-slate-500">Anda belum pernah membuat pesanan. Mulai kirim paket sekarang!</p>
                                        <div class="mt-6">
                                            {{-- ✅ PERBAIKAN: Menggunakan nama route 'customer.pesanan.create' yang benar --}}
                                            <a href="{{ route('customer.pesanan.create') }}" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                                <i class="fas fa-plus -ml-1 mr-2"></i>
                                                Buat Pesanan Baru
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Navigasi Paginasi --}}
            @if ($pesanans->hasPages())
                <div class="border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
                    {{ $pesanan->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
