<?php

namespace App\Services;

use App\Models\PdfFile;
use Smalot\PdfParser\Parser;
use App\Support\Tenant;

class PdfInfoService
{
    public function contaPagine(PdfFile $file): int
    {
        $base = Tenant::praticaPdfFolder($file->cartella);
        $filePath = $base . '/' . $file->file;

        if (!file_exists($filePath)) {
            throw new \Exception("File non trovato: " . $filePath);
        }

        // Usa Smalot PDF Parser
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);

        return count($pdf->getPages());
    }
}
