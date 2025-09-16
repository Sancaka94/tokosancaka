@extends('layouts.customer')

@section('title', 'Edit Data Scan')

@section('content')
<div class="bg-slate-50 min-h-screen">
    <div class="container mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('customer.scan.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                &larr; Kembali ke Riwayat Scan
            </a>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-lg">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Data Scan</h1>
            <p class="mt-1 text-slate-600">Perbarui informasi untuk resi yang dipilih.</p>

            <form action="{{ route('customer.scan.update', $scan->id) }}" method="POST" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="resi_number" class="block text-sm font-medium text-slate-700">Nomor Resi</label>
                    <input type="text" name="resi_number" id="resi_number" value="{{ old('resi_number', $scan->resi_number) }}" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('resi_number') border-red-500 @enderror">
                    @error('resi_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('status') border-red-500 @enderror">
                        <option value="Proses Pickup" {{ old('status', $scan->status) == 'Proses Pickup' ? 'selected' : '' }}>Proses Pickup</option>
                        <option value="Diterima Sancaka" {{ old('status', $scan->status) == 'Diterima Sancaka' ? 'selected' : '' }}>Diterima Sancaka</option>
                        <option value="Dijemput Kurir" {{ old('status', $scan->status) == 'Dijemput Kurir' ? 'selected' : '' }}>Dijemput Kurir</option>
                        <option value="Cancel" {{ old('status', $scan->status) == 'Cancel' ? 'selected' : '' }}>Cancel</option>
                    </select>
                    @error('status')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
