<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Kota</title>
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-3xl mx-auto p-8 mt-10 bg-white border border-gray-200 rounded-lg shadow-sm">
        
        <!-- Header -->
        <div class="mb-8 border-b border-gray-200 pb-4 flex justify-between items-center">
            
            <!-- Sisi Kiri: Judul -->
            <h1 class="text-2xl font-semibold tracking-tight text-black">Tambah Data Kota Baru</h1>
            
            <!-- Sisi Kanan: Kumpulan Aksi -->
            <div class="flex items-center gap-6">
                
                <!-- Link Kembali -->
                <a href="{{ route('cities.index') }}" class="px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm inline-flex items-center gap-2">
                    &larr; Kembali ke Data Kota
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

        <!-- Menampilkan Error Validasi (jika ada form yang kosong/salah) -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-md text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- FORM INPUT DATA -->
        <!-- Pastikan action mengarah ke route store untuk menyimpan data -->
        <form action="{{ route('cities.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Input Nama Kota -->
            <div>
                <label for="nama_kota" class="block text-sm font-medium text-gray-700 mb-1">Nama Kota <span class="text-red-500">*</span></label>
                <input type="text" 
                       name="nama_kota" 
                       id="nama_kota" 
                       value="{{ old('nama_kota') }}" 
                       placeholder="Masukkan nama kota..." 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm transition-colors" 
                       required>
            </div>

            <!-- Input Keterangan -->
            <div>
                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <textarea name="keterangan" 
                          id="keterangan" 
                          rows="4" 
                          placeholder="Masukkan keterangan (opsional)..." 
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm transition-colors">{{ old('keterangan') }}</textarea>
            </div>

            <!-- Tombol Aksi Bawah -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <a href="{{ route('cities.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors shadow-sm">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
                    Simpan Data
                </button>
            </div>

        </form>

    </div>

</body>
</html>