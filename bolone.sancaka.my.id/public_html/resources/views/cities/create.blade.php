<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Kota</title>
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-3xl mx-auto p-8 mt-10 bg-white border border-gray-200 rounded-lg shadow-sm">
        
        <!-- Header -->
        <div class="mb-8 border-b border-gray-200 pb-4 flex justify-between items-center">
            <h1 class="text-2xl font-semibold tracking-tight text-black">Tambah Data Kota Baru</h1>
            <!-- Perbaikan rute kembali ke halaman list kota -->
            <a href="{{ route('cities.index') }}" class="text-sm text-blue-600 hover:underline">Kembali ke Data Kota</a>
        </div>

        <!-- Menampilkan Error Validasi (jika ada form yang kosong) -->
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-md text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form Input Kota (Mengarah ke cities.store di CityController) -->
        <form action="{{ route('cities.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Input Nama Kota -->
                <div>
                    <label for="nama_kota" class="block text-sm font-medium text-gray-700 mb-1">Nama Kota / Wilayah</label>
                    <input type="text" name="nama_kota" id="nama_kota" placeholder="Contoh: Jakarta Pusat" required value="{{ old('nama_kota') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black outline-none transition-colors">
                </div>

                <!-- Input Keterangan -->
                <div>
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" rows="4" placeholder="Contoh: Area pengiriman VIP..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black outline-none transition-colors">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <!-- Tombol Submit -->
            <div class="mt-8 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors shadow-sm">
                    Simpan Kota
                </button>
            </div>
        </form>

    </div>

</body>
</html>