<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Transaksi</title>
    
    <!-- Tailwind CDN dengan plugin forms -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    
    <!-- jQuery & Select2 untuk Dropdown -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Custom Select2 agar match dengan Tailwind forms */
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

    <!-- Ditambahkan mx-4 md:mx-auto, penyesuaian p-4 md:p-8 dan mt-4 md:mt-10 agar responsif di layar kecil -->
    <div class="max-w-3xl mx-4 md:mx-auto p-4 md:p-8 mt-4 md:mt-10 bg-white border border-gray-200 rounded-lg shadow-sm">
        
        <!-- Header -->
        <!-- Ditambahkan relative untuk dropdown absolute di mobile -->
        <div class="mb-8 border-b border-gray-200 pb-4 flex justify-between items-center relative">
            
            <!-- Judul -->
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-black">Edit Data Transaksi</h1>
            
            <!-- Tombol Titik Tiga (Hanya Tampil di Mobile) -->
            <button id="mobileMenuBtn" class="md:hidden p-2 text-gray-600 hover:text-black focus:outline-none">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                </svg>
            </button>

            <!-- Menu Aksi (Dropdown di Mobile, Normal di PC) -->
            <div id="actionMenu" class="hidden absolute top-12 right-0 z-50 w-48 p-4 flex-col gap-3 bg-white border border-gray-200 rounded-lg shadow-xl md:flex md:static md:w-auto md:p-0 md:flex-row md:items-center md:gap-4 md:bg-transparent md:border-none md:shadow-none">
                <!-- Tombol Kembali (Hitam Next.js Style) -->
                <!-- Catatan: Mengarah kembali ke 'transactions.create' karena di sanalah tabel master Anda berada -->
                <a href="{{ route('transactions.create') }}" class="w-full md:w-auto justify-center px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm inline-flex items-center gap-2">
                    &larr; Kembali
                </a>
            </div>
        </div>

        <!-- Menampilkan Error Validasi -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-gray-50 border border-gray-200 text-gray-700 rounded-md text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form Edit -->
        <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT') <!-- Method PUT wajib ada untuk proses Update data di Laravel -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Dropdown Kota (Full Width) -->
                <div class="md:col-span-2">
                    <label for="city_id" class="block text-sm font-medium text-gray-700 mb-1">Kota / Wilayah <span class="text-black font-bold">*</span></label>
                    <select name="city_id" id="city_id" class="w-full select2" required>
                        <option value="" disabled>Pilih kota...</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ $transaction->city_id == $city->id ? 'selected' : '' }}>
                                {{ $city->nama_kota }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Input Tanggal -->
                <div>
                    <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Data Masuk <span class="text-black font-bold">*</span></label>
                    <!-- Formatting Value agar terbaca oleh input type="date" -->
                    <input type="date" name="tanggal" id="tanggal" required 
                           value="{{ \Carbon\Carbon::parse($transaction->tanggal)->format('Y-m-d') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black outline-none transition-colors sm:text-sm">
                </div>

                <!-- Input Jumlah -->
                <div>
                    <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Angka) <span class="text-black font-bold">*</span></label>
                    <input type="number" name="jumlah" id="jumlah" required min="1" 
                           value="{{ $transaction->jumlah }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black outline-none transition-colors sm:text-sm">
                </div>
            </div>

            <!-- Tombol Aksi Bawah: Diubah menjadi flex-col-reverse agar Batal di bawah saat di HP -->
            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-6 border-t border-gray-100 mt-8">
                <!-- Tombol Batal -->
                <a href="{{ route('transactions.create') }}" class="w-full sm:w-auto text-center px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 hover:text-black transition-colors shadow-sm">
                    Batal
                </a>
                <!-- Tombol Update (Hitam Next.js Style) -->
                <button type="submit" class="w-full sm:w-auto text-center px-6 py-2.5 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
                    Update Data
                </button>
            </div>

        </form>
    </div>

    <!-- Script Inisialisasi Dropdown Select2 & Menu Mobile -->
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            $('.select2').select2({ 
                width: '100%',
                placeholder: "Ketik untuk mencari..."
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Logika Dropdown Titik Tiga
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
        });
    </script>
</body>
</html>