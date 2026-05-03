<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kota</title>
    <!-- Tambahkan plugins=forms agar checkbox ter-render dengan rapi -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <!-- Diubah ke p-4 md:p-8 agar pas di layar HP -->
    <div class="max-w-6xl mx-auto p-4 md:p-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-200 pb-4 relative">
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-black">Menajemen Data Wilayah Kota / Kab</h1>
            
            <!-- Tombol Titik Tiga (Hanya Tampil di Mobile) -->
            <button id="mobileMenuBtn" class="md:hidden p-2 text-gray-600 hover:text-black focus:outline-none">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                </svg>
            </button>

            <!-- Wrapper untuk grup tombol (Dropdown di Mobile, Flex Normal di PC) -->
            <div id="actionMenu" class="hidden absolute top-12 right-0 z-50 w-56 p-4 flex-col gap-3 bg-white border border-gray-200 rounded-lg shadow-xl md:flex md:static md:w-auto md:p-0 md:flex-row md:items-center md:gap-3 md:bg-transparent md:border-none md:shadow-none">
                
                <!-- Tombol Input Transaksi (BARU) -->
                <a href="{{ route('transactions.create') }}" 
                   class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors flex items-center justify-center shadow-sm w-full md:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Input Count
                </a>

                <!-- Tombol Tambah Data Manual Kota -->
                <a href="{{ route('cities.create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center shadow-sm w-full md:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Kota
                </a>

                <!-- Tombol Kembali ke Dashboard -->
                <a href="https://bolone.sancaka.my.id/dashboard" 
                   class="px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors flex items-center justify-center shadow-sm w-full md:w-auto">
                    &larr; Kembali Ke Dashboard
                </a>

                <!-- Tombol Logout -->
                <form method="POST" action="{{ route('logout') }}" class="inline-block m-0 w-full md:w-auto">
                    @csrf
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin keluar?');" 
                            class="w-full justify-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-red-50 hover:text-red-700 hover:border-red-300 transition-all shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </button>
                </form>

            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-white border border-black text-black text-sm rounded-md shadow-sm">
                {{ session('success') }}
            </div>
        @endif

       <!-- Form Upload Card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 md:p-6 mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-black">Upload File CSV / XLSX</h2>
                    <p class="text-xs text-gray-500 mt-1">Tarik dan lepas file Anda ke dalam kotak putus-putus di bawah.</p>
                </div>
                
                <!-- Tombol Download Contoh CSV (Red Style) -->
                <a href="{{ route('cities.example') }}" 
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white bg-red-600 border border-red-600 rounded-md hover:bg-red-700 transition-all shadow-sm w-full sm:w-auto justify-center">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download Contoh CSV
                </a>
            </div>

            <!-- Form Upload -->
            <form id="upload-form" action="{{ route('cities.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                @csrf
                
                <!-- Area Drag & Drop -->
                <div class="w-full">
                    <label for="file-upload" id="drop-zone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-green-300 border-dashed rounded-lg cursor-pointer bg-green-50 hover:bg-green-100 transition-all duration-200 ease-in-out group">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none text-center px-2">
                            <svg class="w-8 h-8 mb-3 text-gray-400 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <p class="mb-2 text-sm text-gray-500"><span class="font-semibold text-black">Klik untuk memilih</span> atau drag and drop file ke sini</p>
                            <p class="text-xs text-gray-400">Format: CSV atau XLSX</p>
                            <p id="file-name" class="mt-3 px-3 py-1 bg-green-200 text-black text-xs font-semibold rounded-md hidden truncate max-w-[250px] sm:max-w-md"></p>
                        </div>
                        <input id="file-upload" name="file" type="file" class="hidden" accept=".csv, .xlsx" required />
                    </label>
                </div>

                <!-- LOG LOG - Container Progress Bar -->
                <div id="progress-container" class="hidden w-full mt-2">
                    <div class="flex justify-between items-center mb-1.5 text-xs font-medium">
                        <span id="progress-text" class="text-red-600">Mengunggah... 0%</span>
                        <span id="time-text" class="text-gray-500 tracking-tight text-right w-1/2">Menghitung durasi...</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div id="progress-bar" class="bg-red-500 h-2.5 rounded-full transition-all duration-200 ease-out" style="width: 0%"></div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 pt-4 mt-2">
                    <button type="submit" id="submit-btn" class="w-full sm:w-auto px-6 py-2.5 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
                        Upload & Proses
                    </button>
                </div>
            </form>
        </div>

        <!-- LOG LOG - Filter & Pencarian Box -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 md:p-6 mb-8">
            <h2 class="text-sm font-semibold text-black mb-4">Filter Data</h2>
            <form action="{{ route('cities.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 md:items-end">
                
                <!-- Input Pencarian -->
                <div class="flex-1 w-full">
                    <label for="search" class="block text-xs font-medium text-gray-700 mb-1">Cari Kota / Kabupaten</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Contoh: Surabaya atau Jawa Timur..." class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-black focus:border-black text-sm px-3 py-2 outline-none transition-all">
                </div>

                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                    <!-- Input Tanggal Mulai -->
                    <div class="w-full sm:w-48">
                        <label for="start_date" class="block text-xs font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-black focus:border-black text-sm px-3 py-2 outline-none transition-all">
                    </div>

                    <!-- Input Tanggal Sampai -->
                    <div class="w-full sm:w-48">
                        <label for="end_date" class="block text-xs font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-black focus:border-black text-sm px-3 py-2 outline-none transition-all">
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex gap-2 w-full md:w-auto pt-2 md:pt-0">
                    <button type="submit" class="flex-1 md:flex-none px-5 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
                        Terapkan
                    </button>
                    <!-- Tombol Reset -->
                    <a href="{{ route('cities.index') }}" class="flex-1 md:flex-none px-5 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors shadow-sm text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- FORM BUNGKUS TABEL UNTUK BULK DELETE -->
        <form action="{{ route('cities.bulk-delete') }}" method="POST" id="bulkDeleteForm">
            @csrf
            @method('DELETE')

            <!-- TOMBOL HAPUS MASSAL (Tampil jika ada checkbox yang dipilih) -->
            <div class="mb-4 hidden" id="bulkActionContainer">
                <button type="submit" onclick="return confirm('Yakin ingin menghapus semua data yang dipilih?')" 
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus yang Dipilih (<span id="selectedCount">0</span>)
                </button>
            </div>

            <!-- Tabel Data: Ditambahkan overflow-x-auto agar bisa di-scroll horizontal di HP -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap md:whitespace-normal">
                    <thead class="bg-gray-50">
                        <tr>
                            <!-- HEADER CHECKBOX ALL -->
                            <th scope="col" class="px-4 md:px-6 py-3 text-left w-10">
                                <input type="checkbox" id="checkAll" class="w-4 h-4 rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 cursor-pointer">
                            </th>
                            <th class="px-4 md:px-6 py-3 text-left font-medium text-gray-500 w-16">No</th>
                            <th class="px-4 md:px-6 py-3 text-left font-medium text-gray-500">Nama Kota</th>
                            <th class="px-4 md:px-6 py-3 text-left font-medium text-gray-500 min-w-[200px]">Keterangan</th>
                            <th class="px-4 md:px-6 py-3 text-right font-medium text-gray-500 w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($cities as $index => $city)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- KOLOM CHECKBOX PER BARIS -->
                                <td class="px-4 md:px-6 py-4">
                                    <input type="checkbox" name="ids[]" value="{{ $city->id }}" class="w-4 h-4 city-checkbox rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 cursor-pointer">
                                </td>
                                <td class="px-4 md:px-6 py-4 text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-4 md:px-6 py-4 font-medium text-black">{{ $city->nama_kota }}</td>
                                <td class="px-4 md:px-6 py-4 text-gray-600 truncate max-w-xs md:max-w-none">{{ $city->keterangan }}</td>
                                <!-- LOG LOG - Kolom Aksi dengan Icon SVG -->
                                <td class="px-4 md:px-6 py-4 text-right flex justify-end gap-3 items-center">
                                    <!-- Tombol Edit (Icon Hijau) memanggil fungsi JS -->
                                    <button type="button" 
                                        onclick="openEditModal({{ $city->id }}, '{{ addslashes($city->nama_kota) }}', '{{ addslashes($city->keterangan) }}')" 
                                        class="text-green-500 hover:text-green-700 transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <!-- Tombol Hapus Tunggal -->
                                    <button type="button" onclick="if(confirm('Hapus data ini secara permanen?')) { document.getElementById('delete-form-{{ $city->id }}').submit(); }" class="text-red-500 hover:text-red-700 transition-colors pt-1" title="Hapus">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    Belum ada data. Silakan upload file CSV di atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <!-- FORM HAPUS SATUAN -->
        @foreach($cities as $city)
            <form id="delete-form-{{ $city->id }}" action="{{ route('cities.destroy', $city->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <!-- LOG LOG - Area Pagination Tailwind -->
        <div class="mt-6 mb-10 overflow-x-auto">
            {{ $cities->links() ?? '' }}
        </div>

    </div>

    <!-- SCRIPT GABUNGAN UPLOAD & CHECKBOX -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ==========================================
            // LOGIC TOGGLE MENU MOBILE
            // ==========================================
            const mobileBtn = document.getElementById('mobileMenuBtn');
            const actionMenu = document.getElementById('actionMenu');

            // Toggle menu saat tombol titik tiga di klik
            mobileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                actionMenu.classList.toggle('hidden');
                actionMenu.classList.toggle('flex');
            });

            // Otomatis menutup dropdown menu jika klik di luar area menu
            document.addEventListener('click', (e) => {
                if (!mobileBtn.contains(e.target) && !actionMenu.contains(e.target)) {
                    actionMenu.classList.add('hidden');
                    actionMenu.classList.remove('flex');
                }
            });

            // ==========================================
            // LOGIC UPLOAD DRAG & DROP
            // ==========================================
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file-upload');
            const fileNameDisplay = document.getElementById('file-name');

            // Mencegah browser membuka file secara default saat di-drag
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Memberikan efek visual (hijau lebih gelap) saat file berada di atas kotak
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.add('bg-green-200', 'border-green-500');
                    dropZone.classList.remove('bg-green-50', 'border-green-300');
                }, false);
            });

            // Menghilangkan efek visual saat file keluar kotak
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.remove('bg-green-200', 'border-green-500');
                    dropZone.classList.add('bg-green-50', 'border-green-300');
                }, false);
            });

            // Menangkap file yang di-drop
            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }, false);

            // Menangkap file yang dipilih lewat klik
            fileInput.addEventListener('change', function(e) {
                handleFiles(this.files);
            });

            // Menampilkan nama file ke layar
            function handleFiles(files) {
                if (files.length > 0) {
                    fileInput.files = files; // Memasukkan file ke input tersembunyi
                    fileNameDisplay.textContent = 'File siap: ' + files[0].name;
                    fileNameDisplay.classList.remove('hidden');
                }
            }

            // ==========================================
            // LOGIC UPLOAD PROGRESS BAR
            // ==========================================
            const formUpload = document.getElementById('upload-form');
            const progressBarContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const timeText = document.getElementById('time-text');
            const submitBtn = document.getElementById('submit-btn');

            formUpload.addEventListener('submit', function(e) {
                e.preventDefault(); 

                if (fileInput.files.length === 0) return;

                progressBarContainer.classList.remove('hidden');
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                submitBtn.innerText = "Memproses...";

                const formData = new FormData(formUpload);
                const xhr = new XMLHttpRequest();
                let startTime = new Date().getTime();

                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        let percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';

                        if (percentComplete < 100) {
                            progressBar.classList.remove('bg-green-500');
                            progressBar.classList.add('bg-red-500');
                            progressText.classList.remove('text-green-600');
                            progressText.classList.add('text-red-600');
                            progressText.innerText = 'Mengunggah... ' + percentComplete + '%';

                            let currentTime = new Date().getTime();
                            let elapsedSeconds = (currentTime - startTime) / 1000;
                            
                            if (elapsedSeconds > 0) {
                                let bytesPerSecond = e.loaded / elapsedSeconds;
                                let remainingBytes = e.total - e.loaded;
                                let remainingSeconds = Math.round(remainingBytes / bytesPerSecond);
                                
                                let minutes = Math.floor(remainingSeconds / 60);
                                let seconds = remainingSeconds % 60;
                                
                                let timeString = '';
                                if (minutes > 0) timeString += minutes + 'm ';
                                timeString += seconds + 's';
                                timeText.innerText = 'Sisa durasi: ' + timeString;
                            }
                        } else {
                            progressBar.classList.remove('bg-red-500');
                            progressBar.classList.add('bg-green-500');
                            progressText.classList.remove('text-red-600');
                            progressText.classList.add('text-green-600');
                            progressText.innerText = 'Selesai 100% - Memproses ke Database...';
                            timeText.innerText = 'Mohon tunggu...';
                        }
                    }
                });

                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        window.location.reload();
                    } else {
                        alert('Terjadi kesalahan saat memproses file Anda.');
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        submitBtn.innerText = "Upload & Proses";
                        progressBarContainer.classList.add('hidden');
                    }
                };

                xhr.open('POST', formUpload.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); 
                xhr.send(formData);
            });

            // ==========================================
            // LOGIC CHECKBOX & BULK DELETE
            // ==========================================
            const checkAll = document.getElementById('checkAll');
            const checkboxes = document.querySelectorAll('.city-checkbox');
            const bulkActionContainer = document.getElementById('bulkActionContainer');
            const selectedCountSpan = document.getElementById('selectedCount');

            function toggleBulkAction() {
                const checkedCount = document.querySelectorAll('.city-checkbox:checked').length;
                
                if (checkedCount > 0) {
                    bulkActionContainer.classList.remove('hidden');
                    selectedCountSpan.textContent = checkedCount;
                } else {
                    bulkActionContainer.classList.add('hidden');
                }

                if(checkAll) {
                    checkAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
                }
            }

            if(checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    toggleBulkAction();
                });
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', toggleBulkAction);
            });
        });
    </script>

</body>
</html>