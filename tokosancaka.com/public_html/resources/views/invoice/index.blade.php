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
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Grand Total</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-bold text-blue-600">{{ $invoice->invoice_no }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                            {{ date('d M Y', strtotime($invoice->date)) }}
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
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex items-center justify-center space-x-2">
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
@endsection
