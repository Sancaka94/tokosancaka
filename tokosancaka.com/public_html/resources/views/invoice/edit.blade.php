@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <form action="{{ route('invoice.update', $invoice->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <div class="flex justify-between items-center mb-8 border-b pb-4">
                <h2 class="text-2xl font-semibold text-gray-800">Edit Invoice</h2>
                <span class="bg-amber-100 text-amber-700 font-bold px-3 py-1 rounded-md text-sm border border-amber-200">Mode Edit</span>
            </div>

            {{-- Baris 1: No Invoice & Tanggal --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">No. Invoice</label>
                    <input type="text" name="invoice_no" value="{{ $invoice->invoice_no }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-50 text-gray-600 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="date" value="{{ $invoice->date }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>

            {{-- Baris 2: Penerima & Perusahaan --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Penerima</label>
                    <input type="text" name="customer_name" value="{{ $invoice->customer_name }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Perusahaan</label>
                    <input type="text" name="company_name" value="{{ $invoice->company_name }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Baris 3: Alamat --}}
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Lengkap</label>
                <textarea name="alamat" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Alamat pelanggan...">{{ $invoice->alamat }}</textarea>
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
                        @foreach($invoice->items as $index => $item)
                        <tr class="item-row">
                            <td class="py-3 px-2">
                                <input type="text" name="items[{{ $index }}][description]" value="{{ $item->description }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </td>
                            <td class="py-3 px-2">
                                <input type="number" name="items[{{ $index }}][qty]" value="{{ $item->qty }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 qty text-center input-calc" min="1" required>
                            </td>
                            <td class="py-3 px-2">
                                <input type="number" name="items[{{ $index }}][price]" value="{{ round($item->price) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 price input-calc" min="0" required>
                            </td>
                            <td class="py-3 px-2">
                                <input type="text" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 total-display text-gray-600 font-medium" readonly value="0">
                                <input type="hidden" name="items[{{ $index }}][total_raw]" class="total-raw" value="{{ round($item->total) }}">
                            </td>
                            <td class="py-3 px-2 text-center">
                                <button type="button" class="bg-red-500 text-white rounded-md w-8 h-8 flex items-center justify-center removeRow">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="button" id="addRow" class="w-full bg-gray-500 hover:bg-gray-600 text-white font-medium py-2.5 rounded-lg transition-colors flex items-center justify-center mb-8">
                <i class="fa-solid fa-plus mr-2"></i> Tambah Baris
            </button>

            {{-- Keterangan --}}
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan / Catatan Tambahan</label>
                <textarea name="keterangan" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Syarat pembayaran, info rekening, dll...">{{ $invoice->keterangan }}</textarea>
            </div>

            <hr class="border-gray-200 mb-6">

            {{-- Footer (Upload & Kalkulasi) --}}
            <div class="flex flex-col md:flex-row justify-between items-start gap-8">
                {{-- Bagian Upload Kiri --}}
                <div class="w-full md:w-1/2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Tanda Tangan Baru (Opsional)</label>
                    <input type="file" name="signature" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-lg bg-white mb-4">

                    @if($invoice->signature_path)
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200 inline-block">
                        <span class="text-xs text-gray-500 block mb-2">Tanda Tangan Saat Ini:</span>
                        <img src="{{ storage_path('app/public/' . $invoice->signature_path) }}" class="h-16 object-contain" alt="Current Signature">
                    </div>
                    @else
                    <div class="mt-4 text-sm text-gray-500 italic">Belum ada tanda tangan yang diunggah.</div>
                    @endif
                </div>

                {{-- Bagian Kalkulasi Kanan --}}
                <div class="w-full md:w-1/2 bg-gray-50 p-6 rounded-xl border border-gray-200">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-gray-600 font-medium">Subtotal</span>
                        <span class="text-lg font-bold text-gray-800">Rp <span id="displaySubtotal">0</span></span>
                        <input type="hidden" name="subtotal" id="inputSubtotal" value="{{ round($invoice->subtotal) }}">
                    </div>

                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600 font-medium">Diskon</span>
                            <select name="discount_type" id="discountType" class="border border-gray-300 rounded-md py-1 px-2 text-sm focus:ring-blue-500">
                                <option value="nominal" {{ $invoice->discount_type == 'nominal' ? 'selected' : '' }}>Rp</option>
                                <option value="percent" {{ $invoice->discount_type == 'percent' ? 'selected' : '' }}>%</option>
                            </select>
                        </div>
                        <input type="number" name="discount_value" id="discountValue" value="{{ round($invoice->discount_value) }}" min="0" class="w-32 border border-gray-300 rounded-md px-3 py-1 text-right focus:ring-blue-500 input-calc">
                    </div>

                    <div class="flex justify-between items-center py-3 border-y border-gray-200 my-3">
                        <span class="text-gray-800 font-bold text-lg">Grand Total</span>
                        <span class="text-xl font-bold text-blue-700">Rp <span id="displayGrandTotal">0</span></span>
                    </div>

                    <div class="flex justify-between items-center mb-3">
                        <span class="text-gray-600 font-medium">DP / Uang Muka (Rp)</span>
                        <input type="number" name="dp" id="dpValue" value="{{ round($invoice->dp) }}" min="0" class="w-32 border border-gray-300 rounded-md px-3 py-1 text-right focus:ring-blue-500 input-calc">
                    </div>

                    <div class="flex justify-between items-center mt-4 bg-red-50 p-3 rounded-lg border border-red-100">
                        <span class="text-red-700 font-bold">Sisa Kekurangan</span>
                        <span class="text-lg font-bold text-red-700">Rp <span id="displaySisa">0</span></span>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <a href="{{ route('invoice.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg transition-colors flex items-center">
                    Batal
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition-colors flex items-center">
                    <i class="fa-solid fa-save mr-2"></i> Perbarui Invoice
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.querySelector('#itemTable tbody');
        // Set index mulai dari jumlah item yang ada
        let rowIdx = {{ count($invoice->items) }};

        const formatRupiah = (number) => new Intl.NumberFormat('id-ID').format(number);

        const calculateTotals = () => {
            let subtotal = 0;
            document.querySelectorAll('.item-row').forEach((row) => {
                const qty = parseFloat(row.querySelector('.qty').value) || 0;
                const price = parseFloat(row.querySelector('.price').value) || 0;
                const total = qty * price;

                row.querySelector('.total-display').value = formatRupiah(total);
                row.querySelector('.total-raw').value = total;
                subtotal += total;
            });

            document.getElementById('displaySubtotal').innerText = formatRupiah(subtotal);
            document.getElementById('inputSubtotal').value = subtotal;

            let discountType = document.getElementById('discountType').value;
            let discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
            let discountAmount = discountType === 'percent' ? subtotal * (discountValue / 100) : discountValue;

            let grandTotal = Math.max(0, subtotal - discountAmount);
            document.getElementById('displayGrandTotal').innerText = formatRupiah(grandTotal);

            let dp = parseFloat(document.getElementById('dpValue').value) || 0;
            let sisaTagihan = Math.max(0, grandTotal - dp);
            document.getElementById('displaySisa').innerText = formatRupiah(sisaTagihan);
        };

        const updateRemoveButtons = () => {
            const btns = document.querySelectorAll('.removeRow');
            btns.forEach((btn) => {
                if (btns.length === 1) {
                    btn.disabled = true;
                    btn.classList.add('cursor-not-allowed', 'opacity-50');
                    btn.classList.remove('hover:bg-red-600');
                } else {
                    btn.disabled = false;
                    btn.classList.remove('cursor-not-allowed', 'opacity-50');
                    btn.classList.add('hover:bg-red-600');
                }
            });
        };

        // Event Listener untuk input kalkulasi
        document.body.addEventListener('input', function(e) {
            if (e.target.classList.contains('input-calc') || e.target.id === 'discountValue' || e.target.id === 'dpValue') {
                calculateTotals();
            }
        });
        document.getElementById('discountType').addEventListener('change', calculateTotals);

        // Tambah Baris Baru
        document.getElementById('addRow').addEventListener('click', () => {
            const tr = document.createElement('tr');
            tr.className = 'item-row border-t border-gray-100';
            tr.innerHTML = `
                <td class="py-3 px-2"><input type="text" name="items[${rowIdx}][description]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required></td>
                <td class="py-3 px-2"><input type="number" name="items[${rowIdx}][qty]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 qty text-center input-calc" value="1" min="1" required></td>
                <td class="py-3 px-2"><input type="number" name="items[${rowIdx}][price]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 price input-calc" value="0" min="0" required></td>
                <td class="py-3 px-2">
                    <input type="text" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 total-display text-gray-600 font-medium" readonly value="0">
                    <input type="hidden" name="items[${rowIdx}][total_raw]" class="total-raw" value="0">
                </td>
                <td class="py-3 px-2 text-center"><button type="button" class="bg-red-500 text-white rounded-md w-8 h-8 flex items-center justify-center removeRow"><i class="fa-solid fa-xmark"></i></button></td>
            `;
            tableBody.appendChild(tr);
            rowIdx++;
            updateRemoveButtons();
        });

        // Hapus Baris
        tableBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.removeRow');
            if (btn && !btn.disabled) {
                btn.closest('tr').remove();
                calculateTotals();
                updateRemoveButtons();
            }
        });

        // Jalankan kalkulasi saat halaman pertama kali dimuat
        calculateTotals();
        updateRemoveButtons();
    });
</script>
@endpush
@endsection
