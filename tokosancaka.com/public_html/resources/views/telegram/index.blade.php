@extends('layouts.app')

@section('content')
<div class="container search-container text-center mb-5">

    <div class="google-title mb-3">
        <span class="text-primary">S</span><span class="text-danger">a</span><span class="text-warning">n</span><span class="text-primary">c</span><span class="text-success">a</span><span class="text-danger">k</span><span class="text-warning">a</span>
    </div>
    <p class="text-muted mb-4">Mesin Pencari Artikel & Kajian Salafy ✨</p>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <form action="{{ route('search.do') }}" method="GET">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                    <input type="text" name="q" class="form-control border-0 px-4" value="{{ $keyword ?? '' }}" placeholder="Cari artikel, brosur, atau kajian video..." required>
                    <button class="btn btn-light bg-white border-0 px-4" type="submit">🔍</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">

            @if(isset($keyword))
                @if(empty($groups))
                    <div class="alert alert-warning shadow-sm">
                        ⚠️ <b>Sistem Belum Siap:</b> Admin belum mendaftarkan grup Telegram di database.
                    </div>
                @elseif(empty($hasil_pencarian))
                    <div class="alert alert-danger shadow-sm">
                        Maaf, tidak ditemukan hasil untuk '<b>{{ $keyword }}</b>'.
                    </div>
                @else
                    <p class="text-muted small">Ditemukan {{ count($hasil_pencarian) }} hasil untuk '<b>{{ $keyword }}</b>'</p>

                    <div class="d-flex flex-column gap-3">
                        @foreach($hasil_pencarian as $index => $item)
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    <h5 class="card-title text-primary fw-bold mb-1">🏢 {{ $item['grup'] }}</h5>
                                    <a href="{{ $item['link_grup'] }}" class="text-muted small text-decoration-none d-block mb-3">{{ $item['link_grup'] }}</a>

                                    <div class="p-3 bg-light rounded border-start border-primary border-4 mb-3 text-secondary">
                                        @if($item['tipe_media'] == 'photo') 🖼️ [Gambar] @elseif($item['tipe_media'] == 'video') 🎥 [Video] @endif
                                        {{ Str::limit($item['teks'], 150) }}
                                    </div>

                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $index }}">
                                        📖 Buka Detail & Media
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="modalDetail{{ $index }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title fw-bold">📄 Detail Artikel & Media</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            @if($item['tipe_media'] == 'photo' && $item['path_media'])
                                                <img src="{{ $item['path_media'] }}" class="img-fluid rounded mb-4 w-100" alt="Media Telegram">
                                            @endif
                                            <p class="text-dark" style="white-space: pre-wrap;">{{ $item['teks'] }}</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

        </div>
    </div>
</div>
@endsection
