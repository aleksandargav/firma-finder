<?php

return [
    'api_url' => env('APR_API_URL', 'https://openapi.apr.gov.rs/api/opendata/companies'),

    'ca_bundle' => env('APR_CA_BUNDLE', storage_path('app/apr/ca/apr-ca.pem')),

    'snapshot_directory' => storage_path('app/apr/snapshots'),
];
