<?php

namespace App\Services;

use App\Models\PdfFile;
use Smalot\PdfParser\Parser;

class PdfInfoService
{
    public function contaPagine(PdfFile $file): int
    {
        $base = rtrim(config('pratica.pdf_base_path'), '/');
        $filePath = $base . '/' . $file->cartella . '/' . $file->file;

        if (!file_exists($filePath)) {
            throw new \Exception("File non trovato: " . $filePath);
        }

        // Usa Smalot PDF Parser
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);

        return count($pdf->getPages());
    }
}
