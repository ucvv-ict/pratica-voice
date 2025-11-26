<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfFile extends Model
{
    protected $table = 'pdf_files';

    protected $fillable = [
        'pratica_id',
        'cartella',
        'file',
        'md5',
        'sha256',
        'size_bytes',
        'importante',
    ];

    public function pratica()
    {
        return $this->belongsTo(\App\Models\Pratica::class);
    }

    public function getPathAttribute()
    {
        return storage_path("app/public/PELAGO/PDF/{$this->cartella}/{$this->file}");
    }
}
