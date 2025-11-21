@extends('layouts.customer')

@section('title', 'Keranjang Belanja Anda')

@section('content')
<div class="container mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Keranjang Belanja Anda</h1>

    {{-- ========================================================== --}}
{{-- AWAL BLOK NOTIFIKASI LENGKAP --}}
{{-- ========================================================== --}}
<div id="notification-container" class="mb-4">

    {{-- 1. Pesan SUKSES (dari session 'success') --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
            <p><strong>Sukses!</strong> {{ session('success') }}</p>
        </div>
    @endif

    {{-- 2. Pesan ERROR (dari session 'error') --}}
    {{-- Ini yang paling penting untuk Anda! --}}
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
            <p><strong>Gagal!</strong> {{ session('error') }}</p>
        </div>
    @endif

    {{-- 3. Pesan PERINGATAN (dari session 'warning') --}}
    @if(session('warning'))
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
            <p><strong>Peringatan:</strong> {{ session('warning') }}</p>
        </div>
    @endif

    {{-- 4. Pesan INFO (dari session 'info') --}}
    @if(session('info'))
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md" role="alert">
            <p><strong>Info:</strong> {{ session('info') }}</p>
        </div>
    @endif

    {{-- 5. SEMUA Error Validasi (dari $errors) --}}
    {{-- Ini akan menampilkan error jika validasi form gagal --}}
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
            <strong class="font-bold">Terjadi Kesalahan:</strong>
            <ul class="list-disc list-inside mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Placeholder untuk notifikasi dari JavaScript (seperti yang Anda perbaiki sebelumnya) --}}
    {{-- Posisinya sudah benar di dalam container --}}
</div>
{{-- ========================================================== --}}
{{-- AKHIR BLOK NOTIFIKASI LENGKAP --}}
{{-- ========================================================== --}}
    
    {{-- Container utama untuk isi keranjang, agar bisa diganti dengan pesan kosong via JS --}}
    <div id="cart-content">
        @if($cart && count($cart) > 0)
            <div class="flex flex-col lg:flex-row gap-8">
                
                <div class="w-full lg:w-2/3">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="p-4 font-semibold text-sm text-gray-600 uppercase">Produk</th>
                                    <th class="p-4 font-semibold text-sm text-gray-600 uppercase">Harga</th>
                                    <th class="p-4 font-semibold text-sm text-gray-600 uppercase text-center">Kuantitas</th>
                                    <th class="p-4 font-semibold text-sm text-gray-600 uppercase">Subtotal</th>
                                    <th class="p-4 font-semibold text-sm text-gray-600 uppercase"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $total = 0 @endphp
                                @foreach($cart as $id => $details)
                                    @php $total += $details['price'] * $details['quantity'] @endphp
                                    <tr class="border-b transition-opacity duration-300" id="row-{{ $id }}">
                                        <td class="p-4 flex items-center gap-4">
                                            
                                            {{-- ========================================================== --}}
                                            {{-- PERBAIKAN 1: Menghapus 'public/' dari path gambar --}}
                                            {{-- ========================================================== --}}
                                            <img src="{{ $details['image_url'] ? asset('public/storage/' . $details['image_url']) : 'https://placehold.co/80x80/e2e8f0/94a3b8?text=Produk' }}" 
                                                 alt="{{ $details['name'] }}" class="w-16 h-16 object-cover rounded-md">
                                            
                                            <div>
                                                <p class="font-semibold text-gray-800">{{ $details['name'] }}</p>
                                            </div>
                                        </td>
                                        <td class="p-4 text-gray-700">Rp{{ number_format($details['price']) }}</td>
                                        <td class="p-4">
                                            <div class="flex items-center justify-center">
                                                <button class="px-2 py-1 border rounded-l-md hover:bg-gray-100 quantity-change" data-id="{{ $id }}" data-action="minus">-</button>
                                                <input type="number" value="{{ $details['quantity'] }}" min="1" 
                                                       class="w-16 text-center border-t border-b p-1 quantity-input" 
                                                       data-id="{{ $id }}">
                                                <button class="px-2 py-1 border rounded-r-md hover:bg-gray-100 quantity-change" data-id="{{ $id }}" data-action="plus">+</button>
                                            </div>
                                        </td>
                                        <td class="p-4 text-gray-800 font-semibold subtotal" id="subtotal-{{ $id }}">
                                            Rp{{ number_format($details['price'] * $details['quantity']) }}
                                        </td>
                                        <td class="p-4">
                                            <button class="text-red-500 hover:text-red-700 remove-from-cart" data-id="{{ $id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="w-full lg:w-1/3">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Ringkasan Pesanan</h2>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-semibold text-gray-800" id="cart-subtotal">Rp{{ number_format($total) }}</span>
                        </div>
                        <div class="flex justify-between mb-4">
                            <span class="text-gray-600">Ongkos Kirim</span>
                            <span class="font-semibold text-gray-800">Rp0</span>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-800">Total</span>
                            <span class="text-xl font-bold text-red-600" id="cart-total">Rp{{ number_format($total) }}</span>
                        </div>
                        <a href="{{ route('customer.checkout.index') }}"
                            class="block w-full mt-6 bg-red-600 text-white font-bold py-3 rounded-lg text-center hover:bg-red-700 transition-colors">
                                Lanjutkan ke Checkout
                        </a>
                        <a href="{{ route('katalog.index') }}"
                            class="block w-full mt-4 bg-blue-600 text-white font-bold py-3 rounded-lg text-center hover:bg-blue-700 transition-colors">
                                Lanjutkan Belanja
                        </a>
                    </div>
                </div>

            </div>
        @else
            <div class="text-center py-20 bg-white rounded-lg shadow-md">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">Keranjang Anda Kosong</h2>
                <p class="text-gray-500 mb-6">Sepertinya Anda belum menambahkan produk apapun ke keranjang.</p>
                <a href="{{ route('katalog.index') }}" class="bg-red-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-red-700 transition-colors">
                    Mulai Belanja
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('tbody tr').forEach(row => {
            const priceText = row.querySelector('td:nth-child(2)').innerText.replace(/[^0-9]/g, '');
            const quantity = row.querySelector('.quantity-input').value;
            const price = parseFloat(priceText);
            
            if (!isNaN(price) && quantity > 0) {
                const subtotal = price * quantity;
                row.querySelector('.subtotal').innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(subtotal);
                total += subtotal;
            }
        });

        const formattedTotal = 'Rp' + new Intl.NumberFormat('id-ID').format(total);
        const subtotalEl = document.getElementById('cart-subtotal');
        const totalEl = document.getElementById('cart-total');
        
        if (subtotalEl) subtotalEl.innerText = formattedTotal;
        if (totalEl) totalEl.innerText = formattedTotal;
    }

    function showNotification(message, type = 'error') {
        const container = document.getElementById('notification-container');
        letbgColor = 'bg-red-100 border-red-500 text-red-700'; // error
        if (type === 'success') {
            bgColor = 'bg-green-100 border-green-500 text-green-700';
        }

        const div = document.createElement('div');
        div.className = `bg-opacity-90 border-l-4 p-4 mb-4 rounded-md ${bgColor}`;
        div.setAttribute('role', 'alert');
        div.innerText = message;
        
        container.innerHTML = ''; // Hapus notifikasi lama
        container.appendChild(div);
    }

    function updateCartOnServer(id, quantity) {
        const row = document.getElementById('row-' + id);
        if (row) row.style.opacity = '0.5';
        
        const body = new URLSearchParams();
        body.append('_method', 'PATCH');
        body.append('id', id);
        body.append('quantity', quantity);

        {{-- ========================================================== --}}
        {{-- PERBAIKAN 2: Error handling pada FETCH --}}
        {{-- ========================================================== --}}
        fetch("{{ route('customer.cart.update') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(response => {
            // Cek jika sesi berakhir
            if (response.status === 401 || response.status === 419) {
                throw new Error('Sesi berakhir, silakan muat ulang halaman.');
            }
            // Cek jika TIDAK ok (cth: 422, 500)
            if (!response.ok) {
                // Coba baca JSON error dari Laravel
                return response.json().then(err => { 
                    // Lempar error dengan pesan dari server
                    throw new Error(err.message || 'Error tidak diketahui dari server.'); 
                });
            }
            // Jika OK, lanjutkan
            return response.json();
        })
        .then(data => {
            // Sukses (opsional: tampilkan notif sukses)
            // console.log(data.message); 
        })
        .catch(error => {
            console.error('Fetch Error:', error.message);
            // Tampilkan error ASLI ke user
            showNotification(error.message, 'error');
            
            // Hanya reload jika errornya adalah sesi berakhir
            if (error.message.includes('Sesi berakhir')) {
                setTimeout(() => window.location.reload(), 2000);
            } else {
                // Jika error lain (cth: stok habis), kembalikan kuantitas ke nilai lama (jika ada)
                // atau setidaknya reload halaman untuk data yang konsisten
                // Untuk sekarang, kita log saja dan biarkan opacity kembali
            }
        })
        .finally(() => {
            if (row) row.style.opacity = '1';
        });
    }

    // Event listener untuk input manual kuantitas
    document.querySelectorAll('.quantity-input').forEach(input => {
        let debounceTimer;
        input.addEventListener('input', function() {
            updateTotal();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (this.value > 0) updateCartOnServer(this.dataset.id, this.value);
            }, 800);
        });
    });
    
    // Event listener untuk tombol +/-
    document.querySelectorAll('.quantity-change').forEach(button => {
        button.addEventListener('click', function(){
            const id = this.dataset.id;
            const input = document.querySelector(`.quantity-input[data-id="${id}"]`);
            let currentValue = parseInt(input.value);

            if(this.dataset.action === 'plus') {
                input.value = currentValue + 1;
            } else if (currentValue > 1) {
                input.value = currentValue - 1;
            }
            updateTotal();
            // Memicu event 'input' akan menjalankan debouncer
            input.dispatchEvent(new Event('input', { bubbles: true })); 
        });
    });

    // Event listener untuk hapus item
    document.querySelectorAll('.remove-from-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if(!confirm('Anda yakin ingin menghapus item ini?')) return;
            
            const id = this.dataset.id;
            const row = document.getElementById('row-' + id);
            
            const body = new URLSearchParams();
            body.append('_method', 'DELETE');
            body.append('id', id);

            {{-- ========================================================== --}}
            {{-- PERBAIKAN 2: Error handling pada FETCH (Hapus) --}}
            {{-- ========================================================== --}}
            fetch("{{ route('customer.cart.remove') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(response => {
                if (response.status === 401 || response.status === 419) {
                    throw new Error('Sesi berakhir, silakan muat ulang halaman.');
                }
                if (!response.ok) {
                    return response.json().then(err => { 
                        throw new Error(err.message || 'Error tidak diketahui dari server.'); 
                    });
                }
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    showNotification(data.message || 'Item berhasil dihapus.', 'success');
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        updateTotal();
                        // Logika cerdas Anda untuk mengosongkan keranjang tanpa reload
                        if(document.querySelectorAll('tbody tr').length === 0) {
                            document.getElementById('cart-content').innerHTML = `
                                <div class="text-center py-20 bg-white rounded-lg shadow-md">
                                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                                    <h2 class="text-2xl font-bold text-gray-700 mb-2">Keranjang Anda Kosong</h2>
                                    <p class="text-gray-500 mb-6">Semua item telah dihapus.</p>
                                    <a href="{{ route('katalog.index') }}" class="bg-red-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-red-700 transition-colors">
                                        Mulai Belanja
                                    </a>
                                </div>
                            `;
                        }
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error.message);
                // Tampilkan error ASLI ke user
                showNotification(error.message, 'error');
                
                if (error.message.includes('Sesi berakhir')) {
                    setTimeout(() => window.location.reload(), 2000);
                }
            });
        });
    });
});
</script>
@endpush