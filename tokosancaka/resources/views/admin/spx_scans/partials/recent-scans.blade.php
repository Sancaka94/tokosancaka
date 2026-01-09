{{--
    File: resources/views/admin/spx-scans/partials/recent-scans.blade.php
    Deskripsi: Partial view untuk menampilkan daftar resi yang baru di-scan.
--}}

@forelse ($scans as $scan)
    <div class="scan-history-item flex justify-between items-center p-2 bg-gray-50 rounded-md">
        <span class="font-mono text-sm text-gray-700 scanned-resi-value">{{ $scan->resi_number }}</span>
        <span class="text-xs text-gray-400">{{ $scan->created_at->format('H:i') }}</span>
    </div>
@empty
    <p class="text-center text-sm text-gray-500 py-4">Belum ada paket yang di-scan hari ini.</p>
@endforelse
