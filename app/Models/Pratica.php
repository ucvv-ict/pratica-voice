<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pratica extends Model
{
    protected $table = 'pratiche';

    protected $fillable = [
        'anno_presentazione',
        'data_protocollo',
        'numero_protocollo',
        'oggetto',
        'numero_pratica',
        'data_rilascio',
        'numero_rilascio',
        'riferimento_libero',
        'area_circolazione',
        'civico_esponente',
        'codice_catasto',
        'tipo_catasto',
        'sezione',
        'foglio',
        'particella_sub',
        'nota',
        'rich_cognome1',
        'rich_nome1',
        'rich_cognome2',
        'rich_nome2',
        'rich_cognome3',
        'rich_nome3',
        'sigla_tipo_pratica',
        'pratica_id',
        'cartella',
    ];
}

