<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfAiEmbedding extends Model
{
    protected $table = 'pdf_ai_embeddings';

    protected $fillable = [
        'pdf_index_id',
        'page',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
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
            'id',
            'id',
            'pdf_index_id',
            'pratica_id'
        );
    }
}
