@extends('layouts.admin')
@section('title', 'Transfer Dana ke Toko')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Pencairan Dana Escrow</h1>
            <p class="text-gray-600 mt-1">Halaman khusus untuk mentransfer dana dari Dompet Utama Admin ke Sub Account Toko.</p>
        </div>
        <a href="{{ route('admin.doku.balance') }}" class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors font-medium">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Saldo
        </a>
    </div>

    {{-- ALERT PESAN SUKSES & ERROR --}}
    @if (session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 mb-6 rounded-r-lg shadow-sm">
            <strong class="font-bold">Berhasil!</strong>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 mb-6 rounded-r-lg shadow-sm">
            <strong class="font-bold">Gagal Diproses!</strong>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-xl border border-gray-100 p-6 md:p-8 max-w-3xl">
        <form action="{{ route('admin.doku.transfer.process') }}" method="POST" class="space-y-6">
            @csrf
            
            {{-- Pilihan Toko --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tujuan Transfer (Penerima) <span class="text-red-500">*</span></label>
                <select name="store_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2.5 bg-gray-50" required>
                    <option value="">-- Pilih Toko / Agent --</option>
                    @forelse($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }} (SAC: {{ $store->doku_sac_id }})</option>
                    @empty
                        <option value="" disabled>Belum ada toko yang memiliki Sub Account DOKU.</option>
                    @endforelse
                </select>
                <p class="text-xs text-gray-500 mt-1">Hanya toko yang sudah memiliki DOKU SAC ID yang muncul di sini.</p>
            </div>

            {{-- Nominal --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nominal Transfer (Rp) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 font-medium">Rp</span>
                    </div>
                    <input type="number" name="amount" min="1000" placeholder="Contoh: 150000" class="pl-12 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2.5 bg-gray-50" required>
                </div>
                <p class="text-xs text-gray-500 mt-1">Minimal transfer adalah Rp 1.000</p>
            </div>

            {{-- Deskripsi/Catatan --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan Transaksi (Opsional)</label>
                <textarea name="description" rows="2" placeholder="Contoh: Pencairan dana escrow untuk pesanan INV-9923..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2.5 bg-gray-50" maxlength="255"></textarea>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit" onclick="return confirm('Proses pencairan tidak dapat dibatalkan. Pastikan penerima dan nominal sudah benar. Lanjutkan?')" class="bg-blue-600 text-white font-bold px-6 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center shadow-sm">
                    <i class="fas fa-paper-plane mr-2"></i> Eksekusi Transfer Sekarang
                </button>
            </div>
        </form>
    </div>
</div>
@endsection