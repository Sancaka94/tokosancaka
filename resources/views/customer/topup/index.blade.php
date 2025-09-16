@extends('layouts.customer')

@section('content')
    <div class="flex justify-between items-center mb-8">
        <div>
            <h3 class="text-3xl font-semibold text-gray-700">Riwayat Top Up</h3>
            <p class="mt-1 text-gray-500">Berikut adalah riwayat semua transaksi top up saldo Anda.</p>
        </div>
        <a href="{{ route('customer.topup.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:bg-blue-700 shadow-lg">
            <i class="fas fa-plus-circle mr-2"></i>
            Top Up Sekarang
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                        <th class="px-6 py-3">ID Transaksi</th>
                        <th class="px-6 py-3">Jumlah</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y">
                    {{-- ✅ PERBAIKAN: Menggunakan @forelse untuk menangani kasus data kosong --}}
                    @forelse ($topUps ?? [] as $topUp)
                        <tr class="text-gray-700 hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium">{{ $topUp->transaction_id }}</td>
                            <td class="px-6 py-4 font-semibold text-green-600">Rp {{ number_format($topUp->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-sm font-semibold leading-tight rounded-full
                                    @if($topUp->status == 'success') bg-green-100 text-green-700 @endif
                                    @if($topUp->status == 'pending') bg-yellow-100 text-yellow-700 @endif
                                    @if($topUp->status == 'failed') bg-red-100 text-red-700 @endif
                                ">
                                    {{ ucfirst($topUp->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $topUp->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('customer.topup.show', $topUp->transaction_id) }}" class="text-blue-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        {{-- Tampilan ini akan muncul jika tidak ada data top up --}}
                        <tr>
                            <td colspan="5" class="text-center py-16">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-wallet fa-3x text-gray-400 mb-3"></i>
                                    <h3 class="text-lg font-semibold text-gray-700">Belum Ada Riwayat</h3>
                                    <p class="text-gray-500">Anda belum pernah melakukan top up.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Menampilkan Paginasi Bawaan Laravel --}}
        @if(isset($topUps) && $topUps->hasPages())
            <div class="p-4 bg-white border-t">
                {{ $topUps->links() }}
            </div>
        @endif
    </div>
@endsection
