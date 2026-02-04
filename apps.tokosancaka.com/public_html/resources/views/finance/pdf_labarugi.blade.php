<!DOCTYPE html>
<html>
<head><title>Laba Rugi</title><style>body{font-family:sans-serif}table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #ddd}th{text-align:left}.num{text-align:right}.total{font-weight:bold;background:#f0f0f0}</style></head>
<body>
    <h2>Laporan Laba Rugi</h2>
    <p>Periode: {{ $month }} / {{ $year }}</p>
    <hr>
    <h3>PENDAPATAN</h3>
    <table>
        @foreach($revenues as $r) <tr><td>{{$r->name}}</td><td class="num">{{number_format($r->balance)}}</td></tr> @endforeach
        <tr class="total"><td>TOTAL PENDAPATAN</td><td class="num">{{number_format($totalRevenue)}}</td></tr>
    </table>
    <h3>BEBAN</h3>
    <table>
        @foreach($expenses as $e) <tr><td>{{$e->name}}</td><td class="num">{{number_format($e->balance)}}</td></tr> @endforeach
        <tr class="total"><td>TOTAL BEBAN</td><td class="num">{{number_format($totalExpense)}}</td></tr>
    </table>
    <h3 style="text-align:right; margin-top:20px">LABA BERSIH: Rp {{ number_format($netIncome) }}</h3>
</body>
</html>
