<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Express - Dynamic Gateway</title>
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; padding: 40px 20px; }
        .checkout-card { max-width: 480px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .checkout-header { text-align: center; margin-bottom: 25px; }
        .checkout-header h2 { margin: 0; color: #1a202c; font-size: 22px; }
        .order-summary { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .order-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; color: #1e293b; }
        .payment-methods { display: flex; flex-direction: column; gap: 15px; }
        #google-pay-button-container { min-height: 40px; }
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
        
        <paypal-button id="standard-paypal-btn" hidden></paypal-button>
    </div>
</div>

<script src="https://pay.google.com/gp/p/js/pay.js"></script>

@if($mode === 'production')
    <script src="https://www.paypal.com/web-sdk/v6/core"></script>
@else
    <script src="https://www.sandbox.paypal.com/web-sdk/v6/core"></script>
@endif

<script>
    window.AppConfig = {
        paypalClientId: "{{ $paypalClientId }}",
        googlePayEnv: "{{ $mode === 'production' ? 'PRODUCTION' : 'TEST' }}",
        amount: "{{ $transaction['amount'] }}",
        currency: "{{ $transaction['currency'] }}",
        countryCode: "{{ $transaction['country_code'] }}"
    };
    console.log("LOG LOG: Config terpasang dinamis. Mode saat ini:", "{{ strtoupper($mode) }}");
</script>

<script src="{{ asset('js/googlepay-app.js') }}"></script>

</body>
</html>