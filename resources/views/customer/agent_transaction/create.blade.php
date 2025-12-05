@extends('layouts.customer')

@section('title', 'Kasir Penjualan PPOB')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-cash-register text-blue-600"></i> Kasir / Transaksi Offline
            </h1>
            <p class="text-sm text-gray-500">Gunakan halaman ini untuk melayani pembeli yang datang ke konter Anda.</p>
        </div>
        <div class="bg-blue-50 px-5 py-3 rounded-xl text-right">
            <p class="text-xs text-blue-600 font-bold uppercase">Saldo Aktif Anda</p>
            <p class="text-2xl font-extrabold text-blue-800">Rp {{ number_format(Auth::user()->saldo, 0, ',', '.') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
            <p class="font-bold">Berhasil!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
            <p class="font-bold">Gagal!</p>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI: FORM INPUT NOMOR --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 sticky top-24">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">1. Data Pembeli</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-600 mb-2">Nomor HP / ID Pelanggan</label>
                    <input type="number" id="input_customer_no" 
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-bold"
                           placeholder="Contoh: 08123456789" onkeyup="syncNumber()">
                    <p class="text-xs text-gray-400 mt-2">Masukkan nomor pembeli di sini.</p>
                </div>

                <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-100 text-sm text-yellow-800">
                    <i class="fas fa-lightbulb mr-1"></i> 
                    Tips: Pastikan nomor benar sebelum klik tombol proses. Saldo akan langsung terpotong.
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: PILIH PRODUK --}}
        <div class="lg:col-span-2">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                    <h3 class="font-bold text-gray-800">2. Pilih Produk</h3>
                    
                    {{-- Search --}}
                    <form action="{{ route('agent.transaction.create') }}" method="GET" class="w-full sm:w-1/2 relative">
                        <input type="text" name="q" value="{{ request('q') }}" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500" 
                               placeholder="Cari produk (Telkomsel, Token)...">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-bold">
                            <tr>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3 text-right">Modal Anda</th>
                                <th class="px-4 py-3 text-right text-green-700">Harga Jual</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($products as $product)
                                @php
                                    $modal = $product->modal_agen;
                                    $jual = $product->harga_jual_agen ?? ($modal + 2000);
                                    $profit = $jual - $modal;
                                @endphp
                                <tr class="hover:bg-blue-50 transition group">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-800">{{ $product->product_name }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ $product->brand }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-600">
                                        Rp {{ number_format($modal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-green-700">
                                        Rp {{ number_format($jual, 0, ',', '.') }}
                                        <div class="text-[10px] text-green-500 font-normal">Untung: Rp {{ number_format($profit) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick="confirmTransaction('{{ $product->buyer_sku_code }}', '{{ $product->product_name }}', '{{ $modal }}', '{{ $jual }}')" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold text-xs shadow-md transition transform hover:scale-105">
                                            PROSES
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-gray-500">Produk tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

    </div>
</div>

{{-- MODAL KONFIRMASI --}}
<div id="confirmModal" class="fixed inset-0 z-50 hidden backdrop-blur-sm" role="dialog">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">Konfirmasi Transaksi</h3>
            
            <div class="bg-gray-50 p-4 rounded-xl mb-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Nomor Tujuan:</span>
                    <span class="font-mono font-bold text-gray-800 text-base" id="modal_no">-</span>
                </div>
                <div class="border-t border-dashed border-gray-300"></div>
                <div>
                    <span class="text-gray-500 block text-xs">Produk:</span>
                    <span class="font-bold text-gray-800" id="modal_product">-</span>
                </div>
                <div class="flex justify-between items-center bg-blue-100 p-2 rounded">
                    <span class="text-blue-800 font-bold">Saldo Terpotong:</span>
                    <span class="font-bold text-blue-800 text-lg" id="modal_modal">Rp 0</span>
                </div>
                <div class="flex justify-between items-center bg-green-100 p-2 rounded">
                    <span class="text-green-800 font-bold">Tagih ke Pembeli:</span>
                    <span class="font-bold text-green-800 text-lg" id="modal_jual">Rp 0</span>
                </div>
            </div>

            <form action="{{ route('agent.transaction.store') }}" method="POST">
                @csrf
                <input type="hidden" name="sku" id="form_sku">
                <input type="hidden" name="customer_no" id="form_no">
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition shadow-lg">BAYAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function syncNumber() {
        // Fungsi ini bisa dikembangkan untuk auto-detect provider
        let num = document.getElementById('input_customer_no').value;
    }

    function confirmTransaction(sku, name, modal, jual) {
        let no = document.getElementById('input_customer_no').value;
        if(no.length < 10) {
            alert('Mohon masukkan Nomor HP pembeli terlebih dahulu!');
            document.getElementById('input_customer_no').focus();
            return;
        }

        document.getElementById('modal_no').innerText = no;
        document.getElementById('modal_product').innerText = name;
        document.getElementById('modal_modal').innerText = 'Rp ' + parseInt(modal).toLocaleString('id-ID');
        document.getElementById('modal_jual').innerText = 'Rp ' + parseInt(jual).toLocaleString('id-ID');
        
        document.getElementById('form_sku').value = sku;
        document.getElementById('form_no').value = no;

        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.add('hidden');
    }
</script>
@endsection