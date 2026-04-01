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

    <div class="flex flex-col md:flex-row gap-6">
        <div class="w-full md:w-1/4 lg:w-1/5">
            <div class="bg-white shadow rounded-lg p-4 sticky top-4">
                <h3 class="font-bold text-gray-700 mb-4 text-lg border-b pb-2">Kategori Produk</h3>

                @php
                    // Ambil parameter tab dari URL, default ke 'pulsa-prabayar'
                    $currentTab = request('tab', 'pulsa-prabayar');

                    // Daftar semua kategori
                    $categories = [
                        'pulsa-prabayar' => '📱 Pulsa Prabayar',
                        'paket-data' => '🌐 Paket Data',
                        'token-pln' => '⚡ Token PLN',
                        'e-money' => '💳 E-Money',
                        'game' => '🎮 Game',
                        'voucher-belanja' => '🛍️ Voucher Belanja',
                        'pulsa-internasional' => '🌍 Pulsa Internasional',
                        'esim-internasional' => '📲 e-SIM Internasional',
                        'e-meterai' => '📜 E-Meterai',
                        'streaming' => '🎬 Streaming',
                        'paket-bicara' => '📞 Paket Bicara',
                        'pgn' => '🔥 PGN',
                        'roaming' => '✈️ Roaming',
                        'pascabayar-tagihan' => '🧾 Pulsa Pascabayar & Tagihan'
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

                <form action="" method="GET" class="mb-6 flex flex-col lg:flex-row gap-4 justify-between items-center">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">

                    <h2 class="text-xl font-bold text-gray-800 w-full lg:w-auto">
                        Data {{ ucwords(str_replace('-', ' ', $currentTab)) }}
                    </h2>

                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <div class="relative w-full sm:w-64">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama..."
                                   class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <select name="status" class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="gangguan" {{ request('status') == 'gangguan' ? 'selected' : '' }}>Gangguan</option>
                            <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>

                        <button type="submit" class="bg-blue-600 text-white px-5 py-2 text-sm rounded-lg hover:bg-blue-700 transition-colors w-full sm:w-auto">
                            Terapkan
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode SKU</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Modal</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Jual</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($products as $item)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item->kode_sku }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $item->nama_produk }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        Rp {{ number_format($item->harga_modal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Rp {{ number_format($item->harga_jual, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($item->status == 'aktif')
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                        @elseif($item->status == 'gangguan')
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Gangguan</span>
                                        @else
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <p>Data tidak ditemukan untuk pencarian atau filter ini.</p>
                                        </div>
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
@endsection
