<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ringkasan Checkout</title>
    {{-- Style Anda --}}
</head>
<body>
    <h1>Ringkasan Pesanan #{{ $order->invoice_number }}</h1>
    <p>Total yang harus dibayar: Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
    
    <p>Pilih Metode Pembayaran:</p>

    {{-- Form untuk pembayaran dengan DANA --}}
    <form action="{{ route('dana.payment.create', ['order' => $order->id]) }}" method="POST">
        @csrf
        <button type="submit">Bayar dengan DANA</button>
    </form>

    {{-- Tombol untuk metode pembayaran lain --}}
    {{-- <button>Bayar dengan Metode Lain</button> --}}
</body>
</html>