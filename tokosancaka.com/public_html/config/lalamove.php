<?php

return [
    'key'      => env('LALAMOVE_API_KEY'),
    'secret'   => env('LALAMOVE_API_SECRET'),
    'market'   => env('LALAMOVE_MARKET', 'ID'),
    'base_url' => env('LALAMOVE_BASE_URL', 'https://rest.sandbox.lalamove.com'),
];