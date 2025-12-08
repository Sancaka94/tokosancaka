{{-- resources/views/admin/logs/viewer.blade.php --}}
@extends('layouts.admin') {{-- Ganti dengan layout admin Anda --}}

@section('title', 'Raw Log Viewer')

@section('content')
<h1 class="text-2xl font-bold mb-4">📂 Isi Log ({{ $maxLines }} Baris Terakhir)</h1>

<div class="bg-gray-100 border border-gray-300 p-4 rounded-lg">
    {{-- 
        Menggunakan tag <pre> untuk mempertahankan format baris/spasi/tab asli. 
        Menggunakan {!! e($logs) !!} untuk menampilkan string sebagai raw text
        (e() memastikan output di-escape agar aman dari XSS, tapi nl2br dihilangkan sesuai permintaan).
    --}}
    <pre class="overflow-x-scroll" style="font-family: monospace; font-size: 13px;">{!! e($logs) !!}</pre>
</div>

@endsection