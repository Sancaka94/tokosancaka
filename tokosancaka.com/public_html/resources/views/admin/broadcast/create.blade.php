@extends('layouts.admin')

@section('page-title', 'Kirim Broadcast Notifikasi')

@section('content')
<div class="w-full max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">

        {{-- Header Form --}}
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center gap-3">
                <a href="{{ url()->previous() }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Buat Pengumuman Baru</h2>
                    <p class="text-sm text-gray-500 mt-1">Kirim push notifikasi langsung ke HP seluruh pengguna aplikasi.</p>
                </div>
            </div>
        </div>

        {{-- Form Body --}}
        <form action="{{ route('admin.broadcast.send') }}" method="POST" class="p-6 space-y-6">
            @csrf

            {{-- Tanggal --}}
            <div>
                <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengiriman <span class="text-red-500">*</span></label>
                <input type="date" name="tanggal" id="tanggal" value="{{ date('Y-m-d') }}" required
                       class="block w-full sm:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <p class="text-xs text-gray-500 mt-1">Format: Bulan/Tanggal/Tahun</p>
            </div>

            {{-- Judul --}}
            <div>
                <label for="judul" class="block text-sm font-medium text-gray-700 mb-1">Judul Pengumuman <span class="text-red-500">*</span></label>
                <input type="text" name="judul" id="judul" placeholder="Contoh: Promo Gebyar Diskon Ongkir!" required
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            {{-- Isi Pengumuman --}}
            <div>
                <label for="pesan" class="block text-sm font-medium text-gray-700 mb-1">Isi Pesan / Deskripsi <span class="text-red-500">*</span></label>
                <textarea name="pesan" id="pesan" rows="4" placeholder="Tuliskan pesan lengkap yang akan dibaca oleh pengguna..." required
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
            </div>

            {{-- Jenis Aksi --}}
            <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-lg">
                <label for="jenis_aksi" class="block text-sm font-semibold text-indigo-900 mb-2">Tindakan Saat Notifikasi Diklik</label>
                <select name="jenis_aksi" id="jenis_aksi" class="block w-full rounded-md border-indigo-300 text-indigo-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="info">Hanya Info (Buka Aplikasi Sancaka)</option>
                    <option value="playstore">Arahkan ke Playstore (Minta Rating Bintang 5)</option>
                    <option value="link">Arahkan ke Link Website Lain</option>
                </select>
            </div>

            {{-- Kolom Link Custom (Disembunyikan secara default) --}}
            <div id="link_container" style="display: none;" class="mt-4">
                <label for="link" class="block text-sm font-medium text-gray-700 mb-1">Tautan Web (URL Lengkap)</label>
                <input type="url" name="link" id="link" placeholder="https://tokosancaka.com/promo..."
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <p class="text-xs text-gray-500 mt-1">Pastikan diawali dengan <b>http://</b> atau <b>https://</b></p>
            </div>

            {{-- Footer Tombol Submit --}}
            <div class="pt-5 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" onclick="window.history.back();"
                        class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Batal
                </button>
                <button type="submit"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-md text-sm font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i> Kirim Broadcast Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Script untuk memunculkan kolom link --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jenisAksiDropdown = document.getElementById('jenis_aksi');
        const linkContainer = document.getElementById('link_container');
        const linkInput = document.getElementById('link');

        jenisAksiDropdown.addEventListener('change', function() {
            if (this.value === 'link') {
                linkContainer.style.display = 'block';
                linkInput.setAttribute('required', 'required'); // Wajib isi jika milih link
            } else {
                linkContainer.style.display = 'none';
                linkInput.removeAttribute('required');
                linkInput.value = ''; // Kosongkan saat disembunyikan
            }
        });
    });
</script>
@endpush
@endsection
