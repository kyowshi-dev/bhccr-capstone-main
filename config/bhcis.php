<?php

return [
    'icd_sql_path' => env('BHCIS_ICD_SQL_PATH', ''),

    // ICD API configuration. When enabled and configured, the application will
    // query the external ICD API for diagnosis lookups. If not enabled or the
    // remote search fails, code falls back to the local `diagnosis_lookup` table.
    'icd_api' => [
        'enabled' => env('BHCIS_ICD_API_ENABLED', false),
        'base_url' => env('BHCIS_ICD_API_BASE_URL', ''),
        'token_url' => env('BHCIS_ICD_API_TOKEN_URL', ''),
        'search_path' => env('BHCIS_ICD_API_SEARCH_PATH', '/search'),
        'client_id' => env('BHCIS_ICD_API_CLIENT_ID', ''),
        'client_secret' => env('BHCIS_ICD_API_CLIENT_SECRET', ''),
    ],
];
