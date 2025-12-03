<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfTextPage extends Model
{
    protected $fillable = [
        'pdf_index_id',
        'page',
        'text_ocr',
        'text_clean',
    ];

    public function pdfIndex()
    {
        return $this->belongsTo(PdfIndex::class);
    }

    public function pratica()
    {
        return $this->hasOneThrough(
            Pratica::class,
            PdfIndex::class,
            'id',          // PdfIndex.id
            'id',          // Pratica.id
            'pdf_index_id',// PdfTextPage.pdf_index_id
            'pratica_id'   // PdfIndex.pratica_id
        );
    }
}
