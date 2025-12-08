@extends('layouts.admin')

@section('content')
<h1 class="text-xl font-bold mb-4">Log Viewer (500 Baris Terakhir)</h1>

<pre class="bg-gray-800 text-green-300 p-4 rounded-lg overflow-x-scroll whitespace-pre-wrap" style="max-height: 80vh;">
    {!! nl2br(e($logs)) !!}
</pre>

{{-- Tambahkan tombol atau link untuk download log jika perlu --}}
{{-- <a href="{{ route('admin.logs.download') }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded">Download Full Log</a> --}}
@endsection