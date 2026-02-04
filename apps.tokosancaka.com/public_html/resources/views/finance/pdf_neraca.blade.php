<!DOCTYPE html>
<html>
<head><title>Neraca</title><style>body{font-family:sans-serif}table{width:100%;border-collapse:collapse}th,td{padding:5px;border-bottom:1px solid #ddd}.num{text-align:right}.head{background:#eee;font-weight:bold}</style></head>
<body>
    <h2>Laporan Neraca</h2>
    <p>Per Tanggal: {{ date('d M Y', strtotime($asOfDate)) }}</p>

    <h3 class="head">AKTIVA (ASET)</h3>
    <table>
        @foreach($assets as $a) <tr><td>{{$a->name}}</td><td class="num">{{number_format($a->balance)}}</td></tr> @endforeach
        <tr><td><b>TOTAL ASET</b></td><td class="num"><b>{{number_format($totalAsset)}}</b></td></tr>
    </table>

    <h3 class="head">PASIVA (KEWAJIBAN & MODAL)</h3>
    <table>
        <tr><td colspan="2"><b>KEWAJIBAN</b></td></tr>
        @foreach($liabilities as $l) <tr><td>{{$l->name}}</td><td class="num">{{number_format($l->balance)}}</td></tr> @endforeach
        <tr><td><b>MODAL</b></td></tr>
        @foreach($equities as $e) <tr><td>{{$e->name}}</td><td class="num">{{number_format($e->balance)}}</td></tr> @endforeach
        <tr><td>Laba Berjalan</td><td class="num">{{number_format($labaBerjalan)}}</td></tr>
        <tr><td><b>TOTAL PASIVA</b></td><td class="num"><b>{{number_format($totalLiability + $totalEquity)}}</b></td></tr>
    </table>
</body>
</html>
