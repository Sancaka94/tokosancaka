@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex justify-between items-end mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-800 tracking-tight">🛠️ Dashboard Builder</h1>
            <p class="text-gray-500 mt-1">Desain kartu, atur rentang waktu, dan racik rumus persentase profit sesuka Anda.</p>
        </div>
        <button onclick="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition transform hover:scale-105 flex items-center gap-2">
            <span>➕</span> Buat Kartu Baru
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($widgets as $w)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden relative group {{ !$w->is_active ? 'opacity-60 grayscale' : '' }}">

                <div class="bg-{{ $w->color_theme }}-500 px-5 py-4 text-white flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ $w->icon }}</span>
                        <h3 class="font-bold truncate" title="{{ $w->title }}">{{ $w->title }}</h3>
                    </div>
                    @if(!$w->is_active)
                        <span class="text-[10px] bg-red-600 px-2 py-1 rounded-full font-bold">NONAKTIF</span>
                    @endif
                </div>

                <div class="p-5">
                    <div class="mb-4">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Rentang Waktu</span>
                        <span class="bg-gray-100 text-gray-700 text-xs font-bold px-3 py-1.5 rounded-lg border border-gray-200 inline-block uppercase">
                            {{ str_replace('_', ' ', $w->time_range) }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-2">Rumus Persentase Data</span>
                        <div class="grid grid-cols-2 gap-2 text-sm font-medium">
                            <div class="bg-blue-50 text-blue-800 px-3 py-2 rounded-lg border border-blue-100 flex justify-between">
                                <span>Parkir</span> <span class="font-black">{{ (float)$w->pct_parkir }}%</span>
                            </div>
                            <div class="bg-indigo-50 text-indigo-800 px-3 py-2 rounded-lg border border-indigo-100 flex justify-between">
                                <span>Nginap</span> <span class="font-black">{{ (float)$w->pct_nginap }}%</span>
                            </div>
                            <div class="bg-emerald-50 text-emerald-800 px-3 py-2 rounded-lg border border-emerald-100 flex justify-between">
                                <span>Toilet</span> <span class="font-black">{{ (float)$w->pct_toilet }}%</span>
                            </div>
                            <div class="bg-amber-50 text-amber-800 px-3 py-2 rounded-lg border border-amber-100 flex justify-between">
                                <span>Kas Lain</span> <span class="font-black">{{ (float)$w->pct_kas_lain }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <button onclick="openModal('edit', {{ $w }})" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg shadow-lg">
                        ✏️ Edit Rumus
                    </button>
                    <form action="{{ route('admin.builder.destroy', $w->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kartu ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg shadow-lg">
                            🗑️ Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-white p-10 rounded-2xl shadow-sm border border-dashed border-gray-300 text-center">
                <span class="text-4xl block mb-3">📭</span>
                <h3 class="text-lg font-bold text-gray-700">Belum Ada Kartu Dashboard</h3>
                <p class="text-gray-500 mt-1 mb-4">Mulai bangun dashboard Anda dengan menekan tombol di kanan atas.</p>
                <button onclick="openModal('add')" class="text-blue-600 font-bold hover:underline">Buat Kartu Pertama &rarr;</button>
            </div>
        @endforelse
    </div>

</div>

<div id="widgetModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="bg-slate-800 px-6 py-4 flex justify-between items-center sticky top-0 z-10">
            <h2 id="modalTitle" class="text-xl font-bold text-white flex items-center gap-2">⚙️ Buat Kartu Baru</h2>
            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-2xl leading-none">&times;</button>
        </div>

        <form id="widgetForm" action="{{ route('admin.builder.store') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-4">
                    <h3 class="font-black text-gray-800 border-b pb-2">1. Desain Tampilan</h3>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Judul Kartu</label>
                        <input type="text" name="title" id="inp_title" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Ikon (Emoji)</label>
                            <input type="text" name="icon" id="inp_icon" value="💰" class="w-full border-gray-300 rounded-lg shadow-sm text-center text-xl">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Warna Tema</label>
                            <select name="color_theme" id="inp_color" class="w-full border-gray-300 rounded-lg shadow-sm font-medium">
                                <option value="blue" class="text-blue-600">Biru</option>
                                <option value="green" class="text-green-600">Hijau</option>
                                <option value="red" class="text-red-600">Merah</option>
                                <option value="orange" class="text-orange-500">Oranye</option>
                                <option value="indigo" class="text-indigo-600">Indigo (Ungu Biru)</option>
                                <option value="fuchsia" class="text-fuchsia-600">Fuchsia (Pink Tua)</option>
                                <option value="slate" class="text-slate-600">Abu-abu</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Rentang Waktu Data</label>
                        <select name="time_range" id="inp_time" class="w-full border-gray-300 rounded-lg shadow-sm font-bold">
                            <option value="today">Hari Ini</option>
                            <option value="yesterday">Kemarin</option>
                            <option value="last_7_days">7 Hari Terakhir</option>
                            <option value="this_month">Bulan Ini</option>
                            <option value="last_month">Bulan Kemarin</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="inp_active" value="1" checked class="rounded text-blue-600 w-5 h-5">
                            <span class="font-bold text-gray-700">Tampilkan Kartu Ini (Aktif)</span>
                        </label>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="font-black text-gray-800 border-b pb-2">2. Builder Rumus (Persentase)</h3>
                    <p class="text-xs text-gray-500 mb-2">Tentukan berapa persen omzet dari setiap sumber yang masuk ke kartu ini. Ketik 0 jika diabaikan, 100 jika masuk utuh.</p>

                    <div class="flex items-center gap-3">
                        <input type="number" step="0.01" name="pct_parkir" id="inp_pct_parkir" value="0" class="w-24 border-gray-300 rounded-lg shadow-sm text-center font-black text-blue-600">
                        <span class="text-sm font-bold text-gray-700">% Omzet Parkiran</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="number" step="0.01" name="pct_nginap" id="inp_pct_nginap" value="0" class="w-24 border-gray-300 rounded-lg shadow-sm text-center font-black text-indigo-600">
                        <span class="text-sm font-bold text-gray-700">% Omzet Nginap</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="number" step="0.01" name="pct_toilet" id="inp_pct_toilet" value="0" class="w-24 border-gray-300 rounded-lg shadow-sm text-center font-black text-emerald-600">
                        <span class="text-sm font-bold text-gray-700">% Omzet Toilet</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="number" step="0.01" name="pct_kas_lain" id="inp_pct_kas_lain" value="0" class="w-24 border-gray-300 rounded-lg shadow-sm text-center font-black text-amber-600">
                        <span class="text-sm font-bold text-gray-700">% Omzet Kas Lainnya</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1 mt-4">Urutan Tampil</label>
                        <input type="number" name="order_index" id="inp_order" value="0" class="w-24 border-gray-300 rounded-lg shadow-sm text-center">
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2.5 px-6 rounded-xl transition">Batal</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-black py-2.5 px-8 rounded-xl shadow-lg transition transform hover:scale-105">💾 Simpan Rumus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(mode, data = null) {
        const modal = document.getElementById('widgetModal');
        const form = document.getElementById('widgetForm');

        if (mode === 'add') {
            document.getElementById('modalTitle').innerHTML = '⚙️ Buat Kartu Baru';
            form.action = "{{ route('admin.builder.store') }}";
            document.getElementById('formMethod').value = 'POST';

            // Reset isi form
            document.getElementById('inp_title').value = '';
            document.getElementById('inp_icon').value = '💰';
            document.getElementById('inp_color').value = 'blue';
            document.getElementById('inp_time').value = 'today';
            document.getElementById('inp_active').checked = true;
            document.getElementById('inp_pct_parkir').value = '0';
            document.getElementById('inp_pct_nginap').value = '0';
            document.getElementById('inp_pct_toilet').value = '0';
            document.getElementById('inp_pct_kas_lain').value = '0';
            document.getElementById('inp_order').value = '0';
        } else {
            document.getElementById('modalTitle').innerHTML = '✏️ Edit Rumus Kartu';
            form.action = `/admin/builder/${data.id}`;
            document.getElementById('formMethod').value = 'PUT';

            // Isi form dengan data yang mau di-edit
            document.getElementById('inp_title').value = data.title;
            document.getElementById('inp_icon').value = data.icon;
            document.getElementById('inp_color').value = data.color_theme;
            document.getElementById('inp_time').value = data.time_range;
            document.getElementById('inp_active').checked = data.is_active;

            // Karena MySQL nyimpan desimal seperti "100.00", kita ubah jadi float agar rapi di form
            document.getElementById('inp_pct_parkir').value = parseFloat(data.pct_parkir);
            document.getElementById('inp_pct_nginap').value = parseFloat(data.pct_nginap);
            document.getElementById('inp_pct_toilet').value = parseFloat(data.pct_toilet);
            document.getElementById('inp_pct_kas_lain').value = parseFloat(data.pct_kas_lain);
            document.getElementById('inp_order').value = data.order_index;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('widgetModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
