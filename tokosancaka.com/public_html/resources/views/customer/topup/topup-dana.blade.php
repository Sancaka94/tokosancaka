@extends('layouts.customer')

@section('title', 'Top Up Saldo DANA')

@section('content')
    <div class="mb-6">
        <h3 class="text-3xl font-semibold text-gray-700 tracking-tight">Isi Saldo DANA</h3>
        <p class="text-gray-500 mt-1">Masukkan nomor DANA tujuan, pilih nominal, dan selesaikan pembayaran.</p>
    </div>

    <div class="mt-4 space-y-8">
        {{-- ========================================== --}}
        {{-- 1. BAGIAN FORM TOP UP                      --}}
        {{-- ========================================== --}}
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="p-6 md:p-8">

                {{-- Alert Error / Success / Warning --}}
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 text-lg mr-3"></i>
                            <strong class="font-bold text-red-800">Oops! Terjadi kesalahan.</strong>
                        </div>
                        <ul class="mt-2 ml-7 list-disc list-inside text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-500 text-lg mr-3"></i>
                            <strong class="font-bold text-red-800 mr-2">Error!</strong>
                            <span class="block sm:inline text-red-700">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 text-lg mr-3"></i>
                            <strong class="font-bold text-green-800 mr-2">Berhasil!</strong>
                            <span class="block sm:inline text-green-700">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-lg mr-3"></i>
                            <strong class="font-bold text-yellow-800 mr-2">Perhatian!</strong>
                            <span class="block sm:inline text-yellow-700">{{ session('warning') }}</span>
                        </div>
                    </div>
                @endif

                <form action="{{ route('customer.topupdana.store') }}" method="POST">
                    @csrf
                    {{-- INPUT NOMOR DANA --}}
                    <div class="mb-8">
                        <label class="block text-lg font-bold text-gray-800 mb-3">Nomor DANA Tujuan</label>
                        <div class="relative rounded-xl shadow-sm group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <i class="fas fa-mobile-alt text-gray-400 group-focus-within:text-blue-600 text-xl transition-colors"></i>
                            </div>
                            <input type="number" name="dana_number" id="dana_number"
                                class="block w-full pl-14 pr-4 py-4 text-xl font-bold text-gray-800 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-blue-500 transition-colors bg-gray-50 focus:bg-white"
                                placeholder="Contoh: 081234567890" required value="{{ old('dana_number') }}">
                        </div>
                    </div>

                    {{-- INPUT NOMINAL --}}
                    <div class="mb-10">
                        <label class="block text-lg font-bold text-gray-800 mb-4">Pilih Nominal Top Up</label>
                        <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mb-5">
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 shadow-sm flex flex-col items-center justify-center group" data-amount="10000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">10.000</span>
                            </button>
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 shadow-sm flex flex-col items-center justify-center group" data-amount="20000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">20.000</span>
                            </button>
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 shadow-sm flex flex-col items-center justify-center group" data-amount="50000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">50.000</span>
                            </button>
                        </div>
                        <div class="relative rounded-xl shadow-sm group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <span class="text-gray-400 group-focus-within:text-blue-600 text-xl font-bold transition-colors">Rp</span>
                            </div>
                            <input type="number" name="amount" id="amount"
                                class="block w-full pl-14 pr-4 py-4 text-2xl font-bold text-gray-800 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-blue-500 transition-colors bg-gray-50 focus:bg-white"
                                placeholder="Nominal lainnya (Min. 10000)" min="10000" required value="{{ old('amount') }}">
                        </div>
                    </div>

                    <div class="relative py-4">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-4 bg-white text-sm text-gray-400 font-medium">METODE PEMBAYARAN</span>
                        </div>
                    </div>

                    {{-- INFORMASI SALDO USER --}}
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-5 mb-6 flex items-center justify-between shadow-sm mt-4">
                        <div>
                            <span class="block text-sm font-bold text-blue-800 mb-1">Saldo Sancaka Anda saat ini</span>
                            <span class="block text-3xl font-black text-blue-700">Rp {{ number_format(auth()->user()->saldo ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="h-14 w-14 bg-white rounded-full flex items-center justify-center shadow-md text-blue-600">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                    </div>

                    {{-- PILIH METODE --}}
                    <div class="space-y-8 mt-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="payment_method" value="POTONG SALDO" class="peer sr-only" required>
                                <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-green-400 peer-checked:border-green-600 peer-checked:bg-green-50 peer-checked:shadow-md transition-all flex flex-col items-center justify-center text-center">
                                    <div class="h-12 w-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-3">
                                        <i class="fas fa-wallet text-2xl"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-800">Potong Saldo</span>
                                    <div class="absolute top-3 right-3 text-green-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                        <i class="fas fa-check-circle text-xl"></i>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer group">
                                <input type="radio" name="payment_method" value="DOKU_JOKUL" class="peer sr-only" required>
                                <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center justify-center text-center">
                                    <img src="https://tokosancaka.com/public/storage/logo/doku-ewallet.png" class="h-12 object-contain mb-3 rounded-lg shadow-sm p-1">
                                    <span class="text-sm font-bold text-gray-800">DOKU</span>
                                    <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                        <i class="fas fa-check-circle text-xl"></i>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer group">
                                <input type="radio" name="payment_method" value="QRIS" class="peer sr-only" required>
                                <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center justify-center text-center">
                                    <span class="text-xl font-black text-blue-800 mb-3 flex items-center h-12">QRIS</span>
                                    <span class="text-sm font-bold text-gray-800">Tripay (Otomatis)</span>
                                    <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                        <i class="fas fa-check-circle text-xl"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="submit-section" class="mt-12 pt-8 border-t border-gray-200">
                        <button type="submit" class="w-full py-5 px-6 rounded-xl shadow-xl shadow-blue-600/20 text-xl font-extrabold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 transition-all transform hover:-translate-y-1 flex items-center justify-center">
                            <i class="fas fa-bolt mr-3 text-blue-200"></i> BAYAR & ISI SALDO DANA
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- 2. TABEL RIWAYAT TRANSAKSI                 --}}
        {{-- ========================================== --}}
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mt-8">
            <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                <h4 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-history text-blue-600 mr-2"></i> Riwayat Top Up
                </h4>
                
                {{-- Form Bulk Delete --}}
                <form id="bulkDeleteForm" action="{{ route('customer.topupdana.bulk_destroy') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" onclick="confirmBulkDelete()" class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-2 px-4 rounded-lg shadow-sm transition">
                        <i class="fas fa-trash-alt mr-1"></i> Hapus Terpilih
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="bg-gray-50 text-xs text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-4 text-center">
                                <input type="checkbox" id="checkAll" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-4">No. Invoice</th>
                            <th class="px-6 py-4">Tujuan</th>
                            <th class="px-6 py-4">Nominal</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions ?? [] as $trx)
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" class="checkItem w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="{{ $trx->id }}">
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $trx->reference_id }}
                                    <span class="block text-xs text-gray-400 font-normal">{{ \Carbon\Carbon::parse($trx->created_at)->format('d M Y, H:i') }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                                            <i class="fas fa-phone-alt text-xs"></i>
                                        </div>
                                        <span class="font-bold">{{ $trx->target_phone }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-800">
                                    Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if ($trx->status === 'SUCCESS')
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">SUKSES</span>
                                    @elseif (in_array($trx->status, ['PENDING_PAYMENT', 'PROCESSING', 'PENDING_DANA']))
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">DIPROSES</span>
                                    @else
                                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold">GAGAL</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    {{-- Tombol Detail (DIPERBAIKI: Tambahan parameter 6 untuk danaRef) --}}
                                    <button onclick="showDetail('{{ $trx->reference_id }}', '{{ $trx->target_phone }}', '{{ number_format($trx->amount, 0, ',', '.') }}', '{{ $trx->status }}', '{{ $trx->payment_method }}', '{{ $trx->dana_ref ?? '-' }}')" 
                                        class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 p-2 rounded-lg transition" title="Detail Transaksi">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    {{-- Tombol Cek Status (HANYA MUNCUL JIKA PENDING) --}}
                                    @if (in_array($trx->status, ['PENDING_PAYMENT', 'PROCESSING', 'PENDING_DANA']))
                                    <form action="{{ route('topupdana.check_status') }}" method="POST" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="reference_id" value="{{ $trx->reference_id }}">
                                        <button type="submit" class="text-yellow-500 hover:text-yellow-700 bg-yellow-50 hover:bg-yellow-100 p-2 rounded-lg transition" title="Cek Status DANA">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                    @endif

                                    {{-- Tombol Hapus --}}
                                    <form id="hapusForm-{{ $trx->id }}" action="{{ route('customer.topupdana.destroy', $trx->id) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition" title="Hapus Riwayat">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300 block"></i>
                                    Belum ada riwayat top up DANA.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="p-6 border-t border-gray-100">
                {{ $transactions->links() ?? '' }}
            </div>
        </div>
    </div>

    {{-- MODAL DETAIL (Hidden) --}}
    <div id="detailModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalContent">
            <div class="bg-blue-600 p-4 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg"><i class="fas fa-receipt mr-2"></i> Detail Transaksi</h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-6 space-y-4 text-sm text-gray-700">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold">Invoice:</span>
                    <span id="modInv" class="font-mono text-gray-900 font-bold"></span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold">Nomor Tujuan:</span>
                    <span id="modPhone" class="text-gray-900"></span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold">Nominal:</span>
                    <span class="text-gray-900 font-bold">Rp <span id="modAmount"></span></span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold">Metode Bayar:</span>
                    <span id="modMethod" class="text-gray-900"></span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold">DANA Ref:</span>
                    <span id="modDanaRef" class="font-mono text-gray-900"></span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold">Status DANA:</span>
                    <span id="modStatus" class="font-bold"></span>
                </div>
            </div>
            <div class="p-4 bg-gray-50 text-right">
                <button onclick="closeModal()" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-6 rounded-lg font-bold">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Logika Pilih Nominal Cepat
            const $input = $('#amount');
            $('.btn-quick-amount').on('click', function() {
                let val = $(this).data('amount');
                $('.btn-quick-amount').removeClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1').addClass('bg-blue-50/50 text-blue-700 border-blue-100');
                $(this).removeClass('bg-blue-50/50 text-blue-700 border-blue-100').addClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1');
                $input.val(val).trigger('change');
            });
            $input.on('input', function() {
                $('.btn-quick-amount').removeClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1').addClass('bg-blue-50/50 text-blue-700 border-blue-100');
            });

            // Logika Checkbox Master (Pilih Semua)
            $('#checkAll').on('change', function() {
                $('.checkItem').prop('checked', $(this).prop('checked'));
                toggleBulkDeleteBtn();
            });

            // Logika Checkbox Individual
            $('.checkItem').on('change', function() {
                if ($('.checkItem:checked').length === $('.checkItem').length) {
                    $('#checkAll').prop('checked', true);
                } else {
                    $('#checkAll').prop('checked', false);
                }
                toggleBulkDeleteBtn();
            });
        });

        // Muncul/Hilangkan Tombol Bulk Delete
        function toggleBulkDeleteBtn() {
            if ($('.checkItem:checked').length > 0) {
                $('#bulk-delete-form').removeClass('hidden');
            } else {
                $('#bulk-delete-form').addClass('hidden');
            }
        }

        // Eksekusi Bulk Delete
        function confirmBulkDelete() {
            if (confirm('Yakin ingin menghapus riwayat top up yang dipilih?')) {
                let form = $('#bulk-delete-form');
                
                // Bersihkan input ids[] lama agar tidak tumpang tindih jika di-klik berkali-kali
                form.find('input[name="ids[]"]').remove();
                
                // Buat elemen input hidden untuk setiap checkbox yang dicentang
                $('.checkItem:checked').each(function() {
                    form.append('<input type="hidden" name="ids[]" value="' + $(this).val() + '">');
                });
                
                form.submit();
            }
        }

        // Modal Detail Logic (DIPERBAIKI: Menambahkan parameter danaRef)
        function showDetail(inv, phone, amount, status, method, danaRef) {
            $('#modInv').text(inv);
            $('#modPhone').text(phone);
            $('#modAmount').text(amount);
            $('#modMethod').text(method);
            $('#modDanaRef').text(danaRef || '-'); // Sekarang danaRef akan terdefinisi dengan aman
            
            let statusColor = status === 'SUCCESS' ? 'text-green-600' : (status.includes('FAIL') ? 'text-red-600' : 'text-yellow-600');
            $('#modStatus').text(status).removeClass('text-green-600 text-red-600 text-yellow-600').addClass(statusColor);

            $('#detailModal').removeClass('hidden');
            setTimeout(() => {
                $('#modalContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            }, 50);
        }

        function closeModal() {
            $('#modalContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
            setTimeout(() => {
                $('#detailModal').addClass('hidden');
            }, 300);
        }
    </script>
    @endpush
@endsection