@extends('layouts.app') {{-- Sesuaikan dengan nama file layout utama Anda --}}

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header Section --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Invoice</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola dan pantau semua tagihan Sancaka Express.</p>
        </div>
        <a href="{{ route('invoice.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg flex items-center shadow-md shadow-blue-200 transition duration-200">
            <i class="fa-solid fa-plus mr-2"></i> Buat Invoice Baru
        </a>
    </div>

    {{-- Alert Notifikasi --}}
    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm flex items-center" role="alert">
        <i class="fa-solid fa-circle-check mr-3 text-lg"></i>
        <p>{{ session('success') }}</p>
    </div>
    @endif

    {{-- Table Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Invoice</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan / Perusahaan</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Tagihan</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                    @forelse($invoices as $index => $invoice)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                            {{ $invoices->firstItem() + $index }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold text-blue-600">{{ $invoice->invoice_no }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                            {{ date('d M Y', strtotime($invoice->date)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-gray-900 font-bold">{{ $invoice->customer_name }}</div>
                            <div class="text-gray-500 text-xs mt-0.5">{{ $invoice->company_name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-800">
                            Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex items-center justify-center space-x-2">
                                {{-- Tombol View/PDF --}}
                                <a href="{{ route('invoice.pdf', $invoice->id) }}" target="_blank" class="bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 px-3 py-1.5 rounded-lg transition-all duration-200" title="Cetak PDF">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>

                                {{-- Tombol Edit --}}
                                <a href="{{ route('invoice.edit', $invoice->id) }}" class="bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white border border-amber-200 px-3 py-1.5 rounded-lg transition-all duration-200" title="Edit Invoice">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                {{-- Tombol Hapus --}}
                                <form action="{{ route('invoice.destroy', $invoice->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus invoice {{ $invoice->invoice_no }} ini secara permanen?');">
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
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-gray-100 p-4 rounded-full mb-3">
                                    <i class="fa-solid fa-file-invoice-dollar text-4xl text-gray-400"></i>
                                </div>
                                <p class="font-medium text-gray-600">Belum ada data invoice</p>
                                <p class="text-xs text-gray-400 mt-1">Silakan buat invoice baru terlebih dahulu.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(method_exists($invoices, 'links') && $invoices->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
