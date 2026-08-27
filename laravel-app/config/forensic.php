<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Koneksi ke Python Forensic Analysis Service
    |--------------------------------------------------------------------------
    | Nilai FORENSIC_SERVICE_KEY harus SAMA PERSIS dengan API_SECRET_KEY
    | pada .env milik python-service, karena dipakai sebagai shared-secret
    | untuk autentikasi antar-service (header X-API-Key).
    */
    'base_url' => env('FORENSIC_SERVICE_URL', 'http://python-service:8000'),
    'api_key' => env('FORENSIC_SERVICE_KEY'),
    'timeout' => env('FORENSIC_SERVICE_TIMEOUT', 120),
];
