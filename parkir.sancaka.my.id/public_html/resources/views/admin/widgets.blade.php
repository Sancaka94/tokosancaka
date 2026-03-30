@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-end mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-800 tracking-tight">🛠️ Dashboard Builder</h1>
            <p class="text-gray-500 mt-1">Desain kartu, grafik, dan blok gaji pegawai. Atur rumus persentase sesuka Anda.</p>
        </div>
        <button onclick="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition transform hover:scale-105 flex items-center gap-2">
            <span>➕</span> Buat Widget Baru
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
                        <h3 class="font-bold truncate">{{ $w->title }}</h3>
                    </div>
                </div>
                <div class="p-5">
                    <div class="mb-4 flex gap-2">
                        <span class="bg-gray-100 text-gray-700 text-xs font-bold px-3 py-1.5 rounded-lg border inline-block uppercase">
                            @if($w->display_type == 'card') 💳 KARTU ANGKA
                            @elseif($w->display_type == 'chart_line') 📈 GRAFIK GARIS
                            @elseif($w->display_type == 'chart_bar') 📊 GRAFIK BATANG
                            @elseif($w->display_type == 'employee_salary') 👥 GAJI PEGAWAI @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase block mb-2">Rumus Sumber Omzet</span>
                        <div class="grid grid-cols-2 gap-2 text-sm font-medium">
                            <div class="bg-blue-50 text-blue-800 px-3 py-2 rounded-lg flex justify-between"><span>Parkir</span> <span class="font-black">{{ (float)$w->pct_parkir }}%</span></div>
                            <div class="bg-indigo-50 text-indigo-800 px-3 py-2 rounded-lg flex justify-between"><span>Nginap</span> <span class="font-black">{{ (float)$w->pct_nginap }}%</span></div>
                            <div class="bg-emerald-50 text-emerald-800 px-3 py-2 rounded-lg flex justify-between"><span>Toilet</span> <span class="font-black">{{ (float)$w->pct_toilet }}%</span></div>
                            <div class="bg-amber-50 text-amber-800 px-3 py-2 rounded-lg flex justify-between"><span>Kas Lain</span> <span class="font-black">{{ (float)$w->pct_kas_lain }}%</span></div>
                        </div>
                    </div>
                </div>
                <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <button onclick="openModal('edit', {{ $w }})" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">✏️ Edit</button>
                    <form action="{{ route('admin.builder.destroy', $w->id) }}" method="POST" onsubmit="return confirm('Yakin hapus?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg">🗑️ Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-white p-10 rounded-2xl shadow-sm text-center">Belum ada widget.</div>
        @endforelse
    </div>
</div>

