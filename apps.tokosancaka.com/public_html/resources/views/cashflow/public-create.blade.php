@extends('layouts.app')

@section('content')
<div class="py-10 px-4">
    <div class="bg-white w-full max-w-3xl mx-auto rounded-2xl shadow-xl overflow-hidden" x-data="{
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
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg font-medium">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('cashflow.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-gray-700 font-bold mb-3">Jenis Transaksi</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <button type="button" @click="updateKategori('general'); tipe='income'" :class="kategori=='general' && tipe=='income' ? 'bg-green-600 text-white shadow-md shadow-green-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200'" class="p-3 rounded-xl font-bold text-sm transition text-center">
                             PEMASUKAN<br><span class="text-[10px] font-normal opacity-80">Penjualan/Umum</span>
                        </button>
                        <button type="button" @click="updateKategori('general'); tipe='expense'" :class="kategori=='general' && tipe=='expense' ? 'bg-red-600 text-white shadow-md shadow-red-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200'" class="p-3 rounded-xl font-bold text-sm transition text-center">
                             PENGELUARAN<br><span class="text-[10px] font-normal opacity-80">Beli Barang/Beban</span>
                        </button>

                        <button type="button" @click="updateKategori('piutang_new')" :class="kategori=='piutang_new' ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200'" class="p-3 rounded-xl font-bold text-sm transition text-center">
                            CATAT PIUTANG<br><span class="text-[10px] font-normal opacity-80">Kasih Pinjam (Keluar)</span>
                        </button>
                        <button type="button" @click="updateKategori('piutang_pay')" :class="kategori=='piutang_pay' ? 'bg-emerald-500 text-white shadow-md shadow-emerald-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200'" class="p-3 rounded-xl font-bold text-sm transition text-center">
                            TERIMA PIUTANG<br><span class="text-[10px] font-normal opacity-80">Org Bayar (Masuk)</span>
                        </button>
                        <button type="button" @click="updateKategori('hutang_new')" :class="kategori=='hutang_new' ? 'bg-purple-500 text-white shadow-md shadow-purple-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200'" class="p-3 rounded-xl font-bold text-sm transition text-center">
                            CATAT HUTANG<br><span class="text-[10px] font-normal opacity-80">Ngutang (Masuk)</span>
                        </button>
                        <button type="button" @click="updateKategori('hutang_pay')" :class="kategori=='hutang_pay' ? 'bg-pink-500 text-white shadow-md shadow-pink-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200'" class="p-3 rounded-xl font-bold text-sm transition text-center">
                            BAYAR HUTANG<br><span class="text-[10px] font-normal opacity-80">Bayar (Keluar)</span>
                        </button>
                    </div>

                    <input type="hidden" name="type" x-model="tipe">
                    <input type="hidden" name="category" x-model="kategori">
                </div>

                <div x-show="showContact" x-transition x-cloak class="bg-blue-50/50 p-5 rounded-xl border border-blue-100">
                    <label class="block text-blue-900 font-bold mb-2">Pilih Kontak Transaksi (Wajib)</label>
                    <select name="contact_id" class="w-full p-3 border border-blue-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm">
                        <option value="">-- Pilih Orang / Toko --</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">
                                {{ $contact->name }}
                                @if($contact->store_name) ({{ $contact->store_name }}) @endif
                                — Saldo: Rp {{ number_format($contact->balance, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-blue-600 mt-2 flex items-center gap-1">
                        <i class="fas fa-info-circle"></i> Saldo kontak akan otomatis disesuaikan setelah transaksi disimpan.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Tanggal Transaksi</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nominal (Rp)</label>
                        <input type="number" name="amount" placeholder="0" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-lg shadow-sm" required>
                    </div>
                </div>

                <div x-show="!showContact" x-transition>
                    <label class="block text-gray-700 font-semibold mb-2">Nama Penyetor / Penerima</label>
                    <input type="text" name="name" placeholder="Contoh: Budi Penjualan Harian" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Catatan Tambahan</label>
                    <textarea name="description" rows="3" placeholder="Tulis detail transaksi di sini..." class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"></textarea>
                </div>

                <hr class="border-gray-100">

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-xl text-lg shadow-lg shadow-blue-200 transform transition-all hover:-translate-y-0.5 focus:ring-4 focus:ring-blue-300 flex justify-center items-center gap-2">
                    <i class="fas fa-save"></i> SIMPAN TRANSAKSI
                </button>
            </form>
        </div>

        <div class="bg-gray-50 p-4 text-center text-slate-400 text-xs border-t border-gray-100">
            Sancaka Express Financial System &copy; {{ date('Y') }}
        </div>
    </div>
</div>
@endsection
