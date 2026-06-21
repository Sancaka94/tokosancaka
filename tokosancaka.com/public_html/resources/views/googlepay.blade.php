<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Express - Full Payment Gateway</title>
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; padding: 40px 20px; }
        .checkout-card { max-width: 480px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .checkout-header { text-align: center; margin-bottom: 25px; }
        .checkout-header h2 { margin: 0; color: #1a202c; font-size: 22px; }
        .order-summary { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .order-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; color: #1e293b; }
        .payment-methods { display: flex; flex-direction: column; gap: 15px; }
        #google-pay-button-container { min-height: 40px; margin-bottom: 10px; }
        #paypal-button-container { min-height: 40px; }
    </style>
</head>
<body>

<div class="checkout-card">
    <div class="checkout-header">
        <h2>Metode Pembayaran</h2>
        <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Selesaikan transaksi Anda dengan aman</p>
    </div>

    <div class="order-summary">
        <div class="order-row">
            <span>Total Tagihan:</span>
            <span>{{ $transaction['currency'] }} {{ $transaction['amount'] }}</span>
        </div>
    </div>

    <div class="payment-methods">
        <div id="google-pay-button-container"></div>
        
        <div id="paypal-button-container"></div>
    </div>
</div>

<script>
    window.AppConfig = {
        amount: "{{ $transaction['amount'] }}",
        currency: "{{ $transaction['currency'] }}",
        countryCode: "{{ $transaction['country_code'] }}",
        googlePayEnv: "{{ $mode === 'production' ? 'PRODUCTION' : 'TEST' }}"
    };
    console.log("LOG LOG: Config terpasang dinamis. Mode:", "{{ strtoupper($mode) }}");
</script>

@php
    $paypalDomain = $mode === 'production' ? 'www.paypal.com' : 'www.sandbox.paypal.com';
@endphp
<script src="https://{{ $paypalDomain }}/sdk/js?client-id={{ $paypalClientId }}&currency={{ $transaction['currency'] }}&components=googlepay,buttons"></script>

<script src="{{ asset('js/googlepay-app.js') }}?v={{ time() }}"></script>

<script async src="https://pay.google.com/gp/p/js/pay.js" onload="onGooglePayLoaded()"></script>

</body>
</html>