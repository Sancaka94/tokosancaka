@extends('layouts.admin')

@section('title', 'Manajemen Wilayah')

@push('styles')
<style>
    /* Style spesifik untuk halaman ini */
    select:disabled { background-color: #f3f4f6; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div x-data="wilayahManager">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Manajemen Wilayah</h1>

    <!-- Filter Dropdown -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-white rounded-lg shadow">
        <div>
            <label for="province" class="block text-sm font-medium text-gray-700">Provinsi</label>
            <select id="province" x-model="selected.province" @change="fetchKabupaten"
                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Pilih Provinsi --</option>
                @foreach($provinces as $province)
                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="regency" class="block text-sm font-medium text-gray-700">Kabupaten/Kota</label>
            <select id="regency" x-model="selected.regency" @change="fetchKecamatan"
                :disabled="!selected.province || loading.kabupaten"
                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Pilih Kabupaten/Kota --</option>
                <template x-if="loading.kabupaten"><option disabled>Memuat...</option></template>
                <template x-for="kab in lists.kabupaten" :key="kab.id">
                    <option :value="kab.id" x-text="kab.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="district" class="block text-sm font-medium text-gray-700">Kecamatan</label>
            <select id="district" x-model="selected.district" @change="fetchDesa(1)"
                :disabled="!selected.regency || loading.kecamatan"
                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Pilih Kecamatan --</option>
                <template x-if="loading.kecamatan"><option disabled>Memuat...</option></template>
                <template x-for="kec in lists.kecamatan" :key="kec.id">
                    <option :value="kec.id" x-text="kec.name"></option>
                </template>
            </select>
        </div>
    </div>
    
    <!-- Tabel Desa/Kelurahan -->
    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <div class="p-4 border-b">
            <input type="text" x-model="search" @input.debounce.500ms="fetchDesa(1)"
                :disabled="!selected.district"
                placeholder="Cari nama desa/kelurahan..."
                class="w-full px-4 py-2 border rounded-lg">
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Wilayah</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Kode Pos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Desa (Kode Pos)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Desa (Wilayah)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase sticky right-0 bg-gray-50">Kode Pos</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-if="loading.desa">
                    <tr><td colspan="5" class="p-6 text-center text-gray-500">Memuat data...</td></tr>
                </template>
                <template x-if="!loading.desa && lists.desa.length === 0">
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-500"
                            x-text="selected.district ? 'Tidak ada data ditemukan.' : 'Silakan pilih provinsi, kabupaten, dan kecamatan terlebih dahulu.'">
                        </td>
                    </tr>
                </template>
                <!-- [PERUBAHAN] Mengubah :key menjadi kombinasi unik dengan index -->
                <template x-for="(desa, index) in lists.desa" :key="desa.id_kodepos + '-' + index">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="desa.id_wilayah ? desa.id_wilayah : '-'"></td>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="desa.id_kodepos"></td>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="desa.name_kodepos"></td>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="desa.name_wilayah ? desa.name_wilayah : '-'"></td>
                        <td class="px-6 py-4 whitespace-nowrap sticky right-0 bg-white" x-text="desa.kode_pos ? desa.kode_pos : '-'"></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <!-- Paginasi -->
        <div class="p-4 border-t flex justify-between items-center" x-show="pagination.total > 0">
            <p class="text-sm text-gray-600">
                Menampilkan <span x-text="pagination.from"></span> - <span x-text="pagination.to"></span> dari <span x-text="pagination.total"></span> hasil
            </p>
            <div>
                <button @click="fetchDesa(pagination.currentPage - 1)"
                    :disabled="!pagination.prevPageUrl"
                    class="px-3 py-1 text-sm border rounded disabled:opacity-50">Sebelumnya</button>
                <button @click="fetchDesa(pagination.currentPage + 1)"
                    :disabled="!pagination.nextPageUrl"
                    class="px-3 py-1 text-sm border rounded disabled:opacity-50 ml-2">Berikutnya</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('wilayahManager', () => ({
        selected: { province: '', regency: '', district: '' },
        lists: { kabupaten: [], kecamatan: [], desa: [] },
        loading: { kabupaten: false, kecamatan: false, desa: false },
        search: '',
        pagination: { from: 0, to: 0, total: 0, currentPage: 1, prevPageUrl: null, nextPageUrl: null },

        async fetchKabupaten() {
            this.selected.regency = ''; this.selected.district = '';
            this.lists.kecamatan = []; this.lists.desa = []; this.pagination.total = 0;
            if (!this.selected.province) { this.lists.kabupaten = []; return; }
            
            this.loading.kabupaten = true;
            try {
                let url = `{{ route('admin.wilayah.kabupaten', ':id') }}`.replace(':id', this.selected.province);
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                this.lists.kabupaten = await response.json();
            } catch (error) {
                console.error('Gagal mengambil data kabupaten:', error);
            } finally {
                this.loading.kabupaten = false;
            }
        },

        async fetchKecamatan() {
            this.selected.district = '';
            this.lists.desa = []; this.pagination.total = 0;
            if (!this.selected.regency) { this.lists.kecamatan = []; return; }

            this.loading.kecamatan = true;
            try {
                let url = `{{ route('admin.wilayah.kecamatan', ':id') }}`.replace(':id', this.selected.regency);
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                this.lists.kecamatan = await response.json();
            } catch (error) {
                console.error('Gagal mengambil data kecamatan:', error);
            } finally {
                this.loading.kecamatan = false;
            }
        },

        async fetchDesa(page = 1) {
            if (!this.selected.district) { this.lists.desa = []; this.pagination.total = 0; return; }
            
            this.loading.desa = true;
            try {
                let url = new URL(`{{ route('admin.wilayah.desa', ':id') }}`.replace(':id', this.selected.district));
                url.searchParams.append('page', page);
                if(this.search) url.searchParams.append('search', this.search);
                
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();

                this.lists.desa = data.data;
                this.pagination.from = data.from;
                this.pagination.to = data.to;
                this.pagination.total = data.total;
                this.pagination.currentPage = data.current_page;
                this.pagination.prevPageUrl = data.prev_page_url;
                this.pagination.nextPageUrl = data.next_page_url;
            } catch (error) {
                console.error('Gagal mengambil data desa:', error);
            } finally {
                this.loading.desa = false;
            }
        }
    }));
});
</script>
@endpush

