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

    /**
     * Restituisce solo i campi che differiscono rispetto ai dati originali.
     *
     * @return array<string, array{original:mixed, updated:mixed}>
     */
    public function diff(Pratica $pratica): array
    {
        $diffs = [];

        $ultimo = $pratica->ultimoMetadata;
        if (!$ultimo || !is_array($ultimo->dati)) {
            return $diffs;
        }

        foreach ($ultimo->dati as $key => $value) {
            $original = $pratica->{$key} ?? null;

            if ($value != $original) { // confronto blando per gestire numeri salvati come stringhe
                $diffs[$key] = [
                    'original' => $original,
                    'updated'  => $value,
                ];
            }
        }

        return $diffs;
    }
}