<div id="widgetModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="bg-slate-800 px-6 py-4 flex justify-between items-center sticky top-0 z-10">
            <h2 id="modalTitle" class="text-xl font-bold text-white">⚙️ Buat Widget</h2>
            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
        </div>

        <form id="widgetForm" action="{{ route('admin.builder.store') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-4">
                    <h3 class="font-black text-gray-800 border-b pb-2">1. Desain Tampilan</h3>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tipe Widget</label>
                        <select name="display_type" id="inp_type" class="w-full border-gray-300 rounded-lg shadow-sm font-bold text-blue-700 bg-blue-50">
                            <option value="card">💳 Kartu Angka Biasa</option>
                            <option value="employee_salary">👥 Kartu Gaji Pegawai Spesifik</option>
                            <option value="chart_line">📈 Grafik Garis (Tren 7 Hari)</option>
                            <option value="chart_bar">📊 Grafik Batang (Tren 6 Bulan)</option>
                        </select>
                    </div>

                    <div id="wrap_user" class="hidden">
                        <label class="block text-xs font-bold text-indigo-600 uppercase mb-1">Pilih Pegawai (Database)</label>
                        <select name="user_id" id="inp_user_id" class="w-full border-indigo-300 rounded-lg shadow-sm bg-indigo-50">
                            <option value="">-- Pilih Pegawai --</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">{{ $op->name }} ({{ $op->salary_type == 'percentage' ? (float)$op->salary_amount.'% Omzet' : 'Rp '.(float)$op->salary_amount }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Judul Widget (Kategori)</label>
                        <input type="text" name="title" id="inp_title" required class="w-full border-gray-300 rounded-lg shadow-sm" placeholder="Contoh: Gaji Pak Dodik">
                        <span class="text-[10px] text-gray-400">Kata pertama akan menjadi nama kelompok (Grup).</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Ikon</label>
                            <input type="text" name="icon" id="inp_icon" value="💰" class="w-full border-gray-300 rounded-lg shadow-sm text-center">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Warna Tema</label>
                            <select name="color_theme" id="inp_color" class="w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="blue">Biru</option>
                                <option value="green">Hijau</option>
                                <option value="red">Merah</option>
                                <option value="orange">Oranye</option>
                                <option value="indigo">Indigo</option>
                                <option value="fuchsia">Pink Tua</option>
                                <option value="slate">Abu-abu</option>
                            </select>
                        </div>
                    </div>

                    <div id="wrap_time">
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Waktu</label>
                        <select name="time_range" id="inp_time" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="today">Hari Ini</option>
                            <option value="yesterday">Kemarin</option>
                            <option value="last_7_days">7 Hari Terakhir</option>
                            <option value="this_month">Bulan Ini</option>
                            <option value="last_month">Bulan Kemarin</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" id="inp_active" value="1" checked class="rounded text-blue-600 w-5 h-5">
                            <span class="font-bold text-gray-700">Tampilkan Widget Ini</span>
                        </label>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="font-black text-gray-800 border-b pb-2">2. Dasar Omzet</h3>
                    <p class="text-xs text-gray-500 mb-2">Tentukan sumber uang yang dihitung (Jika gaji, persentase karyawan akan dikalikan dengan total uang dari sini).</p>
                    <div class="flex items-center gap-3">
                        <input type="number" step="0.01" name="pct_parkir" id="inp_pct_parkir" value="0" class="w-20 border-gray-300 rounded-lg text-center font-black text-blue-600">
                        <span class="text-sm font-bold text-gray-700">% Parkiran</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" step="0.01" name="pct_nginap" id="inp_pct_nginap" value="0" class="w-20 border-gray-300 rounded-lg text-center font-black text-indigo-600">
                        <span class="text-sm font-bold text-gray-700">% Nginap</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" step="0.01" name="pct_toilet" id="inp_pct_toilet" value="0" class="w-20 border-gray-300 rounded-lg text-center font-black text-emerald-600">
                        <span class="text-sm font-bold text-gray-700">% Toilet</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" step="0.01" name="pct_kas_lain" id="inp_pct_kas_lain" value="0" class="w-20 border-gray-300 rounded-lg text-center font-black text-amber-600">
                        <span class="text-sm font-bold text-gray-700">% Kas Lain</span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1 mt-4">Urutan Tampil (Order)</label>
                        <input type="number" name="order_index" id="inp_order" value="0" class="w-24 border-gray-300 rounded-lg shadow-sm text-center">
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="bg-gray-200 font-bold py-2.5 px-6 rounded-xl">Batal</button>
                <button type="submit" class="bg-blue-600 text-white font-black py-2.5 px-8 rounded-xl shadow-lg">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // JS Untuk memunculkan Dropdown Pegawai jika tipe yang dipilih = Gaji Pegawai
    document.getElementById('inp_type').addEventListener('change', function() {
        if(this.value === 'employee_salary') {
            document.getElementById('wrap_user').classList.remove('hidden');
            document.getElementById('inp_user_id').required = true;
        } else {
            document.getElementById('wrap_user').classList.add('hidden');
            document.getElementById('inp_user_id').required = false;
            document.getElementById('inp_user_id').value = '';
        }
    });

    function openModal(mode, data = null) {
        const modal = document.getElementById('widgetModal');
        const form = document.getElementById('widgetForm');

        if (mode === 'add') {
            document.getElementById('modalTitle').innerHTML = '⚙️ Buat Widget Baru';
            form.action = "{{ route('admin.builder.store') }}";
            document.getElementById('formMethod').value = 'POST';

            document.getElementById('inp_type').value = 'card';
            document.getElementById('inp_type').dispatchEvent(new Event('change')); // Trigger event

            document.getElementById('inp_title').value = '';
            document.getElementById('inp_active').checked = true;
            document.getElementById('inp_pct_parkir').value = '0';
            document.getElementById('inp_pct_nginap').value = '0';

            // FIX TYPO ID DI SINI:
            document.getElementById('inp_pct_toilet').value = '0';
            document.getElementById('inp_pct_kas_lain').value = '0';
            document.getElementById('inp_order').value = '0';

        } else {
            document.getElementById('modalTitle').innerHTML = '✏️ Edit Widget';
            form.action = `/admin/builder/${data.id}`;
            document.getElementById('formMethod').value = 'PUT';

            document.getElementById('inp_type').value = data.display_type || 'card';
            document.getElementById('inp_type').dispatchEvent(new Event('change')); // Trigger event
            if(data.user_id) document.getElementById('inp_user_id').value = data.user_id;

            document.getElementById('inp_title').value = data.title;
            document.getElementById('inp_icon').value = data.icon;
            document.getElementById('inp_color').value = data.color_theme;
            document.getElementById('inp_time').value = data.time_range;
            document.getElementById('inp_active').checked = data.is_active;

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
        document.getElementById('widgetModal').classList.add('hidden');
        document.getElementById('widgetModal').classList.remove('flex');
    }
</script>
@endsection
