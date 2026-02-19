<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Keuangan & Hutang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden" x-data="{
        tipe: 'income',
        kategori: 'general',
        showContact: false,
        updateKategori(val) {
            this.kategori = val;
            // Jika kategori hutang/piutang, wajib pilih kontak
            if(val !== 'general') {
                this.showContact = true;
                // Set tipe cashflow otomatis berdasarkan logika akuntansi
                if(val == 'piutang_new') this.tipe = 'expense'; // Kas Keluar (Pinjamin orang)
                if(val == 'piutang_pay') this.tipe = 'income';  // Kas Masuk (Dibayar orang)
                if(val == 'hutang_new')  this.tipe = 'income';  // Kas Masuk (Dapat pinjaman)
                if(val == 'hutang_pay')  this.tipe = 'expense'; // Kas Keluar (Bayar utang)
            } else {
                this.showContact = false;
            }
        }
    }">

        <div class="bg-blue-900 p-6 text-white text-center">
            <h1 class="text-3xl font-bold tracking-wide">INPUT KEUANGAN</h1>
            <p class="mt-1 text-blue-200 text-sm">Catat Transaksi & Hutang Piutang</p>
        </div>

        <div class="p-8">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('cashflow.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-gray-700 font-bold mb-2">Jenis Transaksi</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <button type="button" @click="updateKategori('general'); tipe='income'" :class="kategori=='general' && tipe=='income' ? 'bg-green-600 text-white ring-2 ring-green-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="p-3 rounded-lg font-bold text-sm transition text-center">
                             PEMASUKAN<br><span class="text-[10px] font-normal">Penjualan/Umum</span>
                        </button>
                        <button type="button" @click="updateKategori('general'); tipe='expense'" :class="kategori=='general' && tipe=='expense' ? 'bg-red-600 text-white ring-2 ring-red-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="p-3 rounded-lg font-bold text-sm transition text-center">
                             PENGELUARAN<br><span class="text-[10px] font-normal">Beli Barang/Beban</span>
                        </button>

                        <button type="button" @click="updateKategori('piutang_new')" :class="kategori=='piutang_new' ? 'bg-orange-500 text-white ring-2 ring-orange-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="p-3 rounded-lg font-bold text-sm transition text-center">
                            CATAT PIUTANG<br><span class="text-[10px] font-normal">Kasih Pinjam (Keluar)</span>
                        </button>
                        <button type="button" @click="updateKategori('piutang_pay')" :class="kategori=='piutang_pay' ? 'bg-emerald-500 text-white ring-2 ring-emerald-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="p-3 rounded-lg font-bold text-sm transition text-center">
                            TERIMA PIUTANG<br><span class="text-[10px] font-normal">Org Bayar (Masuk)</span>
                        </button>
                        <button type="button" @click="updateKategori('hutang_new')" :class="kategori=='hutang_new' ? 'bg-purple-500 text-white ring-2 ring-purple-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="p-3 rounded-lg font-bold text-sm transition text-center">
                            CATAT HUTANG<br><span class="text-[10px] font-normal">Ngutang (Masuk)</span>
                        </button>
                        <button type="button" @click="updateKategori('hutang_pay')" :class="kategori=='hutang_pay' ? 'bg-pink-500 text-white ring-2 ring-pink-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="p-3 rounded-lg font-bold text-sm transition text-center">
                            BAYAR HUTANG<br><span class="text-[10px] font-normal">Bayar (Keluar)</span>
                        </button>
                    </div>

                    <input type="hidden" name="type" x-model="tipe">
                    <input type="hidden" name="category" x-model="kategori">
                </div>

                <div x-show="showContact" x-transition class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <label class="block text-blue-800 font-bold mb-2">Pilih Kontak (Wajib)</label>
                    <select name="contact_id" class="w-full p-3 border border-blue-300 rounded-lg focus:outline-none focus:border-blue-600 bg-white">
                        <option value="">-- Pilih Orang / Toko --</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">
                                {{ $contact->name }}
                                @if($contact->store_name) ({{ $contact->store_name }}) @endif
                                [Saldo: Rp {{ number_format($contact->balance, 0, ',', '.') }}]
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-blue-600 mt-1">*Saldo akan otomatis terpotong/bertambah sesuai transaksi.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2">Tanggal</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 font-bold text-gray-700" required>
                    </div>
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2">Nominal (Rp)</label>
                        <input type="number" name="amount" placeholder="0" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 font-mono text-xl" required>
                    </div>
                </div>

                <div x-show="!showContact">
                    <label class="block text-gray-600 font-semibold mb-2">Nama Penyetor / Keterangan</label>
                    <input type="text" name="name" placeholder="Contoh: Penjualan Harian" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                </div>

                <div>
                    <label class="block text-gray-600 font-semibold mb-2">Catatan Tambahan</label>
                    <textarea name="description" rows="2" placeholder="Detail transaksi..." class="w-full p-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"></textarea>
                </div>

                <button type="submit" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-4 px-6 rounded-xl text-xl shadow-lg transform transition hover:scale-[1.01]">
                    SIMPAN TRANSAKSI
                </button>
            </form>
        </div>
        <div class="bg-gray-50 p-3 text-center text-gray-400 text-xs">
            Sancaka Express Financial System &copy; 2026
        </div>
    </div>

</body>
</html>
