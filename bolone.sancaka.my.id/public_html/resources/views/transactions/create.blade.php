<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Transaksi</title>
    
    <!-- Tailwind CDN dengan plugin forms -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    
    <!-- jQuery & Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Custom Select2 agar match dengan Tailwind forms (Tema Minimalis) */
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
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #000 !important; /* Warna highlight hitam */
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-5xl mx-auto p-8 mt-6">
        
        <!-- Header -->
        <div class="mb-8 border-b border-gray-200 pb-4 flex justify-between items-center">
            
            <h1 class="text-2xl font-semibold tracking-tight text-black">Input Data Wilayah Kota</h1>
            
            <div class="flex items-center gap-4">
                <!-- Tombol Kembali -->
                <a href="{{ route('cities.index') }}" class="px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm inline-flex items-center">
                    &larr; Kembali
                </a>

                <!-- Tombol Logout -->
                <form method="POST" action="{{ route('logout') }}" class="inline-block m-0">
                    @csrf
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin keluar?');" 
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors shadow-sm inline-flex items-center gap-2">
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
            <div class="mb-6 p-4 bg-gray-100 border border-gray-300 text-black rounded-md text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- ================= BAGIAN 1: UPLOAD EXCEL / CSV ================= -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-5 gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-black">Upload File Transaksi (CSV / XLSX)</h2>
                    <p class="text-xs text-gray-500 mt-1">Otomatis input banyak data. Pastikan format kolom sesuai dengan template.</p>
                </div>
                
                <!-- Tombol Download Format (Minimalis) -->
                <a href="{{ route('transactions.example') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-black bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Format Template
                </a>
            </div>

            <form action="{{ route('transactions.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                @csrf
                <!-- Area Drag & Drop (Minimalis, berubah border hitam saat di-hover/drag) -->
                <div class="w-full">
                    <label for="file-upload" id="drop-zone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 hover:border-black transition-all duration-200">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                            <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <p class="mb-2 text-sm text-gray-500"><span class="font-semibold text-black">Klik untuk memilih</span> atau drag and drop file ke sini</p>
                            <p id="file-name" class="mt-3 px-3 py-1 bg-black text-white text-xs font-semibold rounded-md hidden"></p>
                        </div>
                        <input id="file-upload" name="file" type="file" class="hidden" accept=".csv, .xlsx" required />
                    </label>
                </div>
                <div class="flex justify-end border-t border-gray-100 pt-4 mt-2">
                    <button type="submit" class="px-6 py-2.5 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
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
                    <div>
                        <label for="city_id" class="block text-sm font-medium text-gray-700 mb-1">Kota / Wilayah <span class="text-black font-bold">*</span></label>
                        <select name="city_id" id="city_id" class="w-full select2" required>
                            <option value="" disabled selected>Pilih kota...</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->nama_kota }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Data <span class="text-black font-bold">*</span></label>
                        <input type="date" name="tanggal" id="tanggal" required value="{{ date('Y-m-d') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-black focus:border-black outline-none transition-colors sm:text-sm">
                    </div>
                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Angka) <span class="text-black font-bold">*</span></label>
                        <input type="number" name="jumlah" id="jumlah" placeholder="Contoh: 130" required min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-black focus:border-black outline-none transition-colors sm:text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Divider -->
        <div class="flex items-center my-8">
            <div class="flex-grow border-t border-gray-200"></div>
            <span class="flex-shrink-0 mx-4 text-gray-400 text-xs font-medium uppercase tracking-wider">Riwayat Input</span>
            <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <!-- ================= BAGIAN 3: FILTER & PENCARIAN REALTIME ================= -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 mb-6">
            <form action="{{ url()->current() }}" method="GET" id="filterForm" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label for="search" class="block text-xs font-medium text-gray-700 mb-1">Cari Nama Kota</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Ketik pencarian..." autocomplete="off"
                           class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-black focus:border-black text-sm px-3 py-2 outline-none transition-all sm:text-sm">
                </div>
                <div class="w-full md:w-44">
                    <label for="start_date" class="block text-xs font-medium text-gray-700 mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                           class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-black focus:border-black text-sm px-3 py-2 outline-none transition-all sm:text-sm">
                </div>
                <div class="w-full md:w-44">
                    <label for="end_date" class="block text-xs font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                           class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-black focus:border-black text-sm px-3 py-2 outline-none transition-all sm:text-sm">
                </div>
                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="px-5 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
                        Filter
                    </button>
                    <a href="{{ url()->current() }}" class="px-5 py-2 bg-white border border-gray-300 text-black text-sm font-medium rounded-md hover:bg-gray-50 transition-colors shadow-sm text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- ================= BAGIAN 4: TABEL DATA ================= -->
        <form action="{{ route('transactions.bulk-delete') }}" method="POST" id="bulkDeleteForm">
            @csrf
            @method('DELETE')

            <!-- Tombol Hapus Banyak -->
            <div class="mb-4 hidden" id="bulkActionContainer">
                <button type="submit" onclick="return confirm('Yakin ingin menghapus data yang dipilih secara permanen?')" 
                        class="px-4 py-2 bg-white border border-gray-300 text-red-600 hover:bg-red-50 hover:border-red-200 text-sm font-medium rounded-md transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus Terpilih (<span id="selectedCount">0</span>)
                </button>
            </div>

            <!-- Tabel -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden mb-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <!-- Checkbox Header -->
                            <th scope="col" class="px-6 py-3 text-left w-10">
                                <input type="checkbox" id="checkAll" class="w-4 h-4 rounded border-gray-300 text-black shadow-sm focus:border-black focus:ring focus:ring-gray-200 focus:ring-opacity-50 cursor-pointer">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider w-16">No</th>
                            <th scope="col" class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Nama Kota</th>
                            <th scope="col" class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            <th scope="col" class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $index => $transaction)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="ids[]" value="{{ $transaction->id }}" class="w-4 h-4 transaction-checkbox rounded border-gray-300 text-black shadow-sm focus:border-black focus:ring focus:ring-gray-200 focus:ring-opacity-50 cursor-pointer">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $transactions->firstItem() + $index }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-black">{{ $transaction->city->nama_kota ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ \Carbon\Carbon::parse($transaction->tanggal)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-semibold">{{ number_format($transaction->jumlah, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right flex justify-end gap-4 items-center">
                                
                                <!-- Icon Edit (Abu-abu ke Hitam) -->
                                <a href="{{ route('transactions.edit', $transaction->id) }}" class="text-gray-400 hover:text-black transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>

                                <!-- Icon Hapus (Abu-abu ke Merah untuk alert) -->
                                <button type="button" onclick="if(confirm('Hapus riwayat data ini?')) { document.getElementById('delete-form-{{ $transaction->id }}').submit(); }" class="text-gray-400 hover:text-red-600 transition-colors" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">Tidak ada data ditemukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <!-- FORM HAPUS SATUAN -->
        @if(isset($transactions) && $transactions->count() > 0)
            @foreach($transactions as $transaction)
                <form id="delete-form-{{ $transaction->id }}" action="{{ route('transactions.destroy', $transaction->id) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif

        <!-- Pagination -->
        <div class="mb-10">
            {{ $transactions->links() ?? '' }}
        </div>

    </div>

    <!-- SCRIPT INTERAKTIF -->
    <script>
        // 1. Inisialisasi Select2
        $(document).ready(function() {
            $('.select2').select2({ placeholder: "Pilih kota..." });
        });

        document.addEventListener('DOMContentLoaded', function() {
            
            // 2. Drag & Drop File Upload
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file-upload');
            const fileNameDisplay = document.getElementById('file-name');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); });
                document.body.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); });
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.add('border-black', 'bg-gray-100');
                    dropZone.classList.remove('border-gray-300', 'bg-gray-50');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.remove('border-black', 'bg-gray-100');
                    dropZone.classList.add('border-gray-300', 'bg-gray-50');
                });
            });

            dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
            fileInput.addEventListener('change', function() { handleFiles(this.files); });

            function handleFiles(files) {
                if (files.length > 0) {
                    fileInput.files = files;
                    fileNameDisplay.textContent = files[0].name;
                    fileNameDisplay.classList.remove('hidden');
                }
            }

            // 3. Real-time Search & Filter
            let typingTimer;
            const searchInput = document.getElementById('search');
            const filterForm = document.getElementById('filterForm');

            if (searchInput && filterForm) {
                searchInput.addEventListener('keyup', function () {
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(() => filterForm.submit(), 500); // submit setelah 0.5 detik
                });
                searchInput.addEventListener('search', () => filterForm.submit());
            }

            const dateInputs = document.querySelectorAll('#filterForm input[type="date"]');
            dateInputs.forEach(input => {
                input.addEventListener('change', () => filterForm.submit());
            });
            
            // Fokuskan kembali kursor jika refresh karena pencarian
            if (searchInput && searchInput.value.length > 0) {
                searchInput.focus();
                let val = searchInput.value;
                searchInput.value = '';
                searchInput.value = val;
            }

            // 4. Checkbox Bulk Delete
            const checkAll = document.getElementById('checkAll');
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            const bulkActionContainer = document.getElementById('bulkActionContainer');
            const selectedCountSpan = document.getElementById('selectedCount');

            function toggleBulkAction() {
                const checkedCount = document.querySelectorAll('.transaction-checkbox:checked').length;
                if (checkedCount > 0) {
                    bulkActionContainer.classList.remove('hidden');
                    selectedCountSpan.textContent = checkedCount;
                } else {
                    bulkActionContainer.classList.add('hidden');
                }
                if(checkAll) checkAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
            }

            if(checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    toggleBulkAction();
                });
            }

            checkboxes.forEach(cb => cb.addEventListener('change', toggleBulkAction));
        });
    </script>
</body>
</html>