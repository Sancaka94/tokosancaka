{{-- resources/views/customer/kontak/index.blade.php --}}

@extends('layouts.customer')

@section('title', 'Buku Alamat Lengkap')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- FontAwesome & jQuery UI --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
    /* Styling Autocomplete agar rapi */
    .ui-autocomplete {
        z-index: 9999 !important;
        max-height: 250px;
        overflow-y: auto;
        overflow-x: hidden;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        font-size: 0.875rem;
    }
    .ui-menu-item-wrapper {
        padding: 0.5rem 1rem;
        cursor: pointer;
    }
    .ui-menu-item-wrapper:hover, .ui-state-active {
        background-color: #eff6ff !important; /* blue-50 */
        color: #1e40af !important; /* blue-800 */
        border: none !important;
    }
    /* Input Readonly terlihat abu-abu */
    .form-control-readonly {
        background-color: #f3f4f6;
        cursor: not-allowed;
        color: #6b7280;
    }
</style>
@endpush

@section('content')
<div class="bg-white p-6 rounded-xl shadow-lg min-h-screen">

    {{-- HEADER: PENCARIAN & TOMBOL TAMBAH --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <form action="{{ route('customer.kontak.index') }}" method="GET" class="relative w-full md:w-1/2">
            @if(request('filter'))
                <input type="hidden" name="filter" value="{{ request('filter') }}">
            @endif
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm" placeholder="Cari Nama, No HP, atau Kota..." value="{{ request('search') }}">
            <div class="absolute top-0 left-0 inline-flex items-center p-3 h-full text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </form>
        
        <button type="button" id="btnTambahKontak" class="w-full md:w-auto bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition shadow-md flex items-center justify-center gap-2">
            <i class="fas fa-plus-circle"></i> Tambah Kontak Baru
        </button>
    </div>

    {{-- NAVIGASI TABS (FILTER) --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            @php $f = request('filter', 'Semua'); @endphp
            <a href="{{ route('customer.kontak.index', ['filter' => 'Semua', 'search' => request('search')]) }}" 
               class="{{ $f == 'Semua' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
               Semua Kontak
            </a>
            <a href="{{ route('customer.kontak.index', ['filter' => 'Penerima', 'search' => request('search')]) }}" 
               class="{{ $f == 'Penerima' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
               <i class="fas fa-box-open mr-1"></i> Penerima
            </a>
            <a href="{{ route('customer.kontak.index', ['filter' => 'Pengirim', 'search' => request('search')]) }}" 
               class="{{ $f == 'Pengirim' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
               <i class="fas fa-paper-plane mr-1"></i> Pengirim
            </a>
        </nav>
    </div>

    {{-- ALERT --}}
    @if (session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm flex items-center">
            <i class="fas fa-check-circle text-xl mr-3"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    {{-- TABEL UTAMA --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama & HP</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Alamat</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kelurahan</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kecamatan</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kota/Kab</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Provinsi</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode Pos</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($kontaks as $kontak)
                <tr class="hover:bg-blue-50 transition duration-150">
                    {{-- Nama & HP --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">{{ $kontak->nama }}</div>
                        <div class="text-xs text-gray-500"><i class="fas fa-phone-alt text-gray-400 mr-1"></i>{{ $kontak->no_hp }}</div>
                    </td>
                    
                    {{-- Tipe --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($kontak->tipe == 'Pengirim')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Pengirim</span>
                        @elseif($kontak->tipe == 'Penerima')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Penerima</span>
                        @else
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Keduanya</span>
                        @endif
                    </td>

                    {{-- Alamat Lengkap --}}
                    <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $kontak->alamat }}">
                        {{ Str::limit($kontak->alamat, 30) }}
                    </td>

                    {{-- Detail Wilayah --}}
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $kontak->village ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $kontak->district ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $kontak->regency ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $kontak->province ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 font-mono">{{ $kontak->postal_code ?? '-' }}</td>

                    {{-- Aksi (Lihat, Edit, Hapus) --}}
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex justify-center space-x-2">
                            {{-- Tombol Lihat --}}
                            <button type="button" class="text-gray-500 hover:text-gray-800 btnViewKontak" 
                                    data-id="{{ $kontak->id }}" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </button>

                            {{-- Tombol Edit --}}
                            <button type="button" class="text-blue-600 hover:text-blue-900 btnEditKontak" 
                                    data-id="{{ $kontak->id }}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            {{-- Tombol Hapus --}}
                            <form action="{{ route('customer.kontak.destroy', $kontak->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kontak {{ $kontak->nama }}?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-10 text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-folder-open text-4xl text-gray-300 mb-2"></i>
                            <span>Tidak ada data kontak ditemukan.</span>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINASI --}}
    <div class="mt-4">
        {{ $kontaks->appends(request()->query())->links() }}
    </div>

    {{-- TABEL KHUSUS PENGIRIM (DI BAWAH) --}}
    @if(isset($pengirims) && $pengirims->isNotEmpty())
    <div class="mt-12 pt-8 border-t-2 border-gray-100">
        <div class="flex items-center mb-4 gap-3">
            <div class="p-2.5 bg-indigo-100 text-indigo-600 rounded-lg">
                <i class="fas fa-shipping-fast text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800">Data Pengirim (Profil Saya)</h3>
                <p class="text-sm text-gray-500">Daftar alamat yang disimpan sebagai pengirim.</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-indigo-100 shadow-sm">
            <table class="min-w-full divide-y divide-indigo-100">
                <thead class="bg-indigo-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase">Nama & HP</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase">Detail Alamat</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase">Kelurahan</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase">Kecamatan</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase">Kota/Kab</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase">Provinsi</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase">Kode Pos</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-indigo-800 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-indigo-100">
                    @foreach ($pengirims as $p)
                    <tr class="hover:bg-indigo-50 transition duration-150">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">{{ $p->nama }}</div>
                            <div class="text-xs text-gray-500">{{ $p->no_hp }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ Str::limit($p->alamat, 25) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $p->village ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $p->district ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $p->regency ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $p->province ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $p->postal_code ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-sm font-medium">
                            <div class="flex justify-center space-x-2">
                                <button type="button" class="text-gray-500 hover:text-gray-800 btnViewKontak" data-id="{{ $p->id }}"><i class="fas fa-eye"></i></button>
                                <button type="button" class="text-blue-600 hover:text-blue-900 btnEditKontak" data-id="{{ $p->id }}"><i class="fas fa-edit"></i></button>
                                <form action="{{ route('customer.kontak.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus data pengirim ini?');" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

{{-- MODAL TAMBAH/EDIT KONTAK --}}
<div id="kontakModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 z-50 hidden flex items-center justify-center overflow-auto backdrop-blur-sm transition-opacity">
    <div class="relative w-full max-w-2xl mx-auto my-6 p-4">
        <div class="bg-white rounded-xl shadow-2xl flex flex-col w-full outline-none focus:outline-none">
            
            {{-- Header --}}
            <div class="flex items-center justify-between p-5 border-b border-gray-100 rounded-t-xl bg-gray-50">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800"></h3>
                <button type="button" id="btnCloseHeader" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="relative p-6 flex-auto max-h-[75vh] overflow-y-auto">
                <form id="kontakForm" action="" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    
                    <div class="space-y-5">
                        {{-- Tipe & Identitas --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipe Kontak</label>
                                <select name="tipe" id="tipe" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="Penerima">Penerima</option>
                                    <option value="Pengirim">Pengirim</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="nama" name="nama" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor HP / WhatsApp <span class="text-red-500">*</span></label>
                            <input type="number" id="no_hp" name="no_hp" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        {{-- Search Wilayah --}}
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <label class="block text-sm font-bold text-blue-800 mb-1"><i class="fas fa-search-location mr-1"></i> Cari Wilayah (Kel/Kec/Kota)</label>
                            <input type="text" id="kontak_address_search" class="w-full px-3 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white placeholder-gray-400" placeholder="Ketik minimal 3 huruf..." autocomplete="off">
                            <p class="text-xs text-blue-600 mt-1">Pilih dari daftar yang muncul untuk mengisi data wilayah otomatis.</p>
                        </div>

                        {{-- Alamat Detail --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Detail Alamat (Jalan, RT/RW, No. Rumah) <span class="text-red-500">*</span></label>
                            <textarea id="alamat" name="alamat" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" rows="2" required placeholder="Contoh: Jl. Merdeka No. 123, RT 01/RW 02, Depan Masjid"></textarea>
                        </div>

                        {{-- Data Wilayah Readonly --}}
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Kelurahan</label>
                                <input type="text" name="village" id="village" class="form-control-readonly w-full px-2 py-1 rounded border border-gray-300 text-sm" readonly>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Kecamatan</label>
                                <input type="text" name="district" id="district" class="form-control-readonly w-full px-2 py-1 rounded border border-gray-300 text-sm" readonly>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Kabupaten/Kota</label>
                                <input type="text" name="regency" id="regency" class="form-control-readonly w-full px-2 py-1 rounded border border-gray-300 text-sm" readonly>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Provinsi</label>
                                <input type="text" name="province" id="province" class="form-control-readonly w-full px-2 py-1 rounded border border-gray-300 text-sm" readonly>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Kode Pos</label>
                                <input type="text" name="postal_code" id="postal_code" class="form-control-readonly w-full px-2 py-1 rounded border border-gray-300 text-sm" readonly>
                            </div>
                        </div>

                        {{-- Hidden Fields (Lat/Long/IDs) --}}
                        <input type="hidden" name="lat" id="lat">
                        <input type="hidden" name="lng" id="lng">
                        <input type="hidden" name="district_id" id="district_id">
                        <input type="hidden" name="subdistrict_id" id="subdistrict_id">
                    </div>
                </form>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end p-5 border-t border-gray-100 rounded-b-xl bg-gray-50 gap-3">
                <button type="button" id="btnBatalModal" class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">Batal</button>
                <button type="button" id="btnSimpanForm" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-lg transition transform hover:-translate-y-0.5">Simpan Data</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    
    // --- HELPERS ---
    function openModal(id) { $('#' + id).removeClass('hidden').addClass('flex'); $('body').addClass('overflow-hidden'); }
    function closeModal(id) { $('#' + id).addClass('hidden').removeClass('flex'); $('body').removeClass('overflow-hidden'); }
    function debounce(func, delay) { let timeout; return function(...args) { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), delay); }; }

    // --- AUTOCOMPLETE SEARCH ---
    function setupAddressSearch(inputId) {
        $(`#${inputId}`).autocomplete({
            source: debounce(async (request, response) => {
                if (request.term.length < 3) return response([]);
                try {
                    const res = await fetch(`{{ route('api.address.search') }}?q=${encodeURIComponent(request.term)}`);
                    const data = await res.json();
                    response(data.map(item => ({ label: item.text, value: item.text, data: item })));
                } catch (e) { response([]); }
            }, 300),
            minLength: 3,
            select: function(event, ui) {
                const item = ui.item.data;
                // Isi Form
                $('#village').val(item.village_name || item.village || '');
                $('#district').val(item.district_name || item.district || '');
                $('#regency').val(item.city_name || item.regency || '');
                $('#province').val(item.province_name || item.province || '');
                $('#postal_code').val(item.zip_code || item.postal_code || '');
                
                // Isi Hidden Fields
                $('#district_id').val(item.district_id || '');
                $('#subdistrict_id').val(item.subdistrict_id || '');
                $('#lat').val(item.lat || ''); // Jika ada dari API
                $('#lng').val(item.lon || ''); // Jika ada dari API

                event.preventDefault();
                $(this).val(ui.item.label);
                
                // Visual Feedback
                $(this).addClass('border-green-500 ring-1 ring-green-500');
            }
        });
    }
    setupAddressSearch('kontak_address_search');

    // --- BUTTON ACTIONS ---

    // 1. TAMBAH BARU
    $('#btnTambahKontak').on('click', function() {
        const form = $('#kontakForm');
        form[0].reset();
        form.attr('action', "{{ route('customer.kontak.store') }}");
        $('#formMethod').val('POST');
        $('#modalTitle').html('<i class="fas fa-user-plus mr-2 text-blue-600"></i>Tambah Kontak Baru');
        $('#tipe').val('Penerima'); // Default
        $('#kontak_address_search').removeClass('border-green-500 ring-1 ring-green-500');
        openModal('kontakModal');
    });

    // 2. EDIT KONTAK
    $(document).on('click', '.btnEditKontak', async function() {
        const id = $(this).data('id');
        const form = $('#kontakForm');
        form[0].reset();
        Swal.showLoading();

        try {
            const response = await fetch(`/customer/kontak/${id}/edit`);
            if (!response.ok) throw new Error('Data tidak ditemukan');
            const kontak = await response.json();
            Swal.close();

            // Isi Data
            $('#nama').val(kontak.nama);
            $('#no_hp').val(kontak.no_hp);
            $('#alamat').val(kontak.alamat);
            $('#tipe').val(kontak.tipe);
            
            // Isi Wilayah
            const fullAddr = [kontak.village, kontak.district, kontak.regency, kontak.province, kontak.postal_code].filter(Boolean).join(', ');
            $('#kontak_address_search').val(fullAddr);
            
            $('#province').val(kontak.province);
            $('#regency').val(kontak.regency);
            $('#district').val(kontak.district);
            $('#village').val(kontak.village);
            $('#postal_code').val(kontak.postal_code);
            
            // Isi Hidden
            $('#lat').val(kontak.lat);
            $('#lng').val(kontak.lng);
            $('#district_id').val(kontak.district_id);
            $('#subdistrict_id').val(kontak.subdistrict_id);

            // Setup Modal
            form.attr('action', `/customer/kontak/${kontak.id}`);
            $('#formMethod').val('PUT');
            $('#modalTitle').html('<i class="fas fa-edit mr-2 text-blue-600"></i>Edit Kontak');
            openModal('kontakModal');

        } catch (err) {
            Swal.fire('Error', 'Gagal memuat data kontak.', 'error');
        }
    });

    // 3. LIHAT DETAIL (VIEW)
    $(document).on('click', '.btnViewKontak', async function() {
        const id = $(this).data('id');
        Swal.showLoading();
        
        try {
            const response = await fetch(`/customer/kontak/${id}/edit`); // Reuse endpoint edit
            const d = await response.json();
            Swal.close();

            // Tampilkan Detail di SweetAlert dengan Tabel Rapi
            Swal.fire({
                title: `<span class="text-xl font-bold text-gray-800">Detail Kontak</span>`,
                html: `
                    <div class="text-left bg-gray-50 p-4 rounded-lg text-sm space-y-2 border border-gray-200">
                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                            <span class="font-bold text-gray-500">Nama:</span>
                            <span class="col-span-2 font-semibold text-gray-900">${d.nama}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                            <span class="font-bold text-gray-500">No. HP:</span>
                            <span class="col-span-2 font-mono text-blue-600">${d.no_hp}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                            <span class="font-bold text-gray-500">Tipe:</span>
                            <span class="col-span-2"><span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">${d.tipe}</span></span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                            <span class="font-bold text-gray-500">Alamat:</span>
                            <span class="col-span-2 text-gray-700">${d.alamat}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                            <span class="font-bold text-gray-500">Wilayah:</span>
                            <span class="col-span-2 text-gray-700">
                                ${d.village || '-'}, ${d.district || '-'},<br>
                                ${d.regency || '-'}, ${d.province || '-'}
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-b pb-2">
                            <span class="font-bold text-gray-500">Kode Pos:</span>
                            <span class="col-span-2 font-mono">${d.postal_code || '-'}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="font-bold text-gray-500">Koordinat:</span>
                            <span class="col-span-2 text-xs font-mono text-gray-500">
                                Lat: ${d.lat || '-'} <br> Lng: ${d.lng || '-'}
                            </span>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#3b82f6',
                width: '500px'
            });

        } catch (err) {
            Swal.fire('Error', 'Gagal memuat detail.', 'error');
        }
    });

    // 4. SIMPAN FORM
    $('#btnSimpanForm').on('click', function() {
        // Validasi Sederhana
        if(!$('#nama').val() || !$('#no_hp').val() || !$('#alamat').val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: 'Nama, No HP, dan Alamat wajib diisi!'
            });
            return;
        }
        $('#kontakForm').submit();
    });

    // 5. TUTUP MODAL
    $('#btnBatalModal, #btnCloseHeader').on('click', function() { closeModal('kontakModal'); });

    // --- NOTIFIKASI SESSION ---
    @if (session('success'))
        Swal.fire({ title: 'Berhasil!', text: "{{ session('success') }}", icon: 'success', timer: 2000, showConfirmButton: false });
    @endif
    @if ($errors->any())
        Swal.fire({ title: 'Gagal!', text: 'Mohon periksa inputan Anda.', icon: 'error' });
        openModal('kontakModal');
    @endif
});

function setupAddressSearch(inputId) {
    $(`#${inputId}`).autocomplete({
        // 1. Sumber Data (Memanggil Controller)
        source: debounce(async (request, response) => {
            if (request.term.length < 3) return response([]);
            
            try {
                // Panggil route controller
                const url = `{{ route('api.address.search') }}?q=${encodeURIComponent(request.term)}`;
                const res = await fetch(url);
                const data = await res.json();
                
                // Data dari controller sudah diformat (label, value, data_lengkap)
                // Jadi bisa langsung dipassing ke response
                response(data);

            } catch (e) {
                console.error("Error API:", e);
                response([]);
            }
        }, 400),

        minLength: 3,

        // 2. Aksi saat user memilih alamat dari list
        select: function(event, ui) {
            // Ambil data yang sudah kita mapping di Controller tadi
            const d = ui.item.data_lengkap; 

            // Debugging (cek console browser jika tidak muncul)
            console.log("Data dipilih:", d);

            // Isi Form Readonly (Sesuai ID input di HTML Anda)
            $('#village').val(d.village);
            $('#district').val(d.district);
            $('#regency').val(d.regency);
            $('#province').val(d.province);
            $('#postal_code').val(d.postal_code);

            // Isi Hidden ID (Penting untuk database)
            $('#district_id').val(d.district_id);
            $('#subdistrict_id').val(d.subdistrict_id);

            // Set input pencarian dengan teks alamat lengkap
            $(this).val(ui.item.label);

            // Return false agar input tidak berubah jadi aneh
            return false;
        }
    });
}

</script>
@endpush