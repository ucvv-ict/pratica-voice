<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessoAttiElemento extends Model
{
    protected $table = 'accesso_atti_elementi';

    protected $fillable = [
        'accesso_atti_id',
        'tipo',
        'file_id',
        'file_esterno_path',
        'pagina_inizio',
        'pagina_fine',
        'ordinamento',
    ];

    public function accesso()
    {
        return $this->belongsTo(AccessoAtti::class, 'accesso_atti_id');
    }

    public function file()
    {
        return $this->belongsTo(\App\Models\PdfFile::class, 'file_id');
    }
}
