@extends('layouts.app')

@section('title', 'Pencairan Dana Marketplace (Escrow)')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Pencairan Escrow Marketplace</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola dana tertahan dan cairkan ke saldo masing-masing Tenant.</p>
        </div>
        <div class="flex bg-white rounded-lg shadow-sm border border-gray-200 p-1">
            <a href="{{ route('escrow.index', ['status' => 'held']) }}"
               class="px-4 py-2 rounded-md font-bold text-sm transition-all {{ $status == 'held' ? 'bg-amber-100 text-amber-700 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                <i class="fas fa-lock mr-1"></i> Menunggu Pencairan
            </a>
            <a href="{{ route('escrow.index', ['status' => 'released']) }}"
               class="px-4 py-2 rounded-md font-bold text-sm transition-all {{ $status == 'released' ? 'bg-emerald-100 text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                <i class="fas fa-unlock mr-1"></i> Riwayat Cair
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm">
            <i class="fas fa-check-circle text-xl"></i>
            <div>
                <span class="font-bold block">Berhasil!</span>
                <span class="text-sm">{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <div>
                <span class="font-bold block">Gagal!</span>
                <span class="text-sm">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm align-middle whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-gray-200 text-slate-600 uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="px-4 py-4 font-bold text-center w-12 border-r border-gray-200">No</th>
                        <th class="px-4 py-4 font-bold text-left border-r border-gray-200">Nomor Nota & Item</th>
                        <th class="px-4 py-4 font-bold text-left border-r border-gray-200">Data Pemilik (Tenant)</th>
                        <th class="px-4 py-4 font-bold text-left border-r border-gray-200">Info Pengiriman</th>
                        <th class="px-4 py-4 font-bold text-right border-r border-gray-200">Nilai Cair (Rp)</th>
                        <th class="px-4 py-4 font-bold text-center">Aksi Pencairan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($orders as $index => $order)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-4 border-r border-gray-200 text-center text-gray-500 font-medium">
                                {{ $orders->firstItem() + $index }}
                            </td>

                            <td class="px-4 py-4 border-r border-gray-200">
                                <div class="font-black text-gray-900 text-base">{{ $order->order_number }}</div>
                                <div class="text-xs text-gray-500 mt-1 max-w-[200px] truncate whitespace-normal leading-tight" title="{{ implode(', ', $orderItems[$order->id] ?? []) }}">
                                    <i class="fas fa-box text-gray-400 mr-1"></i>
                                    {{ implode(', ', $orderItems[$order->id] ?? ['Tidak ada rincian produk']) }}
                                </div>
                                <div class="text-[10px] font-mono text-gray-400 mt-1.5">
                                    <i class="far fa-clock mr-1"></i> {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') }}
                                </div>
                            </td>

                            <td class="px-4 py-4 border-r border-gray-200">
                                <div class="font-bold text-blue-700 bg-blue-50 inline-block px-2 py-1 rounded text-xs mb-1 border border-blue-100">
                                    <i class="fas fa-store mr-1 text-blue-500"></i> {{ $order->store_name }}
                                </div>
                                <div class="text-sm font-semibold text-gray-700 flex items-center gap-1.5 mt-1">
                                    <div class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px] text-gray-500">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    {{ $order->owner_name ?? 'Data Terhapus' }}
                                </div>
                            </td>

                            <td class="px-4 py-4 border-r border-gray-200">
                                @if($order->shipping_ref)
                                    <div class="text-xs font-mono bg-slate-100 border border-slate-200 px-2 py-1 rounded inline-block text-slate-700 mb-1">
                                        <span class="text-slate-400">RESI:</span> {{ $order->shipping_ref }}
                                    </div>
                                @else
                                    <div class="text-xs font-mono bg-red-50 border border-red-100 px-2 py-1 rounded inline-block text-red-500 mb-1">
                                        Belum Ada Resi
                                    </div>
                                @endif

                                <div>
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full
                                        @if($order->order_status == 'completed') bg-emerald-100 text-emerald-700
                                        @elseif($order->order_status == 'processing') bg-blue-100 text-blue-700
                                        @else bg-gray-100 text-gray-700 @endif">
                                        {{ $order->order_status }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-4 border-r border-gray-200 text-right">
                                <div class="font-black text-lg {{ $order->escrow_status === 'held' ? 'text-amber-600' : 'text-emerald-600' }}">
                                    {{ number_format($order->final_price, 0, ',', '.') }}
                                </div>
                                <div class="text-[10px] text-gray-400 mt-1">Mata Uang IDR</div>
                            </td>

                            <td class="px-4 py-4 text-center">
                                @if($order->escrow_status === 'held')
                                    <form action="{{ route('escrow.release', $order->id) }}" method="POST" class="inline-block form-cairkan">
                                        @csrf
                                        <button type="button" onclick="confirmRelease(this, '{{ $order->store_name }}', '{{ number_format($order->final_price, 0, ',', '.') }}')"
                                            class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-lg font-bold text-xs shadow-md shadow-emerald-200 transition-all active:scale-95 flex items-center gap-2 mx-auto">
                                            <i class="fas fa-hand-holding-usd text-sm"></i> Buka Kran Cairkan
                                        </button>
                                    </form>
                                @else
                                    <div class="bg-slate-100 text-slate-500 px-4 py-2 rounded-lg font-bold text-xs flex items-center justify-center gap-2 w-fit mx-auto border border-slate-200">
                                        <i class="fas fa-check-double text-emerald-500"></i> Dana Telah Cair
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500 font-medium bg-gray-50/50">
                                <div class="w-16 h-16 bg-gray-100 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-box-open text-2xl"></i>
                                </div>
                                @if($status == 'held')
                                    Tidak ada dana yang tertahan saat ini. Transaksi Marketplace masih kosong.
                                @else
                                    Belum ada riwayat pencairan dana.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-white">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmRelease(btn, storeName, amount) {
        Swal.fire({
            title: 'Cairkan Rp ' + amount + '?',
            html: "Uang akan langsung dikirim ke Saldo Virtual Toko <b>" + storeName + "</b>.<br><br><span class='text-red-500 font-bold'>Pastikan pesanan sudah benar-benar diterima oleh pelanggan!</span>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981', // Emerald 500
            cancelButtonColor: '#64748b',  // Slate 500
            confirmButtonText: '<i class="fas fa-unlock"></i> Ya, Cairkan Dana!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit form ke controller
                btn.closest('form').submit();

                // Ubah tombol jadi loading biar tidak di-klik 2x
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                btn.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                btn.classList.add('bg-gray-400', 'cursor-not-allowed');
                btn.disabled = true;
            }
        })
    }
</script>
@endpush
@endsection
