<?php

return [
    'va'       => env('IPAYMU_VA'),
    'api_key'  => env('IPAYMU_API_KEY'),
    'env'      => env('IPAYMU_ENV', 'sandbox'),
    'base_url' => env('IPAYMU_ENV', 'sandbox') === 'production'
                    ? 'https://my.ipaymu.com'
                    : 'https://sandbox.ipaymu.com',
];
