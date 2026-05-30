@extends('layouts.admin')

@section('title', 'Top Up Saldo DANA Corporate')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Penyesuaian tema Select2 agar menyatu sempurna dengan Tailwind CSS */
        .select2-container .select2-selection--single {
            height: 3.2rem;
            border-color: #e5e7eb; /* tailwind gray-200 */
            border-radius: 0.75rem; /* tailwind rounded-xl */
            background-color: #f8fafc; /* tailwind gray-50 */
            display: flex;
            align-items: center;
            padding-left: 0.75rem;
            transition: all 0.2s ease-in-out;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #334155; /* tailwind slate-700 */
            font-weight: 500;
        }
        /* Efek Focus menyerupai Tailwind Ring */
        .select2-container--open .select2-selection--single,
        .select2-container--focus .select2-selection--single {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
            outline: none !important;
        }
        .select2-search__field {
            outline: none !important;
            border-radius: 0.5rem !important;
        }
    </style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    
    {{-- BAGIAN ATAS: FORMULIR TOP UP --}}
    <div class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6 md:p-8 mb-10 transition-all hover:shadow-md">
        <div class="flex items-center space-x-4 border-b border-slate-100 pb-5 mb-6">
            <div class="bg-blue-50 text-blue-600 p-3 rounded-xl">
                <i class="fas fa-wallet text-2xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Top Up DANA Pelanggan</h2>
                <p class="text-slate-500 mt-1 text-sm">Cairkan komisi aplikasi ke saldo DANA melalui Merchant Deposit Corporate.</p>
            </div>
        </div>

        {{-- ALERT PESAN --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 mb-6 rounded-xl flex items-start">
                <i class="fas fa-check-circle mt-1 mr-3 text-emerald-500"></i>
                <div>
                    <p class="font-bold text-emerald-800">Berhasil</p>
                    <p class="text-sm mt-1">{!! nl2br(e(session('success'))) !!}</p>
                </div>
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-200 text-amber-700 p-4 mb-6 rounded-xl flex items-start">
                <i class="fas fa-clock mt-1 mr-3 text-amber-500"></i>
                <div>
                    <p class="font-bold text-amber-800">Menunggu (Pending)</p>
                    <p class="text-sm mt-1">{!! nl2br(e(session('warning'))) !!}</p>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 mb-6 rounded-xl flex items-start">
                <i class="fas fa-times-circle mt-1 mr-3 text-rose-500"></i>
                <div>
                    <p class="font-bold text-rose-800">Gagal Memproses</p>
                    <p class="text-sm mt-1">{!! nl2br(e(session('error'))) !!}</p>
                </div>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 mb-6 rounded-xl flex items-start">
                <i class="fas fa-exclamation-triangle mt-1 mr-3 text-rose-500"></i>
                <div>
                    <p class="font-bold text-rose-800">Mohon periksa kembali input Anda:</p>
                    <ul class="list-disc ml-5 mt-2 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- FORMULIR --}}
        <form action="{{ route('customer.dana.topup_corporate') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="affiliate_id" class="block text-slate-700 font-bold mb-2">Cari Pelanggan</label>
                    <select name="affiliate_id" id="affiliate_id" class="w-full" required>
                        @if(old('affiliate_id'))
                            <option value="{{ old('affiliate_id') }}" selected>ID Pelanggan: {{ old('affiliate_id') }}</option>
                        @endif
                    </select>
                    <p class="text-xs text-slate-500 mt-2 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i> Saldo Pelanggan akan dipotong otomatis.
                    </p>
                </div>

                <div>
                    <label for="phone" class="block text-slate-700 font-bold mb-2">Nomor HP DANA Tujuan</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <i class="fas fa-mobile-alt text-slate-400"></i>
                        </div>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" 
                            class="w-full pl-11 px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50 text-slate-700 font-medium transition-all" 
                            placeholder="Terisi otomatis..." required>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <label for="amount" class="block text-slate-700 font-bold mb-2">Nominal Top Up</label>
                <div class="relative max-w-md">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-5 pointer-events-none">
                        <span class="text-slate-500 font-bold text-xl">Rp</span>
                    </div>
                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}" min="1000" 
                        class="w-full pl-14 px-4 py-4 text-2xl font-bold border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50 text-slate-800 transition-all placeholder-slate-300" 
                        placeholder="10.000" required>
                </div>
                <p class="text-xs text-slate-500 mt-2">*Minimal pengiriman DANA Rp 1.000</p>
            </div>

            <div class="flex items-center justify-end border-t border-slate-100 pt-6">
                <button type="submit" 
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-500/30 transition-all transform hover:-translate-y-0.5 flex justify-center items-center"
                    onclick="return confirm('PENTING: Pastikan Saldo Corporate Sancaka mencukupi.\n\nApakah Anda yakin ingin memproses top up DANA ini?')">
                    <i class="fas fa-paper-plane mr-2"></i> Proses Pencairan DANA
                </button>
            </div>
        </form>
    </div>

    {{-- BAGIAN BAWAH: TABEL RIWAYAT TRANSAKSI & CRUD --}}
    <div>
        <div class="flex flex-col sm:flex-row justify-between items-center mb-5 space-y-3 sm:space-y-0">
            <h3 class="text-xl font-bold text-slate-800">Riwayat Transaksi Terakhir</h3>
            
            {{-- Tombol Hapus Massal --}}
            <button type="button" onclick="submitBulkDelete()" id="btnBulkDelete" class="bg-rose-500 hover:bg-rose-600 text-white font-bold py-2 px-5 rounded-xl shadow-sm transition-colors text-sm flex items-center hidden">
                <i class="fas fa-trash-alt mr-2"></i> Hapus Terpilih
            </button>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            {{-- FORM UNTUK BULK DELETE --}}
            <form id="bulkDeleteForm" action="{{ route('customer.dana.bulk_destroy_transaction') }}" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-4 text-center w-10">
                                    <input type="checkbox" id="selectAll" class="rounded border-slate-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 cursor-pointer">
                                </th>
                                <th class="px-6 py-4">Waktu</th>
                                <th class="px-6 py-4">No. Referensi</th>
                                <th class="px-6 py-4 text-center">User</th>
                                <th class="px-6 py-4">Target DANA</th>
                                <th class="px-6 py-4">Nominal</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($transactions as $trx)
                                @php
                                    $payload = json_decode($trx->response_payload, true);
                                    $danaRef = $payload['referenceNo'] ?? '-';
                                    $trxDate = $payload['transactionDate'] ?? \Carbon\Carbon::parse($trx->created_at)->format('Y-m-d\TH:i:sP');
                                @endphp
                                
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox" name="ids[]" value="{{ $trx->id }}" class="rowCheckbox rounded border-slate-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        {{ \Carbon\Carbon::parse($trx->created_at)->format('d M Y, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-slate-800">
                                        {{ $trx->reference_no }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-slate-100 text-slate-700 font-bold px-2.5 py-1 rounded-lg text-xs">{{ $trx->affiliate_id }}</span>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-blue-600">
                                        {{ $trx->phone }}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-emerald-600">
                                        Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($trx->status === 'SUCCESS')
                                            <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">SUKSES</span>
                                        @elseif($trx->status === 'PENDING')
                                            <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full bg-amber-100 text-amber-700 border border-amber-200">PENDING</span>
                                        @else
                                            <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full bg-rose-100 text-rose-700 border border-rose-200">GAGAL</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 flex justify-center space-x-2">
                                        {{-- TOMBOL DETAIL --}}
                                        <button type="button" onclick="openDetailModal('{{ $trx->reference_no }}', '{{ $danaRef }}', '{{ $trx->phone }}', 'Rp {{ number_format($trx->amount, 0, ',', '.') }}', '{{ $trxDate }}', '{{ $trx->status }}')" class="text-slate-500 hover:text-blue-600 bg-white hover:bg-blue-50 border border-slate-200 h-8 w-8 rounded-lg flex items-center justify-center transition-colors shadow-sm" title="Lihat Detail">
                                            <i class="fas fa-file-invoice"></i>
                                        </button>

                                        {{-- TOMBOL CEK API --}}
                                        @if($trx->status === 'PENDING')
                                            <button type="button" onclick="event.preventDefault(); if(confirm('Cek status transaksi ini langsung ke sistem DANA?')) document.getElementById('cekStatusForm-{{ $trx->id }}').submit();" class="text-amber-500 hover:text-amber-700 bg-white hover:bg-amber-50 border border-slate-200 h-8 w-8 rounded-lg flex items-center justify-center transition-colors shadow-sm" title="Sinkronisasi Status">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        @endif
                                        
                                        {{-- TOMBOL HAPUS --}}
                                        <button type="button" onclick="event.preventDefault(); if(confirm('Yakin ingin menghapus permanen riwayat transaksi ini?')) document.getElementById('hapusForm-{{ $trx->id }}').submit();" class="text-rose-500 hover:text-rose-700 bg-white hover:bg-rose-50 border border-slate-200 h-8 w-8 rounded-lg flex items-center justify-center transition-colors shadow-sm" title="Hapus Riwayat">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="bg-slate-50 h-16 w-16 rounded-full flex items-center justify-center mb-3">
                                                <i class="fas fa-inbox text-2xl text-slate-300"></i>
                                            </div>
                                            <p class="font-medium">Belum ada riwayat pencairan.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            {{-- Form Tersembunyi untuk Action Buttons --}}
            @foreach($transactions as $trx)
                @if($trx->status === 'PENDING')
                    <form id="cekStatusForm-{{ $trx->id }}" action="{{ route('customer.dana.check_topup_status') }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="reference_no" value="{{ $trx->reference_no }}">
                        <input type="hidden" name="affiliate_id" value="{{ $trx->affiliate_id }}">
                    </form>
                @endif
                <form id="hapusForm-{{ $trx->id }}" action="{{ route('customer.dana.destroy_topup', $trx->id) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
            
            {{-- Navigasi Pagination --}}
            @if(isset($transactions) && $transactions->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL STRUK/RESI DIGITAL --}}
<div id="detailModal" class="fixed inset-0 z-[99] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeDetailModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full border border-slate-100">
            <div class="bg-blue-600 px-6 py-4 text-center">
                <i class="fas fa-check-circle text-white text-4xl mb-2" id="modal-header-icon"></i>
                <h3 class="text-xl font-bold text-white" id="modal-title">Struk Transaksi</h3>
            </div>
            <div class="px-6 pt-5 pb-6">
                <div class="space-y-4">
                    <div class="flex justify-between border-b border-slate-100 pb-3">
                        <span class="text-sm text-slate-500">Status</span>
                        <span class="text-sm font-bold" id="modal-status"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-3">
                        <span class="text-sm text-slate-500">No. Ref Sancaka</span>
                        <span class="text-sm font-bold text-slate-800" id="modal-ref-sancaka"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-3">
                        <span class="text-sm text-slate-500">No. Ref DANA</span>
                        <span class="text-sm font-bold text-slate-800" id="modal-ref-dana"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-3">
                        <span class="text-sm text-slate-500">No. Pelanggan</span>
                        <span class="text-sm font-bold text-slate-800" id="modal-phone"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-3">
                        <span class="text-sm text-slate-500">Waktu Trx</span>
                        <span class="text-sm font-medium text-slate-700" id="modal-date"></span>
                    </div>
                    <div class="flex flex-col items-center pt-2">
                        <span class="text-xs text-slate-400 uppercase tracking-widest font-bold mb-1">Nominal Top Up</span>
                        <span class="text-3xl font-black text-blue-600" id="modal-amount"></span>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-4 flex justify-center border-t border-slate-200">
                <button type="button" onclick="closeDetailModal()" class="w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 transition-colors">
                    Tutup Resi
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi Select2 AJAX
        $('#affiliate_id').select2({
            placeholder: 'Ketik nama, no WA, atau toko...',
            allowClear: true,
            ajax: {
                url: '{{ route("customer.dana.search_pengguna") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term }; 
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: true
            },
            minimumInputLength: 2,
            language: {
                inputTooShort: () => "Ketik minimal 2 huruf...",
                searching: () => "Mencari data...",
                noResults: () => "Data tidak ditemukan."
            }
        });

        // Autocomplete phone number
        $('#affiliate_id').on('select2:select', function (e) {
            var data = e.params.data;
            if(data.phone) {
                let cleanPhone = data.phone.replace(/\D/g, '');
                $('#phone').val(cleanPhone);
            }
        });

        // Bulk Delete Logic
        const selectAll = $('#selectAll');
        const rowCheckboxes = $('.rowCheckbox');
        const btnBulkDelete = $('#btnBulkDelete');

        function toggleBulkDeleteButton() {
            if ($('.rowCheckbox:checked').length > 0) {
                btnBulkDelete.removeClass('hidden');
            } else {
                btnBulkDelete.addClass('hidden');
            }
        }

        selectAll.on('change', function() {
            rowCheckboxes.prop('checked', $(this).prop('checked'));
            toggleBulkDeleteButton();
        });

        rowCheckboxes.on('change', function() {
            if (!$(this).prop('checked')) {
                selectAll.prop('checked', false);
            } else if ($('.rowCheckbox:checked').length === rowCheckboxes.length) {
                selectAll.prop('checked', true);
            }
            toggleBulkDeleteButton();
        });
    });

    function submitBulkDelete() {
        if (confirm('Yakin ingin menghapus semua data transaksi yang dipilih? Data yang dihapus tidak bisa dikembalikan.')) {
            $('#bulkDeleteForm').submit();
        }
    }

    function openDetailModal(refSancaka, refDana, phone, amount, date, status) {
        document.getElementById('modal-ref-sancaka').innerText = refSancaka;
        document.getElementById('modal-ref-dana').innerText = refDana;
        document.getElementById('modal-phone').innerText = phone;
        document.getElementById('modal-amount').innerText = amount;
        document.getElementById('modal-date').innerText = date;
        
        const statusEl = document.getElementById('modal-status');
        const headerIcon = document.getElementById('modal-header-icon');
        const modalHeader = headerIcon.parentElement;
        
        if(status === 'SUCCESS') {
            statusEl.innerText = 'BERHASIL';
            statusEl.className = 'text-sm font-bold text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded';
            headerIcon.className = 'fas fa-check-circle text-white text-5xl mb-3';
            modalHeader.className = 'bg-blue-600 px-6 py-6 text-center';
        } else if(status === 'PENDING') {
            statusEl.innerText = 'PENDING';
            statusEl.className = 'text-sm font-bold text-amber-600 bg-amber-100 px-2 py-0.5 rounded';
            headerIcon.className = 'fas fa-clock text-white text-5xl mb-3';
            modalHeader.className = 'bg-amber-500 px-6 py-6 text-center';
        } else {
            statusEl.innerText = 'GAGAL';
            statusEl.className = 'text-sm font-bold text-rose-600 bg-rose-100 px-2 py-0.5 rounded';
            headerIcon.className = 'fas fa-times-circle text-white text-5xl mb-3';
            modalHeader.className = 'bg-rose-500 px-6 py-6 text-center';
        }

        document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }
</script>
@endpush
@endsection