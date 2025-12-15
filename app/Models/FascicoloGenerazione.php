<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FascicoloGenerazione extends Model
{
    use HasFactory;

    protected $table = 'fascicoli_generazione';

    protected $fillable = [
        'pratica_id',
        'versione',
        'stato',
        'progress',
        'errore',
        'file_zip',
    ];

    public function pratica()
    {
        return $this->belongsTo(Pratica::class);
    }
}
