{{-- Menggunakan layout utama admin --}}
@extends('layouts.admin')

{{-- Menentukan judul halaman yang akan tampil di browser --}}
@section('title', 'Monitoring Surat Jalan')

{{-- Konten utama halaman --}}
@section('content')

{{-- Elemen Notifikasi Modal Real-time --}}
<div id="realtime-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 transition-opacity duration-300 opacity-0 pointer-events-none">
    <div id="modal-content" class="bg-white rounded-xl shadow-2xl w-full max-w-md transform scale-95 transition-all duration-300">
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-2 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-800">Surat Jalan Baru Dibuat!</h3>
                        <p class="text-sm text-gray-500">Total hari ini: <span id="total-today" class="font-semibold">0</span></p>
                    </div>
                </div>
                <button onclick="hideModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="mt-4 max-h-64 overflow-y-auto pr-2">
                <ul id="new-sj-list" class="space-y-3">
                    {{-- Daftar surat jalan baru akan ditambahkan di sini oleh JavaScript --}}
                </ul>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                 <button onclick="hideModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
                    Tutup
                </button>
                <button onclick="location.reload()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Muat Ulang Halaman
                </button>
            </div>
        </div>
    </div>
</div>


<div class="p-4 sm:p-6 lg:p-8">
    
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Monitoring Surat Jalan</h1>
        <nav class="flex mt-2" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <svg class="w-3 h-3 mr-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Monitoring Surat Jalan</span>
                    </div>
                </li>
            </ol>
        </nav>
    </header>

    <!-- Form Filter dan Pencarian -->
    <div class="bg-white p-6 rounded-xl shadow-md mb-6">
        <form action="{{ route('admin.spx_scans.monitor.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Kolom Pencarian -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Kode / Nama</label>
                    <input type="text" name="search" id="search" placeholder="Kode atau Nama Pengirim"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           value="{{ request('search') }}">
                </div>

                <!-- Filter Tanggal Mulai -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           value="{{ request('start_date') }}">
                </div>

                <!-- Filter Tanggal Selesai -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           value="{{ request('end_date') }}">
                </div>

                <!-- Tombol Aksi -->
                <div class="flex items-end space-x-3">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition shadow-sm">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.spx_scans.monitor.index') }}" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition">
                        Reset
                    </a>
                </div>
            </div>

            <!-- Filter Cepat -->
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" onclick="setFilterDate('today')" class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded-full hover:bg-blue-100 transition">Hari Ini</button>
                <button type="button" onclick="setFilterDate('last_7_days')" class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded-full hover:bg-blue-100 transition">7 Hari Terakhir</button>
                <button type="button" onclick="setFilterDate('last_30_days')" class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded-full hover:bg-blue-100 transition">30 Hari Terakhir</button>
                <button type="button" onclick="setFilterDate('last_month')" class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded-full hover:bg-blue-100 transition">Bulan Lalu</button>
            </div>
        </form>
    </div>

    <!-- Tombol Export -->
    <div class="flex justify-end mb-6">
        <a href="{{ route('admin.spx_scans.monitor.export_pdf', request()->query()) }}" class="bg-red-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition shadow-sm flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> Export PDF
        </a>
    </div>

    <!-- Tabel Data Surat Jalan -->
    <div class="bg-white rounded-xl shadow-md overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">No</th>
                    <th scope="col" class="px-6 py-3">Kode Surat Jalan</th>
                    <th scope="col" class="px-6 py-3">Nama Pengirim</th>
                    <th scope="col" class="px-6 py-3 text-center">Jumlah Paket</th>
                    <th scope="col" class="px-6 py-3">Tanggal Dibuat</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suratJalans as $index => $sj)
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">{{ $suratJalans->firstItem() + $index }}</td>
                    <td class="px-6 py-4 font-mono text-gray-800 font-semibold">{{ $sj->kode_surat_jalan }}</td>
                    <td class="px-6 py-4">{{ $sj->user->nama_lengkap ?? ($sj->kontak->nama ?? 'N/A') }}</td>
                    <td class="px-6 py-4 text-center">{{ $sj->jumlah_paket }}</td>
                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($sj->created_at)->translatedFormat('d F Y, H:i') }}</td>
                    <td class="px-6 py-4">
                        @if($sj->status == 'Diterima Kurir')
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ $sj->status }}</span>
                        @elseif($sj->status == 'Menunggu Pickup' || $sj->status == 'Menunggu Pickup Kurir')
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">{{ $sj->status }}</span>
                        @elseif($sj->status == 'Tervalidasi di Gudang')
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">{{ $sj->status }}</span>
                        @else
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">{{ $sj->status ?? 'Tidak Diketahui' }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('admin.suratjalan.download', ['kode_surat_jalan' => $sj->kode_surat_jalan]) }}" class="text-blue-600 hover:text-blue-800" title="Download PDF">
                            <i class="fas fa-download fa-lg"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10 text-gray-500">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="font-semibold">Data Tidak Ditemukan</p>
                            <small class="text-muted">Coba ubah filter pencarian Anda.</small>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        {{ $suratJalans->withQueryString()->links() }}
    </div>
