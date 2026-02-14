<table>
    <thead>
        <tr>
            <th colspan="8" style="font-weight: bold; font-size: 14px; text-align: center; height: 30px;">
                DATA PESERTA SEMINAR SANCAKA
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #d9edf7; border: 1px solid #000000; width: 5px; text-align: center;">No</th>
            <th style="font-weight: bold; background-color: #d9edf7; border: 1px solid #000000; width: 20px;">No. Tiket</th>
            <th style="font-weight: bold; background-color: #d9edf7; border: 1px solid #000000; width: 30px;">Nama Lengkap</th>
            <th style="font-weight: bold; background-color: #d9edf7; border: 1px solid #000000; width: 25px;">Email</th>
            <th style="font-weight: bold; background-color: #d9edf7; border: 1px solid #000000; width: 15px;">No. WA</th>
            <th style="font-weight: bold; background-color: #d9edf7; border: 1px solid #000000; width: 20px;">Instansi</th>
            <th style="font-weight: bold; background-color: #ffffcc; border: 1px solid #000000; width: 10px; text-align: center;">Status NIB</th>
            <th style="font-weight: bold; background-color: #dff0d8; border: 1px solid #000000; width: 20px; text-align: center;">Waktu Kehadiran</th>
        </tr>
    </thead>
    <tbody>
        @foreach($participants as $index => $p)
        <tr>
            <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
            <td style="border: 1px solid #000000;">{{ $p->ticket_number }}</td>
            <td style="border: 1px solid #000000;">{{ $p->nama }}</td>
            <td style="border: 1px solid #000000;">{{ $p->email }}</td>
            <td style="border: 1px solid #000000;">'{{ $p->no_wa }}</td> {{-- Tanda petik agar Excel membacanya sebagai teks, bukan angka ilmiah --}}
            <td style="border: 1px solid #000000;">{{ $p->instansi ?? '-' }}</td>
            <td style="border: 1px solid #000000; text-align: center;">{{ $p->nib_status }}</td>
            <td style="border: 1px solid #000000; text-align: center;">
                {{ $p->is_checked_in ? $p->check_in_at->format('d-m-Y H:i') : 'Belum Hadir' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
