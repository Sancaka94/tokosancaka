<!DOCTYPE html>
<html>
<head>
    <title>Data Kontak</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Data Kontak</h1>
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>No. HP</th>
                <th>Alamat</th>
                <th>Tipe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kontaks as $kontak)
            <tr>
                <td>{{ $kontak->nama }}</td>
                <td>{{ $kontak->no_hp }}</td>
                <td>{{ $kontak->alamat }}</td>
                <td>{{ $kontak->tipe }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
