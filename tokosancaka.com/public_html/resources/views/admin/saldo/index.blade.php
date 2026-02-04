@extends('layouts.admin') {{-- Sesuaikan dengan layout admin Anda --}}

{{-- 
CATATAN PENTING:
Solusi ini menggunakan Alpine.js (https://alpinejs.dev/) untuk modal.
Pastikan layout admin Anda (layouts.admin) sudah memuat script Alpine.js.
Jika belum, tambahkan ini di dalam tag <head> layout Anda:
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script> 
--}}

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Permintaan Saldo Customer</h3>
    <p class="mt-1 text-gray-500">Daftar permintaan top-up (manual) yang memerlukan persetujuan.</p>

    {{-- Notifikasi Sukses/Error --}}
    @if (session('success'))
        <div class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Sukses!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- ========================================================== --}}
    {{-- === 1. PERBAIKAN: Menambahkan x-data untuk modal === --}}
    {{-- ========================================================== --}}
    <div class="mt-8" x-data="{ showModal: false, modalImage: '' }">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Pelanggan
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID Referensi
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Deskripsi / Bukti
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($requests as $request) {{-- Ganti $topUps menjadi $requests --}}
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $request->user->nama_lengkap ?? ('User ID: ' . $request->user_id) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $request->user->email ?? 'Email tidak ada' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $request->reference_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    Rp {{ number_format($request->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $request->created_at->format('d M Y, H:i') }}
                                </td>
                                
                                {{-- ========================================================== --}}
                                {{-- === 2. PERBAIKAN: Logika kolom "Deskripsi / Bukti" === --}}
                                {{-- ========================================================== --}}
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{-- Tampilkan deskripsi --}}
                                    <span class="block">{{ $request->description }}</span>
                                    
                                    {{-- Cek apakah ada BUKTI TRANSFER (kolom yang benar) --}}
                                    @if ($request->payment_proof_path)
                                        {{-- Tampilkan thumbnail, tambahkan event click --}}
                                        <img 
                                            src="{{ asset('public/storage/' . $request->payment_proof_path) }}" 
                                            alt="Bukti Transfer" 
                                            class="mt-2 w-16 h-16 rounded-md object-cover cursor-pointer hover:opacity-75 transition-opacity"
                                            @click="showModal = true; modalImage = '{{ asset('public/storage/' . $request->payment_proof_path) }}'"
                                        >
                                    @else
                                        {{-- Jika tidak ada bukti terupload --}}
                                        <span class="mt-2 text-xs text-red-500">(Belum ada bukti)</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    {{-- Tombol Aksi "Approve" --}}
                                    
                                    {{-- ✅ PERBAIKAN: Nama route diubah menjadi 'admin.saldo.requests.approve' --}}
                                    <form action="{{ route('admin.saldo.requests.approve', $request->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Anda yakin ingin menyetujui top up ini?');">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                    </form>
                                    
                                    <span class="text-gray-300 mx-1">|</span>
                                    
                                    {{-- Tombol Aksi "Reject" --}}
                                    
                                    {{-- ✅ PERBAIKAN: Nama route diubah menjadi 'admin.saldo.requests.reject' --}}
                                    <form action="{{ route('admin.saldo.requests.reject', $request->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Anda yakin ingin MENOLAK top up ini?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Tidak ada permintaan top up yang menunggu persetujuan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $requests->links() }} {{-- Paginasi --}}
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- === 3. TAMBAHAN: Blok HTML untuk Modal === --}}
        {{-- ========================================================== --}}
        <div 
            x-show="showModal" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display: none;" {{-- Mencegah 'flash' saat load halaman --}}
        >
            {{-- Latar belakang overlay --}}
            <div 
                class="fixed inset-0 bg-black bg-opacity-75" 
                @click="showModal = false"
            ></div>
            
            {{-- Konten Modal --}}
            <div 
                class="relative z-10 max-w-3xl max-h-[90vh] bg-white rounded-lg shadow-xl overflow-hidden"
                @click.away="showModal = false"
            >
                {{-- Gambar akan dimuat di sini oleh Alpine --}}
                <img :src="modalImage" alt="Bukti Transfer - Tampilan Penuh" class="object-contain w-full h-full max-h-[90vh]">
                
                {{-- Tombol Close (X) --}}
                <button 
                    @click="showModal = false"
                    class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full p-1 hover:bg-opacity-75 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

    </div> {{-- Penutup div x-data --}}
@endsection