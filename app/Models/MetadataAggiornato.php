<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetadataAggiornato extends Model
{
    protected $table = 'metadati_aggiornati';

    protected $fillable = [
        'pratica_id',
        'user_id',
        'versione',
        'dati',
    ];

    protected $casts = [
        'dati' => 'array',
    ];

    public function pratica()
    {
        return $this->belongsTo(Pratica::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
