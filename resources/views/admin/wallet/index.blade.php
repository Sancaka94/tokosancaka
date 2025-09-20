@extends('layouts.admin')

@section('title', 'Manajemen Wallet')
@section('page-title', 'Wallet Pelanggan')

@push('styles')
{{-- Menambahkan library Select2 untuk dropdown yang bisa dicari --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Penyesuaian style Select2 agar cocok dengan tema TailwindCSS dan Dark Mode */
    .select2-container--default .select2-selection--single { height: 42px; border-color: #d1d5db; border-radius: 0.5rem; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 1rem; color: #111827; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
    
    .dark .select2-container--default .select2-selection--single { background-color: #374151; border-color: #4b5563; }
    .dark .select2-container--default .select2-selection--single .select2-selection__rendered { color: #d1d5db; }
    .dark .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: #d1d5db transparent transparent transparent; }
    .dark .select2-dropdown { background-color: #374151; border-color: #4b5563; }
    .dark .select2-container--default .select2-search--dropdown .select2-search__field { background-color: #1f2937; color: #fff; border-color: #4b5563; }
    .dark .select2-results__option { color: #d1d5db; }
    .dark .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #4f46e5; }
</style>
@endpush

@section('content')
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">

    <!-- Form Top Up Saldo -->
    <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-lg border dark:border-gray-700 mb-8">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Formulir Top Up Saldo</h3>
        <form action="{{ route('admin.wallet.topup') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pilihan Pelanggan dengan AJAX Search -->
                <div>
                    <label for="user_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pilih Pelanggan</label>
                    <select id="user_id" name="user_id" class="w-full" required></select>
                </div>

                <!-- Jumlah Top Up -->
                <div>
                    <label for="amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jumlah Top Up (Rp)</label>
                    <input type="number" id="amount" name="amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600" placeholder="e.g., 50000" min="1000" required>
                </div>

                <!-- Tombol Submit -->
                <div class="self-end">
                    <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <i class="fas fa-plus-circle mr-2"></i> Tambah Saldo
                    </button>
                </div>
            </div>
             @error('user_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
             @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </form>
    </div>

    <!-- Judul dan Form Pencarian Tabel -->
    <div class="flex flex-col md:flex-row items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4 md:mb-0">Daftar Saldo Pelanggan</h2>
        <form action="{{ route('wallet.index') }}" method="GET">
            <div class="flex items-center">
                <input type="text" name="search" placeholder="Cari nama atau email..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600" value="{{ request('search') }}">
                <button type="submit" class="p-2.5 text-sm font-medium text-white bg-indigo-600 rounded-r-lg border border-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pelanggan -->
    <div class="overflow-x-auto relative">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="py-3 px-6">ID</th>
                    <th scope="col" class="py-3 px-6">Nama Pelanggan</th>
                    <th scope="col" class="py-3 px-6">Email</th>
                    <th scope="col" class="py-3 px-6 text-right">Saldo Saat Ini</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pelanggan as $user)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $user->id }}</td>
                    <td class="py-4 px-6">{{ $user->nama_lengkap }}</td>
                    <td class="py-4 px-6">{{ $user->email }}</td>
                    <td class="py-4 px-6 text-right font-bold text-green-600 dark:text-green-400">
                        Rp {{ number_format($user->balance, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-4 px-6 text-center text-gray-500">
                        Tidak ada pelanggan yang cocok dengan pencarian Anda.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    {{-- Link Pagination dengan query pencarian --}}
    <div class="mt-6">
        {{ $pelanggan->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
{{-- Memuat jQuery dan Select2 --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#user_id').select2({
        placeholder: "-- Ketik nama atau email pelanggan --",
        allowClear: true,
        ajax: {
            url: '{{ route('wallet.search') }}',
            dataType: 'json',
            delay: 250, // Menunggu 250ms setelah user berhenti mengetik
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item.id,
                            text: `${item.nama_lengkap} (Email: ${item.email})`,
                            item: item // Menyimpan data asli
                        }
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2, // Minimal 2 karakter untuk memulai pencarian
        templateResult: formatRepo,
        templateSelection: formatRepoSelection
    });

    function formatRepo (repo) {
        if (repo.loading) {
            return repo.text;
        }

        // Tampilan hasil di dropdown
        var $container = $(
            "<div class='select2-result-repository clearfix'>" +
                "<div class='select2-result-repository__title font-semibold'></div>" +
                "<div class='select2-result-repository__description text-sm text-gray-500'></div>" +
            "</div>"
        );

        $container.find(".select2-result-repository__title").text(repo.item.nama_lengkap);
        $container.find(".select2-result-repository__description").text("Email: " + repo.item.email + " - Saldo: Rp " + new Intl.NumberFormat('id-ID').format(repo.item.balance));

        return $container;
    }

    function formatRepoSelection (repo) {
        // Tampilan setelah item dipilih
        return repo.text || "-- Ketik nama atau email pelanggan --";
    }
});
</script>
@endpush

