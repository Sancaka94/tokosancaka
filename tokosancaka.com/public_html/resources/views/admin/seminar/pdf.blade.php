<!DOCTYPE html>
<html>
<head>
    <title>Laporan Peserta Seminar</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; padding: 0; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #555; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 6px 8px; text-align: left; vertical-align: middle; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }

        .text-center { text-align: center; }
        .badge-hadir { color: green; font-weight: bold; }
        .badge-absen { color: red; font-style: italic; }
        .ticket { font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Data Peserta Seminar</h2>
        <p>CV. Sancaka Karya Hutama</p>
        <p>Dicetak pada: {{ date('d F Y, H:i') }} WIB</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">No. Tiket</th>
                <th style="width: 20%">Nama Peserta</th>
                <th style="width: 20%">Instansi</th>
                <th style="width: 15%">No. WA</th>
                <th style="width: 10%">NIB</th>
                <th style="width: 15%">Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($participants as $index => $p)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="ticket">{{ $p->ticket_number }}</td>
                <td>
                    <strong>{{ $p->nama }}</strong><br>
                    <span style="color: #666; font-size: 10px;">{{ $p->email }}</span>
                </td>
                <td>{{ $p->instansi ?? '-' }}</td>
                <td>{{ $p->no_wa }}</td>
                <td class="text-center">
                    {{ $p->nib_status }}
                </td>
                <td class="text-center">
                    @if($p->is_checked_in)
                        <span class="badge-hadir">Hadir</span><br>
                        <small>({{ $p->check_in_at->format('H:i') }})</small>
                    @else
                        <span class="badge-absen">Belum</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: right; font-size: 10px; color: #777;">
        Total Peserta: {{ count($participants) }} Data
    </div>
</body>
</html>
