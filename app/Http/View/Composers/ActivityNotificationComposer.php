<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Http\Controllers\Admin\ActivityLogController;

class ActivityNotificationComposer
{
    /**
     * @var ActivityLogController
     */
    protected $activityLogController;

    /**
     * Buat instance composer baru.
     *
     * @param  ActivityLogController  $activityLogController
     * @return void
     */
    public function __construct(ActivityLogController $activityLogController)
    {
        // Menyuntikkan instance ActivityLogController agar kita bisa menggunakan metodenya.
        $this->activityLogController = $activityLogController;
    }

    /**
     * Mengikat data ke view.
     *
     * Method ini akan dipanggil setiap kali view 'layouts.partials.header' dirender.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // Panggil method dari controller untuk mendapatkan data notifikasi
        $notifications = $this->activityLogController->getHeaderNotifications();
        
        // Kirim data ke view dengan nama variabel '$activityNotifications'
        $view->with('activityNotifications', $notifications);
    }
}

