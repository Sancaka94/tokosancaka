@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="font-bold text-2xl text-gray-800">Manajemen Komisi & Fee Agen</h2>
            <p class="text-gray-500 text-sm mt-1">Atur persentase bagi hasil kustom untuk setiap agen secara spesifik.</p>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CARD STATISTIK PROFIT SHARING -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-indigo-500 relative overflow-hidden">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">Total Agen Terdaftar</p>
            <h3 class="text-xl font-black text-gray-800">{{ number_format($stats['total_agen']) }} <span class="text-sm font-normal">Agen</span></h3>
            <p class="text-[10px] font-semibold text-gray-500 mt-1">Status aktif di sistem</p>
            <i class="fa-solid fa-users absolute -right-3 -bottom-3 text-5xl text-indigo-500 opacity-10"></i>
        </div>

        <div class="bg-blue-50 p-4 rounded-xl shadow-sm border border-blue-200 border-l-4 border-l-blue-500 relative overflow-hidden">
            <p class="text-[10px] text-blue-600 font-bold uppercase tracking-wide">Default Fee Sistem</p>
            <h3 class="text-xl font-black text-blue-700">40%</h3>
            <p class="text-[10px] font-semibold text-blue-500 mt-1">Gunakan fitur edit untuk mengubah fee spesifik</p>
            <i class="fa-solid fa-percent absolute -right-3 -bottom-3 text-5xl text-blue-500 opacity-10"></i>
        </div>

        <div class="bg-orange-50 p-4 rounded-xl shadow-sm border border-orange-200 border-l-4 border-l-orange-500 relative overflow-hidden">
            <p class="text-[10px] text-orange-600 font-bold uppercase tracking-wide">Total Komisi Dibagikan</p>
            <h3 class="text-xl font-black text-orange-700">Rp {{ number_format($stats['total_komisi_dibayar'], 0, ',', '.') }}</h3>
            <p class="text-[10px] font-semibold text-orange-500 mt-1">Total masuk ke dompet agen</p>
            <i class="fa-solid fa-hand-holding-dollar absolute -right-3 -bottom-3 text-5xl text-orange-500 opacity-10"></i>
        </div>

        <div class="bg-green-50 p-4 rounded-xl shadow-sm border border-green-200 border-l-4 border-l-green-600 relative overflow-hidden">
            <p class="text-[10px] text-green-700 font-bold uppercase tracking-wide">Total Laba Sancaka (Bersih)</p>
            <h3 class="text-xl font-black text-green-800">Rp {{ number_format($stats['total_laba_sancaka'], 0, ',', '.') }}</h3>
            <p class="text-[10px] font-semibold text-green-600 mt-1">Sisa persentase untuk kas pusat</p>
            <i class="fa-solid fa-vault absolute -right-3 -bottom-3 text-5xl text-green-600 opacity-10"></i>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- FILTER PENCARIAN -->
    <!-- ========================================== -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('admin.komisi-agent.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari Agen (Nama / Toko / WA)</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full border-gray-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari Agen
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- TABEL DATA LENGKAP & AKSI -->
    <!-- ========================================== -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="p-4 font-bold">Profil Agen & Toko</th>
                        <th class="p-4 font-bold text-center">Persentase Fee (Komisi)</th>
                        <th class="p-4 font-bold text-center">Bagian Pusat (Laba)</th>
                        <th class="p-4 font-bold min-w-[200px]">Total Histori Transaksi</th>
                        <th class="p-4 font-bold text-right">Aksi & Pengaturan</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($agents as $agen)
                        @php
                            // Default 40% jika tidak ada settingan khusus
                            $fee_agen = $agen->agentFee ? $agen->agentFee->fee_percentage : 40;
                            $fee_pusat = 100 - $fee_agen;

                            // Hitung transaksi real-time (bisa dipindah ke controller jika query berat)
                            $total_transaksi = \App\Models\PesananAutokirim::where('user_id', $agen->id)->whereNotIn('status', ['batal', 'gagal'])->count();
                            $omzet_kotor = \App\Models\PesananAutokirim::where('user_id', $agen->id)->whereNotIn('status', ['batal', 'gagal'])->sum('ongkir');
                        @endphp
                    <tr class="hover:bg-gray-50/50 transition">

                        <!-- PROFIL AGEN -->
                        <td class="p-4 align-top">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold shrink-0">
                                    {{ strtoupper(substr($agen->nama_lengkap, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 text-sm">{{ $agen->nama_lengkap }}</p>
                                    <p class="text-[11px] text-gray-500 uppercase tracking-wider"><i class="fa-solid fa-store mr-1"></i> {{ $agen->store_name ?? 'Toko Belum Diset' }}</p>
                                    <p class="text-xs text-gray-600 mt-1"><i class="fa-brands fa-whatsapp text-green-500"></i> {{ $agen->no_wa }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- PERSENTASE FEE AGEN -->
                        <td class="p-4 align-middle text-center">
                            @if($agen->agentFee)
                                <span class="bg-orange-100 text-orange-700 font-black px-3 py-1.5 rounded-lg text-sm border border-orange-200 shadow-sm">
                                    {{ $fee_agen }}%
                                </span>
                                <p class="text-[10px] text-orange-500 mt-2 font-semibold">Kustom Fee</p>
                            @else
                                <span class="bg-gray-100 text-gray-700 font-bold px-3 py-1.5 rounded-lg text-sm border border-gray-200">
                                    {{ $fee_agen }}%
                                </span>
                                <p class="text-[10px] text-gray-400 mt-2">Default Sistem</p>
                            @endif
                        </td>

                        <!-- BAGIAN PUSAT -->
                        <td class="p-4 align-middle text-center">
                            <span class="text-green-700 font-bold text-lg">{{ $fee_pusat }}%</span>
                        </td>

                        <!-- HISTORI TRANSAKSI -->
                        <td class="p-4 align-top">
                            <div class="text-[11px] space-y-1">
                                <div class="flex justify-between text-gray-600">
                                    <span>Total Sukses:</span>
                                    <span class="font-bold text-gray-800">{{ number_format($total_transaksi) }} Resi</span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Omzet Ongkir:</span>
                                    <span class="font-bold text-gray-800">Rp {{ number_format($omzet_kotor, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-indigo-600 font-semibold mt-1 pt-1 border-t border-gray-100">
                                    <span>Saldo Dompet:</span>
                                    <span>Rp {{ number_format($agen->saldo ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- AKSI -->
                        <td class="p-4 align-middle">
                            <div class="flex flex-wrap gap-2 justify-end">
                                <!-- Tombol Buka Modal -->
                                <button type="button"
                                        onclick="openFeeModal({{ $agen->id }}, '{{ $agen->nama_lengkap }}', {{ $fee_agen }})"
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold px-3 py-1.5 rounded text-xs transition shadow-sm"
                                        title="Ubah Persentase Komisi">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Edit Fee
                                </button>

                                @if($agen->agentFee)
                                    <!-- Tombol Reset Fee (Hanya muncul jika fee custom) -->
                                    <button type="button"
                                            onclick="confirmReset('{{ route('admin.komisi-agent.destroy', $agen->id) }}')"
                                            class="bg-red-100 hover:bg-red-200 text-red-700 w-8 h-8 rounded flex items-center justify-center shadow-sm"
                                            title="Reset ke Default Sistem (40%)">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                @endif
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">Tidak ada agen ditemukan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $agents->links() }}
        </div>
    </div>

    <!-- Form Tersembunyi untuk Reset Fee -->
    <form id="resetForm" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <!-- ========================================== -->
    <!-- MODAL EDIT FEE AGEN -->
    <!-- ========================================== -->
    <div id="feeModal" class="fixed inset-0 z-[100] hidden bg-gray-900/60 backdrop-blur-sm flex justify-center items-center">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-200" id="feeModalContent">

            <form id="formUpdateFee" method="POST" action="">
                @csrf
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-5 text-white relative overflow-hidden">
                    <h3 class="font-bold text-lg relative z-10"><i class="fa-solid fa-percent mr-2"></i> Atur Komisi Spesifik</h3>
                    <p class="text-xs text-blue-100 relative z-10 mt-1">Ubah porsi pembagian hasil untuk agen ini.</p>
                </div>

                <div class="p-5 space-y-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm">
                        <span class="text-gray-500 font-semibold block mb-1">Nama Agen:</span>
                        <strong id="modal_agent_name" class="text-gray-800 uppercase text-lg"></strong>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Persentase Jatah Agen (%)</label>
                        <div class="relative">
                            <input type="number" name="fee_percentage" id="modal_fee_input" min="1" max="100" required
                                class="w-full border-2 border-blue-200 rounded-xl px-4 py-3 text-xl font-black text-gray-800 focus:ring-blue-500 focus:border-blue-500">
                            <span class="absolute right-4 top-3.5 text-xl font-black text-gray-400">%</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2"><i class="fa-solid fa-circle-info text-blue-500"></i> Laba bersih pusat (Sancaka) akan otomatis menyesuaikan kekurangannya hingga 100%.</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 flex justify-end gap-2 border-t border-gray-100">
                    <button type="button" onclick="closeFeeModal()" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-4 rounded-xl transition shadow-sm">Batal</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl transition shadow-md">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT INTERAKTIF -->
    <script>
        function openFeeModal(id, name, currentFee) {
            document.getElementById('modal_agent_name').innerText = name;
            document.getElementById('modal_fee_input').value = currentFee;

            // Set action URL form dynamically
            document.getElementById('formUpdateFee').action = `/admin/komisi-agent/update/${id}`;

            const modal = document.getElementById('feeModal');
            modal.classList.remove('hidden');

            setTimeout(() => {
                document.getElementById('feeModalContent').classList.remove('scale-95', 'opacity-0');
                document.getElementById('feeModalContent').classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeFeeModal() {
            document.getElementById('feeModalContent').classList.remove('scale-100', 'opacity-100');
            document.getElementById('feeModalContent').classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                document.getElementById('feeModal').classList.add('hidden');
            }, 200);
        }

        function confirmReset(url) {
            if(confirm('Yakin ingin mereset agen ini kembali ke persentase default (40%)?')) {
                let form = document.getElementById('resetForm');
                form.action = url;
                form.submit();
            }
        }
    </script>
</div>
@endsection
