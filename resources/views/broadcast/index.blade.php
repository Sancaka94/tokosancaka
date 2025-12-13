@extends('layouts.admin')

@section('title', 'Broadcast WhatsApp Center')

@section('content')
<div class="container mx-auto px-4 py-6">
    
    {{-- HEADER HALAMAN --}}
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 flex justify-between items-center border-l-4 border-green-500">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fab fa-whatsapp text-green-500 mr-2"></i> Broadcast WhatsApp</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola pengiriman pesan massal dan pantau riwayat pengiriman.</p>
        </div>
        <div class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
            Admin Mode
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
            <strong class="font-bold"><i class="fas fa-check-circle mr-1"></i> Sukses!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
            <strong class="font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-lg overflow-hidden min-h-[600px]">
        
        {{-- NAVIGASI TAB UTAMA --}}
        <div class="flex border-b border-gray-200 bg-gray-50">
            <button onclick="switchMainTab('kirim')" id="tab-btn-kirim" class="w-1/2 py-4 text-center font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition-all hover:bg-gray-50">
                <i class="fas fa-paper-plane mr-2"></i> Kirim Pesan Baru
            </button>
            <button onclick="switchMainTab('riwayat')" id="tab-btn-riwayat" class="w-1/2 py-4 text-center font-bold text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-all hover:bg-gray-100">
                <i class="fas fa-history mr-2"></i> Riwayat & Laporan
            </button>
        </div>

        <div class="p-6">
            
            {{-- ================= TAB 1: KIRIM PESAN ================= --}}
            <div id="content-kirim" class="block">
                <form action="{{ route('broadcast.send') }}" method="POST" id="broadcastForm">
                    @csrf
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        {{-- KOLOM KIRI: Input Pesan --}}
                        <div class="lg:col-span-1 space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Isi Pesan Broadcast</label>
                                <textarea name="message" id="messageBox" rows="8" class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm resize-none text-sm" placeholder="Halo Kak, ada promo spesial..." required></textarea>
                                <p class="text-xs text-gray-400 mt-2">* Pesan ini akan dikirim ke semua nomor yang dicentang.</p>
                            </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <h4 class="font-bold text-blue-800 text-sm mb-2"><i class="fas fa-info-circle mr-1"></i> Informasi</h4>
                                <ul class="text-xs text-blue-700 space-y-1 list-disc pl-4">
                                    <li>Jeda pengiriman: 2 detik/pesan.</li>
                                    <li>Otomatis ubah 08xx ke 628xx.</li>
                                    <li>Nomor duplikat akan otomatis dihapus.</li>
                                </ul>
                            </div>

                            <button type="submit" onclick="return confirm('Yakin ingin mengirim broadcast ini?')" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition transform hover:-translate-y-1">
                                <i class="fas fa-paper-plane mr-2"></i> KIRIM SEKARANG
                            </button>
                        </div>

                        {{-- KOLOM KANAN: Pilih Penerima --}}
                        <div class="lg:col-span-2 bg-gray-50 rounded-lg border border-gray-200 p-4">
                            <h3 class="font-bold text-gray-700 mb-4 flex justify-between items-center">
                                <span><i class="fas fa-users mr-2"></i> Pilih Penerima</span>
                                <span class="text-sm font-normal bg-white px-3 py-1 rounded border shadow-sm">Terpilih: <strong id="totalSelected" class="text-green-600">0</strong></span>
                            </h3>

                            {{-- Sub-Tabs Sumber Data --}}
                            <div class="flex space-x-2 mb-4">
                                <button type="button" onclick="toggleSource('pelanggan')" id="btn-src-pelanggan" class="flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-blue-600 ring-2 ring-blue-100">
                                    Data Pelanggan ({{ $pelanggans->count() }})
                                </button>
                                <button type="button" onclick="toggleSource('kontak')" id="btn-src-kontak" class="flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-gray-500 hover:bg-gray-50">
                                    Data Kontak ({{ $kontaks->count() }})
                                </button>
                            </div>

                            {{-- List Pelanggan --}}
                            <div id="list-pelanggan" class="block">
                                <div class="flex justify-between items-center mb-2 px-1">
                                    <span class="text-xs font-bold text-gray-500 uppercase">Tabel Pelanggan</span>
                                    <label class="inline-flex items-center cursor-pointer text-xs">
                                        <input type="checkbox" onchange="checkAll(this, '.cb-pel')" class="rounded text-green-600 focus:ring-green-500">
                                        <span class="ml-1 text-gray-600">Pilih Semua</span>
                                    </label>
                                </div>
                                <div class="overflow-y-auto h-80 bg-white border border-gray-300 rounded-lg shadow-inner">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-100 sticky top-0 z-10">
                                            <tr>
                                                <th class="p-3 w-10 text-center">#</th>
                                                <th class="p-3">Nama Pelanggan</th>
                                                <th class="p-3">No. WA</th>
                                                <th class="p-3 text-center">Tipe</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($pelanggans as $p)
                                            <tr class="hover:bg-blue-50 transition cursor-pointer" onclick="document.getElementById('p-{{ $p->id }}').click()">
                                                <td class="p-3 text-center">
                                                    {{-- VALUE: NOMOR|NAMA|TIPE --}}
                                                    <input type="checkbox" name="targets[]" value="{{ $p->nomor_wa }}|{{ $p->nama_pelanggan }}|Pelanggan" id="p-{{ $p->id }}" class="cb-pel rounded text-blue-600 focus:ring-blue-500" onclick="event.stopPropagation()">
                                                </td>
                                                <td class="p-3 font-medium text-gray-900">{{ $p->nama_pelanggan }}</td>
                                                <td class="p-3 text-gray-500 font-mono text-xs">{{ $p->nomor_wa }}</td>
                                                <td class="p-3 text-center">
                                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $p->keterangan == 'Agen' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' }}">
                                                        {{ $p->keterangan }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- List Kontak --}}
                            <div id="list-kontak" class="hidden">
                                <div class="flex justify-between items-center mb-2 px-1">
                                    <span class="text-xs font-bold text-gray-500 uppercase">Tabel Kontak</span>
                                    <label class="inline-flex items-center cursor-pointer text-xs">
                                        <input type="checkbox" onchange="checkAll(this, '.cb-kon')" class="rounded text-green-600 focus:ring-green-500">
                                        <span class="ml-1 text-gray-600">Pilih Semua</span>
                                    </label>
                                </div>
                                <div class="overflow-y-auto h-80 bg-white border border-gray-300 rounded-lg shadow-inner">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-100 sticky top-0 z-10">
                                            <tr>
                                                <th class="p-3 w-10 text-center">#</th>
                                                <th class="p-3">Nama Kontak</th>
                                                <th class="p-3">No. HP</th>
                                                <th class="p-3 text-center">Tipe</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($kontaks as $k)
                                            <tr class="hover:bg-blue-50 transition cursor-pointer" onclick="document.getElementById('k-{{ $k->id }}').click()">
                                                <td class="p-3 text-center">
                                                    <input type="checkbox" name="targets[]" value="{{ $k->no_hp }}|{{ $k->nama }}|Kontak" id="k-{{ $k->id }}" class="cb-kon rounded text-blue-600 focus:ring-blue-500" onclick="event.stopPropagation()">
                                                </td>
                                                <td class="p-3 font-medium text-gray-900">{{ $k->nama }}</td>
                                                <td class="p-3 text-gray-500 font-mono text-xs">{{ $k->no_hp }}</td>
                                                <td class="p-3 text-center">
                                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $k->tipe == 'Pengirim' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                                        {{ $k->tipe }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </div>

            {{-- ================= TAB 2: RIWAYAT ================= --}}
            <div id="content-riwayat" class="hidden">
                
                {{-- Toolbar: Filter, Search, Export --}}
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    
                    {{-- Form Pencarian & Filter --}}
                    <form action="{{ route('broadcast.index') }}" method="GET" class="flex flex-wrap gap-2 w-full md:w-auto items-center">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama/No HP..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 w-full md:w-60">
                        </div>
                        
                        <select name="filter_type" class="py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="">Semua Tipe</option>
                            <option value="Pelanggan" {{ request('filter_type') == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                            <option value="Kontak" {{ request('filter_type') == 'Kontak' ? 'selected' : '' }}>Kontak</option>
                        </select>
                        
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 shadow-sm">Filter</button>
                        
                        @if(request('search') || request('filter_type'))
                            <a href="{{ route('broadcast.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-semibold underline">Reset</a>
                        @endif
                    </form>

                    {{-- Tombol Export --}}
                    <div class="flex gap-2">
                        <a href="{{ route('broadcast.export.excel', request()->all()) }}" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-700 shadow-sm transition">
                            <i class="fas fa-file-excel mr-2"></i> Excel
                        </a>
                        <a href="{{ route('broadcast.export.pdf', request()->all()) }}" class="flex items-center bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700 shadow-sm transition">
                            <i class="fas fa-file-pdf mr-2"></i> PDF
                        </a>
                    </div>
                </div>

                {{-- Tabel Riwayat --}}
                <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b">
                            <tr>
                                <th class="px-6 py-3">Tanggal</th>
                                <th class="px-6 py-3">Penerima</th>
                                <th class="px-6 py-3">Pesan</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($histories as $h)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-gray-900 font-semibold">{{ $h->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $h->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $h->target_name }}</div>
                                    <div class="text-xs font-mono text-gray-500 mb-1">{{ $h->target_number }}</div>
                                    <span class="bg-gray-100 text-gray-600 text-[10px] px-2 py-0.5 rounded border border-gray-200">{{ $h->target_type }}</span>
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate text-gray-600" title="{{ $h->message }}">
                                    {{ Str::limit($h->message, 60) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-0.5 rounded border border-green-200">
                                        {{ $h->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-3">
                                        {{-- View --}}
                                        <button onclick="showDetail('{{ $h->target_name }}', '{{ $h->message }}')" class="text-gray-500 hover:text-blue-600 transition" title="Lihat Isi Pesan">
                                            <i class="fas fa-eye text-lg"></i>
                                        </button>
                                        
                                        {{-- Resend (Edit) --}}
                                        <button onclick="resendMessage('{{ $h->message }}')" class="text-gray-500 hover:text-yellow-600 transition" title="Kirim Ulang / Edit">
                                            <i class="fas fa-reply text-lg"></i>
                                        </button>
                                        
                                        {{-- Delete --}}
                                        <form action="{{ route('broadcast.destroy', $h->id) }}" method="POST" onsubmit="return confirm('Hapus riwayat ini secara permanen?');" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-500 hover:text-red-600 transition" title="Hapus Riwayat">
                                                <i class="fas fa-trash-alt text-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                                        <p>Belum ada riwayat broadcast yang ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $histories->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

{{-- MODAL DETAIL --}}
<div id="detailModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden flex justify-center items-center z-50 transition-opacity backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform transition-all scale-100">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Detail Pesan Broadcast</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <span class="text-xs text-gray-500 uppercase font-bold">Penerima</span>
                <p id="modalName" class="text-gray-900 font-semibold text-lg"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase font-bold">Isi Pesan</span>
                <div class="mt-1 p-3 bg-blue-50 rounded-lg border border-blue-100 text-gray-700 text-sm whitespace-pre-wrap leading-relaxed" id="modalMessage"></div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3 flex justify-end">
            <button onclick="closeModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold transition">Tutup</button>
        </div>
    </div>
</div>

{{-- JAVASCRIPT --}}
<script>
    // --- TAB UTAMA (Kirim vs Riwayat) ---
    function switchMainTab(tab) {
        // Sembunyikan konten
        document.getElementById('content-kirim').classList.add('hidden');
        document.getElementById('content-riwayat').classList.add('hidden');
        
        // Reset Style Tombol Tab
        const btnKirim = document.getElementById('tab-btn-kirim');
        const btnRiwayat = document.getElementById('tab-btn-riwayat');
        
        btnKirim.className = "w-1/2 py-4 text-center font-bold text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-all hover:bg-gray-100";
        btnRiwayat.className = "w-1/2 py-4 text-center font-bold text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-all hover:bg-gray-100";

        // Tampilkan konten yang dipilih
        document.getElementById('content-' + tab).classList.remove('hidden');
        
        // Set Active Style
        const activeClass = "w-1/2 py-4 text-center font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition-all hover:bg-gray-50";
        document.getElementById('tab-btn-' + tab).className = activeClass;
    }

    // --- TAB SUB (Pelanggan vs Kontak) ---
    function toggleSource(source) {
        document.getElementById('list-pelanggan').classList.add('hidden');
        document.getElementById('list-kontak').classList.add('hidden');
        
        // Reset Button Style
        document.getElementById('btn-src-pelanggan').className = "flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-gray-500 hover:bg-gray-50";
        document.getElementById('btn-src-kontak').className = "flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-gray-500 hover:bg-gray-50";

        // Active State
        document.getElementById('list-' + source).classList.remove('hidden');
        document.getElementById('btn-src-' + source).className = "flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-blue-600 ring-2 ring-blue-100";
    }

    // --- LOGIKA CHECKBOX ---
    function checkAll(source, className) {
        document.querySelectorAll(className).forEach(cb => cb.checked = source.checked);
        updateTotal();
    }
    
    // Counter Update
    const checkboxes = document.querySelectorAll('input[name="targets[]"]');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateTotal);
    });

    function updateTotal() {
        const count = document.querySelectorAll('input[name="targets[]"]:checked').length;
        document.getElementById('totalSelected').innerText = count;
    }

    // --- FITUR RIWAYAT ---
    
    // 1. Resend / Edit
    function resendMessage(msg) {
        document.getElementById('messageBox').value = msg;
        switchMainTab('kirim');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Notifikasi kecil (Toast)
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-5 right-5 bg-blue-600 text-white px-4 py-2 rounded shadow-lg z-50 text-sm';
        toast.innerText = 'Pesan disalin! Silakan pilih penerima baru.';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // 2. Lihat Detail Modal
    function showDetail(name, msg) {
        document.getElementById('modalName').innerText = name;
        document.getElementById('modalMessage').innerText = msg;
        document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    // Auto Switch Tab jika sedang searching/pagination
    @if(request('search') || request('filter_type') || request('page'))
        switchMainTab('riwayat');
    @else
        // Default Tab
        switchMainTab('kirim');
    @endif

</script>
@endsection