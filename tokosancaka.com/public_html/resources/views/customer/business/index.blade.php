@extends('layouts.customer')

@section('title', 'Pusat Bisnis & Pendaftaran')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Pusat Bisnis & Keanggotaan</h1>
        <p class="text-gray-500">Kelola status akun, pendaftaran toko, dan integrasi pembayaran Anda di sini.</p>
    </div>

    {{-- TABEL STATUS FITUR --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-blue-50 text-blue-800 text-sm uppercase tracking-wider border-b border-blue-100">
                        <th class="p-4 font-bold">Fitur / Layanan</th>
                        <th class="p-4 font-bold">Deskripsi</th>
                        <th class="p-4 font-bold text-center">Status Saat Ini</th>
                        <th class="p-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">

                    {{-- 1. AGEN RESMI --}}
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mr-3">
                                    <i class="fas fa-crown text-lg"></i>
                                </div>
                                <span class="font-bold text-gray-700">Agen Resmi Sancaka</span>
                            </div>
                        </td>
                        <td class="p-4 text-gray-600">
                            Dapatkan harga modal lebih murah untuk produk PPOB dan akses fitur khusus agen.
                        </td>
                        <td class="p-4 text-center">
                            @if(auth()->user()->role === 'agent')
                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-bold text-xs">AKTIF</span>
                            @else
                                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 font-bold text-xs">MEMBER</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            @if(auth()->user()->role === 'agent')
                                <a href="{{ route('agent.products.index') }}" class="text-blue-600 hover:text-blue-800 font-bold hover:underline">Kelola</a>
                            @else
                                <a href="{{ route('agent.register.index') }}" class="inline-block px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-bold shadow transition">Daftar Agen</a>
                            @endif
                        </td>
                    </tr>

                    {{-- 2. TOKO / SELLER --}}
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mr-3">
                                    <i class="fas fa-store text-lg"></i>
                                </div>
                                <span class="font-bold text-gray-700">Seller / Penjual</span>
                            </div>
                        </td>
                        <td class="p-4 text-gray-600">
                            Buka toko online gratis, upload produk, dan terima pesanan dari pelanggan.
                        </td>
                        <td class="p-4 text-center">
                            @if(auth()->user()->store)
                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-bold text-xs">AKTIF</span>
                            @else
                                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 font-bold text-xs">BELUM ADA</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            @if(auth()->user()->store)
                                <a href="{{ route('seller.dashboard') }}" class="text-blue-600 hover:text-blue-800 font-bold hover:underline">Masuk Toko</a>
                            @else
                                <a href="{{ route('customer.seller.register.form') }}" class="inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow transition">Buka Toko</a>
                            @endif
                        </td>
                    </tr>

                    {{-- 3. MERCHANT DANA --}}
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                                    <i class="fas fa-wallet text-lg"></i>
                                </div>
                                <span class="font-bold text-gray-700">Merchant DANA Bisnis</span>
                            </div>
                        </td>
                        <td class="p-4 text-gray-600">
                            Integrasi pembayaran QRIS dan DANA Bisnis untuk toko Anda.
                        </td>
                        <td class="p-4 text-center">
                            {{-- Ganti logic check DB di bawah sesuai tabel dana_shops Anda --}}
                            @php
                                $danaShop = \Illuminate\Support\Facades\DB::table('dana_shops')->where('user_id', auth()->id())->first();
                            @endphp

                            @if($danaShop && $danaShop->dana_status == 'SUCCESS')
                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-bold text-xs">TERHUBUNG</span>
                            @elseif($danaShop)
                                <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-bold text-xs">PENDING</span>
                            @else
                                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 font-bold text-xs">NON-AKTIF</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            @if($danaShop)
                                <a href="{{ route('customer.merchant.index') }}" class="text-blue-600 hover:text-blue-800 font-bold hover:underline">Kelola</a>
                            @else
                                <a href="{{ route('customer.merchant.create') }}" class="inline-block px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-bold shadow transition">Daftar DANA</a>
                            @endif
                        </td>
                    </tr>

                    {{-- 4. DOMPET SANCAKA (DOKU SAC ID) --}}
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                                    <i class="fas fa-id-card text-lg"></i>
                                </div>
                                <span class="font-bold text-gray-700">Dompet Sancaka (SAC ID)</span>
                            </div>
                        </td>
                        <td class="p-4 text-gray-600">
                            Rekening virtual untuk pencairan dana penjualan otomatis (DOKU).
                        </td>
                        <td class="p-4 text-center">
                            {{-- Cek kolom doku_sac_id di tabel toko --}}
                            @if(auth()->user()->store && auth()->user()->store->doku_sac_id)
                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-bold text-xs">TERDAFTAR</span>
                            @else
                                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 font-bold text-xs">BELUM ADA</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            @if(auth()->user()->store && auth()->user()->store->doku_sac_id)
                                <a href="{{ route('seller.doku.index') }}" class="text-blue-600 hover:text-blue-800 font-bold hover:underline">Cek Saldo</a>
                            @elseif(auth()->user()->store)
                                <a href="{{ route('seller.doku.index') }}" class="inline-block px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold shadow transition">Aktivasi</a>
                            @else
                                <span class="text-xs text-gray-400 italic">Buka Toko Dulu</span>
                            @endif
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
