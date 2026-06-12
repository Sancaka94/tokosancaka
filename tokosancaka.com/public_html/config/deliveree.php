<?php

return [
    'mode' => env('DELIVEREE_MODE', 'sandbox'),
    'api_version' => 'v10',
    'sandbox' => [
        'base_url' => 'https://api.sandbox.deliveree.com/public_api/v10',
    ],
    'production' => [
        'base_url' => 'https://api.deliveree.com/public_api/v10',
    ],
];