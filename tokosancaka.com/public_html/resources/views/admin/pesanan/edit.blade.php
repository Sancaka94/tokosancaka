{{-- resources/views/admin/pesanan/edit.blade.php --}}

@extends('layouts.admin')

@section('title', 'Edit Pesanan ' . ($pesanan->nomor_invoice))
@section('page-title', 'Edit & Re-Payload Pesanan')

@push('styles')
{{-- Font Awesome untuk ikon --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .search-results-container {
        position: absolute;
        z-index: 1000;
        width: 100%;
        max-height: 250px;
        overflow-y: auto;
        background-color: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0 0 0.5rem 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-top: -1px;
    }
    .modal-body-scroll {
        max-height: 70vh;
        overflow-y: auto;
    }
    #confirmBtn:disabled {
        background-color: #bebebeff;
        cursor: not-allowed;
    }
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
</style>
@endpush

@php
    if (!isset($idempotencyKey)) {
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();
    }

    // Inisialisasi tampilan metode pembayaran
    $pm = old('payment_method', $pesanan->payment_method);
    $pmName = 'Pilih...';
    $pmLogo = 'https://cdn-icons-png.flaticon.com/512/2331/2331941.png';
    if ($pm == 'Potong Saldo') {
        $pmName = 'Potong Saldo';
        $pmLogo = 'https://tokosancaka.com/public/assets/saldo.png';
    } elseif ($pm == 'Cash') {
        $pmName = 'Cash (Tanpa Saldo)';
        $pmLogo = 'https://tokosancaka.com/public/assets/saldo.png';
    } elseif ($pm == 'BCA_QRIS') {
        $pmName = 'BCA QRIS (Otomatis)';
        $pmLogo = 'https://tokosancaka.com/assets/bca.png';
    } elseif ($pm == 'DOKU_JOKUL') {
        $pmName = 'DOKU Payment Gateway';
        $pmLogo = 'https://tokosancaka.com/public/assets/doku.png';
    } elseif ($pm == 'COD') {
        $pmName = 'COD Ongkir';
        $pmLogo = asset('public/assets/cod.png');
    } elseif ($pm == 'CODBARANG') {
        $pmName = 'COD Barang + Ongkir';
        $pmLogo = asset('public/assets/cod.png');
    } elseif (!empty($pm)) {
        $pmName = $pm;
    }

    // Gabungkan string alamat untuk pre-fill search bar
    $senderAddrSearch = implode(', ', array_filter([
        $pesanan->sender_village,
        $pesanan->sender_district,
        str_replace(['KABUPATEN ', 'KOTA '], '', strtoupper($pesanan->sender_regency ?? '')),
        $pesanan->sender_postal_code
    ]));
    $receiverAddrSearch = implode(', ', array_filter([
        $pesanan->receiver_village,
        $pesanan->receiver_district,
        str_replace(['KABUPATEN ', 'KOTA '], '', strtoupper($pesanan->receiver_regency ?? '')),
        $pesanan->receiver_postal_code
    ]));

    // Format tampilan ekspedisi lama dari string "service_type-courier-service-cost-..."
    $oldExpeditionVal = old('expedition', $pesanan->expedition);
    $oldExpeditionDisplay = '';
    if (!empty($oldExpeditionVal)) {
        $expParts = explode('-', $oldExpeditionVal);
        if (count($expParts) >= 3) {
            $oldExpeditionDisplay = strtoupper($expParts[1]) . ' - ' . strtoupper($expParts[2]) . (isset($expParts[3]) ? ' (Rp ' . number_format((int)$expParts[3], 0, ',', '.') . ')' : '');
        } else {
            $oldExpeditionDisplay = $oldExpeditionVal;
        }
    }
@endphp

@section('content')

@include('layouts.partials.notifications')

<div class="mb-4 space-y-3">
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0"><i class="fas fa-info-circle text-blue-500"></i></div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    Anda sedang mengedit Invoice: <strong>{{ $pesanan->nomor_invoice }}</strong>. Menyimpan perubahan pada pesanan berstatus <strong>"Gagal Kirim Resi"</strong> akan otomatis melakukan request ulang (Re-Payload) ke API KiriminAja.
                </p>
            </div>
        </div>
    </div>

    @if(!empty($pesanan->keterangan) || !empty($pesanan->error_message))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0"><i class="fas fa-exclamation-triangle text-red-500"></i></div>
            <div class="ml-3">
                <p class="text-sm font-bold text-red-800">Catatan / Alasan Gagal Sebelumnya:</p>
                <p class="text-sm text-red-700 mt-1">{{ $pesanan->error_message ?? $pesanan->keterangan }}</p>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="max-w-7xl mx-auto">
    <form id="orderForm" action="{{ route('admin.pesanan.update', $pesanan->resi ?? $pesanan->nomor_invoice) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Kolom Kiri: Pengirim & Penerima -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Informasi Pengirim -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center border-b pb-4 mb-6">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-arrow-up-from-bracket text-red-500 mr-2"></i>Informasi Pengirim
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="relative">
                            <label for="sender_name" class="block mb-2 text-sm font-medium text-gray-700">Nama Pengirim</label>
                            <div class="relative">
                                <input type="text" id="sender_name" name="sender_name" value="{{ old('sender_name', $pesanan->sender_name) }}"
                                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md" required autocomplete="off">
                                <i class="fas fa-user text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                            <p class="text-xs text-red-500 mt-1 italic"><i class="fas fa-info-circle mr-1"></i>Hanya boleh huruf dan spasi. Angka & simbol otomatis dihapus.</p>
                            <div id="sender_contact_results" class="search-results-container hidden"></div>
                        </div>

                        <div class="relative">
                            <label for="sender_phone" class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>
                            <div class="relative">
                                <input type="tel" id="sender_phone" name="sender_phone" value="{{ old('sender_phone', $pesanan->sender_phone) }}"
                                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md" required autocomplete="off">
                                <i class="fas fa-phone-alt text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>

                        <div class="md:col-span-2 relative">
                            <label for="sender_address_search" class="block mb-2 text-sm font-medium text-gray-700">Cari Alamat Ongkir (Kec/Kel/Kodepos)</label>
                            <div class="relative">
                                <input type="text" id="sender_address_search" value="{{ old('sender_address_search', $senderAddrSearch) }}"
                                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md" required autocomplete="off">
                                <i class="fas fa-search text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                                <i id="sender_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 {{ $pesanan->sender_subdistrict_id ? '' : 'hidden' }}"></i>
                            </div>
                            <div id="sender_address_results" class="search-results-container hidden"></div>
                        </div>

                        <div class="md:col-span-2 relative">
                            <label for="sender_address" class="block mb-2 text-sm font-medium text-gray-700">Detail Alamat Lengkap Pengirim</label>
                            <div class="relative">
                                <textarea id="sender_address" name="sender_address" rows="3" placeholder="Contoh: Jl. Pahlawan No. 12, RT 01/RW 05, (Patokan: Sebelah Kantor Pos)" required
                                    class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md transition duration-150 ease-in-out">{{ old('sender_address', $pesanan->sender_address) }}</textarea>
                                <i class="fas fa-map-marker-alt text-gray-400 absolute top-3.5 left-3"></i>
                            </div>
                            <p class="text-xs text-red-500 mt-1 italic"><i class="fas fa-exclamation-circle mr-1"></i>Alamat wajib kapital & minimal 10 karakter.</p>
                        </div>

                        <div class="md:col-span-2 relative">
                            <label for="customer_email" class="block mb-2 text-sm font-medium text-gray-700">Email Notifikasi Pelanggan (Opsional)</label>
                            <div class="relative">
                                <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email', $pesanan->customer_email ?? 'tokosancaka@gmail.com') }}"
                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md"
                                    placeholder="contoh@gmail.com" autocomplete="off">
                                <i class="fas fa-envelope text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center text-sm text-gray-600">
                                <input type="checkbox" name="save_sender" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2"> Simpan perubahan data pengirim ke kontak
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Informasi Penerima -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center border-b pb-4 mb-6">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-map-marker-alt text-green-500 mr-2"></i>Informasi Penerima
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative">
                            <label for="receiver_name" class="block mb-2 text-sm font-medium text-gray-700">Nama Penerima</label>
                            <div class="relative">
                                <input type="text" id="receiver_name" name="receiver_name" value="{{ old('receiver_name', $pesanan->receiver_name) }}"
                                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10 focus:outline-none focus:border-green-500 focus:ring-4 focus:ring-green-300 focus:shadow-md" required autocomplete="off">
                                <i class="fas fa-user text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                            <p class="text-xs text-red-500 mt-1 italic"><i class="fas fa-info-circle mr-1"></i>Hanya boleh huruf dan spasi. Angka & simbol otomatis dihapus.</p>
                            <div id="receiver_contact_results" class="search-results-container hidden"></div>
                        </div>

                        <div class="relative">
                            <label for="receiver_phone" class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>
                            <div class="relative">
                                <input type="tel" id="receiver_phone" name="receiver_phone" value="{{ old('receiver_phone', $pesanan->receiver_phone) }}"
                                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10 focus:outline-none focus:border-green-500 focus:ring-4 focus:ring-green-300 focus:shadow-md" required autocomplete="off">
                                <i class="fas fa-phone-alt text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>

                        <div class="md:col-span-2 relative">
                            <label for="receiver_address_search" class="block mb-2 text-sm font-medium text-gray-700">Cari Alamat Ongkir (Kec/Kel/Kodepos)</label>
                            <div class="relative">
                                <input type="text" id="receiver_address_search" value="{{ old('receiver_address_search', $receiverAddrSearch) }}"
                                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10 focus:outline-none focus:border-green-500 focus:ring-4 focus:ring-green-300 focus:shadow-md" required autocomplete="off">
                                <i class="fas fa-search text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                                <i id="receiver_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 {{ $pesanan->receiver_subdistrict_id ? '' : 'hidden' }}"></i>
                            </div>
                            <div id="receiver_address_results" class="search-results-container hidden"></div>
                        </div>

                        <div class="md:col-span-2 relative">
                            <label for="receiver_address" class="block mb-2 text-sm font-medium text-gray-700">Alamat Penerima Lengkap</label>
                            <div class="relative">
                                <textarea id="receiver_address" name="receiver_address" rows="3" class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:outline-none focus:border-green-500 focus:ring-4 focus:ring-green-300 focus:shadow-md transition duration-150 ease-in-out" placeholder="Contoh: Jl. Pahlawan No. 12, RT 01/RW 05, (Patokan: Sebelah Kantor Pos)" required>{{ old('receiver_address', $pesanan->receiver_address) }}</textarea>
                                <i class="fas fa-map-marker-alt text-gray-400 absolute top-3.5 left-3"></i>
                            </div>
                            <p class="text-xs text-red-500 mt-1 italic"><i class="fas fa-exclamation-circle mr-1"></i>Alamat wajib kapital & minimal 10 karakter.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center text-sm text-gray-600">
                                <input type="checkbox" name="save_receiver" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2"> Simpan perubahan data penerima ke kontak
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan: Detail Paket & Pembayaran -->
            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">
                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">
                        <i class="fas fa-box-open text-yellow-500 mr-2"></i>Detail Paket
                    </h3>
                    <div class="space-y-4">

                        <div>
                            <label for="item_description" class="block mb-2 text-sm font-medium text-gray-700">Deskripsi Barang</label>
                            <div class="relative">
                                <input type="text" id="item_description" name="item_description"
                                       value="{{ old('item_description', $pesanan->item_description ?? 'Barang Umum') }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10" required>
                                <i class="fas fa-box text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>

                        <div>
                            <label for="item_price" class="block mb-2 text-sm font-medium text-gray-700">Harga Barang (Rp)</label>
                            <div class="relative">
                                <input type="number" name="item_price" id="item_price"
                                       value="{{ old('item_price', $pesanan->total_harga_barang ?? $pesanan->item_price ?? 1000) }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10" required min="1">
                                <i class="fas fa-tag text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>

                        <div>
                            <label for="weight" class="block mb-2 text-sm font-medium text-gray-700">Berat (gram)</label>
                            <div class="relative">
                                <input type="number" id="weight" name="weight"
                                       value="{{ old('weight', $pesanan->weight ?? 1000) }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10" required min="1">
                                <i class="fas fa-weight-hanging text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="length" class="block mb-2 text-sm font-medium text-gray-700">P (cm)</label>
                                <div class="relative">
                                    <input type="number" id="length" name="length"
                                           value="{{ old('length', $pesanan->length ?? 10) }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-8">
                                    <i class="fas fa-arrows-alt-h text-gray-400 absolute top-1/2 left-2.5 transform -translate-y-1/2 text-xs"></i>
                                </div>
                            </div>
                            <div>
                                <label for="width" class="block mb-2 text-sm font-medium text-gray-700">L (cm)</label>
                                <div class="relative">
                                    <input type="number" id="width" name="width"
                                           value="{{ old('width', $pesanan->width ?? 10) }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-8">
                                    <i class="fas fa-arrows-alt-h text-gray-400 absolute top-1/2 left-2.5 transform -translate-y-1/2 text-xs" style="transform: translateY(-50%) rotate(45deg);"></i>
                                </div>
                            </div>
                            <div>
                                <label for="height" class="block mb-2 text-sm font-medium text-gray-700">T (cm)</label>
                                <div class="relative">
                                    <input type="number" id="height" name="height"
                                           value="{{ old('height', $pesanan->height ?? 10) }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-8">
                                    <i class="fas fa-arrows-alt-v text-gray-400 absolute top-1/2 left-2.5 transform -translate-y-1/2 text-xs"></i>
                                </div>
                            </div>
                        </div>

                        @php $itemType = old('item_type', $pesanan->item_type); @endphp
                        <div>
                            <label for="item_type" class="block mb-2 text-sm font-medium text-gray-700">Jenis Barang</label>
                            <div class="relative">
                                <select name="item_type" id="item_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10" required>
                                    <option value="" disabled>Pilih...</option>
                                    <option value="1" {{ $itemType == '1' ? 'selected' : '' }}>Peralatan Elektronik & Gadget</option>
                                    <option value="2" {{ $itemType == '2' ? 'selected' : '' }}>Pakaian / Baju / Kain</option>
                                    <option value="3" {{ $itemType == '3' ? 'selected' : '' }}>Pecah Belah</option>
                                    <option value="4" {{ $itemType == '4' ? 'selected' : '' }}>Dokumen / Berkas / Buku</option>
                                    <option value="5" {{ $itemType == '5' ? 'selected' : '' }}>Peralatan Rumah Tangga</option>
                                    <option value="6" {{ $itemType == '6' ? 'selected' : '' }}>Aksesoris</option>
                                    <option value="7" {{ $itemType == '7' || empty($itemType) ? 'selected' : '' }}>Lain-Lain</option>
                                    <option value="8" {{ $itemType == '8' ? 'selected' : '' }}>Dokumen Berharga</option>
                                    <option value="9" {{ $itemType == '9' ? 'selected' : '' }}>Peralatan Kesehatan / Kecantikan / Kosmetik</option>
                                    <option value="10" {{ $itemType == '10' ? 'selected' : '' }}>Peralatan Olahraga & Hiburan</option>
                                    <option value="11" {{ $itemType == '11' ? 'selected' : '' }}>Perlengkapan Mobil & Motor</option>
                                </select>
                                <i class="fas fa-list-ul text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>

                        @php $svcType = old('service_type', $pesanan->service_type ?? 'regular'); @endphp
                        <div>
                            <label for="service_type" class="block mb-2 text-sm font-medium text-gray-700">Jenis Layanan</label>
                            <div class="relative">
                                <select name="service_type" id="service_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10" required>
                                    <option value="" disabled>Pilih...</option>
                                    <option value="regular" {{ $svcType == 'regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="express" {{ $svcType == 'express' ? 'selected' : '' }}>Express</option>
                                    <option value="sameday" {{ $svcType == 'sameday' ? 'selected' : '' }}>Sameday</option>
                                    <option value="instant" {{ $svcType == 'instant' ? 'selected' : '' }}>Instant</option>
                                    <option value="cargo" {{ $svcType == 'cargo' ? 'selected' : '' }}>Cargo</option>
                                </select>
                                <i class="fas fa-truck text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>

                        @php $asuransi = old('ansuransi', $pesanan->ansuransi ?? 'tidak'); @endphp
                        <div>
                            <label for="ansuransi" class="block mb-2 text-sm font-medium text-gray-700">Asuransi</label>
                            <div class="relative">
                                <select name="ansuransi" id="ansuransi" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pl-10" required>
                                    <option value="tidak" {{ $asuransi == 'tidak' ? 'selected' : '' }}>Tidak</option>
                                    <option value="iya" {{ $asuransi == 'iya' ? 'selected' : '' }}>Iya</option>
                                </select>
                                <i class="fas fa-shield-alt text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2"></i>
                            </div>
                        </div>
                        <hr/>
                        <div>
                            <label for="selected_expedition_display" class="block mb-2 text-sm font-medium text-gray-700">
                                Pilih Ekspedisi <span class="text-xs text-gray-500 font-normal">(Klik untuk ganti / cek ulang tarif)</span>
                            </label>
                            <input type="text" id="selected_expedition_display" value="{{ $oldExpeditionDisplay }}"
                                   class="cursor-pointer bg-red-50 border border-red-300 text-red-600 text-sm rounded-lg block w-full p-2.5 text-center font-semibold"
                                   placeholder="Klik di sini untuk cek & pilih tarif" readonly required>
                        </div>

                        <div>
                            <label for="paymentMethodButton" class="block mb-2 text-sm font-medium text-gray-700">Metode Pembayaran</label>
                            <div id="paymentMethodButton" class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 flex justify-between items-center">
                                <div class="flex items-center">
                                    <img id="selectedPaymentLogo" src="{{ $pmLogo }}" alt="Logo" class="w-6 h-6 mr-2 object-contain">
                                    <span id="selectedPaymentName">{{ $pmName }}</span>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>

                        <!-- WADAH PENCARIAN CUSTOMER (SELECT2 AJAX + PREFILL) -->
                        <div id="customer_container" class="md:col-span-2 {{ $pm == 'Potong Saldo' ? '' : 'hidden' }} mt-2">
                            <label for="customer_id" class="block mb-2 text-sm font-medium text-gray-700">
                                <i class="fas fa-users text-red-600 mr-1"></i> Pilih Pelanggan (Pemilik Saldo)
                            </label>
                            <select id="customer_id" name="customer_id" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg" {{ $pm == 'Potong Saldo' ? 'required' : '' }}>
                                @if(isset($customers))
                                    <option value="">-- Pilih Pelanggan --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id_pengguna }}" {{ old('customer_id', $pesanan->customer_id) == $customer->id_pengguna ? 'selected' : '' }}>
                                            {{ $customer->nama_lengkap }} (Saldo: Rp {{ number_format($customer->saldo ?? 0) }})
                                        </option>
                                    @endforeach
                                @elseif(!empty(old('customer_id', $pesanan->customer_id)))
                                    <option value="{{ old('customer_id', $pesanan->customer_id) }}" selected>
                                        {{ $pesanan->customer->nama_lengkap ?? ('Pelanggan #' . old('customer_id', $pesanan->customer_id)) }}
                                    </option>
                                @endif
                            </select>
                            <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Ketik nama, nama toko, atau No. WA untuk mencari pelanggan.</p>
                        </div>

                        <div class="pt-4">
                            <button type="button" id="confirmBtn" class="w-full text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-3 text-center">
                                Simpan & Re-Payload
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden fields untuk data API --}}
        <input type="hidden" name="pengirim_id" id="sender_id" value="{{ old('pengirim_id', $pesanan->kontak_pengirim_id) }}">
        <input type="hidden" name="sender_lat" id="sender_lat" value="{{ old('sender_lat', $pesanan->sender_lat) }}">
        <input type="hidden" name="sender_lng" id="sender_lng" value="{{ old('sender_lng', $pesanan->sender_lng) }}">
        <input type="hidden" name="sender_province" id="sender_province" value="{{ old('sender_province', $pesanan->sender_province) }}">
        <input type="hidden" name="sender_regency" id="sender_regency" value="{{ old('sender_regency', $pesanan->sender_regency) }}">
        <input type="hidden" name="sender_district" id="sender_district" value="{{ old('sender_district', $pesanan->sender_district) }}">
        <input type="hidden" name="sender_village" id="sender_village" value="{{ old('sender_village', $pesanan->sender_village) }}">
        <input type="hidden" name="sender_postal_code" id="sender_postal_code" value="{{ old('sender_postal_code', $pesanan->sender_postal_code) }}">
        <input type="hidden" name="sender_district_id" id="sender_district_id" value="{{ old('sender_district_id', $pesanan->sender_district_id) }}" required>
        <input type="hidden" name="sender_subdistrict_id" id="sender_subdistrict_id" value="{{ old('sender_subdistrict_id', $pesanan->sender_subdistrict_id) }}" required>

        <input type="hidden" name="penerima_id" id="receiver_id" value="{{ old('penerima_id', $pesanan->kontak_penerima_id) }}">
        <input type="hidden" name="receiver_lat" id="receiver_lat" value="{{ old('receiver_lat', $pesanan->receiver_lat) }}">
        <input type="hidden" name="receiver_lng" id="receiver_lng" value="{{ old('receiver_lng', $pesanan->receiver_lng) }}">
        <input type="hidden" name="receiver_province" id="receiver_province" value="{{ old('receiver_province', $pesanan->receiver_province) }}">
        <input type="hidden" name="receiver_regency" id="receiver_regency" value="{{ old('receiver_regency', $pesanan->receiver_regency) }}">
        <input type="hidden" name="receiver_district" id="receiver_district" value="{{ old('receiver_district', $pesanan->receiver_district) }}">
        <input type="hidden" name="receiver_village" id="receiver_village" value="{{ old('receiver_village', $pesanan->receiver_village) }}">
        <input type="hidden" name="receiver_postal_code" id="receiver_postal_code" value="{{ old('receiver_postal_code', $pesanan->receiver_postal_code) }}">
        <input type="hidden" name="receiver_district_id" id="receiver_district_id" value="{{ old('receiver_district_id', $pesanan->receiver_district_id) }}" required>
        <input type="hidden" name="receiver_subdistrict_id" id="receiver_subdistrict_id" value="{{ old('receiver_subdistrict_id', $pesanan->receiver_subdistrict_id) }}" required>

        <input type="hidden" name="expedition" id="expedition" value="{{ $oldExpeditionVal }}" required>
        <input type="hidden" name="payment_method" id="payment_method" value="{{ $pm }}" required>
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
    </form>
