<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification; // This should point to Laravel's Notification model.
use App\Models\User;         // ✅ Using the standard User model.

class HeaderComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $notifications = [];
        $saldo = 0;

        // Check if a user is currently logged in
        if (Auth::check()) {
            // ✅ Using the standard $user variable name
            $user = Auth::user();
            
            // Fetch the balance from the logged-in user
            $saldo = $user->saldo ?? 0;

            // ✅ Fetch notifications for the App\Models\User model.
            $notifications = Notification::where('notifiable_id', $user->id)
                                         ->where('notifiable_type', User::class) // Using the User class constant
                                         ->whereNull('read_at')
                                         ->latest()
                                         ->take(5)
                                         ->get();
        }

        // Send both variables to the view
        $view->with('saldo', $saldo)
             ->with('notifications', $notifications);
    }
}
