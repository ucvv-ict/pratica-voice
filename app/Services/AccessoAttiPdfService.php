<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use App\Models\AccessoAtti;
use App\Models\AccessoAttiElemento;
use Smalot\PdfParser\Parser;

class AccessoAttiPdfService
{
    private $angle = 0;

    /* ==========================================================
       FUNZIONI DI SUPPORTO
       ========================================================== */

    /**
     * Determina il percorso reale del PDF (pratica o file esterno)
     */
    private function getPdfPath($el): string
    {
        if ($el->tipo === 'file_pratica') {

            $base = rtrim(config('pratica.pdf_base_path'), '/');
            return $base . '/' . $el->file->cartella . '/' . $el->file->file;

        } else {

            return storage_path('app/accesso_atti_temp/' . $el->file_esterno_path);
        }
    }

    /**
     * Ruota il contesto grafico per watermark
     */
    private function Rotate($pdf, $angle, $x = -1, $y = -1)
    {
        if ($x == -1) $x = $pdf->GetX();
        if ($y == -1) $y = $pdf->GetY();
        if ($this->angle != 0) {
            $pdf->out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $pdf->k;
            $cy = ($pdf->h - $y) * $pdf->k;
            $pdf->out(sprintf(
                'q %.5F %.5F %.5F %.5F %.5F %.5F cm',
                $c, $s, -$s, $c,
                $cx - $cx * $c + $cy * $s,
                $cy - $cx * $s - $cy * $c
            ));
        }
    }

    /**
     * Applica watermark a una singola pagina
     */
    private function watermark($pdf, $text)
    {
        $pdf->SetFont('Helvetica', 'B', 40);
        $pdf->SetTextColor(255, 0, 0, 40);
        $pdf->SetXY(0, 0);

        $pdf->ApplyRotation(35, 55, 190);
        $pdf->Text(30, 190, $text);
        $pdf->ApplyRotation(0);
    }

    /**
     * Conteggio pagine con fallback Smalot
     */
    private function getPageCountWithFallback(string $path): int
    {
        try {
            $pdf = new \App\Services\FpdiExtended();
            return $pdf->setSourceFile($path);
        } catch (\Throwable $e) {
            $parser = new Parser();
            $doc = $parser->parseFile($path);
            return count($doc->getPages());
        }
    }

    /**
     * Conversione PDF con Ghostscript per compatibilità FPDI
     */
    private function convertToCompatiblePdf(string $sourcePath): string
    {
        $target = storage_path('app/gs_temp_' . md5($sourcePath . microtime()) . '.pdf');

        $cmd = sprintf(
            'gs -dBATCH -dNOPAUSE -sDEVICE=pdfwrite ' .
            '-dCompatibilityLevel=1.4 -dPDFSETTINGS=/prepress ' .
            '-sOutputFile=%s %s 2>&1',
            escapeshellarg($target),
            escapeshellarg($sourcePath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($target)) {
            throw new \Exception("Ghostscript error converting $sourcePath.\n" . implode("\n", $output));
        }

        return $target;
    }

    /* ==========================================================
       COPERTINA
       ========================================================== */

    private function copertina($pdf, AccessoAtti $accesso)
    {
        $pdf->AddPage('P', 'A4');
        $pdf->SetMargins(20, 20, 20);

        // Logo Comune (salva il file in public/logo.png)
        $logoPath = public_path('logo.png');
        if (file_exists($logoPath)) {
            // x=20, y=12, width=30mm, height auto
            $pdf->Image($logoPath, 20, 12, 30);
        }

        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, "COMUNE DI PELAGO", 0, 1, 'C');

        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->Cell(0, 8, "UFFICIO TECNICO - EDILIZIA PRIVATA", 0, 1, 'C');

        $pdf->Ln(10);

        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(0, 12, "Fascicolo per Accesso agli Atti", 0, 1, 'C');

        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetTextColor(0, 0, 150);
        $pdf->Cell(0, 10, "Versione " . $accesso->versione, 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Ln(10);

        $pratica = $accesso->pratica;

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, "Dati della pratica", 0, 1);

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 6,
            "Numero pratica: " . ($pratica->numero_pratica ?? '-') . "\n" .
            "Oggetto: " . ($pratica->oggetto ?? '-') . "\n" .
            "Richiedente: " . (($pratica->rich_cognome1 ?? '') . " " . ($pratica->rich_nome1 ?? '')) . "\n"
        );

        $pdf->Ln(10);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, "Informazioni generali", 0, 1);

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 6,
            "Data generazione: " . now()->format('d/m/Y H:i') . "\n" .
            "Documenti inclusi: " . $accesso->elementi->count() . "\n" .
            "Descrizione: " . ($accesso->descrizione ?: '-') . "\n"
        );

        $pdf->Ln(10);
    }

    /* ==========================================================
       GENERATORE PDF COMPLETO
       ========================================================== */

    public function genera(AccessoAtti $accesso): string
    {
        // Estende il tempo massimo per la generazione di fascicoli molto grandi
        set_time_limit(300);

        $pdf = new \App\Services\FpdiExtended();
        $this->copertina($pdf, $accesso);

        foreach ($accesso->elementi as $el) {

            $path = $this->getPdfPath($el);

            if (!file_exists($path)) {
                throw new \Exception("File non trovato: $path");
            }

            // 1) Numero pagine (Smalot fallback)
            $pageCount = $this->getPageCountWithFallback($path);

            // 2) Importazione FPDI con fallback Ghostscript
            try {
                $pdf->setSourceFile($path);
            } catch (\Throwable $e) {

                // Conversione PDF → compatibile FPDI
                $converted = $this->convertToCompatiblePdf($path);

                try {
                    $pdf->setSourceFile($converted);
                    $path = $converted;
                } catch (\Throwable $e2) {
                    throw new \Exception("Il PDF non può essere importato nemmeno dopo la conversione: $path");
                }
            }

            // 3) Import delle pagine selezionate
            $start = $el->pagina_inizio ?: 1;
            $end   = $el->pagina_fine ?: $pageCount;

            for ($i = $start; $i <= $end; $i++) {

                $tplId = $pdf->importPage($i);
                $size  = $pdf->getTemplateSize($tplId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
        }

        // 🎯 restituisce PDF come stringa → MAI salvato in locale
        return $pdf->Output('S');
    }
}
