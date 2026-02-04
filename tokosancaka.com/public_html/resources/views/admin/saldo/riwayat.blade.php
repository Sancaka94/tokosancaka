@extends('layouts.admin') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Riwayat Top Up Saldo</h3>
    <p class="mt-1 text-gray-500">Menampilkan seluruh riwayat permintaan top-up dari semua customer (Manual, Doku, Tripay).</p>

    {{-- Notifikasi --}}
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
    {{-- === LENGKAPI: Menambahkan x-data untuk modal === --}}
    {{-- ========================================================== --}}
    <div class="mt-8" x-data="{ showModal: false, modalImage: '' }">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pelanggan
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID Referensi
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Metode
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        
                        {{-- Asumsi Controller mengirim variabel $transactions --}}
                        @forelse ($transactions as $tx)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $tx->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $tx->user->nama_lengkap ?? $tx->user->name ?? ('User ID: ' . $tx->user_id) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $tx->user->email ?? 'Email tidak ada' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $tx->reference_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{-- Logika untuk menampilkan metode pembayaran --}}
                                    @php
                                        $method = 'Lainnya'; 
                                        $method_color_class = 'bg-gray-100 text-gray-800'; // Default (e.g., "Oleh Admin")

                                        if (str_contains($tx->description, 'Transfer Manual')) {
                                            $method = 'Transfer Manual';
                                            $method_color_class = 'bg-purple-100 text-purple-800'; // UNGU

                                        } elseif (str_contains($tx->description, 'DOKU')) {
                                            $method = 'Doku';
                                            $method_color_class = 'bg-red-100 text-red-800'; // MERAH

                                        } elseif (str_contains($tx->description, 'QRIS')) {
                                            $method = 'QRIS (Tripay)';
                                            $method_color_class = 'bg-blue-100 text-blue-800'; // BIRU
                                        
                                        } elseif (str_contains($tx->description, 'BCAVA')) {
                                            $method = 'BCA VA (Tripay)';
                                            $method_color_class = 'bg-blue-100 text-blue-800'; // BIRU
                                        
                                        } elseif (str_contains($tx->description, 'Admin')) {
                                            $method = 'Oleh Admin';
                                            $method_color_class = 'bg-gray-100 text-gray-800'; // Abu-abu
                                        }
                                    @endphp

                                    {{-- Badge untuk Metode --}}
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $method_color_class }}">
                                        {{ $method }}
                                    </span>

                                   

                                    {{-- ========================================================== --}}
                                    {{-- === LENGKAPI: Menampilkan thumbnail bukti transfer === --}}
                                    {{-- ========================================================== --}}
                                    @if ($method == 'Transfer Manual' && $tx->payment_proof_path)
                                        <img 
                                            src="{{ asset('public/storage/' . $tx->payment_proof_path) }}" 
                                            alt="Bukti Transfer" 
                                            class="mt-2 w-12 h-12 rounded-md object-cover cursor-pointer hover:opacity-75 transition-opacity"
                                            @click="showModal = true; modalImage = '{{ asset('public/storage/' . $tx->payment_proof_path) }}'"
                                        >
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    {{-- Badge untuk Status --}}
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($tx->status == 'success') bg-green-100 text-green-800
                                        @elseif($tx->status == 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800 @endif
                                    ">
                                        {{ ucfirst($tx->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Belum ada riwayat transaksi top up.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Paginasi --}}
            <div class="p-4">
                {{ $transactions->links() }}
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- === LENGKAPI: Blok HTML untuk Modal === --}}
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