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
        $base = rtrim(config('pratica.pdf_base_path'), '/');
        $folder = trim($cartella, '/');

        return "{$base}/{$folder}";
    }

    public static function praticaPdfAssetBase(string $cartella): string
    {
        $prefix = self::publicStoragePrefix();
        $folder = trim($cartella, '/');

        return asset("storage/{$prefix}/{$folder}");
    }
}
