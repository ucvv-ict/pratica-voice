<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfIndex extends Model
{
    protected $table = 'pdf_index';

    protected $fillable = [
        'pratica_id',
        'file',
        'hash',
        'content',
    ];

    public function pratica()
    {
        return $this->belongsTo(Pratica::class, 'pratica_id');
    }

    public function pages()
    {
        return $this->hasMany(PdfTextPage::class);
    }

    public function classifications()
    {
        return $this->hasMany(PdfAiClassification::class);
    }

    public function embeddings()
    {
        return $this->hasMany(PdfAiEmbedding::class);
    }
}
