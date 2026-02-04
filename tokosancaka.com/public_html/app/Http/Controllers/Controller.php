<?php

namespace App\Http\Controllers;

use App\Models\Setting;

abstract class Controller
{
    public function __construct()
    {
        $logo = Setting::where('key', 'logo')->value('value');

        view()->share('weblogo', $logo);
    }
}
