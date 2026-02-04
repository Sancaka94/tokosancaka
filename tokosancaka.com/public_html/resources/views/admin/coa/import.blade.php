@extends('layouts.admin')

@section('content')
<div class="bg-gray-800 p-6 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <h1 class="text-3xl font-bold text-white">Import Kode Akun dari Excel</h1>
        <a href="{{ route('admin.coa.index') }}" class="text-gray-300 hover:text-white text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Daftar Akun
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-500 text-white p-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
            <strong class="font-bold">Oops! Terjadi beberapa kesalahan:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-gray-900 p-6 rounded-lg">
        {{-- Link untuk mengunduh template --}}
        <div class="mb-6">
            <a href="{{ route('admin.coa.import.template') }}" class="inline-flex items-center text-sm font-medium text-indigo-400 hover:text-indigo-300">
                <i class="fa-solid fa-download mr-2"></i>
                Unduh Template Excel
            </a>
        </div>

        {{-- Form untuk mengunggah file --}}
        <form action="{{ route('admin.coa.import.excel') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="file" class="block mb-2 text-sm font-medium text-gray-300">Pilih File Excel</label>
                <input type="file" name="file" id="file" required class="block w-full text-sm text-gray-400 border border-gray-600 rounded-lg cursor-pointer bg-gray-700 focus:outline-none file:bg-indigo-600 file:text-white file:border-0 file:px-4 file:py-2 file:mr-4 hover:file:bg-indigo-700">
                <p class="mt-2 text-xs text-gray-400">Format file yang diizinkan: .xlsx, .xls, .csv. Pastikan kolom sesuai template.</p>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">
                    <i class="fa-solid fa-upload mr-2"></i> Unggah dan Import
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

