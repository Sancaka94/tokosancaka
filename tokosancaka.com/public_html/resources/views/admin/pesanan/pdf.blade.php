<!DOCTYPE html>
<html>
<head>
    <title>Data Pesanan</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Data Pesanan</h1>
    <table>
        <thead>
            <tr>
                <th>Resi</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Pengirim</th>
                <th>Penerima</th>
                <th>Ekspedisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
            <tr>
                <td>{{ $order->resi }}</td>
                <td>{{ $order->created_at->format('d M Y') }}</td>
                <td>{{ $order->status }}</td>
                <td>{{ $order->sender_name }}</td>
                <td>{{ $order->receiver_name }}</td>
                <td>{{ $order->expedition }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
