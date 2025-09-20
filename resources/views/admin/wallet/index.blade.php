@extends('layouts.admin')

@section('title', 'Manajemen Wallet')
@section('page-title', 'Wallet Pelanggan')

@push('styles')
{{-- Menambahkan library Select2 untuk dropdown yang bisa dicari --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* ✅ DESAIN BARU: Penyesuaian style Select2 agar lebih menyatu dengan tema profesional */
    .select2-container--default .select2-selection--single {
        background-color: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        height: 42px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #1f2937;
        line-height: 40px;
        padding-left: 1rem;
        padding-right: 2rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 0.5rem;
    }
    .select2-container--open .select2-dropdown--below {
        border-top: 1px solid #d1d5db;
        border-radius: 0 0 0.5rem 0.5rem;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #4f46e5;
    }
    .dark .select2-container--default .select2-selection--single {
        background-color: #374151;
        border-color: #4b5563;
    }
    .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #f3f4f6;
    }
    .dark .select2-dropdown {
        background-color: #374151;
        border-color: #4b5563;
    }
    .dark .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: #1f2937;
        color: #f3f4f6;
        border-color: #4b5563;
    }
    .dark .select2-results__option {
        color: #f3f4f6;
    }
</style>
@endpush

@section('content')
<div class="space-y-8">

    <!-- ✅ DESAIN BARU: Kartu Top Up Saldo yang lebih modern -->
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                <i class="fas fa-wallet mr-3 text-indigo-500"></i>
                Formulir Top Up Saldo
            </h3>
        </div>
        <form action="{{ route('admin.wallet.topup') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
                <div class="lg:col-span-5">
                    <label for="user_id" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Pelanggan</label>
                    <select id="user_id" name="user_id" class="w-full" required></select>
                    @error('user_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="lg:col-span-4">
                    <label for="amount" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah Top Up (Rp)</label>
                    <input type="number" id="amount" name="amount" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="e.g., 50000" min="1000" required>
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="lg:col-span-3">
                    <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-transform transform hover:scale-105">
                        <i class="fas fa-plus-circle mr-2"></i> Tambah Saldo
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ✅ DESAIN BARU: Kartu Daftar Pengguna dengan header terintegrasi -->
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden">
        <div class="px-6 py-4 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-3 sm:mb-0">
                <i class="fas fa-users mr-3 text-gray-500"></i>
                Daftar Saldo Pengguna
            </h2>
            <div class="relative w-full sm:w-auto">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="table-search-input" name="search" placeholder="Cari pengguna..." class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-2.5" autocomplete="off">
            </div>
        </div>

        <!-- Tabel Daftar Pengguna -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">ID</th>
                        <th scope="col" class="py-3 px-6">Nama Pengguna</th>
                        <th scope="col" class="py-3 px-6">Email</th>
                        <th scope="col" class="py-3 px-6 text-right">Saldo Saat Ini</th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                    @forelse ($pengguna as $user)
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600/50">
                        <td class="py-4 px-6 font-mono text-gray-500 dark:text-gray-400">{{ $user->id_pengguna }}</td>
                        <td class="py-4 px-6 font-medium text-gray-900 dark:text-white">{{ $user->nama_lengkap }}</td>
                        <td class="py-4 px-6">{{ $user->email }}</td>
                        <td class="py-4 px-6 text-right font-semibold text-green-600 dark:text-green-400">
                            Rp {{ number_format($user->saldo ?? 0, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-10 px-6 text-center text-gray-500">
                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                            <p>Tidak ada pengguna yang dapat ditampilkan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
            {{ $pengguna->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Memuat jQuery dan Select2 --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Inisialisasi Select2 untuk dropdown top-up
    $('#user_id').select2({
        placeholder: "-- Ketik nama, email, ID, atau no. HP --",
        allowClear: true,
        ajax: {
            url: '{{ route('admin.wallet.search') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        },
        minimumInputLength: 2,
        templateResult: formatRepo,
        templateSelection: formatRepoSelection
    });

    function formatRepo (repo) {
        if (repo.loading) return repo.text;
        if (!repo.item) return repo.text;

        var $container = $(
            "<div class='select2-result-repository clearfix py-2 px-3'>" +
                "<div class='select2-result-repository__title font-semibold text-gray-800 dark:text-gray-100'></div>" +
                "<div class='select2-result-repository__description text-xs text-gray-500 dark:text-gray-400'></div>" +
            "</div>"
        );
        
        $container.find(".select2-result-repository__title").text(repo.item.nama_lengkap);
        let balance = new Intl.NumberFormat('id-ID').format(repo.item.balance ?? 0);
        $container.find(".select2-result-repository__description").text(`Email: ${repo.item.email} - Saldo: Rp ${balance}`);

        return $container;
    }

    function formatRepoSelection (repo) {
        return repo.text || "-- Pilih Pelanggan --";
    }

    // Script untuk Live Search pada tabel utama
    let searchTimeout;
    $('#table-search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val();
        
        if (query.length > 0) {
            $('.pagination').parent().hide();
        } else {
            $('.pagination').parent().show();
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: "{{ route('admin.wallet.liveSearch') }}",
                type: "GET",
                data: {'search': query},
                success: function(data) {
                    var tableBody = '';
                    if (data.length > 0) {
                        $.each(data, function(index, user) {
                            let balance = new Intl.NumberFormat('id-ID').format(user.balance || 0);
                            tableBody += `<tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600/50">
                                <td class="py-4 px-6 font-mono text-gray-500 dark:text-gray-400">${user.id}</td>
                                <td class="py-4 px-6 font-medium text-gray-900 dark:text-white">${user.nama_lengkap}</td>
                                <td class="py-4 px-6">${user.email}</td>
                                <td class="py-4 px-6 text-right font-semibold text-green-600 dark:text-green-400">Rp ${balance}</td>
                            </tr>`;
                        });
                    } else {
                        tableBody = '<tr><td colspan="4" class="py-10 px-6 text-center text-gray-500"><i class="fas fa-search text-2xl mb-2"></i><p>Pengguna tidak ditemukan.</p></td></tr>';
                    }
                    $('#user-table-body').html(tableBody);
                }
            });
        }, 300);
    });
});
</script>
@endpush

