<?php

namespace App\Services;

use Carbon\Carbon;
use FPDF;
use Illuminate\Support\Collection;

class StatisticheReportPdf extends FPDF
{
    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 6, 'PraticaVoice - Pagina '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

class StatistichePdfService
{
    private StatisticheReportPdf $pdf;

    public function generate(array $report): string
    {
        $this->pdf = new StatisticheReportPdf('P', 'mm', 'A4');
        $this->pdf->SetMargins(12, 14, 12);
        $this->pdf->SetAutoPageBreak(true, 17);
        $this->pdf->SetCompression(false);
        $this->pdf->AliasNbPages();
        $this->pdf->AddPage();

        $this->header($report);
        $this->usageSummary($report['riepilogo']);
        $this->usageTable($report['utilizzo_mensile']);
        $this->maintenance($report);
        $this->comparisonTable($report['confronto_mensile']);

        return $this->pdf->Output('S');
    }

    private function header(array $report): void
    {
        $this->pdf->SetTextColor(15, 23, 42);
        $this->pdf->SetFont('Helvetica', 'B', 18);
        $this->pdf->Cell(0, 9, $this->text('PraticaVoice'), 0, 1, 'C');
        $this->pdf->SetFont('Helvetica', 'B', 15);
        $this->pdf->Cell(0, 8, $this->text('Report statistiche di utilizzo'), 0, 1, 'C');
        $this->pdf->SetFont('Helvetica', 'B', 11);
        $this->pdf->Cell(0, 7, $this->text($report['tenant']), 0, 1, 'C');
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->Cell(0, 5, $this->text('Periodo analizzato: '.$report['periodo']), 0, 1, 'C');
        $this->pdf->Cell(0, 5, $this->text('Report generato il: '.$report['generato_il']), 0, 1, 'C');
        $this->pdf->Ln(5);
    }

    private function usageSummary(object $summary): void
    {
        $this->sectionTitle('1. Riepilogo utilizzo');
        $rows = [
            ['Fascicoli completati', (int) ($summary->fascicoli_completati ?? 0)],
            ['Pratiche distinte', (int) ($summary->pratiche_distinte ?? 0)],
            ['Errori di generazione', (int) ($summary->errori ?? 0)],
            ['Totale generazioni', (int) ($summary->totale_generazioni ?? 0)],
            ['Primo fascicolo nel periodo', $this->dateTime($summary->primo_fascicolo ?? null)],
            ['Ultimo fascicolo nel periodo', $this->dateTime($summary->ultimo_fascicolo ?? null)],
        ];

        foreach ($rows as [$label, $value]) {
            $this->pdf->SetFont('Helvetica', 'B', 9);
            $this->pdf->Cell(92, 6, $this->text($label), 1, 0, 'L');
            $this->pdf->SetFont('Helvetica', '', 9);
            $this->pdf->Cell(92, 6, $this->text((string) $value), 1, 1, 'R');
        }
        $this->pdf->Ln(5);
    }

    private function usageTable(Collection $rows): void
    {
        $this->sectionTitle('2. Andamento utilizzo mensile');
        $this->table(
            ['Mese', 'Completati', 'Pratiche', 'Errori', 'Generazioni'],
            [32, 38, 38, 34, 42],
            $rows,
            fn ($row) => [$row->periodo, $row->fascicoli_completati, $row->pratiche_distinte, $row->errori, $row->totale_generazioni]
        );
        $this->pdf->Ln(5);
    }