</div>

@endsection

@push('scripts')
{{-- Pastikan @vite(['resources/js/app.js']) ada di layout utama Anda --}}

<script>
    // Fungsi global untuk modal agar bisa diakses dari HTML
    const modal = document.getElementById('realtime-modal');
    const modalContent = document.getElementById('modal-content');

    window.showModal = function() {
        if (!modal || !modalContent) return;
        modal.classList.remove('opacity-0', 'pointer-events-none');
        modalContent.classList.remove('scale-95');
    }

    window.hideModal = function() {
        if (!modal || !modalContent) return;
        modalContent.classList.add('scale-95');
        // Tambahkan delay agar transisi selesai sebelum disembunyikan
        setTimeout(() => {
            modal.classList.add('opacity-0', 'pointer-events-none');
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const totalTodaySpan = document.getElementById('total-today');
        const newSjList = document.getElementById('new-sj-list');
        let totalTodayCount = 0;

        // Fungsi untuk menambahkan item ke list di modal
        function addSuratJalanToList(sj) {
            if (!newSjList) return;
            const listItem = document.createElement('li');
            listItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg animate-fade-in'; // Tambahkan animasi
            listItem.innerHTML = `
                <div>
                    <p class="font-semibold text-gray-800">${sj.kode_surat_jalan}</p>
                    <p class="text-xs text-gray-500">oleh ${sj.user_name} - ${sj.jumlah_paket} paket</p>
                </div>
                <span class="text-xs font-medium text-gray-400">${sj.time}</span>
            `;
            // Tambahkan item baru di paling atas
            newSjList.prepend(listItem);
        }

        // Fungsi untuk mengambil data awal saat halaman dimuat
        async function fetchInitialData() {
            try {
                const response = await fetch('{{ route("admin.spx_scans.todays_data") }}');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                
                totalTodayCount = data.total || 0;
                if (totalTodaySpan) {
                    totalTodaySpan.textContent = totalTodayCount;
                }
                
                if (newSjList) {
                    newSjList.innerHTML = ''; // Kosongkan list
                    if (data.latest && Array.isArray(data.latest)) {
                        data.latest.forEach(addSuratJalanToList);
                    }
                }
            } catch (error) {
                console.error('Gagal mengambil data awal:', error);
            }
        }

        // Panggil fungsi untuk mengambil data awal
        fetchInitialData();

        // Fungsi untuk setup listener Laravel Echo
        function setupEchoListener() {
            if (typeof window.Echo !== 'undefined') {
                console.log('Echo is initialized. Listening for events...');
                window.Echo.channel('surat-jalan-created')
                    .listen('.SuratJalanCreated', (e) => { // Perhatikan: tambahkan titik di depan nama event
                        console.log('Event received:', e);
                        
                        // Update total
                        totalTodayCount++;
                        if (totalTodaySpan) {
                            totalTodaySpan.textContent = totalTodayCount;
                        }

                        // Tambahkan item baru ke list
                        addSuratJalanToList({
                            kode_surat_jalan: e.suratJalan.kode_surat_jalan,
                            user_name: e.user.nama_lengkap,
                            jumlah_paket: e.suratJalan.jumlah_paket,
                            time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                        });

                        // Tampilkan modal
                        showModal();
                    });
            } else {
                console.error('GAGAL INISIALISASI ECHO: Pastikan Anda sudah menjalankan `npm run dev` dan meng-import Echo di bootstrap.js');
            }
        }
        
        // Panggil fungsi listener
        setupEchoListener();
    });


    // Fungsi untuk filter tanggal cepat
    function setFilterDate(period) {
        const form = document.querySelector('form');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        if (!form || !startDateInput || !endDateInput) return;

        const today = new Date();
        let startDate = new Date();

        const toYmd = (date) => date.toISOString().split('T')[0];

        switch (period) {
            case 'today':
                startDate = today;
                break;
            case 'last_7_days':
                startDate.setDate(today.getDate() - 6);
                break;
            case 'last_30_days':
                startDate.setDate(today.getDate() - 29);
                break;
            case 'last_month':
                // Mengatur tanggal ke hari pertama bulan lalu
                startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                // Mengatur tanggal akhir ke hari terakhir bulan lalu
                const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                endDateInput.value = toYmd(lastDayLastMonth);
                startDateInput.value = toYmd(startDate);
                form.submit();
                return; 
        }

        startDateInput.value = toYmd(startDate);
        endDateInput.value = toYmd(today);
        form.submit();
    }
</script>
@endpush
