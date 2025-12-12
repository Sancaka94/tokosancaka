{{-- resources/views/customer/kontak/index.blade.php --}}

@extends('layouts.customer')

@section('title', 'Buku Alamat')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
    .ui-autocomplete {
        z-index: 1060 !important;
        max-height: 250px;
        overflow-y: auto;
        overflow-x: hidden;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .ui-menu-item-wrapper {
        padding: 0.5rem 1rem;
        cursor: pointer;
    }
    .ui-menu-item-wrapper:hover, .ui-state-active {
        background-color: #f3f4f6 !important;
        color: #1f2937 !important;
        border: none !important;
    }
    .form-control-readonly {
        background-color: #f9fafb;
        cursor: not-allowed;
    }
</style>
@endpush

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md min-h-screen">

    {{-- HEADER & PENCARIAN --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
        <form action="{{ route('customer.kontak.index') }}" method="GET" class="relative w-full md:w-1/3">
            {{-- Simpan filter saat ini agar tidak hilang saat mencari --}}
            @if(request('filter'))
                <input type="hidden" name="filter" value="{{ request('filter') }}">
            @endif
            
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Cari Nama atau No. HP..." value="{{ request('search') }}">
            <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                <i class="fas fa-search w-5 h-5 ml-1 mt-1"></i>
            </div>
        </form>
        
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <button type="button" id="btnTambahKontak" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                <i class="fas fa-plus mr-1"></i> Tambah Kontak
            </button>
        </div>
    </div>

    {{-- TOMBOL FILTER (TABS) --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-4" aria-label="Tabs">
            {{-- Helper Variable --}}
            @php $currentFilter = request('filter', 'Semua'); @endphp

            <a href="{{ route('customer.kontak.index', ['filter' => 'Semua', 'search' => request('search')]) }}" 
               class="{{ $currentFilter == 'Semua' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} 
                      whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
               Semua
            </a>

            <a href="{{ route('customer.kontak.index', ['filter' => 'Penerima', 'search' => request('search')]) }}" 
               class="{{ $currentFilter == 'Penerima' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} 
                      whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
               Penerima
            </a>

            <a href="{{ route('customer.kontak.index', ['filter' => 'Pengirim', 'search' => request('search')]) }}" 
               class="{{ $currentFilter == 'Pengirim' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} 
                      whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
               Pengirim
            </a>
        </nav>
    </div>

    {{-- ALERT SUKSES/ERROR (SESSION) --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r shadow-sm">
            <p><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
        </div>
    @endif

    {{-- TABEL UTAMA (DINAMIS SESUAI FILTER) --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. HP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail Alamat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kota/Kab</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provinsi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($kontaks as $kontak)
                <tr class="hover:bg-gray-50 transition duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $kontak->nama }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $kontak->no_hp }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($kontak->tipe == 'Pengirim')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Pengirim</span>
                        @elseif($kontak->tipe == 'Penerima')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Penerima</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Lainnya</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $kontak->alamat }}">
                        {{ $kontak->alamat ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $kontak->regency ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $kontak->province ?? '-' }}</td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <button type="button" class="text-blue-600 hover:text-blue-900 btnEditKontak" data-id="{{ $kontak->id }}">
                                <i class="fas fa-pencil-alt"></i>
                            </button>

                            <form action="{{ route('customer.kontak.destroy', $kontak->id) }}" method="POST" onsubmit="return confirm('Yakin hapus kontak ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 ml-2">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10 text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-address-book text-4xl text-gray-300 mb-3"></i>
                            <p>Tidak ada data kontak ditemukan untuk filter: <strong>{{ request('filter', 'Semua') }}</strong></p>
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

    {{-- TABEL DATA PENGIRIM (STATIS DI BAWAH - JIKA PERLU) --}}
    {{-- Ini opsional, jika User ingin melihat profil pengirimnya terpisah di bawah --}}
    @if(isset($pengirims) && $pengirims->isNotEmpty())
    <div class="mt-12 pt-6 border-t border-gray-200">
        <div class="flex items-center mb-4 gap-2">
            <div class="p-2 bg-blue-100 rounded-lg">
                <i class="fas fa-user-tie text-blue-600"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Data Pengirim (Profil Saya)</h3>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">No. HP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Alamat Lengkap</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Lokasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($pengirims as $pengirim)
                    <tr class="hover:bg-blue-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $pengirim->nama }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pengirim->no_hp }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $pengirim->alamat }}">{{ $pengirim->alamat }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pengirim->district }}, {{ $pengirim->regency }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button type="button" class="text-blue-600 hover:text-blue-900 btnEditKontak" data-id="{{ $pengirim->id }}"><i class="fas fa-pencil-alt"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

{{-- MODAL (Sama seperti sebelumnya) --}}
<div id="kontakModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden flex items-center justify-center overflow-auto backdrop-blur-sm transition-opacity">
    <div class="relative w-full max-w-lg mx-auto my-6 p-4">
        <div class="bg-white rounded-xl shadow-2xl relative flex flex-col w-full outline-none focus:outline-none">
            
            {{-- Header Modal --}}
            <div class="flex items-center justify-between p-5 border-b border-gray-200 rounded-t-xl bg-gray-50">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800"></h3>
                <button type="button" id="btnCloseHeader" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="relative p-6 flex-auto max-h-[75vh] overflow-y-auto">
                <form id="kontakForm" action="" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    
                    <div class="space-y-5">
                        
                        {{-- Pilihan Tipe Kontak --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Kontak</label>
                            <select name="tipe" id="tipe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <option value="Penerima">Penerima (Orang yang dituju)</option>
                                <option value="Pengirim">Pengirim (Saya/Orang Lain)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="nama" name="nama" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">No. HP (WA) <span class="text-red-500">*</span></label>
                                <input type="number" id="no_hp" name="no_hp" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <label class="block text-sm font-semibold text-blue-800 mb-1"><i class="fas fa-map-marker-alt mr-1"></i> Cari Wilayah (Kel/Kec/Kota)</label>
                            <input type="text" id="kontak_address_search" class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white placeholder-gray-400" placeholder="Ketik minimal 3 huruf..." autocomplete="off">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Detail Alamat Lengkap <span class="text-red-500">*</span></label>
                            <textarea id="alamat" name="alamat" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" rows="2" required placeholder="Jl. Merdeka No. 123, RT 01/RW 02..."></textarea>
                        </div>

                        {{-- Kolom Readonly Wilayah --}}
                        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Kelurahan</label>
                                <input type="text" name="village" id="village" class="w-full bg-transparent border-b border-gray-300 text-sm font-medium text-gray-700 focus:outline-none" readonly>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Kecamatan</label>
                                <input type="text" name="district" id="district" class="w-full bg-transparent border-b border-gray-300 text-sm font-medium text-gray-700 focus:outline-none" readonly>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Kota/Kab</label>
                                <input type="text" name="regency" id="regency" class="w-full bg-transparent border-b border-gray-300 text-sm font-medium text-gray-700 focus:outline-none" readonly>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Provinsi</label>
                                <input type="text" name="province" id="province" class="w-full bg-transparent border-b border-gray-300 text-sm font-medium text-gray-700 focus:outline-none" readonly>
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs font-bold text-gray-500 uppercase">Kode Pos</label>
                                <input type="text" name="postal_code" id="postal_code" class="w-full bg-transparent border-b border-gray-300 text-sm font-medium text-gray-700 focus:outline-none" readonly>
                            </div>
                        </div>

                        {{-- Hidden Fields --}}
                        <input type="hidden" name="lat" id="lat">
                        <input type="hidden" name="lng" id="lng">
                        <input type="hidden" name="district_id" id="district_id">
                        <input type="hidden" name="subdistrict_id" id="subdistrict_id">
                    </div>
                </form>
            </div>

            {{-- Footer Modal --}}
            <div class="flex items-center justify-end p-5 border-t border-gray-200 rounded-b-xl bg-gray-50 gap-3">
                <button type="button" id="btnBatalModal" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">Batal</button>
                <button type="button" id="btnSimpanForm" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-md transition">Simpan Kontak</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- SCRIPT SAMA SEPERTI SEBELUMNYA, TAPI TAMBAHKAN LOGIKA UNTUK INPUT TIPE DI MODAL --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // ... (Fungsi Open/Close Modal & Debounce sama seperti sebelumnya) ...
    function openModal(id) { $('#' + id).removeClass('hidden').addClass('flex'); $('body').addClass('overflow-hidden'); }
    function closeModal(id) { $('#' + id).addClass('hidden').removeClass('flex'); $('body').removeClass('overflow-hidden'); }
    function debounce(func, delay) { let timeout; return function(...args) { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), delay); }; }

    // --- AUTOCOMPLETE SEARCH (SAMA) ---
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
                $('#village').val(item.village_name || item.village);
                $('#district').val(item.district_name || item.district);
                $('#regency').val(item.city_name || item.regency);
                $('#province').val(item.province_name || item.province);
                $('#postal_code').val(item.zip_code || item.postal_code);
                $('#district_id').val(item.district_id);
                $('#subdistrict_id').val(item.subdistrict_id);
                event.preventDefault();
                $(this).val(ui.item.label);
            }
        });
    }
    setupAddressSearch('kontak_address_search');

    // --- LOGIKA TOMBOL ---
    $('#btnTambahKontak').on('click', function() {
        const form = $('#kontakForm');
        form[0].reset();
        form.attr('action', "{{ route('customer.kontak.store') }}");
        $('#formMethod').val('POST');
        $('#modalTitle').text('Tambah Kontak Baru');
        $('#tipe').val('Penerima'); // Default
        openModal('kontakModal');
    });

    $(document).on('click', '.btnEditKontak', async function() {
        const id = $(this).data('id');
        const form = $('#kontakForm');
        form[0].reset();
        Swal.showLoading();

        try {
            const response = await fetch(`/customer/kontak/${id}/edit`);
            const kontak = await response.json();
            Swal.close();

            $('#nama').val(kontak.nama);
            $('#no_hp').val(kontak.no_hp);
            $('#alamat').val(kontak.alamat);
            $('#tipe').val(kontak.tipe); // Isi tipe sesuai data

            // Isi Alamat
            $('#kontak_address_search').val(`${kontak.village}, ${kontak.district}, ${kontak.regency}`);
            $('#province').val(kontak.province);
            $('#regency').val(kontak.regency);
            $('#district').val(kontak.district);
            $('#village').val(kontak.village);
            $('#postal_code').val(kontak.postal_code);
            $('#district_id').val(kontak.district_id);
            $('#subdistrict_id').val(kontak.subdistrict_id);

            form.attr('action', `/customer/kontak/${kontak.id}`);
            $('#formMethod').val('PUT');
            $('#modalTitle').text('Edit Kontak');
            openModal('kontakModal');
        } catch (err) {
            Swal.fire('Error', 'Gagal memuat data.', 'error');
        }
    });

    $('#btnSimpanForm').on('click', function() {
        if(!$('#nama').val() || !$('#no_hp').val() || !$('#alamat').val()) {
            Swal.fire('Peringatan', 'Mohon lengkapi Nama, HP, dan Alamat.', 'warning');
            return;
        }
        $('#kontakForm').submit();
    });

    $('#btnBatalModal, #btnCloseHeader').on('click', function() { closeModal('kontakModal'); });

    // --- SWAL NOTIFIKASI ---
    @if (session('success'))
        Swal.fire({ title: 'Berhasil!', text: "{{ session('success') }}", icon: 'success', timer: 2000, showConfirmButton: false });
    @endif
    @if ($errors->any())
        Swal.fire({ title: 'Gagal!', text: 'Periksa kembali inputan Anda.', icon: 'error' });
        openModal('kontakModal'); // Buka lagi jika error
    @endif
});
</script>
@endpush