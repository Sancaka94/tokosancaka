<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Kota</title>
    <style>
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 12px; 
            color: #333;
        }
        h2 { text-align: center; margin-bottom: 5px; color: #000; }
        .subtitle { text-align: center; color: #666; margin-top: 0; margin-bottom: 20px; font-size: 10px; }
        
        /* Grid Layout menggunakan Tabel untuk kompatibilitas DOMPDF */
        .grid-container { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-bottom: 20px; }
        .grid-container td { 
            width: 50%; 
            vertical-align: top; 
            border: 1px solid #ddd; 
            padding: 15px; 
            border-radius: 5px;
            background-color: #fafafa;
        }
        
        /* Typography */
        .stat-label { font-size: 10px; color: #777; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;}
        .stat-value { font-size: 24px; font-weight: bold; color: #000; margin: 0; }
        .section-title { font-size: 14px; font-weight: bold; color: #000; border-bottom: 2px solid #000; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; }
        
        /* Tabel Data */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background-color: #f2f2f2; color: #000; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        /* CSS Bar Chart */
        .bar-container {
            width: 100%;
            background-color: #f3f4f6;
            border-radius: 3px;
            overflow: hidden;
            height: 12px;
            margin-top: 4px;
        }
        .bar-fill {
            height: 100%;
            background-color: #000; /* Hitam agar match dengan desain minimalis */
        }
    </style>
</head>
<body>

    <h2>LAPORAN ANALITIK DASHBOARD</h2>
    <p class="subtitle">Dicetak pada: {{ date('d/m/Y H:i') }}</p>

    <!-- KOTAK STATISTIK (SUMMARY CARDS) -->
    <table class="grid-container">
        <tr>
            <!-- Card 1 -->
            <td>
                <div class="stat-label">Total Input Transaksi Keseluruhan</div>
                <div class="stat-value">{{ number_format($totalTransaksi ?? 0, 0, ',', '.') }}</div>
            </td>
            <!-- Card 2 -->
            <td>
                <div class="stat-label">Total Frekuensi Master Kota</div>
                <div class="stat-value">{{ number_format($totalData ?? 0, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <!-- MENGHITUNG NILAI MAKSIMAL UNTUK LEBAR GRAFIK BAR TRANSAKSI -->
    @php 
        $maxTransaksi = $chartDataTransaksi->max('total_jumlah'); 
        $maxTransaksi = $maxTransaksi > 0 ? $maxTransaksi : 1; // Hindari pembagian dengan 0
    @endphp

    <!-- SECTION 1: STATISTIK TRANSAKSI -->
    <div class="section-title">Statistik Data Input (Berdasarkan Jumlah)</div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="30%">Nama Kota / Wilayah</th>
                <th width="15%" class="text-center">Jumlah</th>
                <th width="50%">Grafik Distribusi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chartDataTransaksi as $index => $trx)
                @php 
                    $percentage = ($trx->total_jumlah / $maxTransaksi) * 100; 
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $trx->nama_kota ?? '-' }}</td>
                    <td class="text-center"><strong>{{ number_format($trx->total_jumlah, 0, ',', '.') }}</strong></td>
                    <td>
                        <!-- CSS Bar Chart -->
                        <div style="font-size: 9px; color: #666; text-align: right; margin-bottom: 2px;">{{ number_format($percentage, 1) }}%</div>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: {{ $percentage }}%;"></div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    <!-- MENGHITUNG NILAI MAKSIMAL UNTUK LEBAR GRAFIK BAR MASTER -->
    @php 
        $maxMaster = $chartData->max('total'); 
        $maxMaster = $maxMaster > 0 ? $maxMaster : 1; 
    @endphp

    <!-- SECTION 2: STATISTIK MASTER KOTA -->
    <div class="section-title">Statistik Master Data Kota (Frekuensi)</div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="30%">Nama Kota / Wilayah</th>
                <th width="15%" class="text-center">Frekuensi</th>
                <th width="50%">Grafik Distribusi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chartData as $index => $data)
                @php 
                    $percentageMaster = ($data->total / $maxMaster) * 100; 
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $data->nama_kota ?? '-' }}</td>
                    <td class="text-center"><strong>{{ $data->total }}</strong></td>
                    <td>
                        <!-- CSS Bar Chart -->
                        <div style="font-size: 9px; color: #666; text-align: right; margin-bottom: 2px;">{{ number_format($percentageMaster, 1) }}%</div>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: {{ $percentageMaster }}%; background-color: #4b5563;"></div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>