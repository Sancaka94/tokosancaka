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

        <form id="bulkForm" method="POST" action="">
            @csrf

            <!-- Toolbar Bulk Action -->
            <div class="p-4 border-b border-gray-100 bg-gray-50 flex flex-wrap gap-2 items-center justify-between">
                <div class="flex items-center gap-2">
                    <button type="button" onclick="openBulkFeeModal()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition">
                        <i class="fa-solid fa-percent mr-1"></i> Edit Komisi Massal
                    </button>
                    <button type="button" onclick="confirmBulkDestroy('{{ route('admin.komisi-agent.bulk-destroy') }}')" class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition">
                        <i class="fa-solid fa-trash-can mr-1"></i> Hapus yang Dipilih
                    </button>
                </div>
                <div class="text-xs text-gray-500">Centang agen di tabel untuk menerapkan aksi massal</div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="p-4 w-10 text-center">
                                <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            <th class="p-4 font-bold">Profil Agen & Toko</th>
                            <th class="p-4 font-bold text-center">Fee (Komisi)</th>
                            <th class="p-4 font-bold text-center">Bagian Pusat</th>
                            <th class="p-4 font-bold min-w-[200px]">Total Histori Transaksi</th>
                            <th class="p-4 font-bold text-right">Aksi & Pengaturan</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        @forelse($agents as $agen)
                            @php
                                $fee_agen = $agen->fee_autokirim ?? 40;
                                $fee_pusat = 100 - $fee_agen;

                                $excluded_statuses = ['batal', 'gagal', 'waiting_payment', 'menunggu_pembayaran'];
                                $pesanan_agen = \App\Models\PesananAutokirim::where('user_id', $agen->id_pengguna)->whereNotIn('status', $excluded_statuses);

                                $total_transaksi = (clone $pesanan_agen)->count();
                                $omzet_kotor     = (clone $pesanan_agen)->sum('ongkir');
                                $total_komisi    = (clone $pesanan_agen)->sum('komisi_agen');

                                // Siapkan data untuk Modal (HINDARI ERROR QUOTES DENGAN JSON_ENCODE)
                                $agentData = [
                                    'id' => $agen->id_pengguna,
                                    'name' => $agen->nama_lengkap,
                                    'store' => $agen->store_name ?? '-',
                                    'email' => $agen->email ?? '-',
                                    'wa' => $agen->no_wa ?? '-',
                                    'bank' => $agen->bank_name ?? '-',
                                    'rekening' => $agen->bank_account_number ?? '-',
                                    'atas_nama' => $agen->bank_account_name ?? '-',
                                    'alamat' => $agen->address_detail ?? '-',
                                    'logo' => $agen->store_logo_path ? asset($agen->store_logo_path) : null,
                                    'inisial' => strtoupper(substr($agen->nama_lengkap, 0, 1))
                                ];
                            @endphp
                        <tr class="hover:bg-gray-50/50 transition">

                            <!-- Checkbox Bulk -->
                            <td class="p-4 align-top text-center">
                                <input type="checkbox" name="ids[]" value="{{ $agen->id_pengguna }}" class="rowCheckbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </td>

                            <!-- PROFIL AGEN -->
                            <td class="p-4 align-top">
                                <div class="flex items-center gap-3">
                                    <!-- Menampilkan Logo / Inisial -->
                                    @if($agen->store_logo_path)
                                        <img src="{{ asset($agen->store_logo_path) }}" alt="Logo Toko" class="w-10 h-10 rounded-full object-cover border border-gray-200 shrink-0" onerror="this.onerror=null;this.src='https://placehold.co/100x100/e0e7ff/4f46e5?text={{ strtoupper(substr($agen->nama_lengkap, 0, 1)) }}';">
                                    @else
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold shrink-0">
                                            {{ strtoupper(substr($agen->nama_lengkap, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div>
                                        <p class="font-bold text-gray-800 text-sm">{{ $agen->nama_lengkap }}</p>
                                        <p class="text-[11px] text-gray-500 uppercase tracking-wider"><i class="fa-solid fa-store mr-1"></i> {{ $agen->store_name ?? 'Toko Belum Diset' }}</p>
                                        <p class="text-xs text-gray-600 mt-1"><i class="fa-brands fa-whatsapp text-green-500"></i> {{ $agen->no_wa }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- PERSENTASE FEE AGEN -->
                            <td class="p-4 align-middle text-center">
                                @if($fee_agen != 40)
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
                                    <div class="flex justify-between text-green-600">
                                        <span>Total Komisi:</span>
                                        <span class="font-bold text-green-700">+ Rp {{ number_format($total_komisi, 0, ',', '.') }}</span>
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
                                    <!-- Tombol Detail Mata -->
                                    <button type="button"
                                            data-agent="{{ json_encode($agentData) }}"
                                            onclick="openDetailModal(this)"
                                            class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 w-8 h-8 rounded flex items-center justify-center shadow-sm transition"
                                            title="Lihat Detail Agen">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <!-- Tombol Edit Fee -->
                                    <button type="button"
                                            onclick="openFeeModal({{ $agen->id_pengguna }}, '{{ $agen->nama_lengkap }}', {{ $fee_agen }})"
                                            class="bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold px-3 py-1.5 rounded text-xs transition shadow-sm flex items-center"
                                            title="Ubah Persentase Komisi">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit Fee
                                    </button>

                                    <!-- Tombol Reset (Jika Custom) -->
                                    @if($fee_agen != 40)
                                        <button type="button"
                                                onclick="confirmAction('{{ route('admin.komisi-agent.destroy', $agen->id_pengguna) }}', 'Yakin ingin mereset agen ini kembali ke persentase default (40%)?', 'DELETE')"
                                                class="bg-orange-100 hover:bg-orange-200 text-orange-700 w-8 h-8 rounded flex items-center justify-center shadow-sm"
                                                title="Reset ke Default Sistem (40%)">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    @endif

                                    <!-- Tombol Delete Agent -->
                                    <button type="button"
                                            onclick="confirmAction('{{ route('admin.komisi-agent.delete', $agen->id_pengguna) }}', 'Yakin ingin menghapus agen ini secara permanen?', 'DELETE')"
                                            class="bg-red-100 hover:bg-red-200 text-red-700 w-8 h-8 rounded flex items-center justify-center shadow-sm transition"
                                            title="Hapus Agen">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">Tidak ada agen ditemukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $agents->links() }}
        </div>
    </div>

    <!-- Form Tersembunyi untuk Aksi Tunggal (Reset / Delete) -->
    <form id="actionForm" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="_method" id="actionMethod">
    </form>

    <!-- ========================================== -->
    <!-- MODAL DETAIL AGEN -->
    <!-- ========================================== -->
    <div id="detailModal" class="fixed inset-0 z-[100] hidden bg-gray-900/60 backdrop-blur-sm flex justify-center items-center">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-200" id="detailModalContent">

            <div class="bg-gradient-to-r from-indigo-600 to-blue-700 p-5 flex justify-between items-center text-white relative overflow-hidden">
                <i class="fa-solid fa-address-card absolute -right-4 -bottom-4 text-7xl opacity-20"></i>
                <h3 class="font-bold text-lg relative z-10"><i class="fa-solid fa-id-badge mr-2"></i> Profil Lengkap Agen</h3>
                <button onclick="closeDetailModal()" class="text-white/80 hover:text-white focus:outline-none relative z-10"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <div class="p-5 space-y-3 text-sm">
                <!-- Info Header (Logo & Nama) -->
                <div class="flex items-center gap-4 border-b border-gray-100 pb-4 mb-2">
                    <div id="md_logo_container">
                        <!-- Diisi via JS -->
                    </div>
                    <div>
                        <h4 id="md_name" class="font-black text-gray-800 text-lg uppercase"></h4>
                        <p id="md_store" class="text-xs text-indigo-600 font-bold uppercase"><i class="fa-solid fa-store"></i> </p>
                    </div>
                </div>

                <!-- List Detail -->
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500 font-semibold">Email</span>
                    <strong id="md_email" class="text-gray-800"></strong>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500 font-semibold">No. WhatsApp</span>
                    <strong id="md_wa" class="text-gray-800"></strong>
                </div>
                <div class="flex flex-col border-b border-gray-100 pb-2 pt-1">
                    <span class="text-gray-500 font-semibold mb-1"><i class="fa-solid fa-map-location-dot text-red-500 mr-1"></i> Alamat Toko</span>
                    <p id="md_alamat" class="text-gray-800 bg-gray-50 p-2 rounded border border-gray-200 text-xs leading-relaxed"></p>
                </div>

                <!-- Info Bank -->
                <div class="bg-blue-50 border border-blue-100 p-3 rounded-lg mt-2">
                    <span class="text-blue-800 font-black text-xs uppercase tracking-wider mb-2 block"><i class="fa-solid fa-building-columns mr-1"></i> Informasi Rekening Bank</span>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-blue-600">Bank:</span>
                        <strong id="md_bank" class="text-blue-900 uppercase"></strong>
                    </div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-blue-600">No. Rekening:</span>
                        <strong id="md_rekening" class="text-blue-900 font-mono text-sm"></strong>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-blue-600">Atas Nama:</span>
                        <strong id="md_atasnama" class="text-blue-900 uppercase"></strong>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-4 flex justify-end border-t border-gray-100">
                <button onclick="closeDetailModal()" class="bg-gray-800 hover:bg-black text-white font-bold py-2 px-5 rounded-xl transition shadow-md">Tutup Panel</button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL EDIT FEE AGEN (SINGLE) -->
    <!-- ========================================== -->
    <div id="feeModal" class="fixed inset-0 z-[100] hidden bg-gray-900/60 backdrop-blur-sm flex justify-center items-center">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-200" id="feeModalContent">
            <form id="formUpdateFee" method="POST" action="">
                @csrf
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-5 text-white relative overflow-hidden">
                    <h3 class="font-bold text-lg relative z-10"><i class="fa-solid fa-percent mr-2"></i> Atur Komisi Spesifik</h3>
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
                    </div>
                </div>
                <div class="bg-gray-50 p-4 flex justify-end gap-2 border-t border-gray-100">
                    <button type="button" onclick="closeFeeModal()" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-4 rounded-xl transition shadow-sm">Batal</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl transition shadow-md">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL BULK EDIT FEE -->
    <!-- ========================================== -->
    <div id="bulkFeeModal" class="fixed inset-0 z-[100] hidden bg-gray-900/60 backdrop-blur-sm flex justify-center items-center">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-200" id="bulkFeeModalContent">
            <!-- Modal ini akan mensubmit form utama (bulkForm) -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-5 text-white relative overflow-hidden">
                <h3 class="font-bold text-lg relative z-10"><i class="fa-solid fa-layer-group mr-2"></i> Edit Komisi Massal</h3>
                <p class="text-xs text-blue-100 relative z-10 mt-1">Ubah fee agen yang dicentang secara bersamaan.</p>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Set Persentase Baru (%)</label>
                    <div class="relative">
                        <input type="number" id="bulk_fee_input" min="1" max="100" placeholder="Contoh: 40"
                            class="w-full border-2 border-blue-200 rounded-xl px-4 py-3 text-xl font-black text-gray-800 focus:ring-blue-500 focus:border-blue-500">
                        <span class="absolute right-4 top-3.5 text-xl font-black text-gray-400">%</span>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 p-4 flex justify-end gap-2 border-t border-gray-100">
                <button type="button" onclick="closeBulkFeeModal()" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-4 rounded-xl transition shadow-sm">Batal</button>
                <button type="button" onclick="submitBulkEdit()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl transition shadow-md">Terapkan Massal</button>
            </div>
        </div>
    </div>


    <!-- SCRIPT INTERAKTIF -->
    <script>
        // Checkbox Check All Logic
        document.getElementById('selectAll').addEventListener('change', function(e) {
            let checkboxes = document.querySelectorAll('.rowCheckbox');
            checkboxes.forEach(cb => cb.checked = e.target.checked);
        });

        // Konfirmasi Aksi Satuan (Reset / Delete)
        function confirmAction(url, message, method) {
            if(confirm(message)) {
                let form = document.getElementById('actionForm');
                form.action = url;
                document.getElementById('actionMethod').value = method;
                form.submit();
            }
        }

        // Eksekusi Bulk Delete
        function confirmBulkDestroy(url) {
            let checked = document.querySelectorAll('.rowCheckbox:checked');
            if(checked.length === 0) {
                alert('Pilih minimal satu agen untuk dihapus!');
                return;
            }
            if(confirm('Apakah Anda yakin ingin menghapus ' + checked.length + ' agen terpilih secara permanen?')) {
                let form = document.getElementById('bulkForm');
                form.action = url;
                form.submit();
            }
        }

        // ================= MODAL EDIT FEE =================
        function openFeeModal(id, name, currentFee) {
            document.getElementById('modal_agent_name').innerText = name;
            document.getElementById('modal_fee_input').value = currentFee;
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
            setTimeout(() => { document.getElementById('feeModal').classList.add('hidden'); }, 200);
        }

        // ================= MODAL BULK EDIT =================
        function openBulkFeeModal() {
            let checked = document.querySelectorAll('.rowCheckbox:checked');
            if(checked.length === 0) {
                alert('Pilih minimal satu agen untuk diedit!');
                return;
            }
            document.getElementById('bulk_fee_input').value = '';
            const modal = document.getElementById('bulkFeeModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('bulkFeeModalContent').classList.remove('scale-95', 'opacity-0');
                document.getElementById('bulkFeeModalContent').classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeBulkFeeModal() {
            document.getElementById('bulkFeeModalContent').classList.remove('scale-100', 'opacity-100');
            document.getElementById('bulkFeeModalContent').classList.add('scale-95', 'opacity-0');
            setTimeout(() => { document.getElementById('bulkFeeModal').classList.add('hidden'); }, 200);
        }

        function submitBulkEdit() {
            let val = document.getElementById('bulk_fee_input').value;
            if(!val || val < 1 || val > 100) {
                alert("Masukkan persentase yang valid (1-100)!");
                return;
            }
            // Buat input hidden di bulk form untuk menampung nilai fee
            let form = document.getElementById('bulkForm');
            let inputFee = document.createElement("input");
            inputFee.type = "hidden";
            inputFee.name = "fee_percentage";
            inputFee.value = val;
            form.appendChild(inputFee);

            form.action = "{{ route('admin.komisi-agent.bulk-update') }}";
            form.submit();
        }

        // ================= MODAL DETAIL AGEN =================
        function openDetailModal(buttonElement) {
            const data = JSON.parse(buttonElement.getAttribute('data-agent'));

            document.getElementById('md_name').innerText = data.name;
            document.getElementById('md_store').innerHTML = `<i class="fa-solid fa-store mr-1"></i> ${data.store}`;
            document.getElementById('md_email').innerText = data.email;
            document.getElementById('md_wa').innerText = data.wa;
            document.getElementById('md_alamat').innerText = data.alamat;
            document.getElementById('md_bank').innerText = data.bank;
            document.getElementById('md_rekening').innerText = data.rekening;
            document.getElementById('md_atasnama').innerText = data.atas_nama;

            // Render Logo atau Inisial
            const logoContainer = document.getElementById('md_logo_container');
            if(data.logo) {
                logoContainer.innerHTML = `<img src="${data.logo}" class="w-14 h-14 rounded-full object-cover border-2 border-indigo-200">`;
            } else {
                logoContainer.innerHTML = `<div class="w-14 h-14 bg-indigo-200 rounded-full flex items-center justify-center text-indigo-800 font-black text-2xl">${data.inisial}</div>`;
            }

            const modal = document.getElementById('detailModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('detailModalContent').classList.remove('scale-95', 'opacity-0');
                document.getElementById('detailModalContent').classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeDetailModal() {
            document.getElementById('detailModalContent').classList.remove('scale-100', 'opacity-100');
            document.getElementById('detailModalContent').classList.add('scale-95', 'opacity-0');
            setTimeout(() => { document.getElementById('detailModal').classList.add('hidden'); }, 200);
        }
    </script>
</div>
@endsection
