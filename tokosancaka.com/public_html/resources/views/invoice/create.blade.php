@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <form action="{{ route('invoice.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-8">Buat Invoice Baru</h2>

            {{-- Baris 1: No Invoice & Tanggal --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">No. Invoice</label>
                    <input type="text" name="invoice_no" value="{{ $invoiceNo ?? 'INV-'.date('Ymd').'-'.rand(100,999) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-50 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
            </div>

            {{-- Baris 2: Penerima & Perusahaan --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Penerima</label>
                    <input type="text" name="customer_name" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Perusahaan</label>
                    <input type="text" name="company_name" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

            </div>

            {{-- Baris 3: Alamat --}}
                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Lengkap</label>
                    <textarea name="alamat" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Masukkan alamat lengkap penerima..."></textarea>
                </div>

            {{-- Tabel Produk / Jasa --}}
            <div class="overflow-x-auto mb-4">
                <table class="w-full min-w-[800px] text-left border-collapse" id="itemTable">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="py-3 px-2 font-bold text-gray-800 w-1/2">Deskripsi</th>
                            <th class="py-3 px-2 font-bold text-gray-800 w-24">Qty</th>
                            <th class="py-3 px-2 font-bold text-gray-800 w-48">Harga</th>
                            <th class="py-3 px-2 font-bold text-gray-800 w-48">Total</th>
                            <th class="py-3 px-2 font-bold text-gray-800 text-center w-16">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="item-row">
                            <td class="py-3 px-2">
                                <input type="text" name="items[0][description]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </td>
                            <td class="py-3 px-2">
                                <input type="number" name="items[0][qty]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 qty text-center" value="1" min="1" required>
                            </td>
                            <td class="py-3 px-2">
                                <input type="number" name="items[0][price]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 price" value="0" min="0" required>
                            </td>
                            <td class="py-3 px-2">
                                <input type="text" name="items[0][total]" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 total-display text-gray-600 font-medium" readonly value="0">
                                <input type="hidden" name="items[0][total_raw]" class="total-raw" value="0">
                            </td>
                            <td class="py-3 px-2 text-center">
                                <button type="button" class="bg-red-500 hover:bg-red-600 text-white rounded-md w-8 h-8 flex items-center justify-center transition-colors removeRow cursor-not-allowed opacity-50" disabled>
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Tombol Tambah Baris --}}
            <button type="button" id="addRow" class="w-full bg-[#6c757d] hover:bg-gray-600 text-white font-medium py-2.5 rounded-lg transition-colors flex items-center justify-center mb-8">
                <i class="fa-solid fa-plus mr-2"></i> Tambah Baris
            </button>

            {{-- Baris Keterangan (TEXTAREA BARU) --}}
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan / Catatan Tambahan (Opsional)</label>
                <textarea name="keterangan" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400" placeholder="Tuliskan catatan, syarat pembayaran, atau informasi lainnya di sini..."></textarea>
            </div>

            <hr class="border-gray-200 mb-6">

            {{-- Footer (Upload & Subtotal) --}}
            <div class="flex flex-col md:flex-row justify-between items-end gap-6">
                <div class="w-full md:w-1/2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Tanda Tangan (PNG Transparan Disarankan)</label>
                    <input type="file" name="signature" accept="image/*" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2.5 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100
                        border border-gray-300 rounded-lg cursor-pointer bg-white">
                </div>

                <div class="w-full md:w-1/2 text-right">
                    <div class="text-2xl font-bold text-gray-800">
                        Subtotal: Rp <span id="displaySubtotal">0</span>
                    </div>
                    <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
                </div>
            </div>

            {{-- Action Button --}}
            <div class="mt-10 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition-colors flex items-center">
                    <i class="fa-solid fa-save mr-2"></i> Simpan & Cetak Invoice
                </button>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.querySelector('#itemTable tbody');
        const addRowBtn = document.getElementById('addRow');
        let rowIdx = 1; // Start from 1 since 0 is already there

        // Format angka ke format Rupiah
        const formatRupiah = (number) => {
            return new Intl.NumberFormat('id-ID').format(number);
        };

        // Fungsi hitung per baris dan subtotal
        const calculateTotals = () => {
            let subtotal = 0;
            const rows = document.querySelectorAll('.item-row');

            rows.forEach((row) => {
                const qty = parseFloat(row.querySelector('.qty').value) || 0;
                const price = parseFloat(row.querySelector('.price').value) || 0;
                const total = qty * price;

                // Update text display
                row.querySelector('.total-display').value = formatRupiah(total);
                // Update hidden input for raw number to submit to DB
                row.querySelector('.total-raw').value = total;

                subtotal += total;
            });

            document.getElementById('displaySubtotal').innerText = formatRupiah(subtotal);
            document.getElementById('inputSubtotal').value = subtotal;
        };

        // Tambah Baris
        addRowBtn.addEventListener('click', () => {
            const tr = document.createElement('tr');
            tr.className = 'item-row border-t border-gray-100';
            tr.innerHTML = `
                <td class="py-3 px-2">
                    <input type="text" name="items[${rowIdx}][description]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </td>
                <td class="py-3 px-2">
                    <input type="number" name="items[${rowIdx}][qty]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 qty text-center" value="1" min="1" required>
                </td>
                <td class="py-3 px-2">
                    <input type="number" name="items[${rowIdx}][price]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 price" value="0" min="0" required>
                </td>
                <td class="py-3 px-2">
                    <input type="text" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 total-display text-gray-600 font-medium" readonly value="0">
                    <input type="hidden" name="items[${rowIdx}][total_raw]" class="total-raw" value="0">
                </td>
                <td class="py-3 px-2 text-center">
                    <button type="button" class="bg-red-500 hover:bg-red-600 text-white rounded-md w-8 h-8 flex items-center justify-center transition-colors removeRow">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(tr);
            rowIdx++;

            // Re-enable delete button if there's more than 1 row
            updateRemoveButtons();
        });

        // Event Delegation untuk hitung total dan hapus baris
        tableBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('qty') || e.target.classList.contains('price')) {
                calculateTotals();
            }
        });

        tableBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.removeRow');
            if (btn && !btn.disabled) {
                btn.closest('tr').remove();
                calculateTotals();
                updateRemoveButtons();
            }
        });

        // Fungsi untuk disable tombol hapus jika sisa 1 baris
        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            const btns = document.querySelectorAll('.removeRow');
            if (rows.length === 1) {
                btns[0].disabled = true;
                btns[0].classList.add('cursor-not-allowed', 'opacity-50');
            } else {
                btns.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('cursor-not-allowed', 'opacity-50');
                });
            }
        }

        // Initialize disable state
        updateRemoveButtons();
    });
</script>
@endpush
@endsection
