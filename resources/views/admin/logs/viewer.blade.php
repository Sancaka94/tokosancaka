{{-- resources/views/admin/logs/viewer.blade.php --}}

{{-- Pastikan Anda mengganti 'layouts.admin' dengan nama layout admin Anda yang benar --}}
@extends('layouts.admin') 

@section('title', 'Raw Log Viewer')

@section('content')
{{-- PERBAIKAN: Menggunakan variabel $maxLines yang dikirim dari Controller --}}
<h1 class="text-2xl font-bold mb-4">📂 Isi Log ({{ $maxLines }} Baris Terakhir)</h1>

<div class="bg-gray-100 border border-gray-300 p-4 rounded-lg">
    {{-- 
        Menggunakan tag <pre> untuk mempertahankan format baris/spasi/tab asli. 
        Menggunakan {!! e($logs) !!} untuk menampilkan string mentah (raw text) 
        yang sudah di-escape (aman dari XSS) tetapi tetap mempertahankan baris baru.
    --}}
    <pre class="overflow-x-scroll" style="font-family: monospace; font-size: 13px;">{!! e($logs) !!}</pre>
</div>

@endsection