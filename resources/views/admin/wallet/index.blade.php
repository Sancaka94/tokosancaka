@extends('layouts.admin')

@section('title', 'Manajemen Wallet')
@section('page-title', 'Wallet Pelanggan')

@section('content')
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Daftar Saldo Pelanggan</h2>

    <p class="text-gray-600 dark:text-gray-400 mb-6">
        Di halaman ini, Anda dapat melihat saldo terkini dari semua pelanggan dan melakukan penambahan saldo (Top Up) jika diperlukan.
    </p>

    <!-- Form Top Up Saldo -->
    <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-lg border dark:border-gray-700 mb-8">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Formulir Top Up Saldo</h3>
        <form action="{{ route('wallet.topup') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pilihan Pelanggan -->
                <div>
                    <label for="user_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pilih Pelanggan</label>
                    <select id="user_id" name="user_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                        <option value="" disabled selected>-- Cari dan pilih nama pelanggan --</option>
                        @foreach($pelanggan as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} - (ID: {{ $user->id }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Jumlah Top Up -->
                <div>
                    <label for="amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jumlah Top Up (Rp)</label>
                    <input type="number" id="amount" name="amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600" placeholder="e.g., 50000" min="1000" required>
                </div>

                <!-- Tombol Submit -->
                <div class="self-end">
                    <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <i class="fas fa-plus-circle mr-2"></i> Tambah Saldo
                    </button>
                </div>
            </div>
             @error('user_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
             @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </form>
    </div>

    <!-- Tabel Daftar Pelanggan -->
    <div class="overflow-x-auto relative">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="py-3 px-6">ID</th>
                    <th scope="col" class="py-3 px-6">Nama Pelanggan</th>
                    <th scope="col" class="py-3 px-6">Email</th>
                    <th scope="col" class="py-3 px-6 text-right">Saldo Saat Ini</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pelanggan as $user)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $user->id }}
                    </td>
                    <td class="py-4 px-6">{{ $user->name }}</td>
                    <td class="py-4 px-6">{{ $user->email }}</td>
                    <td class="py-4 px-6 text-right font-bold text-green-600 dark:text-green-400">
                        Rp {{ number_format($user->balance, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-4 px-6 text-center text-gray-500">
                        Belum ada data pelanggan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    {{-- Link Pagination --}}
    <div class="mt-6">
        {{ $pelanggan->links() }}
    </div>
</div>
@endsection
