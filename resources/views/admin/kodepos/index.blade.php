@extends('layouts.admin')

@section('content')
{{-- Konten ini menggunakan Tailwind CSS --}}
<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <header class="text-center mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Sistem Pencarian Kode Pos</h1>
            <p class="mt-2 text-gray-600">Impor data dari Excel dan cari kode pos di seluruh Indonesia.</p>
        </header>

        <!-- Card untuk Pencarian dan Tabel Data -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">Daftar Kode Pos</h2>

                <!-- Input Pencarian -->
                <div class="relative mb-6">
                    <input type="text" id="liveSearch" name="search" 
                           placeholder="Cari berdasarkan ID, provinsi, kota, kecamatan..." 
                           value="{{ request('search') }}"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tabel Data -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="sticky left-0 bg-gray-50 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase z-10">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provinsi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kota/Kabupaten</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kecamatan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelurahan/Desa</th>
                            <th class="sticky right-0 bg-gray-50 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode Pos</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($kode_pos_list as $item)
                            <tr>
                                <td class="sticky left-0 bg-white px-6 py-4 text-sm text-gray-500 z-10">{{ $item->id }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->provinsi }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->kota_kabupaten }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->kecamatan }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->kelurahan_desa }}</td>
                                <td class="sticky right-0 bg-white px-6 py-4 text-sm font-semibold text-blue-600">{{ $item->kode_pos }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Tidak ada data ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginasi -->
            @if ($kode_pos_list->hasPages())
            <div class="p-6 border-t border-gray-200">
                {{ $kode_pos_list->appends(request()->input())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('liveSearch');
    const tableBody = document.querySelector('tbody');
    let timer = null;

    searchInput.addEventListener('keyup', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const query = this.value;

            fetch(`{{ route('admin.kodepos.index') }}?search=${encodeURIComponent(query)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTbody = doc.querySelector('tbody');
                if (newTbody) {
                    tableBody.innerHTML = newTbody.innerHTML;
                }
            })
            .catch(err => console.error(err));
        }, 400); // debounce 400ms
    });
});
</script>
@endpush
