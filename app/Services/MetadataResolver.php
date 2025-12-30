<?php

namespace App\Services;

use App\Models\Pratica;

class MetadataResolver
{
    /**
     * Restituisce i metadati “risolti”: ultime correzioni applicate sopra i valori originali.
     */
    public function resolve(Pratica $pratica): array
    {
        $resolved = $pratica->toArray();

        $ultimo = $pratica->ultimoMetadata;
        if ($ultimo && is_array($ultimo->dati)) {
            foreach ($ultimo->dati as $key => $value) {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }
}
