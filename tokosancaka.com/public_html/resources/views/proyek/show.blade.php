@extends('layouts.admin')

@section('content')
<!-- LOG LOG -->
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Breadcrumb & Detail Proyek -->
    <div class="mb-6">
        <a href="{{ route('proyek.index') }}" class="text-sm text-gray-500 hover:text-blue-600 mb-3 inline-flex items-center gap-1">
            &larr; Kembali ke Daftar Proyek
        </a>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5">
            <h1 class="text-xl font-bold text-gray-900 uppercase">{{ $proyek->nama_proyek }}</h1>
            <div class="mt-2 flex flex-col sm:flex-row gap-4 text-sm text-gray-600">
                <div class="flex items-center gap-1.5"><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg> {{ $proyek->lokasi_proyek }}</div>
                <div class="flex items-center gap-1.5"><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg> {{ $proyek->nomor_hp }}</div>
            </div>
        </div>
    </div>

   <!-- Header Tabel Action -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
        <h2 class="text-lg font-bold text-gray-900">Rincian Pekerjaan</h2>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Form Upload Excel -->
            <form action="{{ route('rab.import', $proyek->id) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 bg-white border border-gray-200 rounded-md px-2 py-1 shadow-sm">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                <button type="submit" class="bg-black hover:bg-gray-800 text-white text-xs font-medium py-1.5 px-3 rounded whitespace-nowrap">Upload Excel</button>
            </form>

            <!-- LOG LOG: TOMBOL SHARE LINK BARU -->
            <button onclick="copyShareLink('{{ route('proyek.public.share', $proyek->id) }}', this)" class="bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 shadow-sm text-sm font-medium py-2 px-4 rounded-md transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                <span>Copy Link Public</span>
            </button>

            <!-- LOG LOG: Form Export PDF berdasarkan Kategori -->
            <form action="{{ route('rab.pdf', $proyek->id) }}" method="GET" class="flex items-center gap-2 m-0">
                <select name="kategori" class="bg-white border border-gray-300 text-gray-700 text-sm py-2 px-3 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:border-black cursor-pointer">
                    <option value="">-- Cetak Semua (PDF) --</option>
                    @php
                        // Mengambil otomatis daftar kategori yang ada di RAB ini
                        $listKategori = collect($items)->pluck('kategori')->unique()->filter();
                    @endphp
                    @foreach($listKategori as $kat)
                        <option value="{{ $kat }}">{{ $kat }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm text-sm font-medium py-2 px-4 rounded-md flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    PDF
                </button>
            </form>

            <!-- Tombol Tambah Item -->
            <a href="{{ route('rab.create', ['proyek_id' => $proyek->id]) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-md shadow-sm">
                + Tambah Item
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-auto max-h-[60vh]">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-100 border-b-2 border-gray-200 text-gray-900 font-bold text-xs tracking-wider sticky top-0 z-30 shadow-sm">
                <tr>
                    <th class="px-4 py-3 text-center w-12 border-r border-gray-200 whitespace-nowrap">No.</th>
                    <th class="px-4 py-3 border-r border-gray-200 whitespace-nowrap">URAIAN PEKERJAAN</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-24 whitespace-nowrap">VOL</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-24 whitespace-nowrap">SAT</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-40 whitespace-nowrap">HARGA SATUAN</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-40 whitespace-nowrap">TOTAL</th>
                    <th class="px-4 py-3 text-center w-36 text-gray-500 whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @php
                    $grandTotal = 0;
                    $romanNumerals = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
                    $categoryIndex = 0;
                    $groupedItems = collect($items)->groupBy('kategori');
                @endphp

                @forelse ($groupedItems as $kategori => $kategoriItems)
                    @php
                        $subTotal = $kategoriItems->sum('total');
                        $grandTotal += $subTotal;
                        $roman = $romanNumerals[$categoryIndex] ?? ($categoryIndex + 1);
                    @endphp

                    <!-- Category Title Row -->
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 text-center font-bold text-gray-900 border-r border-gray-200 align-top">{{ $roman }}</td>
                        <td colspan="6" class="px-4 py-3 font-bold text-gray-900 uppercase">
                            {{ $kategori ?: 'PEKERJAAN UMUM' }}
                        </td>
                    </tr>

                    <!-- Items Loop -->
                    @foreach ($kategoriItems as $index => $item)
                        <!-- Tambahkan ID, URL update, dan class target -->
                        <tr id="row-{{ $item->id }}" data-update-url="{{ route('rab.update', $item->id) }}" class="hover:bg-gray-50 transition-colors group">
                            <td class="px-4 py-2 text-center text-gray-500 border-r border-gray-200 align-top">{{ $index + 1 }}</td>

                            <!-- Simpan raw data agar mudah dijadikan input value -->
                            <td class="cell-uraian px-4 py-2 text-gray-800 border-r border-gray-200 whitespace-normal break-words min-w-[250px] align-top" data-raw="{{ $item->uraian_pekerjaan }}">
                                {{ $item->uraian_pekerjaan }}
                            </td>

                            <td class="cell-volume px-4 py-2 text-right text-gray-800 border-r border-gray-200 align-top whitespace-nowrap" data-raw="{{ $item->volume }}">
                                {{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }}
                            </td>

                            <td class="cell-satuan px-4 py-2 text-center text-gray-500 border-r border-gray-200 align-top whitespace-nowrap" data-raw="{{ $item->satuan }}">
                                {{ $item->satuan }}
                            </td>

                            <td class="cell-harga px-4 py-2 text-right text-gray-800 border-r border-gray-200 align-top whitespace-nowrap" data-raw="{{ $item->harga_satuan }}">
                                {{ number_format($item->harga_satuan, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-2 text-right text-gray-900 font-medium border-r border-gray-200 align-top whitespace-nowrap">
                                {{ number_format($item->total, 0, ',', '.') }}
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="action-cell px-4 py-2 text-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity align-top whitespace-nowrap">

                                <!-- Mode Normal (Edit & Hapus) -->
                                <button type="button" onclick="editRow({{ $item->id }})" class="btn-edit text-blue-600 hover:text-blue-800 font-medium text-xs">Edit</button>

                                <form action="{{ route('rab.destroy', $item->id) }}" method="POST" class="inline-block form-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs" onclick="return confirm('Hapus item ini?')">Hapus</button>
                                </form>

                                <!-- Mode Edit (Save & Batal) -->
                                <button type="button" onclick="saveRow({{ $item->id }})" class="btn-save hidden text-white bg-green-600 hover:bg-green-700 font-medium text-xs px-2 py-1 rounded shadow-sm">Save</button>
                                <button type="button" onclick="cancelEdit({{ $item->id }})" class="btn-cancel hidden text-gray-500 hover:text-gray-700 font-medium text-xs">Batal</button>

                            </td>
                        </tr>
                    @endforeach

                    <!-- Sub Total Row -->
                    <tr class="sticky bottom-0 z-10 bg-white outline outline-1 outline-gray-200 shadow-[0_-2px_4px_rgba(0,0,0,0.02)]">
                        <td class="px-4 py-3 border-r border-gray-200 bg-white"></td>
                        <td colspan="4" class="px-4 py-3 text-center font-bold text-gray-900 border-r border-gray-200 bg-white">
                            Sub Total {{ $roman }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 border-r border-gray-200 bg-gray-50">
                            {{ number_format($subTotal, 0, ',', '.') }}
                        </td>
                        <td class="bg-white"></td>
                    </tr>

                    @php $categoryIndex++; @endphp
                @empty
                    <!-- Empty State -->
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500 bg-white">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="font-medium text-gray-600">Belum ada data RAB.</span>
                                <span class="text-xs mt-1">Silakan tambah item secara manual atau upload melalui file Excel.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <!-- Grand Total -->
            @if(count($items) > 0)
            <tfoot class="sticky bottom-0 z-20 bg-gray-100 outline outline-1 outline-gray-300 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
                <tr>
                    <td class="px-4 py-4 border-r border-gray-200"></td>
                    <th colspan="4" class="px-4 py-4 text-center font-bold text-gray-900 border-r border-gray-200 uppercase text-sm tracking-wide">
                        TOTAL KESELURUHAN
                    </th>
                    <th class="px-4 py-4 text-right font-bold text-gray-900 border-r border-gray-200 text-sm">
                        {{ number_format($grandTotal, 0, ',', '.') }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
            @endif
        </table>

        <!-- LOG LOG -->
    </div>

    <!-- Form Catatan Tambahan -->
    <div class="mt-8 bg-white border border-gray-200 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-3">Catatan Tambahan</h3>
        <form action="{{ route('proyek.catatan', $proyek->id) }}" method="POST">
            @csrf
            <textarea id="catatan-editor" name="catatan" rows="10" placeholder="Tuliskan catatan khusus untuk proyek ini (Opsional)..."
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-black focus:border-black mb-3">{{ $proyek->catatan }}</textarea>
            <div class="flex justify-end">
                <button type="submit" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-6 rounded-md transition-colors">
                    Simpan Catatan
                </button>
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
    function copyShareLink(url, btn) {
        navigator.clipboard.writeText(url).then(function() {
            let span = btn.querySelector('span');
            let originalText = span.innerText;

            span.innerText = 'Tersalin!';
            btn.classList.replace('text-indigo-700', 'text-green-700');
            btn.classList.replace('bg-indigo-50', 'bg-green-50');
            btn.classList.replace('border-indigo-200', 'border-green-200');

            setTimeout(() => {
                span.innerText = originalText;
                btn.classList.replace('text-green-700', 'text-indigo-700');
                btn.classList.replace('bg-green-50', 'bg-indigo-50');
                btn.classList.replace('border-green-200', 'border-indigo-200');
            }, 2000);
        }).catch(function(err) {
            alert('Gagal menyalin link: ' + err);
        });
    }

    // --- SCRIPT UNTUK INLINE EDITING TETAP SAMA SEPERTI SEBELUMNYA ---
    function editRow(id) {
        let row = document.getElementById('row-' + id);
        let fields = ['uraian', 'volume', 'satuan', 'harga'];

        let actionCell = row.querySelector('.action-cell');
        actionCell.classList.remove('opacity-0', 'group-hover:opacity-100');

        fields.forEach(field => {
            let td = row.querySelector('.cell-' + field);
            let rawValue = td.getAttribute('data-raw');

            if (!td.hasAttribute('data-original-html')) {
                td.setAttribute('data-original-html', td.innerHTML);
            }

            if (field === 'uraian') {
                td.innerHTML = `<textarea class="input-${field} w-full border-2 border-blue-400 focus:border-blue-600 rounded px-2 py-1 text-sm outline-none shadow-sm" rows="2">${rawValue}</textarea>`;
            } else {
                let inputType = (field === 'volume' || field === 'harga') ? 'number' : 'text';
                let step = field === 'volume' ? 'any' : '1';
                td.innerHTML = `<input type="${inputType}" step="${step}" class="input-${field} w-full border-2 border-blue-400 focus:border-blue-600 rounded px-2 py-1 text-sm outline-none shadow-sm" value="${rawValue}">`;
            }
        });

        row.querySelector('.btn-edit').classList.add('hidden');
        row.querySelector('.form-delete').classList.add('hidden');
        row.querySelector('.btn-save').classList.remove('hidden');
        row.querySelector('.btn-cancel').classList.remove('hidden');
    }

    function cancelEdit(id) {
        let row = document.getElementById('row-' + id);
        let fields = ['uraian', 'volume', 'satuan', 'harga'];

        let actionCell = row.querySelector('.action-cell');
        actionCell.classList.add('opacity-0', 'group-hover:opacity-100');

        fields.forEach(field => {
            let td = row.querySelector('.cell-' + field);
            td.innerHTML = td.getAttribute('data-original-html');
        });

        row.querySelector('.btn-edit').classList.remove('hidden');
        row.querySelector('.form-delete').classList.remove('hidden');
        row.querySelector('.btn-save').classList.add('hidden');
        row.querySelector('.btn-cancel').classList.add('hidden');
    }

    async function saveRow(id) {
        let row = document.getElementById('row-' + id);
        let btnSave = row.querySelector('.btn-save');
        let originalBtnText = btnSave.innerText;

        btnSave.innerText = 'Saving...';
        btnSave.disabled = true;

        let uraian = row.querySelector('.input-uraian').value;
        let volume = row.querySelector('.input-volume').value;
        let satuan = row.querySelector('.input-satuan').value;
        let harga = row.querySelector('.input-harga').value;

        let formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('_method', 'PUT');
        formData.append('uraian_pekerjaan', uraian);
        formData.append('volume', volume);
        formData.append('satuan', satuan);
        formData.append('harga_satuan', harga);

        let updateUrl = row.getAttribute('data-update-url');

        try {
            let response = await fetch(updateUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                alert('Berhasil! Data telah diupdate.');
                window.location.reload();
            } else {
                alert('Gagal menyimpan data! Pastikan kolom tidak kosong.');
                btnSave.innerText = originalBtnText;
                btnSave.disabled = false;
            }
        } catch (error) {
            alert('Terjadi kesalahan pada server.');
            btnSave.innerText = originalBtnText;
            btnSave.disabled = false;
        }
    }
</script>

<!-- INI ADALAH SCRIPT TINYMCE YANG BARU DENGAN API KEY ANDA -->
<script src="https://cdn.tiny.cloud/1/hsfvd81ihieoadc6tlyol8xucnq3i1n2vzuzfr1948kqqcx5/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
  tinymce.init({
    selector: '#catatan-editor', // Pastikan menggunakan ID ini agar menargetkan textarea yang benar
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
    height: 400,
    setup: function (editor) {
        editor.on('change', function () {
            editor.save(); // Wajib agar isi ketikan tersimpan ke textarea asli saat form di-submit
        });
    }
  });
</script>
@endpush

@endsection
