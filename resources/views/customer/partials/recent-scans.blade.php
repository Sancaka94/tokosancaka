{{--
    File: resources/views/customer/partials/recent-scans.blade.php
    Deskripsi: Menampilkan daftar resi yang telah di-scan dalam satu sesi.
--}}

@forelse($scans as $scan)
    {{-- Menggunakan kelas-kelas Tailwind CSS --}}
    <div class="flex justify-between items-center border-b border-gray-200 pb-2 mb-2 scan-history-item">
        {{-- Menambahkan class 'scanned-resi-value' agar bisa dibaca oleh JavaScript --}}
        <span class="font-semibold text-gray-800 scanned-resi-value">{{ $scan->resi_number }}</span>
        <small class="text-sm text-gray-500">{{ $scan->created_at->format('H:i') }}</small>
    </div>
@empty
    <p class="text-gray-500 text-center">Belum ada paket yang di-scan hari ini.</p>
@endforelse
