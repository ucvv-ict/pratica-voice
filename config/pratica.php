<?php

return [
    /*
     |---------------------------------------------------------
     | Base path delle cartelle PDF delle pratiche
     |---------------------------------------------------------
     | Puoi sovrascriverlo con PRACTICE_PDF_BASE_PATH nel .env.
     */
    'pdf_base_path' => env('PRACTICE_PDF_BASE_PATH', storage_path('app/public/PELAGO/PDF')),
    'index_limit' => env('PDF_INDEX_LIMIT', 50),
];

