<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessoAtti extends Model
{
    protected $table = 'accessi_atti';
    const SYSTEM_USER = 1;

    protected $fillable = [
        'pratica_id',
        'versione',
        'descrizione',
        'created_by',
    ];

    public function pratica()
    {
        return $this->belongsTo(\App\Models\Pratica::class, 'pratica_id');
    }

    public function elementi()
    {
        return $this->hasMany(AccessoAttiElemento::class)
            ->orderBy('ordinamento');
    }
}
