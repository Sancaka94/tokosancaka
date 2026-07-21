@extends('layouts.admin') <!-- Sesuaikan dengan layout admin Anda -->

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-12">
    <!-- Bagian Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Level & Akses Driver Sancaka</h2>
        <p class="text-sm text-gray-500 mt-1">
            Penilaian otomatis berdasarkan jumlah pesanan sukses. Ubah bintang secara manual untuk driver bermasalah.
        </p>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="flex items-center p-4 mb-6 text-sm text-green-800 bg-green-100 rounded-lg border border-green-200" role="alert">
            <svg class="flex-shrink-0 inline w-5 h-5 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
            </svg>
            <span class="font-medium mr-1">Berhasil!</span> {{ session('success') }}
        </div>
    @endif

    <!-- Card & Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Driver</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Order Sukses</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Level Medali</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bintang (Manual)</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Izin Sancaka Express?</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($drivers as $d)
                        <!-- Form HTML5 (Definisikan ID Form di luar tag <td> agar valid) -->
                        <form id="form-reward-{{ $d->id_pengguna }}" action="{{ route('admin.reward.update', $d->id_pengguna) }}" method="POST">
                            @csrf
                        </form>

                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <!-- Nama Driver -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                {{ $d->nama_lengkap }}
                            </td>

                            <!-- Total Order -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                    {{ $d->total_order_selesai ?? 0 }} Order
                                </span>
                            </td>

                            <!-- Level Medali -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xl">{{ $d->ikon ?? '🔰' }}</span>
                                    <span class="font-medium">{{ $d->nama_medali ?? 'Newbie' }}</span>
                                </div>
                            </td>

                            <!-- Bintang -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <select name="bintang" form="form-reward-{{ $d->id_pengguna }}" class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-8 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                    @for($i=1; $i<=5; $i++)
                                        <option value="{{ $i }}" {{ ($d->bintang_manual ?? 5) == $i ? 'selected' : '' }}>
                                            {{ $i }} Bintang
                                        </option>
                                    @endfor
                                </select>
                            </td>

                            <!-- Izin Express -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <select name="is_trusted" form="form-reward-{{ $d->id_pengguna }}"
                                        class="block w-full rounded-lg border bg-white py-2 pl-3 pr-8 text-sm focus:outline-none focus:ring-1 cursor-pointer
                                        {{ ($d->is_trusted_express ?? 0) == 1 ? 'border-green-500 text-green-700 focus:border-green-500 focus:ring-green-500 font-medium bg-green-50' : 'border-red-400 text-red-700 focus:border-red-500 focus:ring-red-500 font-medium bg-red-50' }}">
                                    <option value="0" {{ ($d->is_trusted_express ?? 0) == 0 ? 'selected' : '' }}>❌ Belum Dipercaya</option>
                                    <option value="1" {{ ($d->is_trusted_express ?? 0) == 1 ? 'selected' : '' }}>✅ Dipercaya (Akses Express)</option>
                                </select>
                            </td>

                            <!-- Aksi & Catatan -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <div class="flex flex-col gap-2 w-48 mx-auto">
                                    <input type="text" name="catatan" form="form-reward-{{ $d->id_pengguna }}"
                                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                           placeholder="Catatan komplain..."
                                           value="{{ $d->catatan_admin ?? '' }}">

                                    <button type="submit" form="form-reward-{{ $d->id_pengguna }}"
                                            class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition-all duration-200">
                                        Simpan Data
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Kondisi Kosong (Empty State) -->
        @if(count($drivers) == 0)
        <div class="text-center py-10">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada Driver</h3>
            <p class="mt-1 text-sm text-gray-500">Tidak ada data driver yang dapat ditampilkan saat ini.</p>
        </div>
        @endif
    </div>
</div>
@endsection
