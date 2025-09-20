@extends('layouts.admin')

@section('title', 'Manajemen Wallet')
@section('page-title', 'Wallet Pelanggan')

@push('styles')
    {{-- Menambahkan library Select2 untuk dropdown yang bisa dicari --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Penyesuaian style Select2 agar cocok dengan tema TailwindCSS dan Dark Mode */
        .select2-container--default .select2-selection--single { height: 42px !important; border-color: #d1d5db; border-radius: 0.5rem; padding-top: 5px; }
        .dark .select2-container--default .select2-selection--single { background-color: #374151; border-color: #4b5563; }
        .dark .select2-container--default .select2-selection--single .select2-selection__rendered { color: #d1d5db; }
        .dark .select2-dropdown { background-color: #374151; border-color: #4b5563; }
        .dark .select2-container--default .select2-search--dropdown .select2-search__field { background-color: #1f2937; color: #fff; border-color: #4b5563; }
        .dark .select2-results__option { color: #d1d5db; }
        .dark .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #4f46e5; }
        .action-button { transition: all 0.2s ease-in-out; }
        .action-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
@endpush

@section('content')
<div class="space-y-8">

    <!-- Form Top Up Saldo Utama -->
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3 mb-4">
            <div class="bg-indigo-100 dark:bg-indigo-900/50 p-2 rounded-lg">
                <i class="fas fa-wallet text-indigo-600 dark:text-indigo-400"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Formulir Top Up Saldo</h3>
        </div>
        <form action="{{ route('admin.wallet.topup') }}" method="POST">
            @csrf
            {{-- ✅ FIX: Menambahkan hidden input untuk 'action' default --}}
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div class="md:col-span-1">
                    <label for="user_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pilih Pelanggan</label>
                    <select id="user_id" name="user_id" class="w-full" required></select>
                    @error('user_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jumlah (Rp)</label>
                    <input type="number" id="amount" name="amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600" placeholder="e.g., 50000" min="1000" required>
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center action-button">
                        <i class="fas fa-plus-circle mr-2"></i> Tambah Saldo
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
             <div class="flex items-center space-x-3">
                <div class="bg-gray-100 dark:bg-gray-900/50 p-2 rounded-lg">
                    <i class="fas fa-users text-gray-600 dark:text-gray-400"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Daftar Saldo Pengguna</h2>
            </div>
            <div class="relative w-full md:w-auto">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="table-search-input" placeholder="Cari pengguna..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" autocomplete="off">
            </div>
        </div>

        <div class="overflow-x-auto relative">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">ID</th>
                        <th scope="col" class="py-3 px-6">Nama Pengguna</th>
                        <th scope="col" class="py-3 px-6">Email</th>
                        <th scope="col" class="py-3 px-6 text-right">Saldo Saat Ini</th>
                        <th scope="col" class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                    @forelse ($pengguna as $user)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $user->id_pengguna }}</td>
                        <td class="py-4 px-6">{{ $user->nama_lengkap }}</td>
                        <td class="py-4 px-6">{{ $user->email }}</td>
                        <td class="py-4 px-6 text-right font-bold text-green-600 dark:text-green-400">
                            Rp {{ number_format($user->saldo ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-6 text-center">
                            <div class="flex justify-center space-x-2">
                                <button onclick="openModal('add', '{{ $user->id_pengguna }}', '{{ e($user->nama_lengkap) }}')" class="action-button text-white bg-green-500 hover:bg-green-600 font-medium rounded-lg text-xs px-3 py-1.5 text-center">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button onclick="openModal('subtract', '{{ $user->id_pengguna }}', '{{ e($user->nama_lengkap) }}')" class="action-button text-white bg-red-500 hover:bg-red-600 font-medium rounded-lg text-xs px-3 py-1.5 text-center">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-4 px-6 text-center text-gray-500">
                            Tidak ada pengguna yang dapat ditampilkan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 pagination-container">
            {{ $pengguna->links() }}
        </div>
    </div>
</div>

<!-- Modal Aksi Saldo -->
<div id="action-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
            <div class="flex justify-between items-start">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modal-title">...</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Untuk Pengguna: <span id="modal-user-name" class="font-semibold">...</span></p>

            <form id="modal-form" action="{{ route('admin.wallet.topup') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="user_id" id="modal-user-id">
                {{-- ✅ FIX: Hidden input untuk mengirim 'action' ke controller --}}
                <input type="hidden" name="action" id="modal-action">

                <div>
                    <label for="modal-amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jumlah (Rp)</label>
                    <input type="number" name="amount" id="modal-amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600" placeholder="e.g., 20000" min="1" required>
                </div>

                <div class="pt-2">
                    <button type="submit" id="modal-submit-button" class="w-full text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center action-button">
                        ...
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Inisialisasi Select2
    $('#user_id').select2({
        placeholder: "-- Ketik nama, email, ID, atau no. HP --",
        allowClear: true,
        ajax: {
            url: '{{ route('admin.wallet.search') }}',
            dataType: 'json',
            delay: 250,
            data: params => ({ term: params.term }),
            processResults: data => ({ results: data.results }),
            cache: true
        },
        minimumInputLength: 2,
        templateResult: formatRepo,
        templateSelection: formatRepoSelection
    });

    function formatRepo(repo) {
        if (repo.loading) return repo.text;
        if (!repo.item) return repo.text;

        const balance = new Intl.NumberFormat('id-ID').format(repo.item.balance || 0);
        return $(`
            <div>
                <div class='font-semibold'>${repo.item.nama_lengkap}</div>
                <div class='text-xs text-gray-500'>Email: ${repo.item.email} - Saldo: Rp ${balance}</div>
            </div>
        `);
    }

    function formatRepoSelection(repo) {
        return repo.text || "-- Pilih Pelanggan --";
    }

    // Script Live Search
    let searchTimeout;
    $('#table-search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        
        $('.pagination-container').toggle(query.length === 0);

        searchTimeout = setTimeout(() => {
            $.ajax({
                url: "{{ route('admin.wallet.search') }}",
                data: { 'search': query },
                success: function(data) {
                    let tableBody = '';
                    if (data.length > 0) {
                        data.forEach(user => {
                            const balance = new Intl.NumberFormat('id-ID').format(user.balance || 0);
                            const escapedName = $('<div/>').text(user.nama_lengkap).html();
                            tableBody += `
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="py-4 px-6 font-medium text-gray-900 dark:text-white">${user.id}</td>
                                    <td class="py-4 px-6">${user.nama_lengkap}</td>
                                    <td class="py-4 px-6">${user.email}</td>
                                    <td class="py-4 px-6 text-right font-bold text-green-600 dark:text-green-400">Rp ${balance}</td>
                                    <td class="py-4 px-6 text-center">
                                        <div class="flex justify-center space-x-2">
                                            <button onclick="openModal('add', '${user.id}', '${escapedName}')" class="action-button text-white bg-green-500 hover:bg-green-600 font-medium rounded-lg text-xs px-3 py-1.5 text-center"><i class="fas fa-plus"></i></button>
                                            <button onclick="openModal('subtract', '${user.id}', '${escapedName}')" class="action-button text-white bg-red-500 hover:bg-red-600 font-medium rounded-lg text-xs px-3 py-1.5 text-center"><i class="fas fa-minus"></i></button>
                                        </div>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        tableBody = '<tr><td colspan="5" class="py-4 px-6 text-center text-gray-500">Tidak ada pengguna yang cocok.</td></tr>';
                    }
                    $('#user-table-body').html(tableBody);
                }
            });
        }, 300);
    });
});

// Fungsi Modal
const modal = $('#action-modal');
const modalTitle = $('#modal-title');
const modalUserName = $('#modal-user-name');
const modalUserId = $('#modal-user-id');
const modalAction = $('#modal-action');
const modalAmount = $('#modal-amount');
const modalSubmitButton = $('#modal-submit-button');

function openModal(action, userId, userName) {
    modalUserId.val(userId);
    modalUserName.text(userName);
    modalAction.val(action); // ✅ FIX: Mengatur nilai aksi ('add' atau 'subtract')
    modalAmount.val('');

    if (action === 'add') {
        modalTitle.text('Tambah Saldo');
        modalSubmitButton.text('Konfirmasi Tambah Saldo').removeClass('bg-red-600 hover:bg-red-700').addClass('bg-green-600 hover:bg-green-700');
    } else {
        modalTitle.text('Kurangi Saldo');
        modalSubmitButton.text('Konfirmasi Kurangi Saldo').removeClass('bg-green-600 hover:bg-green-700').addClass('bg-red-600 hover:bg-red-700');
    }
    
    modal.removeClass('hidden');
}

function closeModal() {
    modal.addClass('hidden');
}
</script>
@endpush

