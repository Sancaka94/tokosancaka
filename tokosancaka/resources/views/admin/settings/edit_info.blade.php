@extends('layouts.admin') {{-- Sesuaikan dengan layout admin kamu --}}

@section('content')
<div class="max-w-4xl mx-auto py-6">
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Pengaturan Pesan Informasi (Halaman Buat Pesanan)</h2>

        {{-- Notifikasi Sukses --}}
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.info.update') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Isi Pesan Peringatan (Merah)
                </label>
                <p class="text-xs text-gray-500 mb-2">
                    Teks ini akan muncul di halaman "Buat Pesanan" pelanggan dalam kotak merah.
                </p>
                
                <textarea 
                    name="pesan_admin" 
                    rows="5" 
                    class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:border-red-500"
                    placeholder="Contoh: Mohon pastikan alamat penerima lengkap..."
                >{{ old('pesan_admin', $pesan) }}</textarea>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none">
                    Simpan Perubahan
                </button>
                <span class="text-xs text-gray-400">*Kosongkan isi pesan untuk menyembunyikan kotak merah di sisi pelanggan.</span>
            </div>
        </form>
    </div>
</div>
@endsection