    private function maintenance(array $report): void
    {
        $this->sectionTitle('3. Manutenzione tecnica');

        if (! $report['manutenzione']['available']) {
            $this->paragraph('La tabella di audit dei deploy non e disponibile in questa installazione.');
            $this->pdf->Ln(4);

            return;
        }

        $maintenance = $report['manutenzione'];
        $last = $maintenance['ultimo_deploy'];
        $rows = [
            ['Ultimo deploy registrato', $last ? $this->dateTime($last->created_at).($last->commit ? ' - '.$last->commit : '') : '-'],
            ['Giorni con deploy nel periodo', $report['confronto']['giorni_deploy']],
            ['Commit distinti deployati', $report['confronto']['commit_distinti']],
            ['Registrazioni deploy', $maintenance['mensili']->sum('registrazioni_deploy')],
        ];

        foreach ($rows as [$label, $value]) {
            $this->pdf->SetFont('Helvetica', 'B', 9);
            $this->pdf->Cell(92, 6, $this->text($label), 1, 0, 'L');
            $this->pdf->SetFont('Helvetica', '', 9);
            $this->pdf->Cell(92, 6, $this->text((string) $value), 1, 1, 'R');
        }
        $this->pdf->Ln(3);

        if ($maintenance['mensili']->isEmpty()) {
            $this->paragraph('Nessun deploy automatico registrato nel periodo selezionato.');
        } else {
            $this->table(
                ['Mese', 'Giorni con deploy', 'Commit distinti', 'Registrazioni'],
                [34, 52, 48, 50],
                $maintenance['mensili'],
                fn ($row) => [$row->mese, $row->giorni_con_deploy, $row->commit_distinti, $row->registrazioni_deploy]
            );
        }

        $this->pdf->Ln(3);
        $this->paragraph('I dati di manutenzione tecnica derivano dalle registrazioni automatiche dei deploy disponibili sul sistema e hanno valore indicativo.', 8);
        $this->pdf->Ln(5);
    }

    private function comparisonTable(Collection $rows): void
    {
        $this->sectionTitle('4. Confronto utilizzo / manutenzione');

        if ($rows->isEmpty()) {
            $this->paragraph('Nessun dato disponibile nel periodo selezionato.');

            return;
        }

        $this->table(
            ['Mese', 'Fascicoli completati', 'Giorni con deploy', 'Commit distinti'],
            [34, 54, 48, 48],
            $rows,
            fn ($row) => [$row->mese, $row->fascicoli_completati, $row->giorni_con_deploy, $row->commit_distinti]
        );
    }

    private function sectionTitle(string $title): void
    {
        $this->ensureSpace(12);
        $this->pdf->SetFillColor(226, 232, 240);
        $this->pdf->SetTextColor(15, 23, 42);
        $this->pdf->SetFont('Helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, $this->text($title), 0, 1, 'L', true);
        $this->pdf->Ln(2);
    }

    private function table(array $headers, array $widths, Collection $rows, callable $values): void
    {
        $drawHeader = function () use ($headers, $widths): void {
            $this->pdf->SetFillColor(51, 65, 85);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetFont('Helvetica', 'B', 8);
            foreach ($headers as $index => $header) {
                $this->pdf->Cell($widths[$index], 7, $this->text($header), 1, 0, $index === 0 ? 'L' : 'R', true);
            }
            $this->pdf->Ln();
        };

        $drawHeader();
        foreach ($rows as $index => $row) {
            if ($this->pdf->GetY() > 270) {
                $this->pdf->AddPage();
                $drawHeader();
            }

            $this->pdf->SetFillColor($index % 2 === 0 ? 248 : 255, $index % 2 === 0 ? 250 : 255, $index % 2 === 0 ? 252 : 255);
            $this->pdf->SetTextColor(15, 23, 42);
            $this->pdf->SetFont('Helvetica', '', 8);
            foreach (array_values($values($row)) as $column => $value) {
                $this->pdf->Cell($widths[$column], 6, $this->text((string) $value), 1, 0, $column === 0 ? 'L' : 'R', true);
            }
            $this->pdf->Ln();
        }
    }

    private function paragraph(string $text, int $size = 9): void
    {
        $this->pdf->SetTextColor(71, 85, 105);
        $this->pdf->SetFont('Helvetica', '', $size);
        $this->pdf->MultiCell(0, 5, $this->text($text));
    }

    private function ensureSpace(float $height): void
    {
        if ($this->pdf->GetY() + $height > 277) {
            $this->pdf->AddPage();
        }
    }

    private function dateTime($value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y H:i') : '-';
    }

    private function text(string $value): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', $value) ?: $value;
    }
}
