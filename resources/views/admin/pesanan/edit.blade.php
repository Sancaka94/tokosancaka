{{-- resources/views/admin/pesanan/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit Pesanan ' . $pesanan->resi)
@section('page-title', 'Edit Pesanan')

@section('content')
<div class="max-w-7xl mx-auto">
    <form action="{{ route('admin.pesanan.update', $pesanan->resi) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Kolom Kiri: Pengirim & Penerima -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Informasi Pengirim -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">Informasi Pengirim</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="sender_name" class="block mb-2 text-sm font-medium text-gray-700">Nama Pengirim</label>
                            <input type="text" name="sender_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('sender_name', $pesanan->sender_name) }}" required>
                        </div>
                        <div>
                            <label for="sender_phone" class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>
                            <input type="tel" name="sender_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('sender_phone', $pesanan->sender_phone) }}" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="sender_address" class="block mb-2 text-sm font-medium text-gray-700">Alamat Pengirim</label>
                            <textarea name="sender_address" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>{{ old('sender_address', $pesanan->sender_address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Informasi Penerima -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">Informasi Penerima</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="receiver_name" class="block mb-2 text-sm font-medium text-gray-700">Nama Penerima</label>
                            <input type="text" name="receiver_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('receiver_name', $pesanan->receiver_name) }}" required>
                        </div>
                        <div>
                            <label for="receiver_phone" class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>
                            <input type="tel" name="receiver_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('receiver_phone', $pesanan->receiver_phone) }}" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="receiver_address" class="block mb-2 text-sm font-medium text-gray-700">Alamat Penerima</label>
                            <textarea name="receiver_address" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>{{ old('receiver_address', $pesanan->receiver_address) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Detail Paket & Aksi -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Detail Paket -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">Detail Paket</h3>
                    <div>
                        <label for="service_type" class="block mb-2 text-sm font-medium text-gray-700">Jenis Layanan</label>
                        <select id="service_type" name="service_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                            @foreach(['Reguler', 'Kilat', 'Cargo', 'Motor'] as $type)
                                <option value="{{ $type }}" {{ old('service_type', $pesanan->service_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4">
                        <label for="expedition" class="block mb-2 text-sm font-medium text-gray-700">Pilih Ekspedisi</label>
                        <select id="expedition" name="expedition" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" data-selected="{{ old('expedition', $pesanan->expedition) }}"></select>
                    </div>
                    <div class="mt-4">
                        <label for="payment_method" class="block mb-2 text-sm font-medium text-gray-700">Pilih Pembayaran</label>
                        <select name="payment_method" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                            @foreach(['Cash', 'COD Ongkir', 'Transfer', 'COD Barang dan Ongkir'] as $method)
                                <option value="{{ $method }}" {{ old('payment_method', $pesanan->payment_method) == $method ? 'selected' : '' }}>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4">
                        <label for="item_description" class="block mb-2 text-sm font-medium text-gray-700">Deskripsi Barang</label>
                        <input type="text" name="item_description" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('item_description', $pesanan->item_description) }}" required>
                    </div>
                    <div class="mt-4">
                        <label for="weight" class="block mb-2 text-sm font-medium text-gray-700">Berat (gram)</label>
                        <input type="number" name="weight" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('weight', $pesanan->weight) }}" required>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mt-4">
                        <div><label class="block mb-2 text-sm">P (cm)</label><input type="number" name="length" class="bg-gray-50 border rounded-lg w-full p-2.5" value="{{ old('length', $pesanan->length) }}"></div>
                        <div><label class="block mb-2 text-sm">L (cm)</label><input type="number" name="width" class="bg-gray-50 border rounded-lg w-full p-2.5" value="{{ old('width', $pesanan->width) }}"></div>
                        <div><label class="block mb-2 text-sm">T (cm)</label><input type="number" name="height" class="bg-gray-50 border rounded-lg w-full p-2.5" value="{{ old('height', $pesanan->height) }}"></div>
                    </div>
                    <div id="motorcycle_checklist" class="hidden mt-6">
                        <h4 class="text-md font-semibold text-gray-800 border-t pt-4">Kelengkapan Motor</h4>
                        <div class="grid grid-cols-2 gap-2 mt-2 text-sm">
                            @php $kelengkapan = old('kelengkapan', $pesanan->kelengkapan ?? []); @endphp
                            @foreach(['STNK', 'BPKB', 'KTP', 'Helm', 'Surat Jalan', 'Kunci'] as $item)
                                <label class="flex items-center"><input type="checkbox" name="kelengkapan[]" value="{{ $item }}" class="h-4 w-4 rounded border-gray-300" {{ in_array($item, $kelengkapan) ? 'checked' : '' }}> {{ $item }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <!-- Aksi -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-end gap-4">
                        <a href="{{ route('admin.pesanan.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">Batal</a>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const expeditionOptions = {
        Reguler: ['JNE', 'J&T Express', 'Sicepat', 'AnterAja', 'Ninja Xpress', 'POS Indonesia', 'Lion Parcel', 'ID Express', 'SAP Express'],
        Kilat: ['JNE', 'J&T Express', 'Sicepat', 'AnterAja', 'Lion Parcel'],
        Cargo: ['J&T Cargo', 'Indah Cargo', 'Sancaka Cargo', 'Dakota Cargo', 'Elteha', 'Sentral Cargo', 'Klik Logistics', 'Rosalia Express', 'MEX Barlian Dirgantara', 'Wahana Express'],
        Motor: ['J&T Cargo', 'Indah Cargo', 'Sancaka Cargo']
    };

    const serviceTypeSelect = document.getElementById('service_type');
    const expeditionSelect = document.getElementById('expedition');
    const motorcycleChecklist = document.getElementById('motorcycle_checklist');
    
    function updateExpeditions() {
        const service = serviceTypeSelect.value;
        const selectedExpedition = expeditionSelect.getAttribute('data-selected');
        expeditionSelect.innerHTML = '';

        const options = expeditionOptions[service] || [];
        options.forEach(function (name) {
            const opt = document.createElement('option');
            opt.value = name;
            opt.text = name;
            if (selectedExpedition === name) {
                opt.selected = true;
            }
            expeditionSelect.appendChild(opt);
        });

        motorcycleChecklist.classList.toggle('hidden', service !== 'Motor');
    }

    serviceTypeSelect.addEventListener('change', updateExpeditions);
    updateExpeditions(); // Panggil saat halaman dimuat
});
</script>
@endsection
