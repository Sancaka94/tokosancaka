<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kota</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-6xl mx-auto p-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-semibold tracking-tight text-black">Data Kota</h1>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-black transition-colors">
                &larr; Kembali ke Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-white border border-black text-black text-sm rounded-md shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form Upload Card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-sm font-semibold text-black mb-4">Upload File CSV</h2>
            <form action="{{ route('cities.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-4">
                @csrf
                <input type="file" name="file" required accept=".csv"
                    class="block w-full max-w-sm text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-black hover:file:bg-gray-200 transition-colors">
                
                <!-- LOG LOG - Tombol Hitam Solid NextJS -->
                <button type="submit" class="px-5 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm">
                    Upload & Proses
                </button>
            </form>
            <p class="text-xs text-gray-400 mt-2">Format CSV harus memiliki urutan kolom: [Nama Kota], [Keterangan]</p>
        </div>

        <!-- Tabel Data -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 w-16">No</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Nama Kota</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Keterangan</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500 w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($cities as $index => $city)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 font-medium text-black">{{ $city->nama_kota }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $city->keterangan }}</td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('cities.destroy', $city->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-black font-medium transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                Belum ada data. Silakan upload file CSV di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>