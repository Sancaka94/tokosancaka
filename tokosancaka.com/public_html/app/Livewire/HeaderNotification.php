<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class HeaderNotification extends Component
{
    public $notifications = [];
    public $unreadCount = 0;

    public function mount()
    {
        $this->updateCount();
    }

    public function updateCount()
    {
        if (Auth::check()) {
            $this->unreadCount = Auth::user()->unreadNotifications()->count();
        }
    }

    public function loadNotifications()
    {
        if (Auth::check()) {
            // Ambil 10 notifikasi terakhir
            $this->notifications = Auth::user()->notifications()->take(10)->get();
            // Update count agar sinkron
            $this->unreadCount = Auth::user()->unreadNotifications()->count();
        }
    }

    public function markAllAsRead()
    {
        if (Auth::check()) {
            Auth::user()->unreadNotifications->markAsRead();
            $this->updateCount();
            $this->loadNotifications(); // Reload list biar statusnya berubah jadi terbaca
        }
    }

    public function render()
    {
        return view('livewire.header-notification');
    }
}