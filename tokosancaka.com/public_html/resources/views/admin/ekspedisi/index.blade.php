@extends('layouts.admin')

@section('title', 'Manajemen Master Ekspedisi')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative flex justify-between items-center">
        <span><i class="fas fa-check-circle me-2"></i> {{ session('success') }}</span>
        <button @click="show = false" class="text-green-700 hover:text-green-900"><i class="fas fa-times"></i></button>
    </div>
    @endif

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Master Ekspedisi & Diskon</h2>
            <p class="text-sm text-gray-500">Atur nama kurir, keyword deteksi, dan persentase profit per layanan.</p>
        </div>
        <button onclick="openModal('modalCreate')" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition flex items-center gap-2">
            <i class="fas fa-plus-circle"></i> Tambah Ekspedisi
        </button>
    </div>

    {{-- TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-center w-12">No</th>
                        <th class="px-4 py-3 w-20">Logo</th>
                        <th class="px-4 py-3">Nama Ekspedisi</th>
                        <th class="px-4 py-3">Keyword Sistem</th>
                        <th class="px-4 py-3 w-1/3">Aturan Diskon (JSON)</th>
                        <th class="px-4 py-3 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ekspedisi as $index => $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-center font-bold text-gray-500">{{ $index + 1 }}</td>
                        
                        {{-- LOGO --}}
                        <td class="px-4 py-3">
                            @if($item->logo_path)
                                <img src="{{ asset($item->logo_path) }}" alt="Logo" class="h-8 w-auto object-contain">
                            @else
                                <span class="text-xs text-gray-400 italic">No Logo</span>
                            @endif
                        </td>

                        {{-- NAMA --}}
                        <td class="px-4 py-3 font-bold text-gray-800">
                            {{ $item->nama_ekspedisi }}
                        </td>

                        {{-- KEYWORD --}}
                        <td class="px-4 py-3">
                            <span class="bg-blue-100 text-blue-800 text-xs font-mono px-2 py-1 rounded border border-blue-200">
                                {{ $item->keyword ?? '-' }}
                            </span>
                        </td>

                        {{-- DISKON RULES (Preview) --}}
                        <td class="px-4 py-3">
                            <code class="text-[10px] text-gray-600 bg-gray-100 px-2 py-1 rounded block truncate max-w-xs" title="{{ $item->diskon_rules }}">
                                {{ $item->diskon_rules ?? '{}' }}
                            </code>
                        </td>

                        {{-- AKSI --}}
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick='editData(@json($item))' class="bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1.5 rounded text-xs transition shadow-sm" title="Edit Data">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <form action="{{ route('admin.ekspedisi.destroy', $item->id_ekspedisi) }}" method="POST" onsubmit="return confirm('Yakin hapus ekspedisi ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 rounded text-xs transition shadow-sm" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                            Belum ada data ekspedisi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAL CREATE (TAMBAH) --}}
{{-- ================================================================= --}}
<div id="modalCreate" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" onclick="closeModal('modalCreate')"></div>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form action="{{ route('admin.ekspedisi.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="bg-white px-6 py-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Tambah Ekspedisi Baru</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Ekspedisi</label>
                            <input type="text" name="nama_ekspedisi" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: JNE EXPRESS" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Keyword Sistem (Huruf Kecil)</label>
                            <input type="text" name="keyword" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm font-mono" placeholder="Contoh: jne" required>
                            <p class="text-[10px] text-gray-500 mt-1">* Kata kunci untuk mendeteksi kurir dari string Resi/Invoice.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Aturan Diskon (Format JSON)</label>
                            <textarea name="diskon_rules" rows="5" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm font-mono" placeholder='{"reg": 0.15, "yes": 0.10, "default": 0.15}' required></textarea>
                            <p class="text-[10px] text-gray-500 mt-1">
                                * Format: <code>"nama_layanan": desimal</code>. Gunakan titik untuk koma. <br>
                                * Contoh: <code>0.15</code> artinya Diskon 15%.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Upload Logo (Opsional)</label>
                            <input type="file" name="logo" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">Simpan</button>
                    <button type="button" onclick="closeModal('modalCreate')" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAL EDIT --}}
{{-- ================================================================= --}}
<div id="modalEdit" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" onclick="closeModal('modalEdit')"></div>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form id="formEdit" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="bg-white px-6 py-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Edit Data Ekspedisi</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Ekspedisi</label>
                            <input type="text" id="edit_nama" name="nama_ekspedisi" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Keyword Sistem</label>
                            <input type="text" id="edit_keyword" name="keyword" class="w-full border-gray-300 rounded-lg shadow-sm text-sm font-mono" required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Aturan Diskon (JSON)</label>
                            <textarea id="edit_rules" name="diskon_rules" rows="6" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-mono bg-gray-50" required></textarea>
                            <p class="text-[10px] text-gray-500 mt-1">
                                <strong>Tips:</strong> Pastikan format JSON valid (pakai tanda kutip dua untuk teks).
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ganti Logo (Biarkan kosong jika tetap)</label>
                            <input type="file" name="logo" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-2">
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">Update Data</button>
                    <button type="button" onclick="closeModal('modalEdit')" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function editData(data) {
        document.getElementById('edit_nama').value = data.nama_ekspedisi;
        document.getElementById('edit_keyword').value = data.keyword;
        
        // Format JSON agar rapi saat diedit
        try {
            let jsonObj = JSON.parse(data.diskon_rules);
            document.getElementById('edit_rules').value = JSON.stringify(jsonObj, null, 4);
        } catch (e) {
            document.getElementById('edit_rules').value = data.diskon_rules;
        }

        // Set URL Action Form
        let url = "{{ route('admin.ekspedisi.update', ':id') }}";
        url = url.replace(':id', data.id_ekspedisi);
        document.getElementById('formEdit').action = url;

        openModal('modalEdit');
    }
</script>
@endpush