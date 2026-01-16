@extends('layouts.app')

@section('title', 'Kelola Kategori')

@section('content')
<div class="max-w-6xl mx-auto" x-data="categoryManager()">

    {{-- Notifikasi Sukses/Error --}}
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg border border-green-200 text-sm font-bold flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Kategori Produk</h1>
            <p class="text-xs text-slate-500">Atur kategori untuk mengelompokkan barang atau layanan.</p>
        </div>
        <a href="{{ route('products.index') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition">
            &larr; Kembali ke Produk
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">

        {{-- BAGIAN KIRI: FORM (Dynamic Create/Edit) --}}
        <div class="md:col-span-1 sticky top-6">
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200 transition-all duration-300"
                 :class="isEditMode ? 'border-amber-400 ring-2 ring-amber-100' : 'border-slate-200'">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-black text-lg" :class="isEditMode ? 'text-amber-600' : 'text-slate-700'"
                        x-text="isEditMode ? 'Edit Kategori' : 'Tambah Baru'"></h3>

                    {{-- Tombol Batal Edit --}}
                    <button x-show="isEditMode" @click="resetForm()" class="text-xs text-slate-400 hover:text-red-500 font-bold underline">
                        Batal
                    </button>
                </div>

                {{-- FORM START --}}
                <form :action="formAction" method="POST">
                    @csrf
                    {{-- Hidden Method PUT untuk Edit --}}
                    <input type="hidden" name="_method" :value="isEditMode ? 'PUT' : 'POST'">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Kategori</label>
                            <input type="text" name="name" x-model="form.name" required placeholder="Contoh: Laundry Kiloan"
                                   class="w-full px-4 py-3 rounded-xl border-slate-300 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Deskripsi</label>
                            <textarea name="description" x-model="form.description" rows="3" placeholder="Keterangan singkat..."
                                      class="w-full px-4 py-3 rounded-xl border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 transition-all resize-none"></textarea>
                        </div>

                        {{-- Toggle Switch Status --}}
                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-slate-600">Status Aktif</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

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
        <div class="md:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4">Nama & Keterangan</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($categories as $cat)
                            <tr class="group hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 text-base">{{ $cat->name }}</div>
                                    <div class="text-xs text-slate-400 mt-1 line-clamp-1 italic">
                                        {{ $cat->description ?? 'Tidak ada deskripsi' }}
                                    </div>
                                    <span class="inline-block mt-1 text-[9px] bg-slate-100 text-slate-500 px-1.5 rounded border border-slate-200">
                                        /{{ $cat->slug }}
                                    </span>
                                </td>
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
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">

                                        {{-- TOMBOL EDIT (Trigger Alpine) --}}
                                        <button @click="editCategory({{ $cat }})"
                                                class="h-8 w-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition border border-amber-100">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </button>

                                        {{-- TOMBOL HAPUS --}}
                                        <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Yakin hapus kategori {{ $cat->name }}? Produk terkait mungkin akan kehilangan kategori.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-8 w-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition border border-red-100">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-slate-400">
                                    <i class="fas fa-inbox text-4xl mb-3 block opacity-30"></i>
                                    Belum ada kategori. Silakan tambah baru.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t border-slate-100">
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
            formAction: "{{ route('categories.store') }}", // Default action

            // Model Data Form
            form: {
                id: null,
                name: '',
                description: '',
                is_active: true
            },

            // Saat tombol Edit diklik
            editCategory(data) {
                this.isEditMode = true;

                // Isi form dengan data dari tabel
                this.form.id = data.id;
                this.form.name = data.name;
                this.form.description = data.description;
                this.form.is_active = data.is_active == 1 ? true : false;

                // Ubah action form ke URL Update
                // Pastikan route('categories.update', 'ID_PLACEHOLDER') tersedia atau construct manual stringnya
                // Cara manual string replacement agar JS valid:
                let baseUrl = "{{ url('categories') }}";
                this.formAction = baseUrl + "/" + data.id;

                // Scroll ke atas (untuk mobile)
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            // Reset form kembali ke mode Tambah
            resetForm() {
                this.isEditMode = false;
                this.formAction = "{{ route('categories.store') }}";
                this.form.id = null;
                this.form.name = '';
                this.form.description = '';
                this.form.is_active = true;
            }
        }
    }
</script>
@endsection
