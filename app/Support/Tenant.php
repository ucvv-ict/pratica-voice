<?php

namespace App\Support;

use Illuminate\Support\Str;

class Tenant
{
    public static function slug(): string
    {
        return trim(config('praticavoice.tenant.slug', ''), '/');
    }

    public static function name(): string
    {
        return config('praticavoice.tenant.name', 'PraticaVoice');
    }

    public static function publicStoragePrefix(): string
    {
        $slug = self::slug();
        $dir = trim(config('praticavoice.tenant.pdf_dir', 'PDF'), '/');

        return Str::of($slug)->trim('/')->append('/' . $dir)->value();
    }

    public static function praticaPdfFolder(string $cartella): string
    {
        // Caso on-prem: PDF su filesystem esterno (NAS / mount)
        if (config('praticavoice.mode') === 'on_prem') {
            return rtrim(config('pratica.pdf_base_path'), '/') . '/' . $cartella;
        }

        // Caso cloud: PDF in storage Laravel
        return storage_path(
            'app/public/' .
            self::slug() .
            '/' .
            config('praticavoice.tenant.pdf_dir') .
            '/' .
            $cartella
        );
    }

    public static function praticaPdfAssetBase(string $cartella): string
    {
        $prefix = self::publicStoragePrefix();
        $folder = trim($cartella, '/');

        return asset("storage/{$prefix}/{$folder}");
    }
}
