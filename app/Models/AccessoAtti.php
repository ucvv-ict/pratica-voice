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
        'note',
        'created_by',
        'r2_link',
        'r2_link_generated_at',
        'r2_link_expires_at',
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
