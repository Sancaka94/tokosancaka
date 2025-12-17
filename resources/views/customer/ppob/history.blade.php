@extends('layouts.customer')

@section('title', 'Riwayat Transaksi Digital')

@section('content')
<div class="space-y-6">
    
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Transaksi PPOB</h2>
            <p class="text-sm text-gray-500 mt-1">Daftar pembelian pulsa, data, dan pembayaran tagihan Anda.</p>
        </div>
        
        {{-- Tombol Export (EXCEL & PDF) --}}
        <div class="flex gap-2">
            {{-- Tombol Excel --}}
            <a href="{{ route('customer.ppob.export.excel') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition shadow-sm">
                <i class="fas fa-file-excel mr-2 text-sm"></i> ExportExcel
            </a>
            {{-- Tombol PDF --}}
            <a href="{{ route('customer.ppob.export.pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition shadow-sm">
                <i class="fas fa-file-pdf mr-2 text-sm"></i> ExportPDF
            </a>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <form action="{{ route('customer.ppob.history') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cari Transaksi</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="Order ID, No HP, atau Produk...">
                    </div>
                </div>

                {{-- Status --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Status</option>
                        <option value="Success" {{ request('status') == 'Success' ? 'selected' : '' }}>Berhasil</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Diproses</option>
                        <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Gagal</option>
                    </select>
                </div>

                {{-- Tanggal --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Rentang Tanggal</label>
                    <div class="flex gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-xs">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-xs">
                    </div>
                </div>

                {{-- Tombol Filter --}}
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg text-sm transition">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ========================================== --}}
    {{-- [BARU] ALERT MERAH PERINGATAN REFRESH --}}
    {{-- ========================================== --}}
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm flex items-start">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-red-500 text-xl mt-0.5"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-bold text-red-800 uppercase tracking-wide">
                INFORMASI:
            </h3>
            <p class="text-sm text-red-700 mt-1 leading-relaxed">
                AGAR <strong>SN DAN STATUS</strong> UPDATE SETELAH TRANSAKSI, MOHON REFRESH HALAMAN INI DENGAN <strong>F5</strong> ATAU KLIK TOMBOL <strong>REFRESH</strong> PADA BROWSER. 
                JIKA MENGGUNAKAN TABLET ATAU HANDPHONE, BISA <strong>TARIK LAYAR</strong> KE BAWAH AGAR DEVICE MEREFRESH HALAMAN INI.
            </p>
        </div>
    </div>
    {{-- ========================================== --}}

    {{-- Tabel Data --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                        <th class="px-6 py-4">Produk</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">Harga / Metode</th>
                        <th class="px-6 py-4">Status / SN</th>
                        <th class="px-6 py-4 text-right">Tanggal</th>
                        <th class="px-6 py-4 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        
                        {{-- 1. PRODUK & LOGO --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center">
                                @php
                                    $sku = strtolower($trx->buyer_sku_code);
                                    // Logika Logo Sederhana
                                    $logo = 'https://cdn-icons-png.flaticon.com/512/1067/1067566.png'; // Default
                                    if(str_contains($sku, 'pln')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/pln.png';
                                    elseif(str_contains($sku, 'bpjs')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/bpjs.png';
                                    elseif(str_contains($sku, 'telkomsel') || str_contains($sku, 'simpati')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/telkomsel.png';
                                    elseif(str_contains($sku, 'indosat')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/indosat.png';
                                    elseif(str_contains($sku, 'xl')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/xl.png';
                                    elseif(str_contains($sku, 'dana')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/dana.png';
                                    elseif(str_contains($sku, 'gopay')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/go pay.png';
                                @endphp
                                <div class="h-10 w-10 flex-shrink-0 mr-3 bg-white border border-gray-200 rounded-full p-1 flex items-center justify-center overflow-hidden">
                                    <img class="h-full w-full object-contain" src="{{ $logo }}" alt="{{ $trx->buyer_sku_code }}">
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 uppercase">{{ $trx->buyer_sku_code }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $trx->order_id }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 2. PELANGGAN --}}
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm font-medium text-gray-900 font-mono">{{ $trx->customer_no }}</div>
                            <div class="text-xs text-gray-500">ID Pelanggan</div>
                        </td>

                        {{-- 3. HARGA & METODE --}}
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm font-bold text-gray-900">Rp {{ number_format($trx->selling_price, 0, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 uppercase">{{ $trx->payment_method ?? 'Unknown' }}</div>
                        </td>

                        {{-- 4. STATUS & SN (UPDATED) --}}
                        <td class="px-6 py-4 align-top">
                            @php
                                $statusClasses = match($trx->status) {
                                    'Success' => 'bg-green-100 text-green-800 border-green-200',
                                    'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'Processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'Failed' => 'bg-red-100 text-red-800 border-red-200',
                                    default => 'bg-gray-100 text-gray-800 border-gray-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClasses }} mb-1">
                                {{ $trx->status }}
                            </span>
                            
                            @if($trx->sn)
                                {{-- BUTTON PEMICU MODAL --}}
                                <div class="mt-1 group cursor-pointer" onclick="showSnModal('{{ $trx->sn }}', '{{ $trx->buyer_sku_code }}')">
                                    <div class="flex items-center justify-between text-xs bg-gray-50 hover:bg-green-50 hover:border-green-300 transition-all px-2 py-1.5 rounded border border-gray-300 border-dashed font-mono text-gray-600 max-w-[160px]">
                                        <div class="flex flex-col truncate mr-2">
                                            <span class="text-[10px] text-gray-400 uppercase leading-none mb-0.5">SN / Token</span>
                                            <span class="font-bold text-green-700 truncate">
                                                {{-- Ambil 20 karakter pertama saja biar rapi --}}
                                                {{ Str::limit(explode('/', $trx->sn)[0], 18) }}
                                            </span>
                                        </div>
                                        <i class="fas fa-eye text-gray-400 group-hover:text-green-600"></i>
                                    </div>
                                </div>
                            @elseif($trx->status == 'Failed')
                                <div class="mt-1 text-xs text-red-500 italic max-w-[160px] truncate" title="{{ $trx->message }}">
                                    {{ $trx->message }}
                                </div>
                            @endif
                        </td>

                        {{-- 5. TANGGAL --}}
                        <td class="px-6 py-4 align-top text-right">
                            <div class="text-sm text-gray-900 font-medium">{{ $trx->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $trx->created_at->format('H:i') }} WIB</div>
                        </td>

                        {{-- 6. AKSI --}}
                        <td class="px-6 py-4 align-top text-center">
                            <a href="{{ route('ppob.invoice', ['invoice' => $trx->order_id]) }}" 
                               class="text-gray-400 hover:text-blue-600 transition duration-150" 
                               title="Lihat Invoice">
                                <i class="fas fa-file-invoice text-lg"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-gray-100 p-4 rounded-full mb-3">
                                    <i class="fas fa-receipt text-3xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Belum ada transaksi</h3>
                                <p class="text-gray-500 text-sm mt-1">Transaksi PPOB Anda akan muncul di sini.</p>
                                <a href="https://tokosancaka.com/daftar-harga" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition">
                                    Mulai Transaksi
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
    </div>

</div>

<script>
    // Fungsi Menampilkan Modal Detail SN
    function showSnModal(rawSn, skuCode) {
        // 1. Cek apakah ini Format PLN (biasanya dipisah tanda /)
        const parts = rawSn.split('/');
        const isPln = parts.length > 1; // Asumsi kalau ada / berarti PLN token/tagihan
        
        let htmlContent = '';
        let tokenOnly = rawSn; // Default untuk SN biasa

        if (isPln) {
            // --- TAMPILAN KHUSUS PLN (Sesuai Gambar Referensi) ---
            const token = parts[0];
            const nama  = parts[1] || '-';
            const tarif = parts[2] || '-';
            const daya  = parts[3] || '-';
            const kwh   = parts[4] || '-';
            
            tokenOnly = token; // Untuk fungsi copy nanti

            htmlContent = `
                <div class="text-left">
                    <div class="bg-green-50 border-2 border-dashed border-green-400 rounded-xl p-6 relative overflow-hidden">
                        
                        <div class="text-xs text-gray-500 font-bold tracking-widest uppercase mb-2">
                            Token Listrik:
                        </div>

                        <div class="font-mono text-3xl sm:text-4xl font-extrabold text-green-600 tracking-wider mb-5 break-all leading-tight">
                            ${token}
                        </div>

                        <div class="border-t border-dashed border-green-300 mb-4"></div>

                        <div class="space-y-2 text-sm font-mono text-gray-700">
                            <div class="flex">
                                <span class="w-16 text-gray-500">Nama</span>
                                <span class="mr-2 text-gray-400">:</span>
                                <span class="font-bold truncate flex-1">${nama}</span>
                            </div>
                            <div class="flex">
                                <span class="w-16 text-gray-500">Tarif</span>
                                <span class="mr-2 text-gray-400">:</span>
                                <span class="font-bold truncate flex-1">${tarif}</span>
                            </div>
                            <div class="flex">
                                <span class="w-16 text-gray-500">Daya</span>
                                <span class="mr-2 text-gray-400">:</span>
                                <span class="font-bold truncate flex-1">${daya}</span>
                            </div>
                            <div class="flex">
                                <span class="w-16 text-gray-500">KWH</span>
                                <span class="mr-2 text-gray-400">:</span>
                                <span class="font-bold truncate flex-1">${kwh}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // --- TAMPILAN SN BIASA (Pulsa/Data) ---
            htmlContent = `
                <div class="text-left">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                        <div class="text-xs text-gray-500 font-bold uppercase mb-1">Kode SN / Ref:</div>
                        <div class="font-mono text-xl font-bold text-gray-800 break-all bg-white p-3 border rounded shadow-sm">
                            ${rawSn}
                        </div>
                        <div class="mt-4 text-xs text-gray-400">
                            Gunakan kode di atas sebagai bukti transaksi yang sah.
                        </div>
                    </div>
                </div>
            `;
        }

        // Tampilkan SweetAlert
        Swal.fire({
            title: isPln ? 'Detail Token Listrik' : 'Detail Serial Number',
            html: htmlContent,
            showConfirmButton: false, // Kita buat tombol sendiri
            showCloseButton: true,
            width: '500px',
            padding: '20px',
            footer: `
                <div class="grid grid-cols-2 gap-3 w-full">
                    <button onclick="Swal.close()" class="w-full py-2.5 rounded-lg border border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition">
                        KEMBALI
                    </button>
                    <button onclick="copySn('${tokenOnly}')" class="w-full py-2.5 rounded-lg bg-green-600 text-white font-bold hover:bg-green-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-copy"></i> COPY SN
                    </button>
                </div>
            `
        });
    }

    // Fungsi Copy ke Clipboard
    function copySn(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Tampilkan Toast Kecil di atas modal
            const Toast = Swal.mixin({
                toast: true,
                position: 'top', // Tampil di tengah atas agar terlihat jelas di hp
                showConfirmButton: false,
                timer: 2000,
                customClass: { popup: 'z-[9999]' } // Pastikan di atas modal utama
            });

            Toast.fire({
                icon: 'success',
                title: 'Disalin!',
                background: '#166534',
                color: '#fff'
            });
        });
    }
</script>

@endsection