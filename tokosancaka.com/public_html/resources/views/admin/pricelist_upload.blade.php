@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-4xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Upload Pricelist Prabayar (Excel)</h1>
        <p class="text-sm text-gray-500 mt-1">Import data harga produk prabayar dari file Excel ke dalam database.</p>
    </div>

    @if(session('success'))
        <div class="flex p-4 mb-6 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50" role="alert">
            <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
            <span class="sr-only">Sukses</span>
            <div>
                <span class="font-medium">Berhasil!</span> {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="flex p-4 mb-6 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
            <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
            <span class="sr-only">Error</span>
            <div>
                <span class="font-medium">Gagal!</span> {{ session('error') }}
            </div>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">Form Upload Pricelist</h2>
        </div>

        <div class="p-6">
            <form action="{{ route('admin.pricelist.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label for="type" class="block mb-2 text-sm font-semibold text-gray-900">Kategori / Tipe Produk <span class="text-red-500">*</span></label>
                    <select name="type" id="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 outline-none transition-all" required>
                        <option value="">-- Pilih Tipe --</option>
                        <option value="Pulsa">Pulsa</option>
                        <option value="Data">Paket Data</option>
                        <option value="Game">Voucher Game</option>
                        <option value="Etoll">E-Toll / Saldo Digital</option>
                        <option value="PLN">Token PLN</option>
                    </select>
                    <p class="mt-2 text-sm text-gray-500">Pilih tipe produk yang sesuai dengan isi *sheet* Excel yang sedang Anda unggah.</p>
                </div>

                <div class="mb-6">
                    <label class="block mb-2 text-sm font-semibold text-gray-900" for="file_input">File Excel (.xlsx / .csv) <span class="text-red-500">*</span></label>

                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2.5 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:transition-all"
                           id="file_input"
                           name="file"
                           type="file"
                           accept=".xlsx, .xls, .csv"
                           required>

                    <p class="mt-2 text-sm text-red-500 bg-red-50 p-2 rounded border border-red-100 inline-block">
                        <strong>Perhatian:</strong> Pastikan format kolom berurutan: A (No), B (Operator), C (Kode), D (Nominal), F (Harga Rp), G (Status).
                    </p>
                </div>

                <div class="flex justify-end mt-8 pt-4 border-t border-gray-100">
                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-semibold rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center shadow-sm transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Upload & Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
