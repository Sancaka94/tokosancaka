@extends('layouts.admin')

@section('title', 'Broadcast WhatsApp Center')

@push('styles')
    {{-- Meta CSRF untuk AJAX Request (Penting untuk Fitur AI) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Animasi Loading untuk AI */
        .loader { border: 2px solid #f3f3f3; border-top: 2px solid #6b21a8; border-radius: 50%; width: 14px; height: 14px; animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    
    {{-- HEADER --}}
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 flex justify-between items-center border-l-4 border-green-500">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fab fa-whatsapp text-green-500 mr-2"></i> Broadcast WhatsApp</h1>
            <p class="text-gray-500 text-sm mt-1">Kirim pesan massal cerdas dengan AI dan Personalisasi.</p>
        </div>
        <div class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
            <i class="fas fa-shield-alt mr-1"></i> Admin Mode
        </div>
    </div>

    {{-- NOTIFIKASI SYSTEM --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold"><i class="fas fa-check-circle mr-1"></i> Berhasil!</strong> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal!</strong> {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-lg overflow-hidden min-h-[650px]">
        
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
                        
                        {{-- KOLOM KIRI: EDITOR PESAN --}}
                        <div class="lg:col-span-1 space-y-4">
                            
                            {{-- BOX AI GENERATOR --}}
                            <div class="bg-gradient-to-r from-purple-50 to-blue-50 p-4 rounded-lg border border-blue-100 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-2 opacity-10"><i class="fas fa-robot text-4xl text-purple-800"></i></div>
                                <label class="block text-xs font-bold text-purple-700 mb-2 flex items-center">
                                    <i class="fas fa-magic mr-2"></i> Buat Pesan Otomatis (AI)
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" id="aiTopic" class="w-full text-sm border-purple-200 rounded focus:ring-purple-500 focus:border-purple-500" placeholder="Topik: Promo Lebaran...">
                                    <button type="button" onclick="generateAiMessage()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded text-xs font-bold transition shadow flex items-center gap-2">
                                        <span id="btnAiText">Generate</span>
                                        <div id="btnAiLoad" class="loader hidden"></div>
                                    </button>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-1 italic">AI akan otomatis menambahkan Sapaan Nama & Footer Sancaka.</p>
                            </div>

                            {{-- EDITOR TEXTAREA --}}
                            <div>
                                <div class="flex justify-between items-end mb-2">
                                    <label class="block text-sm font-bold text-gray-700">Isi Pesan</label>
                                    {{-- Tombol Insert Variable --}}
                                    <button type="button" onclick="insertVariable()" class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded transition border border-gray-300" title="Sisipkan nama pelanggan otomatis">
                                        <i class="fas fa-user-tag mr-1 text-blue-600"></i> + {name}
                                    </button>
                                </div>
                                <textarea name="message" id="messageBox" rows="12" class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm text-sm font-mono leading-relaxed" placeholder="Halo {name}, ..." required></textarea>
                                
                                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-100 rounded text-xs text-yellow-800 space-y-1">
                                    <p class="font-bold"><i class="fas fa-lightbulb mr-1"></i> Tips:</p>
                                    <ul class="list-disc pl-4 space-y-1">
                                        <li>Gunakan <strong>{name}</strong> agar pesan menyebut nama pelanggan secara otomatis.</li>
                                        <li>Jika menggunakan {name}, pesan akan dikirim satu per satu (lebih aman tapi sedikit lebih lama).</li>
                                    </ul>
                                </div>
                            </div>
                            
                            {{-- TOMBOL KIRIM --}}
                            <button type="submit" onclick="return confirm('Yakin ingin mengirim pesan broadcast ini? Pastikan kuota Fonnte mencukupi.')" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition transform hover:-translate-y-1 flex justify-center items-center gap-2">
                                <i class="fas fa-paper-plane"></i> KIRIM SEKARANG
                            </button>
                        </div>

                        {{-- KOLOM KANAN: PILIH PENERIMA --}}
                        <div class="lg:col-span-2 bg-gray-50 rounded-lg border border-gray-200 p-4 flex flex-col h-full">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-700"><i class="fas fa-users mr-2"></i> Target Penerima</h3>
                                <span class="text-xs bg-white border px-3 py-1 rounded-full shadow-sm font-mono">
                                    Total Dipilih: <strong id="totalSelected" class="text-green-600 text-sm">0</strong>
                                </span>
                            </div>

                            {{-- Sub-Tabs Sumber Data --}}
                            <div class="flex space-x-2 mb-3">
                                <button type="button" onclick="toggleSource('pelanggan')" id="btn-src-pelanggan" class="flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-blue-600 ring-2 ring-blue-100 transition">
                                    <i class="fas fa-user-tie mr-1"></i> Pelanggan ({{ $pelanggans->count() }})
                                </button>
                                <button type="button" onclick="toggleSource('kontak')" id="btn-src-kontak" class="flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-gray-500 hover:bg-gray-50 transition">
                                    <i class="fas fa-address-book mr-1"></i> Kontak Lain ({{ $kontaks->count() }})
                                </button>
                            </div>

                            {{-- SEARCH BOX (Client Side) --}}
                            <div class="relative mb-3">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
                                <input type="text" id="searchReceiver" onkeyup="filterList()" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Cari nama atau nomor WA di list bawah...">
                            </div>

                            {{-- LIST PELANGGAN --}}
                            <div id="list-pelanggan" class="flex-1 overflow-y-auto bg-white border border-gray-300 rounded-lg shadow-inner max-h-[450px]">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-100 sticky top-0 z-10 shadow-sm text-xs uppercase text-gray-600">
                                        <tr>
                                            <th class="p-3 w-10 text-center bg-gray-100">
                                                <input type="checkbox" onchange="checkAll(this, '.cb-pel')" class="rounded text-green-600 focus:ring-green-500 cursor-pointer" title="Pilih Semua">
                                            </th>
                                            <th class="p-3 bg-gray-100">Nama Pelanggan</th>
                                            <th class="p-3 bg-gray-100">No. WA</th>
                                            <th class="p-3 text-center bg-gray-100">Ket</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100" id="tbody-pelanggan">
                                        @foreach($pelanggans as $p)
                                        <tr class="hover:bg-blue-50 transition cursor-pointer searchable-row" onclick="toggleRow(this)">
                                            <td class="p-3 text-center">
                                                {{-- VALUE: NOMOR|NAMA|TIPE --}}
                                                <input type="checkbox" name="targets[]" value="{{ $p->nomor_wa }}|{{ $p->nama_pelanggan }}|Pelanggan" class="cb-pel rounded text-blue-600 focus:ring-blue-500 cursor-pointer" onclick="event.stopPropagation()">
                                            </td>
                                            <td class="p-3 font-medium text-gray-900 search-name">{{ $p->nama_pelanggan }}</td>
                                            <td class="p-3 text-gray-500 font-mono text-xs search-no">{{ $p->nomor_wa }}</td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $p->keterangan == 'Agen' ? 'bg-purple-100 text-purple-700 border-purple-200' : 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                                    {{ $p->keterangan }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- LIST KONTAK --}}
                            <div id="list-kontak" class="hidden flex-1 overflow-y-auto bg-white border border-gray-300 rounded-lg shadow-inner max-h-[450px]">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-100 sticky top-0 z-10 shadow-sm text-xs uppercase text-gray-600">
                                        <tr>
                                            <th class="p-3 w-10 text-center bg-gray-100">
                                                <input type="checkbox" onchange="checkAll(this, '.cb-kon')" class="rounded text-green-600 focus:ring-green-500 cursor-pointer">
                                            </th>
                                            <th class="p-3 bg-gray-100">Nama Kontak</th>
                                            <th class="p-3 bg-gray-100">No. HP</th>
                                            <th class="p-3 text-center bg-gray-100">Tipe</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100" id="tbody-kontak">
                                        @foreach($kontaks as $k)
                                        <tr class="hover:bg-blue-50 transition cursor-pointer searchable-row" onclick="toggleRow(this)">
                                            <td class="p-3 text-center">
                                                <input type="checkbox" name="targets[]" value="{{ $k->no_hp }}|{{ $k->nama }}|Kontak" class="cb-kon rounded text-blue-600 focus:ring-blue-500 cursor-pointer" onclick="event.stopPropagation()">
                                            </td>
                                            <td class="p-3 font-medium text-gray-900 search-name">{{ $k->nama }}</td>
                                            <td class="p-3 text-gray-500 font-mono text-xs search-no">{{ $k->no_hp }}</td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $k->tipe == 'Pengirim' ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-green-50 text-green-700 border-green-100' }}">
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
                </form>
            </div>

            {{-- ================= TAB 2: RIWAYAT & LAPORAN ================= --}}
            <div id="content-riwayat" class="hidden">
                
                {{-- TOOLBAR FILTER LENGKAP --}}
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                    <form action="{{ route('broadcast.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        
                        {{-- Search Text --}}
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Cari Pesan/Nama</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Keyword..." class="w-full pl-8 border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        {{-- Tanggal Mulai --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500">
                        </div>

                        {{-- Tanggal Sampai --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500">
                        </div>

                        {{-- Filter Tipe --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Tipe Target</label>
                            <select name="filter_type" class="w-full border-gray-300 rounded-lg text-sm bg-white focus:ring-blue-500">
                                <option value="">Semua</option>
                                <option value="Pelanggan" {{ request('filter_type') == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                                <option value="Kontak" {{ request('filter_type') == 'Kontak' ? 'selected' : '' }}>Kontak</option>
                            </select>
                        </div>

                        {{-- Tombol Filter --}}
                        <div class="md:col-span-1">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-bold hover:bg-blue-700 shadow-sm transition"><i class="fas fa-filter"></i> Filter</button>
                        </div>

                        {{-- Tombol Reset --}}
                        @if(request()->has('search') || request()->has('start_date'))
                        <div class="md:col-span-1 text-center">
                            <a href="{{ route('broadcast.index') }}" class="text-sm text-red-500 hover:text-red-700 font-semibold underline">Reset</a>
                        </div>
                        @endif
                    </form>
                </div>

                {{-- TOOLBAR EXPORT & DELETE --}}
                <div class="flex flex-wrap justify-end gap-2 mb-4 items-center">
                    
                    {{-- Tombol Hapus Semua (BARU) --}}
                    <form action="{{ route('broadcast.destroy.all') }}" method="POST" onsubmit="return confirm('PERINGATAN KERAS:\n\nApakah Anda yakin ingin MENGHAPUS SEMUA riwayat broadcast?\n\nData yang dihapus tidak dapat dikembalikan!');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex items-center bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-black shadow-sm transition border border-gray-700">
                            <i class="fas fa-trash-alt mr-2"></i> Hapus Semua Riwayat
                        </button>
                    </form>

                    {{-- Divider Kecil --}}
                    <div class="w-px h-8 bg-gray-300 mx-1"></div>

                    {{-- Tombol Export Excel --}}
                    <a href="{{ route('broadcast.export.excel', request()->all()) }}" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-700 shadow-sm transition">
                        <i class="fas fa-file-excel mr-2"></i> Export Excel
                    </a>

                    {{-- Tombol Export PDF --}}
                    <a href="{{ route('broadcast.export.pdf', request()->all()) }}" class="flex items-center bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700 shadow-sm transition">
                        <i class="fas fa-file-pdf mr-2"></i> Export PDF
                    </a>
                </div>

                {{-- TABEL RIWAYAT --}}
                <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b">
                            <tr>
                                <th class="px-6 py-3">Waktu Kirim</th>
                                <th class="px-6 py-3">Penerima</th>
                                <th class="px-6 py-3">Isi Pesan</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($histories as $h)
                            <tr class="hover:bg-gray-50 transition group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-gray-900 font-semibold">{{ $h->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $h->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $h->target_name }}</div>
                                    <div class="text-xs font-mono text-gray-500 mb-1">{{ $h->target_number }}</div>
                                    <span class="bg-gray-100 text-gray-600 text-[10px] px-2 py-0.5 rounded border border-gray-200">{{ $h->target_type }}</span>
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate text-gray-600 cursor-pointer group-hover:text-blue-600" onclick="showDetail('{{ $h->target_name }}', '{{ $h->message }}')" title="Klik untuk baca lengkap">
                                    {{ Str::limit($h->message, 60) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded border {{ $h->status == 'Terkirim' || str_contains($h->status, 'Terkirim') ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200' }}">
                                        {{ $h->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <button onclick="showDetail('{{ $h->target_name }}', '{{ $h->message }}')" class="p-1.5 text-gray-500 hover:text-blue-600 bg-gray-50 hover:bg-blue-50 rounded shadow-sm" title="Lihat"><i class="fas fa-eye"></i></button>
                                        <button onclick="resendMessage('{{ $h->message }}')" class="p-1.5 text-gray-500 hover:text-yellow-600 bg-gray-50 hover:bg-yellow-50 rounded shadow-sm" title="Gunakan Lagi"><i class="fas fa-reply"></i></button>
                                        <form action="{{ route('broadcast.destroy', $h->id) }}" method="POST" onsubmit="return confirm('Hapus riwayat ini?');" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 text-gray-500 hover:text-red-600 bg-gray-50 hover:bg-red-50 rounded shadow-sm" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                        <p>Tidak ada riwayat broadcast yang cocok dengan filter.</p>
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

{{-- MODAL DETAIL PESAN --}}
<div id="detailModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden flex justify-center items-center z-50 transition-opacity backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden transform transition-all scale-100">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-envelope-open-text mr-2 text-blue-600"></i> Detail Pesan</h3>
            <button onclick="document.getElementById('detailModal').classList.add('hidden')" class="text-gray-400 hover:text-red-600 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6">
            <div class="mb-4 bg-green-50 p-3 rounded border border-green-100">
                <span class="text-xs text-green-600 uppercase font-bold block mb-1">Penerima</span>
                <p id="modalName" class="text-gray-900 font-bold text-lg"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase font-bold mb-1 block">Isi Pesan</span>
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 text-gray-700 text-sm whitespace-pre-wrap leading-relaxed max-h-60 overflow-y-auto" id="modalMessage"></div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3 flex justify-end">
            <button onclick="document.getElementById('detailModal').classList.add('hidden')" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold transition">Tutup</button>
        </div>
    </div>
</div>

{{-- JAVASCRIPT --}}
<script>
    // --- 1. FITUR AI GENERATOR ---
    async function generateAiMessage() {
        const topic = document.getElementById('aiTopic').value;
        if(!topic) {
            alert('Harap isi topik pesan terlebih dahulu! Contoh: "Promo Diskon Lebaran"');
            document.getElementById('aiTopic').focus();
            return;
        }

        // UI Loading
        const btnText = document.getElementById('btnAiText');
        const btnLoad = document.getElementById('btnAiLoad');
        btnText.innerText = "Thinking...";
        btnLoad.classList.remove('hidden');

        try {
            const response = await fetch("{{ route('broadcast.ai') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ topic: topic })
            });

            const data = await response.json();

            if(data.text) {
                document.getElementById('messageBox').value = data.text;
                // Efek visual sukses
                document.getElementById('messageBox').classList.add('ring-2', 'ring-purple-500');
                setTimeout(() => document.getElementById('messageBox').classList.remove('ring-2', 'ring-purple-500'), 500);
            } else {
                alert('Gagal generate AI. ' + (data.error || 'Silakan coba lagi.'));
            }

        } catch (error) {
            console.error(error);
            alert('Terjadi kesalahan koneksi ke AI Server.');
        } finally {
            // Reset UI
            btnText.innerText = "Generate";
            btnLoad.classList.add('hidden');
        }
    }

    // --- 2. SISIPKAN VARIABLE {name} ---
    function insertVariable() {
        const textarea = document.getElementById('messageBox');
        const textToInsert = "{name}";
        
        // Modern Browser Way
        if (textarea.selectionStart || textarea.selectionStart == '0') {
            var startPos = textarea.selectionStart;
            var endPos = textarea.selectionEnd;
            textarea.value = textarea.value.substring(0, startPos) +
                textToInsert +
                textarea.value.substring(endPos, textarea.value.length);
            
            // Kembalikan fokus dan kursor setelah variabel
            textarea.focus();
            textarea.selectionStart = startPos + textToInsert.length;
            textarea.selectionEnd = startPos + textToInsert.length;
        } else {
            textarea.value += textToInsert;
            textarea.focus();
        }
    }

    // --- 3. UI TABS LOGIC ---
    function switchMainTab(tab) {
        document.getElementById('content-kirim').classList.add('hidden');
        document.getElementById('content-riwayat').classList.add('hidden');
        
        const btnKirim = document.getElementById('tab-btn-kirim');
        const btnRiwayat = document.getElementById('tab-btn-riwayat');
        
        // Reset Styles
        const inactiveClass = "w-1/2 py-4 text-center font-bold text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-all hover:bg-gray-100";
        btnKirim.className = inactiveClass;
        btnRiwayat.className = inactiveClass;

        // Activate Selected
        document.getElementById('content-' + tab).classList.remove('hidden');
        const activeClass = "w-1/2 py-4 text-center font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition-all hover:bg-gray-50";
        document.getElementById('tab-btn-' + tab).className = activeClass;
    }

    function toggleSource(source) {
        document.getElementById('list-pelanggan').classList.add('hidden');
        document.getElementById('list-kontak').classList.add('hidden');
        
        // Reset Style
        const inactiveBtn = "flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-gray-500 hover:bg-gray-50 transition";
        document.getElementById('btn-src-pelanggan').className = inactiveBtn;
        document.getElementById('btn-src-kontak').className = inactiveBtn;

        // Active
        document.getElementById('list-' + source).classList.remove('hidden');
        document.getElementById('btn-src-' + source).className = "flex-1 py-2 px-4 rounded-lg text-sm font-bold bg-white border border-gray-300 shadow-sm text-blue-600 ring-2 ring-blue-100 transition";
        
        // Reset Search saat pindah tab agar hasil search tidak "nyangkut"
        document.getElementById('searchReceiver').value = '';
        filterList();
    }

    // --- 4. SEARCH & FILTER LIST PENERIMA (JS) ---
    function filterList() {
        const input = document.getElementById('searchReceiver');
        const filter = input.value.toLowerCase();
        
        // Deteksi tabel mana yang sedang aktif
        let activeTbodyId = !document.getElementById('list-pelanggan').classList.contains('hidden') 
                            ? 'tbody-pelanggan' : 'tbody-kontak';
        
        const rows = document.getElementById(activeTbodyId).getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const nameCol = rows[i].getElementsByClassName('search-name')[0];
            const noCol = rows[i].getElementsByClassName('search-no')[0];
            
            if (nameCol && noCol) {
                const nameVal = nameCol.textContent || nameCol.innerText;
                const noVal = noCol.textContent || noCol.innerText;

                if (nameVal.toLowerCase().indexOf(filter) > -1 || noVal.indexOf(filter) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    }

    // --- 5. CHECKBOX LOGIC ---
    function checkAll(source, className) {
        // Hanya centang baris yang terlihat (hasil search)
        const visibleRows = Array.from(document.querySelectorAll(className)).filter(cb => {
            return cb.closest('tr').style.display !== 'none';
        });
        
        visibleRows.forEach(cb => cb.checked = source.checked);
        updateTotal();
    }
    
    function toggleRow(row) {
        const cb = row.querySelector('input[type="checkbox"]');
        cb.checked = !cb.checked;
        updateTotal();
    }

    // Listener counter
    document.addEventListener('change', function(e) {
        if(e.target.name === 'targets[]' || e.target.type === 'checkbox') {
            updateTotal();
        }
    });

    function updateTotal() {
        const count = document.querySelectorAll('input[name="targets[]"]:checked').length;
        document.getElementById('totalSelected').innerText = count;
    }

    // --- 6. HELPER ACTIONS (Resend & View) ---
    function resendMessage(msg) {
        document.getElementById('messageBox').value = msg;
        switchMainTab('kirim');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Toast Notification
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-5 right-5 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 text-sm font-bold animate-bounce';
        toast.innerHTML = '<i class="fas fa-check mr-2"></i> Pesan disalin ke editor!';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    function showDetail(name, msg) {
        document.getElementById('modalName').innerText = name;
        document.getElementById('modalMessage').innerText = msg;
        document.getElementById('detailModal').classList.remove('hidden');
    }

    // --- AUTO OPEN TAB BERDASARKAN REQUEST ---
    @if(request('search') || request('start_date') || request('page'))
        switchMainTab('riwayat');
    @else
        switchMainTab('kirim');
    @endif


</script>
@endsection