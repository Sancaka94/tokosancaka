<table>
    <thead>
        <tr>
            <th colspan="5" style="font-weight: bold; text-align: center;">LAPORAN KEUANGAN KAS MANUAL</th>
        </tr>
        <tr>
            <th colspan="5" style="text-align: center;">Dicetak pada: {{ date('d/m/Y H:i') }}</th>
        </tr>
        <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Keterangan</th>
            <th>Pemasukan</th>
            <th>Pengeluaran</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reports as $row)
        <tr>
            <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('Y-m-d') }}</td>
            <td>{{ $row->kategori }}</td>
            <td>{{ $row->keterangan ?? '-' }}</td>
            <td>{{ $row->jenis == 'pemasukan' ? $row->nominal : 0 }}</td>
            <td>{{ $row->jenis == 'pengeluaran' ? $row->nominal : 0 }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: right;">TOTAL</td>
            <td style="font-weight: bold;">{{ $totalPemasukan }}</td>
            <td style="font-weight: bold;">{{ $totalPengeluaran }}</td>
        </tr>
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: right;">SALDO AKHIR</td>
            <td colspan="2" style="font-weight: bold; text-align: center;">{{ $saldo }}</td>
        </tr>
    </tbody>
</table>
