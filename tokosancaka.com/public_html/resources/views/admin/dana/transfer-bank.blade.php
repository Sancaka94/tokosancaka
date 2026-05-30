@extends('layouts.admin')

@section('title', 'Transfer Bank Corporate')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 3rem;
            border-color: #d1d5db;
            border-radius: 0.5rem;
            background-color: #f9fafb;
            display: flex;
            align-items: center;
            padding-left: 0.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: 10px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #374151;
            font-weight: 500;
        }
        .select2-search__field {
            outline: none !important;
        }
    </style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    
    {{-- BAGIAN ATAS: FORMULIR INQUIRY & TRANSFER --}}
    <div class="bg-white shadow-md rounded-lg p-6 max-w-4xl mx-auto mb-10 border border-gray-200">
        <div class="border-b pb-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Transfer ke Rekening Bank (Corporate)</h2>
            <p class="text-gray-600 mt-1">Formulir untuk memproses cek rekening (Inquiry) dan pencairan saldo ke rekening Bank pelanggan.</p>
        </div>

        {{-- ALERT PESAN --}}
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Berhasil</p>
                <p>{!! nl2br(e(session('success'))) !!}</p>
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Menunggu (Pending)</p>
                <p>{!! nl2br(e(session('warning'))) !!}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Gagal</p>
                <p>{!! nl2br(e(session('error'))) !!}</p>
            </div>
        @endif

        {{-- ALUR 1: FORM INQUIRY (MUNCUL JIKA BELUM ADA DATA REKENING VALID) --}}
        @if (!session('valid_account_name'))
            <form action="{{ route('customer.dana.bank_inquiry') }}" method="POST">
                @csrf
                
                <div class="mb-5">
                    <label for="affiliate_id" class="block text-gray-700 font-semibold mb-2">Cari Pelanggan (Nama / WA / Toko)</label>
                    <select name="affiliate_id" id="affiliate_id" class="w-full" required>
                        @if(old('affiliate_id'))
                            <option value="{{ old('affiliate_id') }}" selected>ID Pelanggan Terpilih: {{ old('affiliate_id') }}</option>
                        @endif
                    </select>
                </div>

                <div class="mb-5">
                    <label for="bank_code" class="block text-gray-700 font-semibold mb-2">Pilih Bank Tujuan</label>
                    <select name="bank_code" id="bank_code" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" required>
                        <option value="">-- Pilih Bank --</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->bank_code }}" {{ old('bank_code') == $bank->bank_code ? 'selected' : '' }}>
                                {{ $bank->bank_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-5">
                    <label for="account_no" class="block text-gray-700 font-semibold mb-2">Nomor Rekening</label>
                    <input type="text" name="account_no" id="account_no" value="{{ old('account_no') }}" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" 
                        placeholder="Contoh: 1234567890" required>
                </div>

                <div class="mb-6">
                    <label for="amount" class="block text-gray-700 font-semibold mb-2">Nominal Transfer</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <span class="text-gray-500 font-bold text-lg">Rp</span>
                        </div>
                        <input type="number" name="amount" id="amount" value="{{ old('amount') }}" min="10000" 
                            class="w-full pl-12 px-4 py-3 text-lg font-bold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" 
                            placeholder="10000" required>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-gray-200 pt-6">
                    <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition duration-300 flex justify-center items-center">
                        <i class="fas fa-search mr-2"></i> Cek Rekening (Inquiry)
                    </button>
                </div>
            </form>

        {{-- ALUR 2: FORM TRANSFER (MUNCUL SETELAH INQUIRY SUKSES) --}}
        @else
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6">
                <h3 class="text-lg font-bold text-blue-800 mb-3 border-b border-blue-200 pb-2">Hasil Cek Rekening Valid</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Bank Tujuan</p>
                        <p class="font-bold text-gray-800">{{ session('valid_bank_name') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Nomor Rekening</p>
                        <p class="font-bold text-gray-800">{{ old('account_no') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Atas Nama</p>
                        <p class="font-bold text-gray-800">{{ session('valid_account_name') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Nominal Transfer</p>
                        <p class="font-bold text-green-600 text-lg">Rp {{ number_format(old('amount'), 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('customer.dana.transfer_bank') }}" method="POST">
                @csrf
                <input type="hidden" name="affiliate_id" value="{{ old('affiliate_id') }}">
                <input type="hidden" name="bank_code" value="{{ old('bank_code') }}">
                <input type="hidden" name="account_no" value="{{ old('account_no') }}">
                <input type="hidden" name="account_name" value="{{ session('valid_account_name') }}">
                <input type="hidden" name="amount" value="{{ old('amount') }}">

                <div class="flex flex-col sm:flex-row items-center justify-end space-y-3 sm:space-y-0 sm:space-x-4 border-t border-gray-200 pt-6">
                    <a href="{{ url()->current() }}" class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 px-8 rounded-xl shadow-sm transition duration-300 text-center">
                        Batal / Ubah Data
                    </a>
                    <button type="submit" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition duration-300 flex justify-center items-center"
                        onclick="return confirm('PENTING: Saldo akan dipotong dan ditransfer ke rekening di atas. Lanjutkan?')">
                        <i class="fas fa-paper-plane mr-2"></i> Proses Transfer Sekarang
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- BAGIAN BAWAH: TABEL RIWAYAT TRANSAKSI --}}
    <div class="max-w-6xl mx-auto">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Riwayat Transfer Bank Corporate</h3>
        
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4">Ref. Transaksi</th>
                            <th class="px-6 py-4 text-center">ID User</th>
                            <th class="px-6 py-4">Bank & Rekening</th>
                            <th class="px-6 py-4">Nominal</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($transactions as $trx)
                            @php
                                $payload = json_decode($trx->response_payload, true);
                                $danaRef = $payload['referenceNo'] ?? '-';
                                $trxDate = $payload['transactionDate'] ?? \Carbon\Carbon::parse($trx->created_at)->format('Y-m-d\TH:i:sP');
                            @endphp
                            
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ \Carbon\Carbon::parse($trx->created_at)->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $trx->reference_no }}
                                </td>
                                <td class="px-6 py-4 text-sm text-center text-gray-700 font-bold">
                                    {{ $trx->affiliate_id }}
                                </td>
                                <td class="px-6 py-4 text-sm text-blue-600 font-medium">
                                    {{ $trx->phone }}
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-green-600">
                                    Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($trx->status === 'SUCCESS')
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Sukses</span>
                                    @elseif($trx->status === 'PENDING')
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">Gagal</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 flex justify-center space-x-2">
                                    
                                    <button type="button" onclick="openDetailModal('{{ $trx->reference_no }}', '{{ $danaRef }}', '{{ $trx->phone }}', 'Rp {{ number_format($trx->amount, 0, ',', '.') }}', '{{ $trxDate }}', '{{ $trx->status }}')" class="text-white bg-teal-500 hover:bg-teal-600 px-3 py-1.5 rounded-md text-xs font-bold transition-colors shadow-sm" title="Cek Detail">
                                        <i class="fas fa-info-circle mr-1"></i> Detail
                                    </button>

                                    {{-- TOMBOL CEK STATUS API (Pastikan route cek status mengarah ke checkTransferStatus) --}}
                                    @if($trx->status === 'PENDING')
                                        <form action="{{ route('customer.dana.check_transfer_status', $trx->id) }}" method="POST" onsubmit="return confirm('Cek status transaksi ini ke API Bank/DANA?');">
                                            @csrf
                                            <button type="submit" class="text-white bg-blue-500 hover:bg-blue-600 px-3 py-1.5 rounded-md text-xs font-bold transition-colors shadow-sm" title="Cek Status API">
                                                <i class="fas fa-sync-alt mr-1"></i> Cek
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                    <i class="fas fa-university text-4xl mb-3 text-gray-300 block"></i>
                                    Belum ada riwayat transfer bank.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(isset($transactions) && $transactions->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL DETAIL (Tetap Sama dengan Kode Awalmu) --}}
<div id="detailModal" class="fixed inset-0 z-[99] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true" onclick="closeDetailModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-teal-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-receipt text-teal-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">Detail Struk Transfer</h3>
                        <div class="mt-5 space-y-3">
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-sm text-gray-500">Status</span>
                                <span class="text-sm font-bold" id="modal-status"></span>
                            </div>
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-sm text-gray-500">No. Ref Sancaka</span>
                                <span class="text-sm font-bold text-gray-800" id="modal-ref-sancaka"></span>
                            </div>
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-sm text-gray-500">No. Ref Bank/DANA</span>
                                <span class="text-sm font-bold text-gray-800" id="modal-ref-dana"></span>
                            </div>
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-sm text-gray-500">Bank & Rekening</span>
                                <span class="text-sm font-bold text-gray-800" id="modal-phone"></span>
                            </div>
                            <div class="flex justify-between border-b border-gray-100 pb-2">
                                <span class="text-sm text-gray-500">Nominal Transfer</span>
                                <span class="text-sm font-bold text-green-600" id="modal-amount"></span>
                            </div>
                            <div class="flex justify-between pb-2">
                                <span class="text-sm text-gray-500">Waktu Transaksi</span>
                                <span class="text-sm font-bold text-gray-800" id="modal-date"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                <button type="button" onclick="closeDetailModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    Tutup
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
        // ==============================================================
        // 1. INISIALISASI SELECT2 UNTUK DROPDOWN BANK
        // ==============================================================
        $('#bank_code').select2({
            placeholder: '-- Ketik / Pilih Bank Tujuan --',
            allowClear: true
        });

        // ==============================================================
        // 2. INISIALISASI SELECT2 UNTUK PENCARIAN PELANGGAN
        // ==============================================================
        $('#affiliate_id').select2({
            placeholder: 'Ketik nama, nomor WA, atau nama toko...',
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
                inputTooShort: function() { return "Ketik minimal 2 huruf..."; },
                searching: function() { return "Mencari data..."; },
                noResults: function() { return "Data pengguna tidak ditemukan."; }
            }
        });

        // ==============================================================
        // 3. FITUR AUTO-FILL NOMOR REKENING & BANK
        // ==============================================================
        $('#affiliate_id').on('select2:select', function (e) {
            var data = e.params.data;

            // Isi Nomor Rekening otomatis jika ada di database
            if(data.bank_account_number) {
                $('#account_no').val(data.bank_account_number);
            } else {
                $('#account_no').val('');
            }

            // Pencocokan Bank otomatis
            if(data.bank_name) {
                var userBank = data.bank_name.toLowerCase();
                var matchedValue = null;

                // Looping semua pilihan bank di dropdown untuk mencari kecocokan kata
                // Misal: di tabel Pengguna 'BCA', di tabel Bank 'Bank BCA' -> akan otomatis cocok
                $('#bank_code option').each(function() {
                    var optionText = $(this).text().toLowerCase();
                    if (optionText.includes(userBank) || userBank.includes(optionText.replace('bank ', '').trim())) {
                        matchedValue = $(this).val();
                        return false; // Hentikan loop jika sudah ketemu
                    }
                });

                if (matchedValue) {
                    $('#bank_code').val(matchedValue).trigger('change');
                } else {
                    $('#bank_code').val('').trigger('change');
                }
            } else {
                $('#bank_code').val('').trigger('change');
            }
        });

        // Membersihkan input jika form pencarian disilang (X)
        $('#affiliate_id').on('select2:clear', function (e) {
            $('#account_no').val('');
            $('#bank_code').val('').trigger('change');
        });
    });

    // ==============================================================
    // 4. FUNGSI MODAL STRUK (TIDAK BERUBAH)
    // ==============================================================
    function openDetailModal(refSancaka, refDana, phone, amount, date, status) {
        document.getElementById('modal-ref-sancaka').innerText = refSancaka;
        document.getElementById('modal-ref-dana').innerText = refDana;
        document.getElementById('modal-phone').innerText = phone;
        document.getElementById('modal-amount').innerText = amount;
        document.getElementById('modal-date').innerText = date;
        
        const statusEl = document.getElementById('modal-status');
        if(status === 'SUCCESS') {
            statusEl.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Sukses';
            statusEl.className = 'text-sm font-bold text-green-600';
        } else if(status === 'PENDING') {
            statusEl.innerHTML = '<i class="fas fa-clock mr-1"></i> Pending';
            statusEl.className = 'text-sm font-bold text-yellow-600';
        } else {
            statusEl.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Gagal';
            statusEl.className = 'text-sm font-bold text-red-600';
        }

        document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }
</script>
@endpush
@endsection