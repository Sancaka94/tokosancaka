@extends('layouts.admin')

@section('title', 'Detail Email')
@section('page-title', 'Detail Email')

@section('content')
<div class="flex flex-col h-[calc(100vh-150px)] bg-white rounded-xl shadow-lg overflow-hidden p-4">

    {{-- Tombol kembali --}}
    <div class="mb-4">
        <a href="{{ route('admin.imap.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Inbox
        </a>
    </div>

    {{-- Konten Email --}}
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden bg-white rounded-lg shadow-lg">

        {{-- Info Email --}}
        <div class="md:w-1/3 p-6 border-b md:border-b-0 md:border-r border-gray-200 flex-shrink-0">
            <h2 class="text-xl font-bold mb-4 truncate">{{ $message->getSubject() ?? '(Tanpa Subjek)' }}</h2>

            <div class="space-y-2 text-sm text-gray-700">
                <div>
                    <span class="font-semibold">Dari:</span>
                    <span>{{ $message->getFrom()[0]->personal ?? $message->getFrom()[0]->mail ?? 'Tidak diketahui' }}</span>
                </div>
                <div>
                    <span class="font-semibold">Untuk:</span>
                    <span>
                        @foreach($message->getTo() as $to)
                            {{ $to->personal ?? $to->mail }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </span>
                </div>
                <div>
                    <span class="font-semibold">Tanggal:</span>
                    @php
                        $dt = \Carbon\Carbon::parse($message->getDate());
                        $dayName = $dt->translatedFormat('l');
                        $dateTime = $dt->format('d M Y, H:i');
                    @endphp
                    <span>{{ $dayName }}, {{ $dateTime }}</span>
                </div>
                <div>
                    <span class="font-semibold">Status:</span>
                    <span>{{ $message->getFlags()->has('seen') ? 'Dibaca' : 'Belum dibaca' }}</span>
                </div>
            </div>
        </div>

        {{-- Body Email --}}
        <div class="md:w-2/3 p-6 overflow-y-auto">
            <div class="prose max-w-full">
                {!! $message->getHTMLBody() ?? nl2br(e($message->getTextBody())) !!}
            </div>
        </div>
    </div>
</div>
@endsection
