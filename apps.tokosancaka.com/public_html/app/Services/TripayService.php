<?php

namespace App\Services;

class TripayService
{
    /**
     * Placeholder agar tidak error saat Dependency Injection
     */
    public function createTransaction($order, $items, $method = null)
    {
        return [
            'success' => false,
            'message' => 'Layanan Tripay belum dikonfigurasi.'
        ];
    }
}