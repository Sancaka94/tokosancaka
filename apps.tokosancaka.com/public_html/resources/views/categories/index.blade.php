@extends('layouts.app')

@section('title', 'Kelola Kategori')

@section('content')
<div class="max-w-7xl mx-auto" x-data="categoryManager()">

    {{-- Notifikasi Sukses/Error --}}
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg border border-green-200 text-sm font-bold flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg border border-red-200 text-sm font-bold flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    <div class="flex flex-col mb-6">

        {{-- [BARU] TOMBOL KEMBALI --}}
        {{-- [BARU] TOMBOL KEMBALI KE URL / --}}
        <div class="mb-4">
            <a href="{{ url('/products') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-xl text-slate-600 text-sm font-bold hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm">
                <i class="fas fa-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Kategori Produk & Layanan</h1>
                <p class="text-xs text-slate-500">Atur kategori untuk mengelompokkan barang fisik atau layanan jasa.</p>
            </div>

            {{-- Tombol Tambah (Hanya muncul di Mobile) --}}
            <button @click="resetForm(); window.scrollTo({top:0, behavior:'smooth'})" class="md:hidden px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold shadow-lg shadow-indigo-200 transition active:scale-95">
                <i class="fas fa-plus mr-1"></i> Tambah
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        {{-- BAGIAN KIRI: FORM (Dynamic Create/Edit) --}}
        <div class="lg:col-span-1 sticky top-6">
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200 transition-all duration-300"
                 :class="isEditMode ? 'border-amber-400 ring-2 ring-amber-100' : 'border-slate-200'">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-black text-lg" :class="isEditMode ? 'text-amber-600' : 'text-slate-700'"
                        x-text="isEditMode ? 'Edit Kategori' : 'Tambah Baru'"></h3>

                    {{-- Tombol Batal Edit --}}
                    <button x-show="isEditMode" @click="resetForm()" class="text-xs text-slate-400 hover:text-red-500 font-bold underline transition">
                        Batal
                    </button>
                </div>

                {{-- FORM START --}}
                <form :action="formAction" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{-- Hidden Method PUT untuk Edit --}}
                    <input type="hidden" name="_method" :value="isEditMode ? 'PUT' : 'POST'">

                    <div class="space-y-4">

                        {{-- Nama Kategori --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Kategori <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="form.name" required placeholder="Contoh: Laundry Kiloan"
                                   class="w-full px-4 py-3 rounded-xl border-slate-300 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all bg-slate-50 focus:bg-white">
                        </div>

                        {{-- Tipe & Satuan (Grid 2 Kolom) --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tipe <span class="text-red-500">*</span></label>
                                <select name="type" x-model="form.type" class="w-full px-3 py-3 rounded-xl border-slate-300 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    <option value="physical">Barang Fisik</option>
                                    <option value="service">Jasa / Layanan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Satuan Default <span class="text-red-500">*</span></label>
                                <input type="text" name="default_unit" x-model="form.default_unit" required placeholder="pcs, kg, jam..."
                                       class="w-full px-3 py-3 rounded-xl border-slate-300 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Deskripsi</label>
                            <textarea name="description" x-model="form.description" rows="2" placeholder="Keterangan singkat..."
                                      class="w-full px-4 py-3 rounded-xl border-slate-300 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all resize-none"></textarea>
                        </div>

                        {{-- Upload Gambar --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Gambar / Ikon</label>
                            <input type="file" name="image" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
                            <template x-if="isEditMode && form.image_url">
                                <div class="mt-2 text-xs text-emerald-600 flex items-center gap-1">
                                    <i class="fas fa-image"></i> Gambar saat ini tersimpan. Upload baru untuk mengganti.
                                </div>
                            </template>
                        </div>

                        {{-- Product Presets (Textarea) --}}
                        <div x-show="form.type === 'service'" x-transition>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                Preset Layanan <span class="text-amber-500 ml-1 text-[9px] normal-case">(Pisahkan dengan Enter)</span>
                            </label>
                            <textarea name="product_presets_input" x-model="form.product_presets_input" rows="4" placeholder="Cuci Kering&#10;Cuci Basah&#10;Setrika Saja"
                                      class="w-full px-4 py-3 rounded-xl border-slate-300 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all font-mono text-xs"></textarea>
                        </div>

                        {{-- Toggle Switch Status --}}
                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-slate-600">Status Aktif</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        {{-- Action Buttons --}}
                        <button type="submit"
                                class="w-full py-3 rounded-xl text-sm font-bold text-white shadow-md transition-all flex justify-center items-center gap-2 transform active:scale-[0.98]"
                                :class="isEditMode ? 'bg-amber-500 hover:bg-amber-600 shadow-amber-200' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200'">
                            <i class="fas" :class="isEditMode ? 'fa-save' : 'fa-plus'"></i>
                            <span x-text="isEditMode ? 'Simpan Perubahan' : 'Simpan Kategori'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- BAGIAN KANAN: TABEL LIST --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 w-16 text-center">Img</th>
                                <th class="px-6 py-4">Nama & Detail</th>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($categories as $cat)
                            <tr class="group hover:bg-slate-50 transition-colors">
                                {{-- Kolom Gambar --}}
                                <td class="px-6 py-4 text-center">
                                    @if($cat->image)
                                        <img src="{{ asset('storage/' . $cat->image) }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm mx-auto">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-300 mx-auto">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </td>

                                {{-- Kolom Nama --}}
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 text-base">{{ $cat->name }}</div>
                                    <div class="text-xs text-slate-400 mt-1 line-clamp-1 italic">
                                        {{ $cat->description ?? 'Tidak ada deskripsi' }}
                                    </div>

                                    {{-- Tampilkan Presets jika ada (Khusus Service) --}}
                                    {{-- Tambahkan is_array() untuk memastikan datanya benar-benar array --}}
                                    @if($cat->type == 'service' && !empty($cat->product_presets) && is_array($cat->product_presets))

                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach(array_slice($cat->product_presets, 0, 3) as $preset)
                                                <span class="text-[9px] bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded border border-indigo-100">
                                                    {{ $preset }}
                                                </span>
                                            @endforeach

                                            @if(count($cat->product_presets) > 3)
                                                <span class="text-[9px] text-slate-400">+{{ count($cat->product_presets) - 3 }} lainnya</span>
                                            @endif
                                        </div>

                                    @endif
                                </td>

                                {{-- Kolom Tipe --}}
                                <td class="px-6 py-4">
                                    @if($cat->type == 'service')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold bg-purple-50 text-purple-600 border border-purple-100">
                                            <i class="fas fa-hands-helping"></i> Jasa
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                            <i class="fas fa-box"></i> Fisik
                                        </span>
                                    @endif
                                    <div class="text-[10px] text-slate-400 mt-1">Unit: <b>{{ $cat->default_unit }}</b></div>
                                </td>

                                {{-- Kolom Status --}}
                                <td class="px-6 py-4 text-center">
                                    @if($cat->is_active)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                                            <div class="w-1.5 h-1.5 rounded-full bg-slate-400"></div> Non-Aktif
                                        </span>
                                    @endif
                                </td>

                                {{-- Kolom Aksi --}}
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        {{-- TOMBOL EDIT --}}
                                        <button @click="editCategory({{ json_encode($cat) }})"
                                                class="h-8 w-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition border border-amber-100 shadow-sm"
                                                title="Edit">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </button>

                                        {{-- TOMBOL HAPUS --}}
                                        <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Yakin hapus kategori {{ $cat->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-8 w-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition border border-red-100 shadow-sm"
                                                    title="Hapus">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400 bg-slate-50/50">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                            <i class="fas fa-inbox text-2xl text-slate-300"></i>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">Belum ada kategori data.</p>
                                        <p class="text-xs text-slate-400">Silakan tambahkan kategori baru melalui formulir di samping.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT LOGIC (Alpine.js) --}}
<script>
    function categoryManager() {
        return {
            isEditMode: false,
            formAction: "{{ route('categories.store') }}",

            // Model Data Form
            form: {
                id: null,
                name: '',
                type: 'physical', // Default
                default_unit: 'pcs', // Default
                description: '',
                image_url: null, // Indikator ada gambar lama
                product_presets_input: '', // String text area
                is_active: true
            },

            // Saat tombol Edit diklik
            editCategory(data) {
                this.isEditMode = true;

                // Isi form dengan data
                this.form.id = data.id;
                this.form.name = data.name;
                this.form.type = data.type;
                this.form.default_unit = data.default_unit;
                this.form.description = data.description;
                this.form.image_url = data.image; // Path gambar
                this.form.is_active = data.is_active == 1 ? true : false;

                // Handle Presets (JSON Array -> String with Newlines)
                if (data.product_presets && Array.isArray(data.product_presets)) {
                    this.form.product_presets_input = data.product_presets.join("\n");
                } else {
                    this.form.product_presets_input = '';
                }

                // Ubah action URL
                let baseUrl = "{{ url('categories') }}";
                this.formAction = baseUrl + "/" + data.id;

                // Scroll ke form (UX Mobile)
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            // Reset form kembali ke mode Tambah
            resetForm() {
                this.isEditMode = false;
                this.formAction = "{{ route('categories.store') }}";

                this.form.id = null;
                this.form.name = '';
                this.form.type = 'physical';
                this.form.default_unit = 'pcs';
                this.form.description = '';
                this.form.image_url = null;
                this.form.product_presets_input = '';
                this.form.is_active = true;

                // Reset input file secara manual jika perlu (optional)
                document.querySelector('input[type="file"]').value = '';
            }
        }
    }
</script>
@endsection
