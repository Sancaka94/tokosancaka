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
            
            <!-- Sisi Kanan: Kumpulan Aksi (Link & Logout dibungkus dalam satu div) -->
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

    <!-- Form untuk Bulk Delete -->
    <form action="{{ route('cities.bulk-delete') }}" method="POST" id="bulkDeleteForm">
        @csrf
        @method('DELETE')

        <!-- Tombol Hapus Banyak (Sembunyi secara default, muncul jika ada yang dicentang) -->
        <div class="mb-4 hidden" id="bulkActionContainer">
            <button type="submit" onclick="return confirm('Yakin ingin menghapus semua data yang dipilih?')" 
                    class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Hapus yang Dipilih (<span id="selectedCount">0</span>)
            </button>
        </div>

        <!-- Struktur Tabel -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <!-- HEADER CHECKBOX ALL -->
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                            <input type="checkbox" id="checkAll" class="w-4 h-4 rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 cursor-pointer">
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kota</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    
                    @forelse($cities as $index => $city)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <!-- CHECKBOX PER BARIS -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="ids[]" value="{{ $city->id }}" class="w-4 h-4 city-checkbox rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 cursor-pointer">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $cities->firstItem() + $index }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $city->nama_kota }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $city->keterangan }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            
                            <!-- Pindahkan tombol Aksi Edit/Delete bawaan Anda ke sini -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('cities.edit', $city->id) }}" class="text-green-500 hover:text-green-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <!-- Ini tombol hapus satuan (opsional jika ingin tetap ada) -->
                                <button type="submit" form="delete-form-{{ $city->id }}" class="text-red-500 hover:text-red-700" onclick="return confirm('Hapus data ini?')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data kota.</td>
                    </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </form>

    <!-- Pagination bawaan Laravel (biarkan jika sudah ada) -->
    <div class="mt-4">
        {{ $cities->links() }}
    </div>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.city-checkbox');
        const bulkActionContainer = document.getElementById('bulkActionContainer');
        const selectedCountSpan = document.getElementById('selectedCount');

        // Fungsi mengecek apakah tombol "Hapus yang Dipilih" perlu ditampilkan
        function toggleBulkAction() {
            const checkedCount = document.querySelectorAll('.city-checkbox:checked').length;
            
            if (checkedCount > 0) {
                bulkActionContainer.classList.remove('hidden');
                selectedCountSpan.textContent = checkedCount;
            } else {
                bulkActionContainer.classList.add('hidden');
            }

            // Atur status Check All jika semua dicentang satu per satu
            checkAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
        }

        // Event listener saat Check All di-klik
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            toggleBulkAction();
        });

        // Event listener saat tiap checkbox baris di-klik
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', toggleBulkAction);
        });
    });
</script>

</body>
</html>