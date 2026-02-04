<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Semua Controller</title>
</head>
<body>
    <h1>Daftar Semua Controller</h1>
    <ul>
        @forelse($controllers as $controller)
            <li>{{ $controller }}</li>
        @empty
            <li><em>Tidak ada controller ditemukan.</em></li>
        @endforelse
    </ul>
</body>
</html>
