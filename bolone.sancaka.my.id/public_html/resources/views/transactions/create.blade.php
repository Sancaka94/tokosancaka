<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Transaksi Harian</title>
    
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery & Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        .select2-container .select2-selection--single {
            height: 42px !important;
            border-color: #d1d5db !important;
            border-radius: 0.375rem !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-4xl mx-auto p-8 mt-6">
        
       <!-- Header -->
        <div class="mb-8 border-b border-gray-200 pb-4 flex justify-between items-center">
            
            <!-- Sisi Kiri: Judul -->
            <h1 class="text-2xl font-semibold tracking-tight text-black">Input Data Transaksi Kota</h1>
            
            <!-- Sisi Kanan: Kumpulan Aksi (Tombol Kembali & Logout) -->
            <div class="flex items-center gap-4">
                
                <!-- Tombol Kembali -->
                <a href="{{ route('cities.index') }}" class="px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm inline-flex items-center">
                    &larr; Kembali
                </a>

                <!-- Tombol Logout -->
                <form method="POST" action="{{ route('logout') }}" class="inline-block m-0">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('Apakah Anda yakin ingin keluar?');" 
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-red-50 hover:text-red-700 hover:border-red-300 transition-all shadow-sm inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </button>
                </form>

            </div>
        </div>

        <!-- Alert Sukses / Error -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-md">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md">
                {{ session('error') }}
            </div>
        @endif

        <!-- ================= BAGIAN 1: UPLOAD EXCEL / CSV ================= -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-5 gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-black">Upload File Transaksi (CSV / XLSX)</h2>
                    <p class="text-xs text-gray-500 mt-1">Otomatis input banyak data. Pastikan format kolom sesuai dengan template Excel.</p>
                </div>
                
                <!-- Tombol Download Format Kolom Excel (Warna Hijau Excel) -->
                <a href="{{ route('transactions.example') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download Format Kolom Excel
                </a>
            </div>

            <form action="{{ route('transactions.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                @csrf
                <!-- Area Drag & Drop -->
                <div class="w-full">
                    <label for="file-upload" id="drop-zone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-green-300 border-dashed rounded-lg cursor-pointer bg-green-50 hover:bg-green-100 transition-all duration-200">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                            <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <p class="mb-2 text-sm text-gray-500"><span class="font-semibold text-black">Klik untuk memilih</span> atau drag and drop file ke sini</p>
                            <p id="file-name" class="mt-3 px-3 py-1 bg-green-200 text-black text-xs font-semibold rounded-md hidden"></p>
                        </div>
                        <input id="file-upload" name="file" type="file" class="hidden" accept=".csv, .xlsx" required />
                    </label>
                </div>
                <div class="flex justify-end border-t border-gray-100 pt-4 mt-2">
                    <button type="submit" class="px-6 py-2.5 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm">
                        Upload & Proses Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Divider -->
        <div class="flex items-center my-8">
            <div class="flex-grow border-t border-gray-200"></div>
            <span class="flex-shrink-0 mx-4 text-gray-400 text-xs font-medium uppercase tracking-wider">Atau Input Manual</span>
            <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <!-- ================= BAGIAN 2: INPUT MANUAL ================= -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-10">
            <h2 class="text-sm font-semibold text-black mb-5">Form Input Manual</h2>
            
            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Dropdown Kota -->
                    <div>
                        <label for="city_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Kota / Wilayah</label>
                        <select name="city_id" id="city_id" class="w-full select2" required>
                            <option value="" disabled selected>Cari kota...</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->nama_kota }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Input Tanggal -->
                    <div>
                        <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Data Masuk</label>
                        <input type="date" name="tanggal" id="tanggal" required value="{{ date('Y-m-d') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black outline-none transition-colors">
                    </div>

                    <!-- Input Jumlah -->
                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Angka)</label>
                        <input type="number" name="jumlah" id="jumlah" placeholder="Contoh: 130" required min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black outline-none transition-colors">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors shadow-sm">
                        Simpan Manual
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Script Interaktif (Select2 & Drag-Drop) -->
    <script>
        $(document).ready(function() {
            $('.select2').select2({ placeholder: "Ketik untuk mencari...", allowClear: true, width: '100%' });
        });

        // Script Drag & Drop File Upload
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file-upload');
            const fileNameDisplay = document.getElementById('file-name');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.add('bg-green-200', 'border-green-500');
                    dropZone.classList.remove('bg-green-50', 'border-green-300');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.remove('bg-green-200', 'border-green-500');
                    dropZone.classList.add('bg-green-50', 'border-green-300');
                }, false);
            });

            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                handleFiles(dt.files);
            }, false);

            fileInput.addEventListener('change', function() { handleFiles(this.files); });

            function handleFiles(files) {
                if (files.length > 0) {
                    fileInput.files = files;
                    fileNameDisplay.textContent = 'File siap: ' + files[0].name;
                    fileNameDisplay.classList.remove('hidden');
                }
            }
        });
    </script>
</body>
</html>