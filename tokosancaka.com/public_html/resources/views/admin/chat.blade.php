<!-- resources/views/admin/chat.blade.php -->
@extends('layouts.admin')

@section('title', 'Live Chat')

@push('styles')
    @include('chat.partials.styles')
@endpush

@section('content')
<div class="chat-container">
    <!-- Sidebar (Daftar Pengguna) -->
    <div class="sidebar">
        <header class="sidebar-header">
            <img src="{{ Auth::user()->avatar_url ?? 'https://i.pravatar.cc/150?u=admin' }}" alt="Avatar" class="avatar">
            <h3>Admin Chat</h3>
        </header>
        <div class="sidebar-search">
             <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Cari pelanggan...">
             </div>
        </div>
        <div class="conversation-list" id="conversation-list">
            <!-- Daftar percakapan dimuat oleh JS -->
        </div>
    </div>

    <!-- Panel Chat -->
    <div class="chat-panel">
        <div id="welcome-panel" style="display: flex; justify-content:center; align-items:center; height:100%; color: #667781;">
            <h2>Pilih percakapan untuk memulai</h2>
        </div>
        <div id="main-chat-panel" class="hidden">
            <header class="chat-header" id="chat-header"></header>
            <div class="chat-messages" id="chat-messages"></div>
            <div class="chat-input">
                <button><i class="fa-regular fa-face-smile"></i></button>
                <button><i class="fa-solid fa-paperclip"></i></button>
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