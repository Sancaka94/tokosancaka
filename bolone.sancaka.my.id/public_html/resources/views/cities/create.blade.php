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

    <!-- Ditambahkan mx-4 md:mx-auto, penyesuaian p-4 md:p-8, mt-4 md:mt-10 agar responsif di HP -->
    <div class="max-w-3xl mx-4 md:mx-auto p-4 md:p-8 mt-4 md:mt-10 bg-white border border-gray-200 rounded-lg shadow-sm">
        
        <!-- Header -->
        <!-- Ditambahkan relative untuk memposisikan absolute dropdown -->
        <div class="mb-8 border-b border-gray-200 pb-4 flex justify-between items-center relative">
            
            <!-- Sisi Kiri: Judul -->
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-black">Tambah Data Kota Baru</h1>
            
            <!-- Tombol Titik Tiga (Hanya Tampil di Mobile) -->
            <button id="mobileMenuBtn" class="md:hidden p-2 text-gray-600 hover:text-black focus:outline-none">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                </svg>
            </button>

            <!-- Sisi Kanan: Kumpulan Aksi (Dropdown di Mobile, Flex Normal di PC) -->
            <div id="actionMenu" class="hidden absolute top-12 right-0 z-50 w-56 p-4 flex-col gap-3 bg-white border border-gray-200 rounded-lg shadow-xl md:flex md:static md:w-auto md:p-0 md:flex-row md:items-center md:gap-6 md:bg-transparent md:border-none md:shadow-none">
                
                <!-- Link Kembali -->
                <a href="{{ route('cities.index') }}" class="w-full md:w-auto justify-center px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm inline-flex items-center gap-2">
                    &larr; Kembali ke Data Kota
                </a>

                <!-- Tombol Logout -->
                <form method="POST" action="{{ route('logout') }}" class="inline-block m-0 w-full md:w-auto">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('Apakah Anda yakin ingin keluar?');" 
                            class="w-full justify-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-red-50 hover:text-red-700 hover:border-red-300 transition-all shadow-sm inline-flex items-center gap-2">
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

            <!-- Tombol Aksi Bawah: Disesuaikan menjadi flex-col-reverse di HP agar tombol Batal di bawah -->
            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <a href="{{ route('cities.index') }}" class="w-full sm:w-auto text-center px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors shadow-sm">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto text-center px-5 py-2.5 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
                    Simpan Data
                </button>
            </div>

        </form>

    </div>

    <!-- SCRIPT TOGGLE MENU MOBILE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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