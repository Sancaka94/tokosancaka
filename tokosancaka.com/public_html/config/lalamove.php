<?php

return [
    'env' => env('LALAMOVE_ENV', 'sandbox'),
    'api_key' => env('LALAMOVE_API_KEY', ''),
    'api_secret' => env('LALAMOVE_API_SECRET', ''),
    'market' => env('LALAMOVE_MARKET', 'ID'),
    'base_url' => env('LALAMOVE_ENV', 'sandbox') === 'production' 
        ? 'https://rest.lalamove.com' 
        : 'https://rest.sandbox.lalamove.com',
];