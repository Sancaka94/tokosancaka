@extends('layouts.admin')

@section('title', 'Live Chat Sancaka')

@push('styles')
    @include('chat.partials.styles')
    <style>
        /* CSS Tambahan agar Header Chat Admin punya tombol aksi */
        .header-actions { display: flex; gap: 15px; margin-left: auto; align-items: center; }
        .header-actions i { cursor: pointer; font-size: 18px; color: #667781; transition: 0.2s; }
        .header-actions i:hover { color: #111b21; }
        .fa-whatsapp { color: #25D366; }
        .fa-trash-can { color: #ef4444; }

        /* Style untuk Card Produk yang muncul di Web Admin */
        .product-card-msg {
            background: #fff; border-radius: 8px; padding: 10px; border: 1px solid #e9edef;
            display: flex; gap: 10px; max-width: 280px; margin-bottom: 5px; text-decoration: none !important;
        }
        .product-card-msg img { width: 50px; height: 50px; border-radius: 4px; object-fit: cover; }
        .product-info-msg { display: flex; flex-direction: column; justify-content: center; }
        .product-title-msg { font-size: 12px; font-weight: bold; color: #111b21; }
        .product-price-msg { font-size: 12px; color: #dc2626; }
    </style>
@endpush

@section('content')
<div class="chat-container">
    <div class="sidebar">
        <header class="sidebar-header">
            <img src="{{ Auth::user()->profile_photo_url ?? 'https://ui-avatars.com/api/?name=Admin' }}" alt="Avatar" class="avatar">
            <div class="header-text">
                <h3>Admin Sancaka</h3>
                <small class="text-success">Online</small>
            </div>
        </header>
        <div class="sidebar-search">
             <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="user-search-input" placeholder="Cari pelanggan...">
             </div>
        </div>
        <div class="conversation-list" id="conversation-list">
            </div>
    </div>

    <div class="chat-panel">
        <div id="welcome-panel" style="display: flex; flex-direction:column; justify-content:center; align-items:center; height:100%; color: #667781; background: #f0f2f5;">
            <i class="fa-solid fa-comments" style="font-size: 50px; margin-bottom: 15px; color: #d1d5db;"></i>
            <h2>Pilih percakapan untuk memulai</h2>
        </div>

        <div id="main-chat-panel" class="hidden">
            <header class="chat-header">
                <div id="chat-header-info" style="display: flex; align-items: center; gap: 12px;">
                    </div>
                <div class="header-actions">
                    <i class="fa-brands fa-whatsapp" id="btn-wa-direct" title="Hubungi via WhatsApp"></i>
                    <i class="fa-solid fa-trash-can" id="btn-delete-history" title="Bersihkan Chat"></i>
                </div>
            </header>

            <div class="chat-messages" id="chat-messages"></div>

            <div class="chat-input">
                <button type="button" id="btn-emoji"><i class="fa-regular fa-face-smile"></i></button>
                <button type="button" id="btn-attach"><i class="fa-solid fa-paperclip"></i></button>
                <input type="text" id="message-input" placeholder="Ketik pesan" disabled>
                <button id="send-button"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('chat.partials.scripts', ['userRole' => 'admin'])
@endpush
