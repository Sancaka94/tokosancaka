@extends('layouts.customer')

@section('title', 'Keranjang Belanja Anda')

@section('content')
<div class="container mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Keranjang Belanja Anda</h1>

    {{-- Container untuk notifikasi --}}
    <div id="notification-container">
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md" role="alert">
                {{ session('success') }}
            </div>
        @endif
    </div>
    
    {{-- Container utama untuk isi keranjang, agar bisa diganti dengan pesan kosong via JS --}}
    <div id="cart-content">
        @if($cart && count($cart) > 0)
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Daftar Item Keranjang -->
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
                                            <img src="{{ $details['image_url'] ? asset('storage/' . $details['image_url']) : 'https://placehold.co/80x80/e2e8f0/94a3b8?text=Produk' }}" alt="{{ $details['name'] }}" class="w-16 h-16 object-cover rounded-md">
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

                <!-- Ringkasan Pesanan -->
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
                <a href="{{ route('katalog.index') }}" class="bg-indigo-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-indigo-700 transition-colors">
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
        document.getElementById('cart-subtotal').innerText = formattedTotal;
        document.getElementById('cart-total').innerText = formattedTotal;
    }

    function updateCartOnServer(id, quantity) {
        const row = document.getElementById('row-' + id);
        if (row) row.style.opacity = '0.5';
        
        const body = new URLSearchParams();
        body.append('_method', 'PATCH');
        body.append('id', id);
        body.append('quantity', quantity);

        fetch("{{ route('customer.cart.update') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(response => {
            if (response.ok && response.headers.get("content-type")?.includes("application/json")) {
                return response.json();
            }
            throw new Error("Sesi berakhir");
        })
        .catch(error => {
            console.error('Fetch Error:', error.message);
            if (error.message === "Sesi berakhir") {
                window.location.reload();
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

            fetch("{{ route('customer.cart.remove') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(response => {
                if (response.ok && response.headers.get("content-type")?.includes("application/json")) {
                    return response.json();
                }
                throw new Error("Sesi berakhir");
            })
            .then(data => {
                if(data.success) {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        updateTotal();
                        // PERBAIKAN: Ganti isi container jika keranjang kosong, tanpa reload.
                        if(document.querySelectorAll('tbody tr').length === 0) {
                            document.getElementById('cart-content').innerHTML = `
                                <div class="text-center py-20 bg-white rounded-lg shadow-md">
                                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                                    <h2 class="text-2xl font-bold text-gray-700 mb-2">Keranjang Anda Kosong</h2>
                                    <p class="text-gray-500 mb-6">Semua item telah dihapus.</p>
                                    <a href="{{ route('katalog.index') }}" class="bg-indigo-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-indigo-700 transition-colors">
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
                if (error.message === "Sesi berakhir") {
                    window.location.reload();
                }
            });
        });
    });
});
</script>
@endpush

