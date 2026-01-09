<?php

// in app/Http/Controllers/Customer/Auth.php

namespace App\Http\Controllers\Customer; // ✅ Correct namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Auth extends Controller // ✅ Correct class name
{
    // Your controller methods, e.g., showLoginForm, login, etc.
    public function showLoginForm()
    {
        return view('customer.auth.login');
    }
}