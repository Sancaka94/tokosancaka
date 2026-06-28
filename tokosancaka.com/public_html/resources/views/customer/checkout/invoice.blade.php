@extends('layouts.customer')

@section('title', 'Invoice Pesanan - ' . ($order->invoice_number ?? ''))

@section('content')
    <div class="bg-gradient-to-br from-gray-50 to-gray-200 min-h-screen flex items-center justify-center p-4 sm:p-6 font-sans">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden">
            
            {{-- Header Section --}}
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center">
                        <img src="https://tokosancaka.biz.id/storage/uploads/logo.jpeg" alt="Logo CV. Sancaka Karya Hutama" class="h-16 w-16 mr-4 flex-shrink-0 rounded-lg object-cover" onerror="this.style.display='none';">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">CV. Sancaka Karya Hutama</h2>
                            <div class="flex items-start text-xs text-gray-500 mt-1">
                                <i class="fas fa-map-marker-alt mr-2 mt-0.5"></i>
                                <span class="max-w-xs">JL.DR.WAHIDIN NO.18A RT.22 RW.05 KEL.KETANGGI KEC.NGAWI KAB.NGAWI JAWA TIMUR 63211</span>
                            </div>
                            <div class="flex items-center text-xs text-gray-500 mt-2">
                                <i class="fas fa-phone mr-2"></i>
                                <span>085745808809 / 08819435180</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-left sm:text-right w-full sm:w-auto flex-shrink-0">
                        <h1 class="text-2xl font-bold text-blue-600">INVOICE</h1>
                        <p class="font-semibold text-gray-700">#{{ $order->invoice_number }}</p>
                        <p class="text-sm text-gray-500">Tanggal: {{ $order->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row">
                
                {{-- Left Column: Order Details --}}
                <div class="w-full md:w-1/2 p-8">
                    @php
                        $isPureDigital = str_contains(strtolower($order->shipping_method), 'digital');
                    @endphp
                    
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            {{ $isPureDigital ? 'Informasi Penerima' : 'Detail Pengiriman' }}
                        </h2>
                        <div class="text-sm text-gray-800 space-y-1">
                            <p class="font-bold text-gray-900">{{ $order->user->nama_lengkap ?? ($order->receiver_name ?? 'Pelanggan') }}</p>
                            <p>{{ $order->user->no_wa ?? ($order->receiver_phone ?? '-') }}</p>
                            <p>{{ $order->shipping_address ?? $order->user->address_detail ?? 'Alamat tidak tersedia' }}</p>
                        </div>
                        
                        @if(in_array(strtolower($order->status), ['paid', 'processing', 'shipped', 'completed']))
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                @if($isPureDigital)
                                    <p class="text-sm text-gray-600">Sistem: <span class="font-bold text-green-600">Pengiriman Otomatis (E-Ticket)</span></p>
                                @else
                                    @php
                                        $kurirParts = explode('-', $order->shipping_method);
                                        $namaKurir = strtoupper(($kurirParts[1] ?? 'KURIR') . ' - ' . ($kurirParts[2] ?? ''));
                                    @endphp
                                    <p class="text-sm text-gray-600">Ekspedisi: <span class="font-bold text-gray-900">{{ $namaKurir }}</span></p>
                                    <div class="mt-1 flex items-center">
                                        <span class="text-sm text-gray-600 mr-2">Resi:</span>
                                        @php $nomorResi = $order->shipping_reference ?? $order->resi ?? null; @endphp
                                        @if(!empty($nomorResi) && $nomorResi !== '-' && $nomorResi !== 'Menunggu Penjual')
                                            <span class="px-2 py-1 bg-white border border-blue-200 text-blue-700 font-mono font-bold rounded text-xs select-all">{{ $nomorResi }}</span>
                                        @else
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded italic">Menunggu update kurir...</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <h3 class="text-base font-semibold text-gray-700 mb-2">Ringkasan Pesanan</h3>
                    <ul role="list" class="divide-y divide-gray-200 border-b border-t border-gray-200">
                        @foreach($order->items as $item)
                        @php
                            $katObj = $item->product ? $item->product->category()->first() : null;
                            $isItemDigital = ($katObj && in_array($katObj->category_group, ['produk_digital', 'jasa'])) || $isPureDigital;
                        @endphp
                        <li class="flex py-4 items-start">
                            <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                @if($item->product && $item->product->image_url)
                                    <img src="{{ asset('public/storage/'.$item->product->image_url) }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover object-center">
                                @else
                                    <img src="https://placehold.co/64x64/EFEFEF/333333?text=?" alt="Tidak ditemukan" class="h-full w-full object-cover object-center">
                                @endif
                            </div>
                            <div class="ml-4 flex flex-1 flex-col text-sm">
                                <h4 class="font-medium text-gray-800">{{ $item->product->name ?? 'Produk dihapus' }}</h4>
                                @if($item->variant) <p class="text-xs text-gray-500">Varian: {{ $item->variant->name }}</p> @endif
                                <div class="flex justify-between w-full mt-1">
                                    <p class="text-gray-500">Qty: {{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                                    <p class="font-medium text-gray-800">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</p>
                                </div>

                                {{-- 🔥 LOGIKA AKSES PRODUK DIGITAL (CERDAS PER ITEM) 🔥 --}}
                                @if($isItemDigital && in_array(strtolower($order->status), ['paid', 'processing', 'completed', 'shipped']))
                                    @php
                                        $aksesData = null;
                                        $aksesTipe = null;
                                        if (!empty($item->product->digital_url)) {
                                            $aksesData = $item->product->digital_url;
                                            $aksesTipe = 'url';
                                        } elseif (!empty($item->product->digital_file_path)) {
                                            $aksesData = asset('public/storage/' . $item->product->digital_file_path);
                                            $aksesTipe = 'file';
                                        } elseif (!empty($order->shipping_reference) && !str_contains($order->shipping_reference, 'Menunggu') && $isPureDigital) {
                                            $aksesData = $order->shipping_reference;
                                            $aksesTipe = filter_var($aksesData, FILTER_VALIDATE_URL) ? 'url' : 'text';
                                        }
                                    @endphp

                                    <div class="mt-3 w-full">
                                        @if($aksesData)
                                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                                <p class="text-xs font-bold text-green-800 mb-2"><i class="fas fa-key mr-1"></i> Akses Produk Digital:</p>
                                                @if($aksesTipe === 'url' || $aksesTipe === 'file')
                                                    <a href="{{ $aksesData }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-md shadow-sm transition">
                                                        <i class="fas {{ $aksesTipe === 'file' ? 'fa-download' : 'fa-external-link-alt' }} mr-2"></i> {{ $aksesTipe === 'file' ? 'Download File' : 'Buka Tautan Akses' }}
                                                    </a>
                                                @else
                                                    <code class="px-3 py-1.5 bg-white border border-green-300 text-green-800 font-mono text-sm rounded shadow-sm select-all">{{ $aksesData }}</code>
                                                @endif
                                            </div>
                                        @elseif($order->shipping_reference === 'Menunggu Penjual' || strtolower($order->status) === 'processing')
                                            <div class="p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                                                <p class="text-xs text-yellow-700 font-medium"><i class="fas fa-clock mr-1"></i> Menunggu penjual mengunggah akses.</p>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                {{-- 🔥 AKHIR LOGIKA DIGITAL 🔥 --}}
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    
                    {{-- === BAGIAN RINCIAN BIAYA === --}}
                    <div class="mt-6 space-y-2">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($order->shipping_cost > 0)
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Ongkos Kirim</span><span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($order->insurance_cost > 0)
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Asuransi</span><span>Rp {{ number_format($order->insurance_cost, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($order->cod_fee > 0)
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Biaya COD</span><span>Rp {{ number_format($order->cod_fee, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between text-lg font-bold text-gray-800 border-t border-gray-200 pt-4 mt-2">
                            <span>Total Pembayaran:</span><span class="text-blue-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Payment Instructions --}}
                <div class="w-full md:w-1/2 p-8 bg-gray-50 md:border-l border-t md:border-t-0 border-gray-200">
                     @php
                        $status = strtolower($order->status);
                        $badgeClass = match($status) {
                            'pending'    => 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                            'paid'       => 'bg-blue-100 text-blue-800 border border-blue-300',
                            'processing' => 'bg-blue-100 text-blue-800 border border-blue-300',
                            'shipped'    => 'bg-purple-100 text-purple-800 border border-purple-300',
                            'completed'  => 'bg-green-100 text-green-800 border border-green-300',
                            default      => 'bg-gray-100 text-gray-800 border border-gray-300'
                        };
                     @endphp
                     
                     <div class="text-center mb-6">
                        <p class="text-gray-600 mb-2">Status Pesanan:</p>
                        <span class="px-5 py-2 rounded-full text-sm font-bold uppercase tracking-wider {{ $badgeClass }}">
                            {{ $status === 'processing' ? 'DIPROSES' : $status }}
                        </span>
                     </div>

                     @if($status === 'pending')
                        <div class="h-full flex flex-col justify-start mt-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4 text-center">Instruksi Pembayaran</h2>
                            @php
                                $method = strtoupper($order->payment_method);
                                $url    = $order->payment_url;
                            @endphp
                            
                            <div class="text-center">
                                @if (str_contains($method, 'QRIS'))
                                    <p class="text-gray-600 mb-4">Scan QR di bawah ini:</p>
                                    <div class="flex justify-center p-2 bg-white rounded-lg shadow-inner mb-3">
                                        <img src="{{ $url }}" alt="QRIS Payment" class="w-48 h-48 rounded-md">
                                    </div>
                                @elseif (in_array($method, ['DANA', 'OVO']) || str_contains($method, 'DOKU_JOKUL'))
                                    <p class="text-gray-600 mb-4">Lanjutkan ke aplikasi e-Wallet:</p>
                                    <a href="{{ $url }}" target="_blank" class="inline-block w-full">
                                        <button class="w-full px-8 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transition">Bayar Sekarang</button>
                                    </a>
                                @elseif(!empty($order->pay_code))
                                    <p class="text-gray-600 mb-2">Nomor Kode Bayar / VA:</p>
                                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                        <strong class="text-2xl font-mono tracking-widest text-blue-700">{{ $order->pay_code }}</strong>
                                    </div>
                                @else
                                    <a href="{{ $url }}" target="_blank" class="inline-block w-full">
                                        <button class="w-full px-8 py-3 bg-red-600 text-white font-bold rounded-lg shadow-md hover:bg-red-700 transition">Bayar Sekarang</button>
                                    </a>
                                @endif
                            </div>
                        </div>
                     @else
                        <div class="h-full flex flex-col justify-center items-center text-center mt-10">
                           <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4 text-4xl">
                               <i class="fas fa-check-circle"></i>
                           </div>
                           <h2 class="text-xl font-bold text-gray-800">Pembayaran Diterima!</h2>
                           <p class="text-gray-600 mt-2">Terima kasih, pesanan Anda sedang kami proses.</p>
                           <a href="{{ url('invoice/' . $order->invoice_number . '/pdf') }}" target="_blank" class="mt-6 w-full px-6 py-3 bg-white border-2 border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm hover:bg-gray-50 transition flex items-center justify-center">
                               <i class="fas fa-file-pdf text-red-500 mr-2"></i> Download Invoice (PDF)
                           </a>
                        </div>
                     @endif
                </div>
            </div>
        </div>
    </div>
@endsection