<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Ujicobacontroller extends Controller
{
    /**
     * Halaman utama uji coba
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $data = [
            'title'     => 'Halaman Uji Coba',
            'message'   => 'Controller Ujicobacontroller berhasil dibuat dan berjalan 🚀',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];

        return response()->json([
            'status'  => 'success',
            'message' => $data['message'],
            'data'    => $data,
        ], 200);
    }

    /**
     * Endpoint ping untuk health check
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ping()
    {
        return response()->json([
            'status'    => 'ok',
            'service'   => 'Ujicobacontroller',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Uji koneksi database
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testDb()
    {
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();

            return response()->json([
                'status'    => 'success',
                'message'   => 'Koneksi database berhasil',
                'database'  => $dbName,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('[Ujicobacontroller] DB error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Koneksi database gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan data yang dikirim dari request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function echo(Request $request)
    {
        return response()->json([
            'status'   => 'success',
            'method'   => $request->method(),
            'ip'       => $request->ip(),
            'headers'  => $request->headers->all(),
            'payload'  => $request->all(),
        ], 200);
    }
}