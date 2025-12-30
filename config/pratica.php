<?php

return [
    'pdf_base_path' => env('PDF_BASE_PATH'),
    'index_limit'   => env('PRATICA_INDEX_LIMIT', 10),
    'fascicolo_expiry_days' => env('FASCICOLO_EXPIRY_DAYS', 3),
];
