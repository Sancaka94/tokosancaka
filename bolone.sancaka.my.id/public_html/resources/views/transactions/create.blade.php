<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Transaksi Harian</title>
    
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery & Select2 (Untuk Dropdown Pencarian) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Modifikasi sedikit Select2 agar cocok dengan Tailwind */
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

    <div class="max-w-3xl mx-auto p-8 mt-10 bg-white border border-gray-200 rounded-lg shadow-sm">
        
        <!-- Header -->
        <div class="mb-8 border-b border-gray-200 pb-4 flex justify-between items-center">
            <h1 class="text-2xl font-semibold tracking-tight text-black">Input Data Transaksi Kota</h1>
            <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline">Kembali ke Dashboard</a>
        </div>

        <!-- Alert Sukses -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form Input -->
        <!-- Pastikan action mengarah ke route 'transactions.store' -->
        <form action="{{ route('transactions.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Dropdown Pilih Kota dengan Fitur Search -->
                <div>
                    <label for="city_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Kota / Wilayah</label>
                    <select name="city_id" id="city_id" class="w-full select2" required>
                        <option value="" disabled selected>Ketik untuk mencari kota...</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->nama_kota }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Input Tanggal (Otomatis terisi tanggal hari ini) -->
                <div>
                    <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Data Masuk</label>
                    <input type="date" name="tanggal" id="tanggal" required value="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black outline-none transition-colors">
                </div>

                <!-- Input Jumlah Angka -->
                <div>
                    <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">Jumlah Data (Masukan Angka)</label>
                    <input type="number" name="jumlah" id="jumlah" placeholder="Contoh: 130" required min="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black outline-none transition-colors">
                </div>
            </div>

            <!-- Tombol Submit -->
            <div class="mt-8 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm">
                    Simpan Data Transaksi
                </button>
            </div>
        </form>

    </div>

    <!-- Script inisialisasi Library Select2 untuk fitur Pencarian Dropdown -->
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Ketik untuk mencari kota...",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</body>
</html>