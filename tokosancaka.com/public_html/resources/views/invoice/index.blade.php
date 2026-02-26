@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Invoice</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola dan pantau semua tagihan CV. Sancaka Karya Hutama.</p>
        </div>
        <a href="{{ route('invoice.create') }}" wire:navigate class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-5 rounded-lg flex items-center shadow-md shadow-blue-200 transition duration-200">
            <i class="fa-solid fa-plus mr-2"></i> Buat Invoice Baru
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm flex items-center" role="alert">
        <i class="fa-solid fa-circle-check mr-3 text-lg"></i>
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Invoice</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Grand Total</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keuangan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status Tracking</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-bold text-blue-600 block">{{ $invoice->invoice_no }}</span>
                            <span class="text-xs text-gray-500">{{ date('d M Y', strtotime($invoice->date)) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-gray-900 font-bold">{{ $invoice->customer_name }}</div>
                            @if($invoice->company_name)
                                <div class="text-gray-500 text-xs mt-0.5">{{ $invoice->company_name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-800">
                            Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($invoice->sisa_tagihan <= 0)
                                <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold border border-green-200">LUNAS</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2.5 py-1 rounded-full text-xs font-bold border border-red-200">Sisa: Rp {{ number_format($invoice->sisa_tagihan, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="badge px-2 py-1 rounded text-xs font-semibold {{ $invoice->progress_percent >= 100 ? 'bg-green-500 text-white' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $invoice->status ?? 'Invoice Diterbitkan' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex items-center justify-center space-x-2">

                                {{-- Tombol Update Status (MEMANGGIL MODAL) --}}
                                <button type="button"
                                    onclick="openStatusModal({{ $invoice->id }}, '{{ $invoice->status ?? 'Invoice Diterbitkan' }}', '{{ addslashes($invoice->tracking_note) }}')"
                                    class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-200 px-3 py-1.5 rounded-lg transition-all duration-200"
                                    title="Update Status & Resi">
                                    <i class="fa-solid fa-truck-fast"></i>
                                </button>

                                <a href="{{ route('invoice.pdf', $invoice->id) }}" target="_blank" class="bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 px-3 py-1.5 rounded-lg transition-all duration-200" title="Cetak PDF">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                                <a href="{{ route('invoice.edit', $invoice->id) }}" class="bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white border border-amber-200 px-3 py-1.5 rounded-lg transition-all duration-200" title="Edit Invoice">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('invoice.destroy', $invoice->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus permanen invoice {{ $invoice->invoice_no }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 px-3 py-1.5 rounded-lg transition-all duration-200" title="Hapus Invoice">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            <i class="fa-solid fa-file-invoice-dollar text-4xl text-gray-300 mb-3"></i>
                            <p class="font-medium text-gray-600">Belum ada data invoice</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($invoices, 'links') && $invoices->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ================= MODAL UPDATE STATUS ================= --}}
<div id="statusModal" class="fixed inset-0 z-[99] hidden items-center justify-center bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 transform transition-all scale-95 opacity-0" id="statusModalContent">
        <form id="statusForm" method="POST" action="">
            @csrf
            @method('PATCH')

            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                <h3 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-truck-fast text-blue-600 me-2"></i> Update Status Tracking</h3>
                <button type="button" onclick="closeStatusModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="p-6">
                {{-- Dropdown Status --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Tahapan / Progress</label>
                    <select name="status" id="modalStatus" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="Invoice Diterbitkan">Invoice Diterbitkan (10%)</option>
                        <option value="Pembayaran Terverifikasi">Pembayaran Terverifikasi (25%)</option>
                        <option value="Proses Pengerjaan">Proses Pengerjaan (50%)</option>
                        <option value="Finishing & Siap Kirim">Finishing & Siap Kirim (75%)</option>
                        <option value="Selesai & Lunas">Selesai & Lunas (100%)</option>
                    </select>
                </div>

                {{-- Textarea Keterangan --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan Tambahan / Nomor Resi</label>
                    <textarea name="tracking_note" id="modalNote" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" placeholder="Tulis catatan, nomor resi pengiriman, atau kendala pengerjaan di sini..."></textarea>
                    <p class="text-xs text-gray-500 mt-2"><i class="fa-solid fa-circle-info me-1"></i> Catatan ini akan bisa dibaca oleh pelanggan saat mereka melacak invoice.</p>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeStatusModal()" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-lg transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-colors flex items-center">
                    <i class="fa-solid fa-save mr-2"></i> Simpan Status
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Fungsi untuk membuka Modal dan mengisi data
    function openStatusModal(id, currentStatus, currentNote) {
        const modal = document.getElementById('statusModal');
        const content = document.getElementById('statusModalContent');
        const form = document.getElementById('statusForm');

        // Atur action form ke rute update status
        form.action = `/admin/invoice/${id}/status`;

        // Isi nilai input dari data yang dilempar
        document.getElementById('modalStatus').value = currentStatus || 'Invoice Diterbitkan';
        document.getElementById('modalNote').value = currentNote || '';

        // Tampilkan Modal dengan efek animasi (Tailwind)
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    // Fungsi untuk menutup Modal
    function closeStatusModal() {
        const modal = document.getElementById('statusModal');
        const content = document.getElementById('statusModalContent');

        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200); // Waktu yang sama dengan durasi animasi
    }
</script>
@endpush
@endsection
