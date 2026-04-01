@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-6 bg-gradient-to-r from-blue-700 to-blue-900 rounded-xl shadow-lg p-6 flex flex-col md:flex-row justify-between items-center text-white relative overflow-hidden">
        <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
            <svg class="w-48 h-48 -mt-10 -mr-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.64-2.25 1.64-1.74 0-2.33-.97-2.4-1.93H7.81c.12 1.74 1.36 2.91 3.09 3.27V19h2.34v-1.64c1.65-.3 2.85-1.4 2.85-2.97 0-2.02-1.72-2.77-3.78-3.25z"/></svg>
        </div>

        <div class="flex items-center gap-5 z-10 mb-4 md:mb-0">
            <div class="bg-white/20 p-4 rounded-full backdrop-blur-sm shadow-inner">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-blue-200 text-sm font-semibold uppercase tracking-wider mb-1">Total Saldo IAK</h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight" id="live-balance-amount">
                        <span class="animate-pulse text-lg font-normal text-blue-200">Menghubungkan ke API...</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="z-10">
             <button onclick="fetchLiveBalance()" id="btn-refresh-balance" class="bg-white/10 hover:bg-white/25 border border-white/20 transition-all px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 backdrop-blur-sm shadow-sm">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                 Refresh Real-Time
             </button>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="w-full md:w-1/4 lg:w-1/5">
            <div class="bg-white shadow rounded-lg p-4 sticky top-4">
                <h3 class="font-bold text-gray-700 mb-4 text-lg border-b pb-2">Kategori Produk</h3>

                @php
                    // Tab default disesuaikan dengan isi kolom 'type' di database IAK
                    $currentTab = request('tab', 'pulsa');

                    // Mapping slug tab ke value kolom 'type' di database
                    $categories = [
                        'pulsa' => '📱 Pulsa Prabayar',
                        'data' => '🌐 Paket Data',
                        'pln' => '⚡ Token PLN',
                        'etoll' => '💳 E-Money',
                        'game' => '🎮 Game',
                        'voucher' => '🛍️ Voucher Belanja',
                        'intl' => '🌍 Internasional',
                        'esim' => '📲 e-SIM',
                        'meterai' => '📜 E-Meterai',
                        'streaming' => '🎬 Streaming',
                        'call' => '📞 Paket Bicara',
                        'pgn' => '🔥 PGN',
                        'roaming' => '✈️ Roaming',
                        'pasca' => '🧾 Pascabayar & Tagihan'
                    ];
                @endphp

                <div class="max-h-[calc(100vh-10rem)] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent">
                    <ul class="space-y-1">
                        @foreach($categories as $slug => $label)
                            <li>
                                <a href="?tab={{ $slug }}"
                                   class="block px-4 py-2.5 rounded-md text-sm transition-all duration-200 {{ $currentTab == $slug ? 'bg-blue-600 text-white shadow-md font-medium' : 'text-gray-600 hover:bg-gray-100 hover:text-blue-600' }}">
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="w-full md:w-3/4 lg:w-4/5">
            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 mb-6 border-b pb-4">
                    <h2 class="text-2xl font-bold text-gray-800">
                        Data {{ $categories[$currentTab] ?? ucwords($currentTab) }}
                    </h2>

                    <div class="flex flex-wrap gap-2">
                        <form action="{{ route('admin.iak.check_balance') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="flex items-center text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 hover:border-blue-300 focus:ring-4 focus:outline-none focus:ring-blue-100 font-medium rounded-lg text-sm px-4 py-2 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Cek Saldo IAK
                            </button>
                        </form>

                        <form action="{{ route('admin.iak.sync_pricelist') }}" method="POST" class="m-0" onsubmit="return confirm('Tarik data harga terbaru dari server IAK? Proses ini membutuhkan waktu beberapa detik.')">
                            @csrf
                            <button type="submit" class="flex items-center text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Sinkron API IAK
                            </button>
                        </form>
                    </div>
                </div>

                <form action="" method="GET" class="mb-6 flex flex-col lg:flex-row gap-4 justify-between items-center">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">

                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto ms-auto">
                        <div class="relative w-full sm:w-64">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau deskripsi..."
                                   class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <select name="status" class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>

                        <button type="submit" class="bg-blue-600 text-white px-5 py-2 text-sm rounded-lg hover:bg-blue-700 transition-colors w-full sm:w-auto">
                            Filter
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harga Dasar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($products as $item)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                                        {{ $item->operator }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item->code }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $item->description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        Rp {{ number_format($item->price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(strtolower($item->status) == 'active')
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                        @else
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.produk.edit', $item->id) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                            <form action="{{ route('admin.produk.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                        Data tidak ditemukan. Silakan klik "Sinkron API IAK" untuk menarik data pricelist terbaru.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $products->appends(request()->query())->links() }}
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function fetchLiveBalance() {
    const balanceEl = document.getElementById('live-balance-amount');
    const btnIcon = document.querySelector('#btn-refresh-balance svg');

    // Efek muter saat diklik
    btnIcon.classList.add('animate-spin');
    balanceEl.innerHTML = '<span class="animate-pulse text-lg font-normal text-blue-200">Memuat saldo...</span>';

    fetch('{{ route("admin.iak.live_balance") }}', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btnIcon.classList.remove('animate-spin');
        if(data.success) {
            // Ubah format angka menjadi format Rupiah (Rp 10.000.000)
            let rp = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(data.balance);
            balanceEl.innerHTML = rp;
        } else {
            balanceEl.innerHTML = '<span class="text-red-300 text-lg text-sm">Gagal memuat saldo</span>';
        }
    })
    .catch(err => {
        btnIcon.classList.remove('animate-spin');
        balanceEl.innerHTML = '<span class="text-red-300 text-lg text-sm">Koneksi terputus</span>';
    });
}

// Otomatis tarik saldo saat halaman admin dibuka
document.addEventListener('DOMContentLoaded', fetchLiveBalance);
</script>

@endsection


