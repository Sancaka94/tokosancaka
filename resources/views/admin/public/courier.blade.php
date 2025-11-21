{{-- 
|--------------------------------------------------------------------------
| 1. Kode untuk resources/views/admin/scan/courier.blade.php
|--------------------------------------------------------------------------
--}}

@extends('layouts.admin')
@section('title', 'Scan Terima Paket (Kurir)')
@section('page-title', 'Scan Surat Jalan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Scan Tanda Terima dari Customer</h2>
        <p class="text-sm text-gray-500 mb-4">Scan QR Code atau ketik manual Kode Surat Jalan yang diberikan oleh customer untuk menandai bahwa semua paket telah Anda terima.</p>
        <form id="scan-form">
            <input type="text" id="scan-input" class="w-full py-3 px-4 border-2 border-gray-300 rounded-lg text-xl focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Scan atau ketik Kode Surat Jalan..." autocomplete="off" autofocus>
        </form>
    </div>

    <div id="result-container" class="mt-8">
        {{-- Hasil akan ditampilkan di sini oleh JavaScript --}}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scanForm = document.getElementById('scan-form');
    const scanInput = document.getElementById('scan-input');
    const resultContainer = document.getElementById('result-container');

    scanForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const kodeSuratJalan = scanInput.value.trim();
        if (!kodeSuratJalan) return;

        try {
            const response = await fetch("{{ route('admin.scan.courier.handle') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ kode_surat_jalan: kodeSuratJalan })
            });
            const result = await response.json();
            
            let resultHTML = '';
            if (response.ok && result.success) {
                const data = result.data;
                resultHTML = `
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-lg shadow-md">
                        <h3 class="font-bold text-lg">Berhasil Diterima!</h3>
                        <p><strong>Kode:</strong> ${data.kode_surat_jalan}</p>
                        <p><strong>Pengirim:</strong> ${data.nama_pengirim}</p>
                        <p><strong>Jumlah Paket:</strong> ${data.jumlah_paket}</p>
                        <p><strong>Waktu:</strong> ${data.waktu_scan}</p>
                    </div>`;
            } else {
                resultHTML = `
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-lg shadow-md">
                        <h3 class="font-bold text-lg">Gagal!</h3>
                        <p>${result.message || 'Kode Surat Jalan tidak valid.'}</p>
                    </div>`;
            }
            resultContainer.innerHTML = resultHTML;
        } catch (error) {
            resultContainer.innerHTML = `
                <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-lg shadow-md">
                    <h3 class="font-bold text-lg">Error!</h3>
                    <p>Koneksi ke server gagal. Silakan coba lagi.</p>
                </div>`;
        }
        scanInput.value = '';
    });
});
</script>
@endpush


{{-- 
|--------------------------------------------------------------------------
| 2. Kode untuk resources/views/admin/scan/validation.blade.php
|--------------------------------------------------------------------------
--}}

@extends('layouts.admin')
@section('title', 'Validasi Paket di Gudang')
@section('page-title', 'Validasi Paket')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Kolom Kiri: Input & Aksi -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-lg shadow-md sticky top-24 space-y-6">
            <div>
                <label for="surat-jalan-input" class="block text-sm font-medium text-gray-700">1. Scan Kode Surat Jalan</label>
                <input type="text" id="surat-jalan-input" class="mt-1 w-full py-2 px-3 border rounded-lg" placeholder="Kode Surat Jalan...">
            </div>
            <div>
                <label for="resi-input" class="block text-sm font-medium text-gray-700">2. Scan Setiap Resi Paket</label>
                <input type="text" id="resi-input" class="mt-1 w-full py-2 px-3 border rounded-lg" placeholder="Scan resi di sini..." disabled>
            </div>
            <button id="validate-btn" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-colors" disabled>Validasi Sekarang</button>
             <div id="flash-message" class="hidden p-4 rounded-md text-sm font-medium"></div>
        </div>
    </div>

    <!-- Kolom Kanan: Daftar Resi yang di-Scan -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold text-gray-800">Paket yang Telah Di-scan (<span id="scan-count">0</span>)</h2>
            <ul id="scanned-list" class="mt-4 space-y-2 list-decimal list-inside">
                <!-- Resi akan ditambahkan di sini -->
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sjInput = document.getElementById('surat-jalan-input');
    const resiInput = document.getElementById('resi-input');
    const validateBtn = document.getElementById('validate-btn');
    const scanCountEl = document.getElementById('scan-count');
    const scannedListEl = document.getElementById('scanned-list');
    const flashMessageEl = document.getElementById('flash-message');
    
    let scannedResi = new Set();

    sjInput.addEventListener('change', function() {
        if (this.value.trim() !== '') {
            resiInput.disabled = false;
            resiInput.focus();
        } else {
            resiInput.disabled = true;
        }
    });

    resiInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const resi = this.value.trim();
            if (resi && !scannedResi.has(resi)) {
                scannedResi.add(resi);
                const li = document.createElement('li');
                li.textContent = resi;
                li.classList.add('font-mono');
                scannedListEl.appendChild(li);
                scanCountEl.textContent = scannedResi.size;
                validateBtn.disabled = false;
            }
            this.value = '';
        }
    });

    validateBtn.addEventListener('click', async function() {
        const kodeSuratJalan = sjInput.value.trim();
        if (!kodeSuratJalan || scannedResi.size === 0) {
            showFlash('Kode surat jalan dan minimal satu resi harus diisi.', 'error');
            return;
        }

        try {
            const response = await fetch("{{ route('admin.scan.validation.handle') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({
                    kode_surat_jalan: kodeSuratJalan,
                    scanned_resi: Array.from(scannedResi)
                })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showFlash(result.message, 'success');
                // Reset form
                setTimeout(() => {
                    sjInput.value = '';
                    resiInput.value = '';
                    resiInput.disabled = true;
                    validateBtn.disabled = true;
                    scannedListEl.innerHTML = '';
                    scannedResi.clear();
                    scanCountEl.textContent = 0;
                }, 2000);
            } else {
                showFlash(result.message, 'error');
            }
        } catch (error) {
            showFlash('Koneksi ke server gagal.', 'error');
        }
    });

    function showFlash(message, type) {
        flashMessageEl.textContent = message;
        flashMessageEl.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
        if (type === 'success') {
            flashMessageEl.classList.add('bg-green-100', 'text-green-800');
        } else {
            flashMessageEl.classList.add('bg-red-100', 'text-red-800');
        }
    }
});
</script>
@endpush
