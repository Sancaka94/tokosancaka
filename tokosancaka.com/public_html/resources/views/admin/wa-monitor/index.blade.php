@extends('layouts.admin')

@if (session('success'))
    <div class="mb-4 rounded-lg bg-green-100 p-4 text-green-800">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-lg bg-red-100 p-4 text-red-800">
        {{ session('error') }}
    </div>
@endif

@if (session('warning'))
    <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800">
        {{ session('warning') }}
    </div>
@endif

@section('content')
<div x-data="waMonitor()" class="p-6 bg-gray-50 min-h-screen font-sans text-gray-800">

    <!-- Header & Actions -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Monitoring WA Bot</h1>
            <p class="text-sm text-gray-500 mt-1">Evaluasi draft percakapan, resi, dan status DOKU pelanggan Sancaka.</p>
        </div>

        <!-- Filter & Search Form -->
        <form method="GET" action="{{ route('admin.wa-monitor.index') }}" class="flex flex-wrap gap-3">
            <input type="date" name="date" value="{{ request('date') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-gray-900 focus:border-gray-900 shadow-sm">

            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama / No WA / Invoice..." class="border border-gray-300 rounded-md pl-9 pr-3 py-2 text-sm focus:ring-gray-900 focus:border-gray-900 shadow-sm w-64">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                Filter
            </button>
            <a href="{{ route('admin.wa-monitor.index') }}" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                Reset
            </a>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="mb-4 flex items-center space-x-3" x-show="selected.length > 0" x-cloak>
        <span class="text-sm text-gray-600 font-medium"><span x-text="selected.length"></span> dipilih</span>
        <form method="POST" action="{{ route('admin.wa-monitor.destroy.bulk') }}" onsubmit="return confirm('Hapus semua data terpilih?');">
            @csrf
            @method('DELETE')
            <template x-for="id in selected">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded text-xs font-semibold flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Destroy Selected
            </button>
        </form>
    </div>

    <!-- Main Table -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="p-4 w-12 text-center">
                        <input type="checkbox" @change="toggleAll" :checked="isAllSelected()" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                    </th>
                    <th class="p-4 font-semibold">Pelanggan</th>
                    <th class="p-4 font-semibold">Step (Draft)</th>
                    <th class="p-4 font-semibold">Invoice & Resi</th>
                    <th class="p-4 font-semibold">Status DOKU</th>
                    <th class="p-4 font-semibold">Last Activity</th>
                    <th class="p-4 font-semibold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($drafts as $draft)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="p-4 text-center">
                        <input type="checkbox" value="{{ $draft->id }}" x-model="selected" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                    </td>
                    <td class="p-4">
                        <div class="font-medium text-gray-900">{{ $draft->sender_name ?? 'Belum Input' }}</div>
                        <div class="text-gray-500 text-xs">{{ $draft->phone_number }}</div>
                    </td>
                    <td class="p-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                            {{ str_replace('AWAITING_', '', $draft->step) }}
                        </span>
                    </td>
                    <td class="p-4">
                        <div class="text-gray-900 font-mono text-xs">{{ $draft->invoice_number ?? '-' }}</div>
                        <div class="text-blue-600 font-mono text-xs mt-1">{{ $draft->resi_awb ?? 'Resi Pending' }}</div>
                    </td>
                    <td class="p-4">
                        @if($draft->status_pembayaran === 'paid')
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Lunas
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> {{ $draft->status_pembayaran ?? 'Draft / Belum Checkout' }}
                            </span>
                        @endif
                    </td>
                    <td class="p-4 text-gray-500 text-xs">
                        {{ $draft->updated_at->diffForHumans() }}
                    </td>
                    <td class="p-4 flex justify-end gap-2">
                        <!-- Btn Cek Pembayaran DOKU -->
                        @if($draft->invoice_number)
                        <form action="{{ route('admin.wa-monitor.check-payment', $draft->invoice_number) }}" method="POST" class="inline" title="Cek Status DOKU">
                            @csrf
                            <button type="submit" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded transition-colors flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                        </form>
                        @endif

                        <!-- Btn Hit Ulang KiriminAja (Hanya muncul jika lunas & belum punya resi) -->
                        @if($draft->status_pembayaran === 'paid' && empty($draft->resi_awb))
                        <form action="{{ route('admin.wa-monitor.retry-kiriminaja', $draft->id) }}" method="POST" onsubmit="return confirm('Proses ulang pembentukan resi ke KiriminAja untuk pesanan ini?');" class="inline" title="Hit Ulang KiriminAja">
                            @csrf
                            <button type="submit" class="p-1.5 text-green-500 hover:text-green-700 hover:bg-green-50 rounded transition-colors flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            </button>
                        </form>
                        @endif

                        <!-- Btn Detail Chat WA -->
                        <button type="button" @click="openModal({{ json_encode($draft) }})" class="p-1.5 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors" title="Lihat Chat">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>

                        <!-- Btn Destroy -->
                        <form action="{{ route('admin.wa-monitor.destroy', $draft->id) }}" method="POST" onsubmit="return confirm('Hapus percakapan ini?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Hapus">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-400 text-sm">Tidak ada data percakapan ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $drafts->links() }}
    </div>

    <!-- MODAL WA CHAT (Clean UI) -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 backdrop-blur-sm" x-cloak>
        <div @click.away="closeModal" class="bg-[#efeae2] w-full max-w-md h-[32rem] rounded-xl shadow-2xl flex flex-col overflow-hidden relative border border-gray-300">

            <!-- WA Header -->
            <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900" x-text="activeData.phone_number"></h3>
                    <p class="text-xs text-gray-500">Status State: <span class="font-medium" x-text="activeData.step"></span></p>
                </div>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- WA Chat Body Pattern -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4" style="background-image: url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23d1d5db\' fill-opacity=\'0.2\' fill-rule=\'evenodd\'%3E%3Ccircle cx=\'3\' cy=\'3\' r=\'3\'/%3E%3Ccircle cx=\'13\' cy=\'13\' r=\'3\'/%3E%3C/g%3E%3C/svg%3E');">

                <!-- Tanggal Dummy -->
                <div class="flex justify-center">
                    <span class="bg-white/80 border border-gray-200 text-gray-500 text-[11px] font-medium px-3 py-1 rounded-lg backdrop-blur shadow-sm">
                        <span x-text="formatDate(activeData.updated_at)"></span>
                    </span>
                </div>

                <!-- Chat Bot (Bubble Kiri) -->
                <div class="flex justify-start">
                    <div class="bg-white text-gray-800 text-sm py-2 px-3 shadow-sm rounded-lg rounded-tl-none max-w-[85%] border border-gray-100">
                        <p class="font-medium text-xs text-gray-400 mb-1">Bot Sancaka</p>
                        <p class="whitespace-pre-line">Data Terakhir Terisi:
                        - Pengirim: <span x-text="activeData.sender_name || '-'"></span>
                        - Penerima: <span x-text="activeData.receiver_name || '-'"></span>
                        - Tujuan: <span x-text="activeData.destination_text || '-'"></span>
                        - Kurir: <span x-text="activeData.courier_code || '-'"></span></p>
                    </div>
                </div>

                <!-- Chat Customer (Bubble Kanan - Menunggu Input) -->
                <div class="flex justify-end">
                    <div class="bg-[#dcf8c6] text-gray-800 text-sm py-2 px-3 shadow-sm rounded-lg rounded-tr-none max-w-[85%] border border-[#c1e8a8]">
                        <p class="italic text-gray-500 text-xs">... System sedang menunggu input <span x-text="activeData.step"></span> dari pelanggan ...</p>
                    </div>
                </div>

            </div>

            <!-- WA Footer Fake -->
            <div class="bg-gray-50 border-t border-gray-200 px-4 py-3 flex items-center">
                <div class="bg-white border border-gray-300 w-full rounded-full px-4 py-2 text-sm text-gray-400">
                    Mode Read-Only Admin...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('waMonitor', () => ({
        selected: [],
        allIds: @json($drafts->pluck('id')),
        modalOpen: false,
        activeData: {},

        toggleAll() {
            if (this.selected.length === this.allIds.length) {
                this.selected = [];
            } else {
                this.selected = this.allIds;
            }
        },
        isAllSelected() {
            return this.selected.length === this.allIds.length && this.allIds.length > 0;
        },
        openModal(data) {
            this.activeData = data;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
        },
        formatDate(dateStr) {
            if(!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute:'2-digit' });
        }
    }))
})
</script>
@endsection

