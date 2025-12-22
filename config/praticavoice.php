<?php

return [
    'mode' => env('PRATICAVOICE_MODE', 'cloud'),

    'tenant' => [
        'slug' => env('TENANT_SLUG', 'default'),
        'name' => env('TENANT_NAME', 'PraticaVoice'),
        'pdf_dir' => env('TENANT_PDF_DIR', 'PDF'),
    ],
];