</div>

<!-- Modal Pilihan Ekspedisi -->
<div id="ongkirModal" class="fixed inset-0 bg-gray-800 bg-opacity-60 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4">
        <div class="p-4 border-b flex justify-between items-center">
            <h5 class="text-lg font-semibold"><i class="fas fa-shipping-fast mr-2 text-red-600"></i>Pilihan Ekspedisi</h5>
            <button type="button" class="close-modal-btn text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div id="ongkirModalBody" class="p-6 modal-body-scroll"></div>
        <div class="p-4 border-t text-right">
            <button type="button" class="close-modal-btn px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Metode Pembayaran -->
<div id="paymentMethodModal" class="fixed inset-0 bg-gray-800 bg-opacity-60 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-4 border-b flex justify-between items-center">
            <h5 class="text-lg font-semibold"><i class="fas fa-credit-card mr-2 text-red-600"></i>Pilih Metode Pembayaran</h5>
            <button type="button" class="close-modal-btn text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="modal-body-scroll">
            <ul id="paymentOptionsList" class="divide-y">
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 {{ $pm == 'Potong Saldo' ? 'bg-red-50' : '' }}" data-value="Potong Saldo" data-label="Potong Saldo">
                    <img src="https://tokosancaka.com/public/assets/saldo.png" class="w-8 h-8 mr-4">Potong Saldo
                </li>

                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 {{ $pm == 'Cash' ? 'bg-red-50' : '' }}" data-value="Cash" data-label="Cash (Tanpa Saldo)">
                    <img src="https://tokosancaka.com/public/assets/saldo.png" class="w-8 h-8 mr-4">Cash (Tanpa Saldo)
                </li>

                <li class="bg-blue-100 p-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Direct Payment (Bebas Biaya Tripay)</li>

                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 {{ $pm == 'BCA_QRIS' ? 'bg-red-50' : '' }}" data-value="BCA_QRIS" data-label="BCA QRIS (Otomatis)">
                    <img src="https://tokosancaka.com/assets/bca.png" class="w-8 h-8 mr-4 object-contain">BCA QRIS (Generate Barcode)
                </li>

                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 {{ $pm == 'DOKU_JOKUL' ? 'bg-red-50' : '' }}" data-value="DOKU_JOKUL" data-label="DOKU Payment Gateway">
                    <img src="https://tokosancaka.com/public/assets/doku.png" class="w-8 h-8 mr-4 object-contain">DOKU Payment Gateway
                </li>

                <li class="bg-red-100 p-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Bayar Ditempat</li>

                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 cod-payment-option {{ $pm == 'COD' ? 'bg-red-50' : '' }}" data-value="COD" data-label="COD Ongkir">
                    <img src="{{ asset('public/assets/cod.png') }}" class="w-8 h-8 mr-4">COD Ongkir
                </li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 cod-payment-option {{ $pm == 'CODBARANG' ? 'bg-red-50' : '' }}" data-value="CODBARANG" data-label="COD Barang + Ongkir">
                    <img src="{{ asset('public/assets/cod.png') }}" class="w-8 h-8 mr-4">COD Barang + Ongkir
                </li>

                <li class="bg-blue-100 p-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Transfer Bank & Minimarket (Otomatis)</li>

                <div id="tripayChannelsContainer">
                    <div class="p-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Memuat saluran pembayaran...
                    </div>
                </div>
            </ul>
        </div>
        <div class="p-4 border-t text-right">
            <button type="button" class="close-modal-btn px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Tutup</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ongkirModalEl = document.getElementById('ongkirModal');
    const paymentModalEl = document.getElementById('paymentMethodModal');
    let runValidityChecks = () => {};

    function formatRupiah(angka) {
        return 'Rp ' + (parseInt(angka, 10) || 0).toLocaleString('id-ID');
    }

    const debounce = (func, wait) => {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    };

    function resetExpeditionSelection(reason = 'Data berubah, klik untuk cek ulang tarif') {
        const expeditionDisplay = document.getElementById('selected_expedition_display');
        const expeditionValue = document.getElementById('expedition');
        if (expeditionDisplay && expeditionValue) {
            expeditionValue.value = '';
            expeditionDisplay.value = '';
            expeditionDisplay.placeholder = reason;
            expeditionDisplay.classList.add('animate-pulse');
        }
        runValidityChecks();
    }

    // --- PENCARIAN KONTAK ---
    function setupContactSearch(prefix) {
        const nameInput = document.getElementById(`${prefix}_name`);
        const phoneInput = document.getElementById(`${prefix}_phone`);
        const resultsContainer = document.getElementById(`${prefix}_contact_results`);

        const performSearch = async (query) => {
            if (query.length < 3) { resultsContainer.classList.add('hidden'); return; }
            try {
                const url = `{{ route('api.contacts.search') }}?search=${encodeURIComponent(query)}`;
                const response = await fetch(url);
                if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                const contacts = await response.json();
                resultsContainer.innerHTML = '';
                resultsContainer.classList.remove('hidden');

                if (contacts && contacts.length > 0) {
                    contacts.forEach(contact => {
                        const resultDiv = document.createElement('div');
                        resultDiv.className = 'p-3 border-b hover:bg-gray-100 cursor-pointer text-sm';
                        resultDiv.innerHTML = `
                            <div class="font-semibold">${contact.nama}</div>
                            <div class="text-xs text-gray-500">${contact.no_hp}</div>
                            <div class="text-xs text-gray-400 truncate">${contact.alamat || '-'}</div>`;

                        resultDiv.addEventListener('click', () => {
                            document.getElementById(`${prefix}_id`).value = contact.id || '';
                            document.getElementById(`${prefix}_name`).value = (contact.nama || '').replace(/[^a-zA-Z\s]/g, '').toUpperCase();
                            document.getElementById(`${prefix}_phone`).value = normalizePhone(contact.no_hp || '');
                            document.getElementById(`${prefix}_address`).value = (contact.alamat || '').toUpperCase();
                            document.getElementById(`${prefix}_province`).value = contact.province || '';
                            document.getElementById(`${prefix}_regency`).value = contact.regency || '';
                            document.getElementById(`${prefix}_district`).value = contact.district || '';
                            document.getElementById(`${prefix}_village`).value = contact.village || '';
                            document.getElementById(`${prefix}_postal_code`).value = contact.postal_code || '';
                            const kiriminAjaSearchString = [contact.village, contact.district, contact.regency, contact.postal_code].filter(Boolean).join(', ');
                            document.getElementById(`${prefix}_address_search`).value = kiriminAjaSearchString;
                            resultsContainer.classList.add('hidden');
                            if (kiriminAjaSearchString) performAddressSearch(prefix, kiriminAjaSearchString, contact);
                            runValidityChecks();
                        });
                        resultsContainer.appendChild(resultDiv);
                    });
                } else {
                    resultsContainer.innerHTML = '<div class="p-3 text-gray-500">Kontak tidak ditemukan.</div>';
                }
            } catch (error) {
                console.error(`[${prefix}] Gagal melakukan pencarian kontak:`, error);
                resultsContainer.classList.remove('hidden');
                resultsContainer.innerHTML = `<div class="p-3 text-red-500">Gagal memuat data.</div>`;
            }
        };
        const debouncedSearch = debounce(performSearch, 400);
        nameInput.addEventListener('input', () => debouncedSearch(nameInput.value));
        phoneInput.addEventListener('input', () => debouncedSearch(phoneInput.value));
    }

    // --- PEMILIHAN ALAMAT + KOORDINAT LAT/LNG ---
    function selectAddress(prefix, item) {
        document.getElementById(`${prefix}_address_search`).value = item.full_address;
        const parts = item.full_address.split(',').map(s => s.trim());
        document.getElementById(`${prefix}_village`).value = parts[0] || '';
        document.getElementById(`${prefix}_district`).value = parts[1] || '';
        document.getElementById(`${prefix}_regency`).value = parts[2] || '';
        document.getElementById(`${prefix}_province`).value = parts[3] || '';
        document.getElementById(`${prefix}_postal_code`).value = parts[4] || '';
        document.getElementById(`${prefix}_district_id`).value = item.district_id;
        document.getElementById(`${prefix}_subdistrict_id`).value = item.subdistrict_id;
        document.getElementById(`${prefix}_lat`).value = item.lat || '';
        document.getElementById(`${prefix}_lng`).value = item.lng || '';
        document.getElementById(`${prefix}_address_results`).classList.add('hidden');
        document.getElementById(`${prefix}_address_check`).classList.remove('hidden');

        resetExpeditionSelection('Alamat berubah, klik untuk cek ulang ongkir');
    }

    async function performAddressSearch(prefix, query, contactToMatch = null) {
        const resultsContainer = document.getElementById(`${prefix}_address_results`);
        if (query.length < 3) { resultsContainer.classList.add('hidden'); return; }
        try {
            const response = await fetch(`{{ route('api.address.search') }}?search=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('Network response error');
            const data = await response.json();
            resultsContainer.innerHTML = '';
            resultsContainer.classList.remove('hidden');
            if (data && data.length > 0) {
                if (contactToMatch) {
                    const exactMatch = data.find(item => {
                        const normalizedApiAddress = item.full_address.toLowerCase();
                        const village = (contactToMatch.village || '').toLowerCase();
                        const district = (contactToMatch.district || '').toLowerCase();
                        const regency = (contactToMatch.regency || '').toLowerCase().replace(/kabupaten |kota /g, '');
                        const postalCode = (contactToMatch.postal_code || '');
                        return village && district && regency && postalCode &&
                               normalizedApiAddress.includes(village) &&
                               normalizedApiAddress.includes(district) &&
                               normalizedApiAddress.includes(regency) &&
                               normalizedApiAddress.includes(postalCode);
                    });
                    if (exactMatch) { selectAddress(prefix, exactMatch); return; }
                }
                if (data.length === 1) { selectAddress(prefix, data[0]); return; }
                data.forEach(item => {
                    const resultDiv = document.createElement('div');
                    resultDiv.className = 'p-3 border-b hover:bg-gray-100 cursor-pointer text-sm';
                    resultDiv.innerHTML = `<div class="font-semibold">${item.full_address}</div>`;
                    resultDiv.addEventListener('click', () => selectAddress(prefix, item));
                    resultsContainer.appendChild(resultDiv);
                });
            } else {
                resultsContainer.innerHTML = '<div class="p-3 text-gray-500">Alamat tidak ditemukan.</div>';
            }
        } catch (error) {
            console.error('Address search failed:', error);
            resultsContainer.innerHTML = '<div class="p-3 text-red-500">Gagal memuat data alamat.</div>';
        }
    }

    function setupAddressSearch(prefix) {
        const searchInput = document.getElementById(`${prefix}_address_search`);
        const debouncedSearch = debounce(() => {
            document.getElementById(`${prefix}_address_check`).classList.add('hidden');
            document.getElementById(`${prefix}_lat`).value = '';
            document.getElementById(`${prefix}_lng`).value = '';
            document.getElementById(`${prefix}_district_id`).value = '';
            document.getElementById(`${prefix}_subdistrict_id`).value = '';
            resetExpeditionSelection('Alamat berubah, klik untuk cek ulang ongkir');
            performAddressSearch(prefix, searchInput.value, null);
        }, 400);
        searchInput.addEventListener('input', debouncedSearch);
    }

    // --- CEK ONGKIR ---
    async function runCekOngkir() {
        const requiredFields = {
            '#sender_subdistrict_id': 'Alamat Pengirim',
            '#receiver_subdistrict_id': 'Alamat Penerima',
            '#item_price': 'Harga Barang',
            '#weight': 'Berat',
            '#service_type': 'Jenis Layanan',
            '#ansuransi': 'Asuransi'
        };
        let missing = Object.keys(requiredFields).filter(s => !document.querySelector(s).value);
        if (missing.length > 0) {
            Swal.fire('Data Belum Lengkap', 'Harap lengkapi: ' + missing.map(s => requiredFields[s]).join(', '), 'warning');
            return;
        }
        const ongkirModalBody = document.getElementById('ongkirModalBody');
        ongkirModalBody.innerHTML = `<div class="text-center p-5"><i class="fas fa-spinner fa-spin text-3xl text-red-600"></i><p class="mt-2 text-gray-500">Memuat tarif...</p></div>`;
        ongkirModalEl.classList.remove('hidden');
        try {
            const formData = new FormData(document.getElementById('orderForm'));
            formData.delete('_method'); // Hindari konflik method PUT di query GET
            const params = new URLSearchParams(formData).toString();

            const response = await fetch(`{{ route('kirimaja.cekongkir') }}?${params}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Gagal mengambil data ongkir');
            }
            const res = await response.json();
            ongkirModalBody.innerHTML = '';
            let results = (res.results || []).concat((res.result || []).flatMap(v => v.costs.map(c => ({
                ...c,
                service: v.name,
                service_name: `${v.name.toUpperCase()} - ${c.service_type}`,
                cost: c.price.total_price,
                etd: c.estimation || '-',
                setting: c.setting || {},
                insurance: c.price.insurance_fee || 0,
                cod: c.cod
            }))));

            if (results.length === 0) {
                ongkirModalBody.innerHTML = '<div class="bg-yellow-100 text-yellow-800 p-4 rounded-md text-center">Layanan pengiriman tidak ditemukan.</div>';
                return;
            }

            results.sort((a, b) => a.cost - b.cost).forEach(item => {
                const isCod = item.cod;
                const shippingCost = parseInt(item.cost) || 0;
                const insuranceFee = parseInt(item.insurance) || 0;
                const codFeeApi = parseInt(item.add_cost) || parseInt(item.setting?.cod_fee_amount) || 0;

                const value = `${document.getElementById('service_type').value}-${item.service}-${item.service_type}-${shippingCost}-${insuranceFee}-${codFeeApi}-${codFeeApi}`;

                let details = `<small class="text-gray-500 block">Estimasi: ${item.etd}</small>`;
                if (document.getElementById('ansuransi').value == 'iya' && insuranceFee > 0) {
                    details += `<small class="text-gray-500 block">Asuransi: ${formatRupiah(insuranceFee)}</small>`;
                }
                if (isCod) {
                    details += `<small class="text-gray-600 font-medium block mt-1">Biaya COD (API): <span class="text-red-600">${formatRupiah(codFeeApi)}</span></small>`;
                    details += `<small class="text-green-600 font-bold block mt-1">COD Tersedia</small>`;
                }

                const card = document.createElement('div');
                card.className = 'border rounded-lg mb-3 shadow-sm hover:border-red-500 transition-colors';
                card.innerHTML = `
                    <div class="p-4 flex justify-between items-center">
                        <div class="flex items-center">
                            <img src="{{ asset('/public/storage/logo-ekspedisi/') }}/${item.service.toLowerCase().replace(/\s+/g, '')}.png" class="w-16 h-auto mr-4 object-contain" onerror="this.src='https://placehold.co/100x40?text=${item.service}'">
                            <div><h6 class="font-bold text-gray-800">${item.service_name}</h6>${details}</div>
                        </div>
                        <div class="text-right">
                            <small class="text-gray-500">Ongkir</small>
                            <strong class="block text-lg text-red-600">${formatRupiah(shippingCost)}</strong>
                            <button type="button" class="select-ongkir-btn mt-1 bg-red-600 text-white px-4 py-1.5 rounded-md hover:bg-red-700 text-sm font-semibold" data-value="${value}" data-display="${item.service_name} (${formatRupiah(shippingCost)})" data-cod-supported="${isCod}">Pilih</button>
                        </div>
                    </div>`;
                ongkirModalBody.appendChild(card);
            });
        } catch (error) {
            console.error('Cek Ongkir failed:', error);
            ongkirModalBody.innerHTML = `<div class="bg-red-100 text-red-800 p-4 rounded-md text-center">${error.message}</div>`;
        }
    }

    // --- FORMATTING OTOMATIS (HP, NAMA, ALAMAT) ---
    function normalizePhone(val) {
        val = (val || '').replace(/\D/g, '');
        if (val.startsWith('62')) val = '0' + val.substring(2);
        else if (val.length > 0 && !val.startsWith('0')) val = '0' + val;
        return val;
    }

    function setupPhoneFormatting(inputId) {
        const phoneInput = document.getElementById(inputId);
        if (!phoneInput) return;
        phoneInput.value = normalizePhone(phoneInput.value);
        phoneInput.addEventListener('input', function () {
            this.value = normalizePhone(this.value);
        });
    }

    function setupNameFormatting(inputId) {
        const nameInput = document.getElementById(inputId);
        if (!nameInput) return;
        nameInput.value = (nameInput.value || '').replace(/[^a-zA-Z\s]/g, '').toUpperCase();
        nameInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase();
        });
    }

    function setupAddressFormatting(inputId) {
        const addressInput = document.getElementById(inputId);
        if (!addressInput) return;
        addressInput.value = (addressInput.value || '').toUpperCase();
        addressInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }

    setupContactSearch('sender');
    setupContactSearch('receiver');
    setupAddressSearch('sender');
    setupAddressSearch('receiver');
    setupPhoneFormatting('sender_phone');
    setupPhoneFormatting('receiver_phone');
    setupNameFormatting('sender_name');
    setupNameFormatting('receiver_name');
    setupAddressFormatting('sender_address');
    setupAddressFormatting('receiver_address');

    // Reset ekspedisi jika parameter tarif (berat/dimensi/layanan) diubah
    ['weight', 'length', 'width', 'height', 'service_type', 'item_price'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => resetExpeditionSelection('Data paket berubah, klik untuk cek ulang tarif'));
        }
    });

    document.getElementById('selected_expedition_display').addEventListener('click', runCekOngkir);

    ongkirModalEl.addEventListener('click', function(e) {
        if (e.target.classList.contains('select-ongkir-btn')) {
            document.getElementById('expedition').value = e.target.dataset.value;
            const displayEl = document.getElementById('selected_expedition_display');
            displayEl.value = e.target.dataset.display;
            displayEl.classList.remove('animate-pulse');
            ongkirModalEl.classList.add('hidden');
            runValidityChecks();
        }
    });

    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            ongkirModalEl.classList.add('hidden');
            paymentModalEl.classList.add('hidden');
        });
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('#sender_address_search, #sender_address_results')) {
            document.getElementById('sender_address_results').classList.add('hidden');
        }
        if (!event.target.closest('#receiver_address_search, #receiver_address_results')) {
            document.getElementById('receiver_address_results').classList.add('hidden');
        }
        if (!event.target.closest('#sender_name, #sender_contact_results, #sender_phone')) {
            document.getElementById('sender_contact_results').classList.add('hidden');
        }
        if (!event.target.closest('#receiver_name, #receiver_contact_results, #receiver_phone')) {
            document.getElementById('receiver_contact_results').classList.add('hidden');
        }
    });

    // --- PROTEKSI ASURANSI ---
    const itemTypeSelect = document.getElementById('item_type');
    const asuransiSelect = document.getElementById('ansuransi');
    const wajibAsuransiIds = ['1', '3', '4', '8'];

    function applyStrictInsurance(isInitial = false) {
        if (!itemTypeSelect || !asuransiSelect) return;

        const selectedType = itemTypeSelect.value;
        const itemTypeName = itemTypeSelect.options[itemTypeSelect.selectedIndex].text;

        if (wajibAsuransiIds.includes(selectedType)) {
            asuransiSelect.value = 'iya';
            asuransiSelect.classList.remove('bg-green-50', 'border-green-300', 'text-green-600');
            asuransiSelect.classList.add('bg-red-50', 'border-red-300', 'text-red-600', 'font-semibold', 'cursor-not-allowed');
            asuransiSelect.style.pointerEvents = 'none';

            if (!isInitial) {
                Swal.fire({
                    icon: 'info',
                    title: 'Asuransi Wajib',
                    text: `Kategori "${itemTypeName}" wajib asuransi. Pilihan dikunci ke "Iya".`,
                    confirmButtonColor: '#ef4444'
                });
            }
        } else {
            asuransiSelect.classList.remove('bg-red-50', 'border-red-300', 'text-red-600', 'cursor-not-allowed');
            asuransiSelect.classList.add('bg-green-50', 'border-green-300', 'text-green-600', 'font-semibold');
            asuransiSelect.style.pointerEvents = 'auto';
            if (!isInitial) asuransiSelect.value = 'tidak';
        }

        if (!isInitial) {
            resetExpeditionSelection('Kategori/asuransi berubah, klik untuk cek ulang tarif');
        }
        runValidityChecks();
    }

    itemTypeSelect.addEventListener('change', () => applyStrictInsurance(false));

    asuransiSelect.addEventListener('change', function() {
        if (this.value === 'iya') {
            this.classList.replace('bg-green-50', 'bg-red-50');
            this.classList.replace('border-green-300', 'border-red-300');
            this.classList.replace('text-green-600', 'text-red-600');
        } else {
            this.classList.replace('bg-red-50', 'bg-green-50');
            this.classList.replace('border-red-300', 'border-green-300');
            this.classList.replace('text-red-600', 'text-green-600');
        }
        resetExpeditionSelection('Asuransi berubah, klik untuk cek ulang tarif');
    });

    applyStrictInsurance(true);

    // --- VALIDASI FORM ---
    const form = document.getElementById('orderForm');
    const confirmBtn = document.getElementById('confirmBtn');
    const expeditionInput = document.getElementById('expedition');
    const paymentMethodInput = document.getElementById('payment_method');
    const customerSelect = document.getElementById('customer_id');

    runValidityChecks = function() {
        const html5Valid = form.checkValidity();
        const expeditionChosen = expeditionInput && expeditionInput.value.trim() !== '';
        const paymentChosen = paymentMethodInput && paymentMethodInput.value.trim() !== '';

        let potongSaldoFailsCustomer = false;
        if (paymentChosen && paymentMethodInput.value === 'Potong Saldo') {
            const customerChosen = customerSelect && customerSelect.value && customerSelect.value.trim() !== '';
            if (!customerChosen) potongSaldoFailsCustomer = true;
        }

        const allOk = html5Valid && expeditionChosen && paymentChosen && !potongSaldoFailsCustomer;
        if (!allOk) {
            confirmBtn.disabled = true;
            confirmBtn.classList.add('opacity-60', 'cursor-not-allowed');
            confirmBtn.setAttribute('title', potongSaldoFailsCustomer ? 'Pilih pelanggan saat menggunakan Potong Saldo' : 'Lengkapi form terlebih dahulu');
        } else {
            confirmBtn.disabled = false;
            confirmBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            confirmBtn.removeAttribute('title');
        }
    };

    runValidityChecks();

    Array.from(document.querySelectorAll('input, select, textarea')).forEach(el => {
        if (el.type === 'hidden') return;
        const debouncedCheck = debounce(runValidityChecks, 200);
        el.addEventListener('input', debouncedCheck);
        el.addEventListener('change', runValidityChecks);
        el.addEventListener('blur', runValidityChecks);
    });

    confirmBtn.addEventListener('click', function(e) {
        e.preventDefault();
        runValidityChecks();

        if (confirmBtn.disabled) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: (paymentMethodInput.value === 'Potong Saldo' && (!customerSelect.value || customerSelect.value === ''))
                        ? 'Anda harus memilih pelanggan jika menggunakan metode Potong Saldo.'
                        : 'Harap lengkapi semua field yang wajib diisi, termasuk alamat, ekspedisi, dan metode pembayaran.'
            });
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Re-Payload',
            text: "Apakah semua data sudah benar? Pesanan ini akan dicoba kirim ulang ke API KiriminAja.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ff0000ff',
            cancelButtonColor: '#0be628ff',
            confirmButtonText: 'Ya, Simpan & Re-Payload',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...`;
                form.submit();
            }
        });
    });

    // --- PEMBAYARAN & TRIPAY ---
    let tripayLoaded = false;

    async function loadTripayChannels() {
        if (tripayLoaded) return;
        const container = document.getElementById('tripayChannelsContainer');
        try {
            const response = await fetch("{{ route('admin.pesanan.get_channels') }}");
            const res = await response.json();

            if (res.success && res.data.length > 0) {
                container.innerHTML = '';
                res.data.forEach(channel => {
                    if (channel.active) {
                        const li = document.createElement('li');
                        li.className = 'payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 border-b';
                        if (channel.code === document.getElementById('payment_method').value) {
                            li.classList.add('bg-red-50');
                        }
                        li.dataset.value = channel.code;
                        li.dataset.label = channel.name;
                        li.dataset.img   = channel.icon_url;

                        li.innerHTML = `
                            <img src="${channel.icon_url}" class="w-10 h-10 mr-4 object-contain p-1 border rounded bg-white" onerror="this.src='https://placehold.co/50'">
                            <div>
                                <div class="font-semibold text-gray-800">${channel.name}</div>
                                <div class="text-xs text-gray-500">${channel.group_name || channel.group || ''}</div>
                            </div>
                        `;
                        li.addEventListener('click', () => selectPayment(li));
                        container.appendChild(li);
                    }
                });
                tripayLoaded = true;
            } else {
                container.innerHTML = '<div class="p-4 text-center text-red-500 text-sm">Gagal memuat saluran pembayaran.</div>';
            }
        } catch (error) {
            console.error(error);
            container.innerHTML = '<div class="p-4 text-center text-red-500 text-sm">Terjadi kesalahan koneksi API.</div>';
        }
    }

    function selectPayment(element) {
        const val = element.dataset.value;
        const label = element.dataset.label;
        const img = element.dataset.img || element.querySelector('img').src;

        document.getElementById('payment_method').value = val;
        document.getElementById('selectedPaymentName').textContent = label;
        document.getElementById('selectedPaymentLogo').src = img;

        document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('bg-red-50'));
        element.classList.add('bg-red-50');

        const custContainer = document.getElementById('customer_container');
        const custSelect = document.getElementById('customer_id');

        if (val === 'Potong Saldo') {
            custContainer.classList.remove('hidden');
            custSelect.setAttribute('required', 'required');
        } else {
            custContainer.classList.add('hidden');
            custSelect.removeAttribute('required');
        }

        paymentModalEl.classList.add('hidden');
        runValidityChecks();
    }

    document.getElementById('paymentMethodButton').addEventListener('click', () => {
        paymentModalEl.classList.remove('hidden');
        loadTripayChannels();
    });

    document.querySelectorAll('#paymentOptionsList > li.payment-option').forEach(li => {
        li.addEventListener('click', () => selectPayment(li));
    });

    // --- SELECT2 PELANGGAN ---
    $('#customer_id').select2({
        width: '100%',
        placeholder: 'Ketik nama / toko / no WA...',
        allowClear: true,
        ajax: {
            url: "{{ route('admin.pesanan.search_customer') }}",
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return { q: params.term, page: params.page || 1 };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: { more: data.pagination.more }
                };
            },
            cache: true
        },
        templateResult: function(repo) {
            if (repo.loading) return "Mencari data...";
            if (!repo.nama_lengkap) return repo.text;
            let toko = repo.store_name ? `<span class="text-xs text-gray-500 block mt-0.5"><i class="fas fa-store text-gray-400 mr-1"></i> ${repo.store_name}</span>` : '';
            let saldoFormat = new Intl.NumberFormat('id-ID').format(repo.saldo || 0);
            return $(`
                <div class="p-2 border-b border-gray-100">
                    <div class="font-bold text-gray-800">${repo.nama_lengkap}</div>
                    ${toko}
                    <div class="text-xs text-red-600 font-bold mt-1 bg-red-50 inline-block px-2 py-0.5 rounded border border-red-100">
                        <i class="fas fa-wallet mr-1"></i> Saldo: Rp ${saldoFormat}
                    </div>
                </div>
            `);
        },
        templateSelection: function(repo) {
            if (!repo.id) return repo.text || "Pilih Pelanggan...";
            if (!repo.nama_lengkap) return repo.text;
            let saldoFormat = new Intl.NumberFormat('id-ID').format(repo.saldo || 0);
            return repo.nama_lengkap + ' (Saldo: Rp ' + saldoFormat + ')';
        }
    });

    $('#customer_id').on('select2:select select2:clear change', function () {
        runValidityChecks();
    });
});
</script>

@if(session('tripay_error_modal'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'error',
            title: 'Pembayaran Gagal!',
            html: `
                <div class="text-left">
                    <p class="mb-2">Tripay menolak permintaan ini dengan alasan:</p>
                    <div class="p-3 bg-red-50 border border-red-200 rounded text-red-700 font-mono text-sm">
                        {{ session('tripay_error_modal') }}
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Silakan periksa nominal ongkir atau item tambahan.</p>
                </div>
            `,
            confirmButtonText: 'Perbaiki Pesanan',
            confirmButtonColor: '#d33'
        });
    });
</script>
@endif

@endpush
