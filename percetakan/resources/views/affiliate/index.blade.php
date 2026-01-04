@extends('layouts.app')

@section('title', 'Data Partner Afiliasi')

@section('content')
    {{-- Hidden Element: Menyimpan QR Register untuk diprint via JS --}}
    <div id="qr-register-content" style="display: none;">
        {!! $qrRegister ?? '' !!}
    </div>

    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Partner Afiliasi</h1>
            <p class="text-sm font-medium text-slate-500 mt-1">Data partner, performa kupon, dan hitungan komisi.</p>
        </div>
        
        <div class="flex gap-2">

            {{-- TOMBOL SYNC SALDO --}}
            <form action="{{ route('affiliate.sync') }}" method="POST" onsubmit="return confirm('Yakin ingin hitung ulang saldo semua member berdasarkan riwayat transaksi?');">
                @csrf
                <button type="submit" 
                    class="bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i> 
                    <span>Sync Saldo</span>
                </button>
            </form>

            {{-- TOMBOL CETAK QR PENDAFTARAN --}}
            <button onclick="printRegistrationQR()" 
                class="bg-slate-800 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-slate-300 hover:bg-slate-900 transition flex items-center gap-2">
                <i class="fas fa-print"></i> 
                <span>Cetak QR Daftar</span>
            </button>

            {{-- TOMBOL SALIN LINK --}}
            <div x-data="{ copied: false }">
                <button @click="navigator.clipboard.writeText('{{ $registerUrl ?? route('affiliate.create') }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                    class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-200 hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas" :class="copied ? 'fa-check' : 'fa-link'"></i> 
                    <span x-text="copied ? 'Link Disalin!' : 'Salin Link'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- STATISTIK CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-users text-8xl text-blue-500"></i>
            </div>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Partner</p>
            <h2 class="text-2xl font-black text-slate-800">{{ $totalAffiliates }} <span class="text-sm font-medium text-slate-400">Orang</span></h2>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-shopping-bag text-8xl text-emerald-500"></i>
            </div>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Transaksi Via Afiliasi</p>
            <h2 class="text-2xl font-black text-emerald-600">{{ $totalTransactions }} <span class="text-sm font-medium text-slate-400">Trx</span></h2>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl text-white shadow-xl shadow-slate-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-coins text-8xl text-white"></i>
            </div>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Omzet Dari Afiliasi</p>
            <h2 class="text-2xl font-black text-white">Rp {{ number_format($totalRevenueGenerated, 0, ',', '.') }}</h2>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Partner</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Info Rekening</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kupon</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Performa</th>
                        <th class="px-6 py-4 text-[10px] font-black text-emerald-600 uppercase tracking-widest text-right">Estimasi Komisi</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse($affiliates as $aff)
                    @php
                        // LOGIKA HITUNG KOMISI 10% DARI OMZET
                        $omzetGenerated = 0;
                        $trxCount = 0;
                        
                        if($aff->coupon) {
                            $omzetGenerated = $aff->coupon->orders->sum('final_price');
                            $trxCount = $aff->coupon->orders->count();
                        }

                        $komisiRate = 0.10; // 10%
                        $estimasiKomisi = $omzetGenerated * $komisiRate;
                    @endphp

                    <tr class="hover:bg-slate-50 transition group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold uppercase">
                                    {{ substr($aff->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-700">{{ $aff->name }}</div>
                                    <div class="text-xs text-slate-400 flex items-center gap-1">
                                        <i class="fab fa-whatsapp text-emerald-500"></i> {{ $aff->whatsapp }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 max-w-[150px] truncate" title="{{ $aff->address }}">
                                        <i class="fas fa-map-marker-alt text-red-400"></i> {{ $aff->address }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">{{ $aff->bank_name }}</div>
                            <div class="font-mono text-xs bg-slate-100 px-2 py-0.5 rounded inline-block text-slate-600 border border-slate-200">
                                {{ $aff->bank_account_number }}
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            @if($aff->coupon)
                                <div class="flex flex-col items-start gap-1">
                                    <span class="font-black text-xs bg-red-50 text-red-600 px-2 py-1 rounded border border-red-100 border-dashed">
                                        {{ $aff->coupon->code }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">Diskon: {{ $aff->coupon->value }}%</span>
                                </div>
                            @else
                                <span class="text-xs text-slate-300 italic">Belum ada kupon</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-col items-center">
                                <span class="font-black text-slate-800 text-lg">{{ $trxCount }}</span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Terpakai</span>
                                <span class="text-[10px] text-emerald-600 font-bold mt-1">
                                    Omzet: Rp {{ number_format($omzetGenerated, 0, ',', '.') }}
                                </span>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="font-black text-emerald-600 text-base">
                                Rp {{ number_format($estimasiKomisi, 0, ',', '.') }}
                            </div>
                            <p class="text-[9px] text-slate-400">*Rate: 10% dari Omzet</p>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                {{-- TOMBOL KIRIM WA --}}
                                <a href="https://wa.me/{{ preg_replace('/^0/', '62', $aff->whatsapp) }}?text=Halo%20{{ urlencode($aff->name) }},%20berikut%20laporan%20komisi%20Anda:%0A%0ATotal%20Transaksi:%20{{ $trxCount }}%0AOmzet:%20Rp%20{{ number_format($omzetGenerated,0,',','.') }}%0AKomisi:%20Rp%20{{ number_format($estimasiKomisi,0,',','.') }}%0A%0AMohon%20dikonfirmasi." 
                                   target="_blank"
                                   class="h-8 w-8 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-600 hover:text-white transition flex items-center justify-center" title="Hubungi WA">
                                    <i class="fab fa-whatsapp"></i>
                                </a>

                                {{-- TOMBOL CETAK QR CODE KUPON --}}
                                <a href="{{ route('affiliate.print_qr', $aff->id) }}" 
                                   target="_blank"
                                   class="h-8 w-8 rounded-full bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-600 hover:text-white transition flex items-center justify-center" 
                                   title="Cetak QR Kupon">
                                    <i class="fas fa-qrcode text-xs"></i>
                                </a>
                                
                                {{-- TOMBOL EDIT (BARU DITAMBAHKAN DISINI) --}}
                                <a href="{{ route('affiliate.edit', $aff->id) }}"
                                   class="h-8 w-8 rounded-full bg-amber-50 text-amber-600 border border-amber-200 hover:bg-amber-500 hover:text-white transition flex items-center justify-center"
                                   title="Edit Data">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="fas fa-users-slash text-4xl mb-3 text-slate-300"></i>
                                <p class="italic">Belum ada partner afiliasi yang mendaftar.</p>
                                <p class="text-xs mt-1">Bagikan link pendaftaran untuk mulai merekrut.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Script untuk Print QR Register --}}
    <script>
        function printRegistrationQR() {
            // Ambil konten SVG dari hidden div
            const qrContent = document.getElementById('qr-register-content').innerHTML;
            const registerLink = '{{ $registerUrl ?? route("affiliate.create") }}';

            if(!qrContent.trim()) {
                alert('Gagal memuat QR Code. Pastikan Controller mengirim data $qrRegister.');
                return;
            }

            // Buka jendela baru untuk print
            const printWindow = window.open('', '', 'height=600,width=500');
            
            printWindow.document.write('<html><head><title>Scan untuk Daftar Partner</title>');
            printWindow.document.write('<style>body { font-family: sans-serif; text-align: center; padding-top: 50px; } .qr-box { margin: 20px auto; width: fit-content; } h2 { color: #333; } p { color: #666; font-size: 12px; margin-top: 10px; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h2>Scan untuk Daftar Partner Afiliasi</h2>');
            printWindow.document.write('<p>Jadilah partner kami dan dapatkan komisi menarik!</p>');
            printWindow.document.write('<div class="qr-box">' + qrContent + '</div>');
            printWindow.document.write('<p><strong>' + registerLink + '</strong></p>');
            printWindow.document.write('<script>window.print();<\/script>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
        }
    </script>
@endsection