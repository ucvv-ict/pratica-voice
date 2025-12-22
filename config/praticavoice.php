<?php

return [
    'mode' => env('PRATICAVOICE_MODE', 'onprem'),
    'tenant' => [
        'slug' => env('TENANT_SLUG', 'pelago'),
        'name' => env('TENANT_NAME', 'Comune di Pelago'),
        'pdf_dir' => env('TENANT_PDF_DIR', 'PDF'),
    ],
];
