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
        border: 1px solid #FF0000;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .ui-menu-item-wrapper {
        padding: 0.75rem 1rem;
        cursor: pointer;
    }
    .ui-menu-item-wrapper:hover,
    .ui-state-active {
        background-color: #FF0000;
    }
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
    .form-control-readonly {
        background-color: #e9ecef;
        opacity: 1;
        cursor: not-allowed;
    }
</style>
@endpush

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
        <form action="{{ route('customer.kontak.index') }}" method="GET" class="relative w-full md:w-1/3">
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Cari Nama atau No. HP..." value="{{ request('search') }}">
            <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </form>
        
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <button type="button" id="btnTambahKontak" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700">
                <i class="fas fa-plus mr-1"></i> Tambah Kontak
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
            <p class="font-bold">Error!</p>
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. HP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detail Alamat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kel/Desa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kecamatan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kota/Kab</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provinsi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode Pos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($kontaks as $kontak)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $kontak->nama }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $kontak->no_hp }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $kontak->alamat }}">{{ $kontak->alamat ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $kontak->village ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $kontak->district ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $kontak->regency ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $kontak->province ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $kontak->postal_code ?? '-' }}</td>

                    <td class="px-6 py-4 text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded-md text-xs btnEditKontak" data-id="{{ $kontak->id }}">
                                <i class="fas fa-pencil-alt"></i>
                            </button>

                            <form action="{{ route('customer.kontak.destroy', $kontak->id) }}" method="POST" onsubmit="return confirm('Yakin hapus kontak ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-md text-xs">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-gray-500">
                        Buku alamat Anda masih kosong.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- BATAS TABEL UTAMA DI ATAS --}}

    {{-- MULAI TABEL DATA PENGIRIM --}}
    <div class="mt-12">
        <div class="flex items-center mb-4 pb-2 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-paper-plane mr-2 text-blue-600"></i> Data Pengirim (Profil Saya)
            </h3>
        </div>

        <div class="overflow-x-auto bg-blue-50 rounded-lg border border-blue-100">
            <table class="min-w-full divide-y divide-blue-200">
                <thead class="bg-blue-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-blue-800 uppercase">Nama Pengirim</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-blue-800 uppercase">No. HP</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-blue-800 uppercase">Alamat Lengkap</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-blue-800 uppercase">Lokasi</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-blue-800 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-200">
                    @forelse ($pengirims as $pengirim)
                    <tr class="hover:bg-blue-100 transition duration-150">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $pengirim->nama }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $pengirim->no_hp }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="{{ $pengirim->alamat }}">
                            {{ $pengirim->alamat ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $pengirim->district ?? '-' }}, {{ $pengirim->regency ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button type="button" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded-md text-xs btnEditKontak" data-id="{{ $pengirim->id }}">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                                
                                <form action="{{ route('customer.kontak.destroy', $pengirim->id) }}" method="POST" onsubmit="return confirm('Hapus data pengirim ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-md text-xs">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-6 text-gray-500 italic">
                            Belum ada data pengirim tersimpan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{-- SELESAI TABEL PENGIRIM --}}

    <div class="mt-4">{{ $kontaks->appends(request()->query())->links() }}</div>
</div>

{{-- Modal --}}
<div id="kontakModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden flex items-center justify-center overflow-auto">
    <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white my-8">
        <form id="kontakForm" action="" method="POST">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <h3 id="modalTitle" class="text-xl font-semibold mb-4"></h3>

            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                <div>
                    <label class="block text-sm font-medium">Nama</label>
                    <input type="text" id="nama" name="nama" class="mt-1 block w-full border rounded-md p-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">No. HP</label>
                    <input type="text" id="no_hp" name="no_hp" class="mt-1 block w-full border rounded-md p-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Cari Alamat (Kec/Kel/Kodepos)</label>
                    <input type="text" id="kontak_address_search" class="mt-1 block w-full border rounded-md p-2" placeholder="Ketik untuk mencari..." autocomplete="off">
                </div>

                <div>
                    <label class="block text-sm font-medium">Detail Alamat Lengkap</label>
                    <textarea id="alamat" name="alamat" class="mt-1 block w-full border rounded-md p-2" rows="2" required></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Kelurahan / Desa</label>
                        <input type="text" name="village" id="village" class="form-control-readonly mt-1 block w-full border rounded-md p-2" readonly required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Kecamatan</label>
                        <input type="text" name="district" id="district" class="form-control-readonly mt-1 block w-full border rounded-md p-2" readonly required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Kabupaten / Kota</label>
                        <input type="text" name="regency" id="regency" class="form-control-readonly mt-1 block w-full border rounded-md p-2" readonly required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Provinsi</label>
                        <input type="text" name="province" id="province" class="form-control-readonly mt-1 block w-full border rounded-md p-2" readonly required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Kode Pos</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control-readonly mt-1 block w-full border rounded-md p-2" readonly required>
                </div>

                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">
                <input type="hidden" name="district_id" id="district_id" required>
                <input type="hidden" name="subdistrict_id" id="subdistrict_id" required>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="btnBatalModal" class="bg-gray-200 px-4 py-2 rounded-md">Batal</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {

    // FUNGSI HELPER MODAL
    function openModal(id) {
        $('#' + id).removeClass('hidden');
    }
    function closeModal(id) {
        $('#' + id).addClass('hidden');
    }

    // FUNGSI DEBOUNCE
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // FUNGSI PENCARIAN ALAMAT
    function setupAddressSearch(inputId) {
        const s = $(`#${inputId}`);

        s.autocomplete({
            source: debounce(async (request, response) => {
                if (request.term.length < 3) return response([]);

                try {
                    const res = await fetch(`{{ route('api.address.search') }}?search=${request.term}`);
                    const data = await res.json();

                    response(data.map(item => ({
                        label: item.full_address,
                        value: item.full_address,
                        data: item
                    })));
                } catch (e) {
                    console.error("Gagal fetch alamat:", e);
                    response([]);
                }
            }, 400),

            minLength: 3,

            select: function(event, ui) {
                const i = ui.item.data;

                const parts = i.full_address.split(',').map(s => s.trim());
                $('#village').val(parts[0] || '');
                $('#district').val(parts[1] || '');
                $('#regency').val(parts[2] || '');
                $('#province').val(parts[3] || '');
                $('#postal_code').val(parts[4] || '');

                $('#lat').val(i.lat || '');
                $('#lng').val(i.lon || '');
                $('#district_id').val(i.district_id);
                $('#subdistrict_id').val(i.subdistrict_id);

                event.preventDefault();
                setTimeout(() => s.val(i.full_address), 10);
            }
        });
    }

    // INISIALISASI PENCARIAN ALAMAT
    setupAddressSearch('kontak_address_search');

    // EVENT LISTENER TOMBOL TAMBAH
    $('#btnTambahKontak').on('click', function() {
        const form = $('#kontakForm');
        form[0].reset();
        form.attr('action', "{{ route('customer.kontak.store') }}");
        $('#formMethod').val('POST');
        $('#modalTitle').text('Tambah Kontak Baru');
        openModal('kontakModal');
    });

    // EVENT LISTENER TOMBOL EDIT
    $('.btnEditKontak').on('click', async function() {
        const id = $(this).data('id');
        const form = $('#kontakForm');
        form[0].reset();

        try {
            const response = await fetch(`/customer/kontak/${id}/edit`);
            if (!response.ok) throw new Error('Network response was not ok.');
            const kontak = await response.json();

            $('#nama').val(kontak.nama);
            $('#no_hp').val(kontak.no_hp);
            $('#alamat').val(kontak.alamat);
            
            $('#kontak_address_search').val(`${kontak.village}, ${kontak.district}, ${kontak.regency}, ${kontak.province} ${kontak.postal_code}`);
            
            $('#lat').val(kontak.lat || '');
            $('#lng').val(kontak.lng || '');
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
            console.error('Gagal mengambil data kontak:', err);
            Swal.fire('Error', 'Gagal memuat data untuk diedit.', 'error');
        }
    });

    // EVENT LISTENER TOMBOL BATAL
    $('#btnBatalModal').on('click', function() {
        closeModal('kontakModal');
    });

    // TAMPILKAN NOTIFIKASI
    @if (session('success'))
        Swal.fire({ title: 'Berhasil!', text: "{{ session('success') }}", icon: 'success', timer: 2000, showConfirmButton: false });
    @endif

    @if ($errors->any())
        let errorHtml = '<ul class="list-unstyled text-start mb-0" style="padding-left: 1rem;">';
        @foreach ($errors->all() as $error)
            errorHtml += '<li class="mb-1"><i class="fas fa-exclamation-circle me-2 text-danger"></i>{{ $error }}</li>';
        @endforeach
        errorHtml += '</ul>';
        Swal.fire({ title: 'Data Tidak Valid!', html: errorHtml, icon: 'error', confirmButtonColor: '#dc2626' });

        openModal('kontakModal');
        
        @if (old('_method') == 'PUT')
            $('#modalTitle').text('Edit Kontak');
            $('#formMethod').val('PUT');
        @else
            $('#modalTitle').text('Tambah Kontak Baru');
            $('#formMethod').val('POST');
            $('#kontakForm').attr('action', "{{ route('customer.kontak.store') }}");
        @endif
    @endif

});
</script>
@endpush
