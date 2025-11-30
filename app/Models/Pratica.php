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
        'numero_pdf',
    ];

    public function getRichiedentiCompletiAttribute()
    {
        $lista = [];

        if ($this->rich_cognome1 || $this->rich_nome1) {
            $lista[] = trim($this->rich_cognome1 . ' ' . $this->rich_nome1);
        }
        if ($this->rich_cognome2 || $this->rich_nome2) {
            $lista[] = trim($this->rich_cognome2 . ' ' . $this->rich_nome2);
        }
        if ($this->rich_cognome3 || $this->rich_nome3) {
            $lista[] = trim($this->rich_cognome3 . ' ' . $this->rich_nome3);
        }

        return implode(', ', $lista);
    }

}

