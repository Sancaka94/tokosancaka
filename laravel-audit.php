<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Controller</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { font-size: 24px; margin-bottom: 20px; }
        ul { list-style: none; padding: 0; }
        li { padding: 8px 12px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Daftar Semua Controller</h1>
    <ul>
        @foreach($controllers as $controller)
            <li>{{ $controller }}</li>
        @endforeach
    </ul>
</body>
</html>
