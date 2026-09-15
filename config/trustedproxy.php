<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxy Addresses
    |--------------------------------------------------------------------------
    |
    | Local tunnel agents connect to Laravel through the loopback interface.
    | Deployments with another proxy network may provide a comma-separated
    | list of individual addresses or CIDR ranges through the environment.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1'),
];
