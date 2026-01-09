<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException; // <-- PENTING: Tambahkan ini

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * -- KODE BARU: Menangani pengalihan untuk pengguna yang tidak terotentikasi --
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Auth\AuthenticationException $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Jika request bukan mengharapkan JSON
        if (! $request->expectsJson()) {
            // Jika pengguna mencoba mengakses URL admin
            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->guest(url('/admin/login'));
            }

            // Untuk semua kasus lainnya, arahkan ke login customer
            return redirect()->guest(url('/customer/login'));
        }

        // Default response untuk API (jika ada)
        return response()->json(['message' => $exception->getMessage()], 401);
    }
}
