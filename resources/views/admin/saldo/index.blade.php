@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 sm:px-8">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight text-gray-800">Permintaan Saldo Customer</h2>
            <p class="text-sm text-gray-600">Daftar permintaan top-up yang memerlukan persetujuan.</p>
        </div>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative my-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="mt-6 -mx-4 sm:-mx-8 px-4 sm:px-8 py-4 overflow-x-auto">
            <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="bg-gray-100 text-left text-gray-600 uppercase text-sm">
                            <th class="px-5 py-3 border-b-2 border-gray-200">Nama Pelanggan</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">ID Transaksi</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Jumlah</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Tanggal</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                        <tr class="bg-white hover:bg-gray-50">
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">{{ $request->customer->nama_lengkap ?? 'Pengguna tidak ditemukan' }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                <p class="text-gray-600 whitespace-no-wrap">{{ $request->transaction_id }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                <p class="text-gray-900 font-semibold whitespace-no-wrap">Rp {{ number_format($request->amount, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">{{ $request->created_at->format('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                <div class="flex items-center space-x-2">
                                    {{-- ✅ PERBAIKAN: Menggunakan transaction_id di URL --}}
                                    <form action="{{ route('admin.saldo.requests.approve', $request->transaction_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui permintaan ini?');">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900 font-semibold">Approve</button>
                                    </form>
                                    <span class="text-gray-300">|</span>
                                    {{-- ✅ PERBAIKAN: Menggunakan transaction_id di URL --}}
                                    <form action="{{ route('admin.saldo.requests.reject', $request->transaction_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menolak permintaan ini?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-10">
                                <p class="text-gray-500">Tidak ada permintaan saldo yang pending saat ini.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
