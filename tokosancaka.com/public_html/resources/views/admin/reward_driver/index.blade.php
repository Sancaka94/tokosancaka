@extends('layouts.admin') <!-- Sesuaikan dengan layout admin Anda -->

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-12">
    <!-- Bagian Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Level & Akses Driver Sancaka</h2>
        <p class="text-sm text-gray-500 mt-1">
            Penilaian otomatis berdasarkan jumlah pesanan sukses. Klik langsung pada bintang untuk mengubah rating driver secara manual.
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
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Total Order Sukses</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Level Medali</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Bintang (Manual)</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Izin Sancaka Express?</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($drivers as $d)
                        <!-- Form HTML5 (ID Form di luar tag <td> agar struktur HTML valid) -->
                        <form id="form-reward-{{ $d->id_pengguna }}" action="{{ route('admin.reward.update', $d->id_pengguna) }}" method="POST">
                            @csrf
                        </form>

                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <!-- Nama Driver -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                {{ $d->nama_lengkap }}
                            </td>

                            <!-- Total Order -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                    {{ $d->total_order_selesai ?? 0 }} Order
                                </span>
                            </td>

                            <!-- Level Medali (Konversi ke FontAwesome) -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="flex items-center gap-2">
                                    @php
                                        // Deteksi warna dan icon berdasarkan nama medali
                                        $medalColor = 'text-gray-400';
                                        $medalIcon = 'fa-solid fa-user-shield';

                                        switch(strtolower($d->nama_medali ?? 'newbie')) {
                                            case 'bronze':
                                                $medalColor = 'text-amber-700'; $medalIcon = 'fa-solid fa-medal'; break;
                                            case 'silver':
                                                $medalColor = 'text-gray-400'; $medalIcon = 'fa-solid fa-medal'; break;
                                            case 'gold':
                                                $medalColor = 'text-yellow-500'; $medalIcon = 'fa-solid fa-medal'; break;
                                            case 'platinum':
                                                $medalColor = 'text-cyan-500'; $medalIcon = 'fa-solid fa-gem'; break;
                                            default:
                                                $medalColor = 'text-emerald-500'; $medalIcon = 'fa-solid fa-user-shield'; break;
                                        }
                                    @endphp

                                    <i class="{{ $medalIcon }} {{ $medalColor }} text-xl drop-shadow-sm"></i>
                                    <span class="font-bold text-gray-800">{{ $d->nama_medali ?? 'Newbie' }}</span>
                                </div>
                            </td>

                            <!-- Bintang Interaktif Alpine.js -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <div x-data="{ rating: {{ $d->bintang_manual ?? 5 }} }" class="inline-flex items-center gap-1 cursor-pointer">
                                    <template x-for="i in 5">
                                        <i @click="rating = i"
                                           class="fa-solid fa-star text-xl transition-all duration-200 transform hover:scale-110"
                                           :class="i <= rating ? 'text-yellow-400 drop-shadow-md' : 'text-gray-300 hover:text-yellow-200'">
                                        </i>
                                    </template>

                                    <!-- Input tersembunyi yang akan dikirim ke Backend -->
                                    <input type="hidden" name="bintang" :value="rating" form="form-reward-{{ $d->id_pengguna }}">
                                </div>
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
            <i class="fa-solid fa-users-slash text-4xl text-gray-300 mb-3"></i>
            <h3 class="text-sm font-medium text-gray-900">Belum ada Driver</h3>
            <p class="mt-1 text-sm text-gray-500">Tidak ada data driver yang dapat ditampilkan saat ini.</p>
        </div>
        @endif
    </div>
</div>
@endsection
