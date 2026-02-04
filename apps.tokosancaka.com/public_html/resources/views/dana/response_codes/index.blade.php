@extends('layouts.app')

@section('title', 'Master Response Codes')

@section('content')
{{-- Pastikan AlpineJS sudah di-load --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>[x-cloak] { display: none !important; }</style>

<div x-data="responseCodeHandler()">
    
    {{-- HEADER SECTION --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">DANA Response Codes</h1>
            <p class="text-slate-500 text-sm font-medium">Kamus kode respon API DANA untuk mapping error & success.</p>
        </div>
        <button @click="openModal('create')" 
                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-200 hover:bg-blue-700 transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Tambah Kode
        </button>
    </div>

    {{-- ALERT MESSAGE (Jika ada session flash) --}}
    @if(session('success'))
    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center gap-3">
        <i class="fas fa-check-circle text-xl"></i>
        <span class="font-bold text-sm">{{ session('success') }}</span>
    </div>
    @endif

    {{-- FILTER & SEARCH SECTION --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6">
        <form action="{{ route('dana_response_codes.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-center justify-between">
            
            {{-- KIRI: Pencarian --}}
            <div class="w-full md:w-1/3 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-slate-400"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 transition-all" 
                       placeholder="Cari kode, pesan, atau solusi...">
            </div>

            {{-- KANAN: Filter Dropdown --}}
            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                
                {{-- Filter Kategori --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-filter text-slate-400 text-xs"></i>
                    </div>
                    <select name="category" onchange="this.form.submit()" class="pl-8 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-600 focus:ring-blue-500 focus:border-blue-500 cursor-pointer hover:bg-slate-50 transition uppercase">
                        <option value="ALL">Semua Kategori</option>
                        
                        {{-- LOOPING KATEGORI DARI DATABASE --}}
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                        @endforeach

                    </select>
                </div>

                {{-- Filter Status --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-check-circle text-slate-400 text-xs"></i>
                    </div>
                    <select name="status" onchange="this.form.submit()" class="pl-8 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-600 focus:ring-blue-500 focus:border-blue-500 cursor-pointer hover:bg-slate-50 transition">
                        <option value="ALL">Semua Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Sukses (Success)</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Gagal (Failed)</option>
                    </select>
                </div>

                {{-- Tombol Cari & Reset --}}
                <div class="flex gap-2">
                    <button type="submit" class="bg-slate-800 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-black transition shadow-md">
                        Cari
                    </button>
                    
                    @if(request()->hasAny(['search', 'category', 'status']))
                        <a href="{{ route('dana_response_codes.index') }}" class="bg-rose-50 text-rose-600 border border-rose-100 px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-rose-100 transition flex items-center" title="Reset Filter">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- 
        ====================================================
        STATUS LAPORAN KATEGORI (MUNCUL JIKA DIPILIH)
        ====================================================
    --}}
    @if(request()->filled('category') && request('category') !== 'ALL')
        @php
            $selectedCat = request('category');
            
            // Tentukan Tema Warna Berdasarkan Kategori
            $theme = match($selectedCat) {
                'INQUIRY' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-100', 'text' => 'text-indigo-600', 'icon' => 'bg-indigo-100'],
                'TOPUP'   => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-100', 'text' => 'text-emerald-600', 'icon' => 'bg-emerald-100'],
                'GENERAL' => ['bg' => 'bg-slate-100', 'border' => 'border-slate-200', 'text' => 'text-slate-600', 'icon' => 'bg-white'],
                default   => ['bg' => 'bg-blue-50', 'border' => 'border-blue-100', 'text' => 'text-blue-600', 'icon' => 'bg-blue-100']
            };
        @endphp

        <div class="{{ $theme['bg'] }} border {{ $theme['border'] }} rounded-2xl p-5 mb-6 flex flex-col sm:flex-row items-center justify-between shadow-sm animate-fade-in-down">
            
            <div class="flex items-center gap-4">
                {{-- Icon Kategori --}}
                <div class="w-14 h-14 {{ $theme['icon'] }} {{ $theme['text'] }} rounded-2xl flex items-center justify-center text-2xl shadow-sm">
                    <i class="fas fa-layer-group"></i>
                </div>
                
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Laporan Kategori</p>
                    <h2 class="text-2xl font-black {{ $theme['text'] }} tracking-tight">{{ $selectedCat }}</h2>
                    <p class="text-xs text-slate-500 font-medium">Menampilkan data spesifik untuk kategori ini.</p>
                </div>
            </div>

            {{-- Statistik Angka --}}
            <div class="mt-4 sm:mt-0 flex items-center gap-6">
                
                {{-- Total Data --}}
                <div class="text-right bg-white/60 p-3 rounded-xl border border-white/50 backdrop-blur-sm">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Total Item</p>
                    <p class="text-3xl font-black {{ $theme['text'] }}">{{ $codes->total() }}</p>
                </div>

                {{-- Hiasan Visual (Opsional) --}}
                <div class="hidden md:block h-10 w-[1px] bg-slate-300/50"></div>

                {{-- Status Filter Info (Jika ada filter status juga) --}}
                @if(request()->filled('status') && request('status') !== 'ALL')
                    <div class="text-right">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Filter Status</p>
                        @if(request('status') == '1')
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-white px-2 py-1 rounded-lg border border-emerald-100 shadow-sm">
                                <i class="fas fa-check-circle"></i> SUKSES
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-white px-2 py-1 rounded-lg border border-rose-100 shadow-sm">
                                <i class="fas fa-times-circle"></i> GAGAL
                            </span>
                        @endif
                    </div>
                @endif

            </div>
        </div>
    @endif

    {{-- MAIN TABLE --}}
    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                        <th class="p-5">Code</th>
                        <th class="p-5">Kategori</th>
                        <th class="p-5">Pesan & Deskripsi</th>
                        <th class="p-5">Solusi (Saran)</th>
                        <th class="p-5 text-center">Tipe</th>
                        <th class="p-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse($codes as $code)
                    <tr class="hover:bg-slate-50 transition-colors group">
                        
                        {{-- CODE --}}
                        <td class="p-5 align-top">
                            <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                {{ $code->response_code }}
                            </span>
                        </td>

                        {{-- KATEGORI --}}
                        <td class="p-5 align-top">
                            @php
                                $catColor = match($code->category) {
                                    'INQUIRY' => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                                    'TOPUP' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                    'GENERAL' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default => 'bg-blue-50 text-blue-600 border-blue-100'
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wide uppercase border {{ $catColor }}">
                                {{ $code->category }}
                            </span>
                        </td>

                        {{-- PESAN --}}
                        <td class="p-5 align-top max-w-xs">
                            <div class="font-bold text-slate-800 mb-1">{{ $code->message_title }}</div>
                            <div class="text-xs text-slate-500 leading-relaxed">{{ $code->description }}</div>
                        </td>

                        {{-- SOLUSI --}}
                        <td class="p-5 align-top max-w-xs">
                            <div class="flex gap-2">
                                <i class="fas fa-lightbulb text-amber-400 mt-0.5 text-xs"></i>
                                <span class="text-xs text-slate-600 font-medium italic">{{ $code->solution ?? '-' }}</span>
                            </div>
                        </td>

                        {{-- TIPE (SUCCESS/FAIL) --}}
                        <td class="p-5 align-top text-center">
                            @if($code->is_success)
                                <div class="inline-flex flex-col items-center gap-1">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <span class="text-[9px] font-bold text-emerald-600 uppercase">Sukses</span>
                                </div>
                            @else
                                <div class="inline-flex flex-col items-center gap-1">
                                    <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center text-rose-600">
                                        <i class="fas fa-times"></i>
                                    </div>
                                    <span class="text-[9px] font-bold text-rose-600 uppercase">Gagal</span>
                                </div>
                            @endif
                        </td>

                        {{-- AKSI --}}
                        <td class="p-5 align-top text-center">
                            <div class="flex justify-center gap-2">
                                {{-- Edit Button --}}
                                <button @click='openModal("edit", @json($code))' 
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>

                                {{-- Delete Button --}}
                                <form action="{{ route('dana_response_codes.destroy', $code->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kode ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 italic">
                            Belum ada data response code.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination jika ada --}}
        @if(method_exists($codes, 'links'))
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $codes->links() }}
        </div>
        @endif
    </div>


    {{-- 
        ====================================================
        MODAL FORM (CREATE & EDIT)
        ====================================================
    --}}
    <div x-show="showModal" 
         x-transition.opacity
         style="display: none"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden" @click.away="showModal = false">
            
            {{-- Header Modal --}}
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 text-lg" x-text="isEdit ? 'Edit Response Code' : 'Tambah Response Code'"></h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 transition"><i class="fas fa-times"></i></button>
            </div>

            {{-- Form --}}
            <form :action="formAction" method="POST" class="p-6 space-y-4">
                @csrf
                {{-- Method Spoofing untuk Edit --}}
                <input type="hidden" name="_method" :value="isEdit ? 'PUT' : 'POST'">
                
                {{-- Kode & Kategori --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Response Code</label>
                        <input type="text" name="response_code" x-model="form.response_code" class="w-full border-slate-200 rounded-lg text-sm font-mono focus:ring-blue-500 focus:border-blue-500 font-bold" placeholder="Contoh: 2003700" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Kategori</label>
                        <select name="category" x-model="form.category" class="w-full border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="INQUIRY">INQUIRY</option>
                            <option value="TOPUP">TOPUP</option>
                            <option value="GENERAL">GENERAL</option>
                        </select>
                    </div>
                </div>

                {{-- Judul Pesan --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Message Title</label>
                    <input type="text" name="message_title" x-model="form.message_title" class="w-full border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Successful" required>
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Deskripsi Lengkap</label>
                    <textarea name="description" x-model="form.description" rows="2" class="w-full border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Penjelasan detail error/sukses..."></textarea>
                </div>

                {{-- Solusi --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Solusi / Tindakan</label>
                    <textarea name="solution" x-model="form.solution" rows="2" class="w-full border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-amber-50 border-amber-100" placeholder="Apa yang harus dilakukan user?"></textarea>
                </div>

                {{-- Status Toggle --}}
                <div class="flex items-center justify-between bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <span class="text-sm font-bold text-slate-600">Apakah Transaksi Sukses?</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_success" value="0">
                        <input type="checkbox" name="is_success" value="1" x-model="form.is_success" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                {{-- Footer Modal --}}
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="showModal = false" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold rounded-lg text-sm hover:bg-slate-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg text-sm hover:bg-blue-700 shadow-lg shadow-blue-200">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function responseCodeHandler() {
        return {
            showModal: false,
            isEdit: false,
            formAction: '',
            form: {
                id: null,
                response_code: '',
                category: 'INQUIRY',
                message_title: '',
                description: '',
                solution: '',
                is_success: false
            },
            // Base URL Route (Sesuaikan dengan route name Anda)
            baseUrl: "{{ route('dana_response_codes.index') }}", 

            openModal(type, data = null) {
                this.isEdit = (type === 'edit');
                this.showModal = true;

                if (this.isEdit && data) {
                    // Mode Edit: Isi form dengan data yang dipilih
                    this.formAction = `${this.baseUrl}/${data.id}`; // Route update: /dana_response_codes/{id}
                    this.form.id = data.id;
                    this.form.response_code = data.response_code;
                    this.form.category = data.category;
                    this.form.message_title = data.message_title;
                    this.form.description = data.description;
                    this.form.solution = data.solution;
                    this.form.is_success = data.is_success == 1; // Konversi int ke boolean
                } else {
                    // Mode Create: Reset form
                    this.formAction = this.baseUrl; // Route store: /dana_response_codes
                    this.form.id = null;
                    this.form.response_code = '';
                    this.form.category = 'INQUIRY';
                    this.form.message_title = '';
                    this.form.description = '';
                    this.form.solution = '';
                    this.form.is_success = false;
                }
            }
        }
    }
</script>
@endsection