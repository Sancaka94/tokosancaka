@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <div class="mb-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            {{ isset($transaction) ? 'Edit Transaksi Darurat' : 'Form Transaksi Darurat / Manual' }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ isset($transaction) ? 'Silakan perbarui data transaksi di bawah ini.' : 'Gunakan form ini untuk mencatat kendaraan yang masuk tanpa tiket atau saat sistem sedang down.' }}
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                            <ul class="pl-5 list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Ubah action URL tergantung mode Edit atau Create --}}
                    <form action="{{ isset($transaction) ? route('transactions.update', $transaction->id) : route('transactions.storeManual') }}" method="POST">
                        @csrf

                        {{-- Tambahkan method PUT jika dalam mode Edit --}}
                        @if(isset($transaction))
                            @method('PUT')
                        @endif

                        <div class="mb-4">
                            <label for="plate_number" class="block text-sm font-medium text-gray-700">Plat Nomor</label>
                            <input type="text" name="plate_number" id="plate_number" value="{{ old('plate_number', $transaction->plate_number ?? '') }}" required placeholder="Contoh: AE 1234 XX" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('plate_number')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="vehicle_type" class="block text-sm font-medium text-gray-700">Jenis Kendaraan</label>
                            <select name="vehicle_type" id="vehicle_type" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="" disabled {{ old('vehicle_type', $transaction->vehicle_type ?? '') == '' ? 'selected' : '' }}>-- Pilih Jenis Kendaraan --</option>
                                <option value="motor" {{ old('vehicle_type', $transaction->vehicle_type ?? '') == 'motor' ? 'selected' : '' }}>Motor</option>
                                <option value="mobil" {{ old('vehicle_type', $transaction->vehicle_type ?? '') == 'mobil' ? 'selected' : '' }}>Mobil</option>
                            </select>
                            @error('vehicle_type')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="fee" class="block text-sm font-medium text-gray-700">Nominal Tarif Parkir (Rp)</label>
                            <input type="number" name="fee" id="fee" value="{{ old('fee', $transaction->fee ?? '') }}" required min="0" placeholder="Contoh: 10000" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('fee')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="toilet_fee" class="block text-sm font-medium text-gray-700">Pemasukan Toilet (Rp)</label>
                            <input type="number" name="toilet_fee" id="toilet_fee" value="{{ old('toilet_fee', $transaction->toilet_fee ?? 2000) }}" min="0" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Nilai bawaan adalah Rp 2.000. Ubah menjadi 0 jika tidak ada pemasukan toilet.</p>
                            @error('toilet_fee')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('transactions.index') }}" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Batal & Kembali
                            </a>
                            <button type="submit" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{-- Ubah label tombol --}}
                                {{ isset($transaction) ? 'Simpan Perubahan' : 'Simpan Transaksi' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
