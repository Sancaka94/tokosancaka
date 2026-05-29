@extends('layouts.admin') <!-- Sesuaikan dengan nama layout admin Anda, misal: admin.layouts.app -->

@section('title', 'Top Up Saldo DANA Corporate')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white shadow-md rounded-lg p-6 max-w-3xl mx-auto">
        
        <div class="border-b pb-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Top Up DANA Pelanggan (Corporate)</h2>
            <p class="text-gray-600 mt-1">Formulir untuk mencairkan saldo komisi aplikasi menjadi saldo DANA secara otomatis melalui saldo Merchant Deposit Sancaka.</p>
        </div>

        <!-- ========================================== -->
        <!-- ALERT PESAN SUKSES / ERROR -->
        <!-- ========================================== -->
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Berhasil</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if (session('warning'))
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Menunggu (Pending)</p>
                <p>{{ session('warning') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Gagal</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-5 rounded" role="alert">
                <p class="font-bold">Mohon periksa kembali form Anda:</p>
                <ul class="list-disc ml-5 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- FORMULIR TOP UP CORPORATE -->
        <!-- ========================================== -->
        <form action="{{ route('customer.dana.topup_corporate') }}" method="POST">
            @csrf

            <div class="mb-5">
                <label for="affiliate_id" class="block text-gray-700 font-semibold mb-2">ID Pengguna Pelanggan</label>
                <input type="text" name="affiliate_id" id="affiliate_id" value="{{ old('affiliate_id') }}" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    placeholder="Contoh: 12" required>
                <p class="text-sm text-gray-500 mt-1">*Saldo aplikasi milik ID Pengguna ini akan dipotong.</p>
            </div>

            <div class="mb-5">
                <label for="phone" class="block text-gray-700 font-semibold mb-2">Nomor HP DANA Tujuan</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    placeholder="Contoh: 081234567890" required>
            </div>

            <div class="mb-6">
                <label for="amount" class="block text-gray-700 font-semibold mb-2">Nominal Top Up (Rp)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-600 font-semibold">Rp</span>
                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}" min="1000" 
                        class="w-full pl-10 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Minimal 1000" required>
                </div>
            </div>

            <div class="flex items-center justify-end border-t pt-4">
                <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg focus:outline-none shadow-md transition duration-300"
                    onclick="return confirm('PENTING: Pastikan Saldo Corporate Sancaka mencukupi. Apakah Anda yakin ingin mencairkan saldo ini?')">
                    Proses Pencairan DANA
                </button>
            </div>
        </form>

    </div>
</div>
@endsection