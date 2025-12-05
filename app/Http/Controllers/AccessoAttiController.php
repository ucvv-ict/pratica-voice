<?php

namespace App\Http\Controllers;

use App\Models\AccessoAtti;
use App\Models\AccessoAttiElemento;
use App\Models\PdfFile;
use App\Services\PdfInfoService;
use App\Services\AccessoAttiPdfService;
use Illuminate\Http\Request;

class AccessoAttiController extends Controller
{
    public function create($praticaId, PdfInfoService $info)
    {
        $files = PdfFile::where('pratica_id', $praticaId)->get();

        // Conta pagine
        foreach ($files as $f) {
            $f->num_pagine = $info->contaPagine($f);
        }

        return view('accesso_atti.create', compact('praticaId', 'files'));
    }

    public function duplica($id)
    {
        $originale = AccessoAtti::with('elementi')->findOrFail($id);

        // nuova versione
        $ultima = AccessoAtti::where('pratica_id', $originale->pratica_id)->max('versione') ?? 0;

        $copia = AccessoAtti::create([
            'pratica_id' => $originale->pratica_id,
            'versione'   => $ultima + 1,
            'descrizione'=> $originale->descrizione . " (duplicata)",
            'note'       => $originale->note,
            'created_by' => auth()->id() ?? AccessoAtti::SYSTEM_USER,
        ]);

        // copia elementi
        foreach ($originale->elementi as $el) {
            AccessoAttiElemento::create([
                'accesso_atti_id'  => $copia->id,
                'tipo'             => $el->tipo,
                'file_id'          => $el->file_id,
                'file_esterno_path'=> $el->file_esterno_path,
                'pagina_inizio'    => $el->pagina_inizio,
                'pagina_fine'      => $el->pagina_fine,
                'ordinamento'      => $el->ordinamento,
            ]);
        }

        return redirect()->route('accesso-atti.show', $copia->id)
            ->with('success', 'Versione duplicata con successo.');
    }

    public function store(Request $request, $praticaId)
    {
        // Crea una nuova versione incrementando la precedente
        $ultima = AccessoAtti::where('pratica_id', $praticaId)->max('versione') ?? 0;

        $accesso = AccessoAtti::create([
            'pratica_id' => $praticaId,
            'versione'   => $ultima + 1,
            'descrizione'=> $request->descrizione,
            'note'       => $request->note,        // <-- AGGIUNTO
            'created_by' => auth()->id() ?? AccessoAtti::SYSTEM_USER,
        ]);

        // Decodifica elementi JSON
        $elementi = json_decode($request->elementi, true) ?? [];

        foreach ($elementi as $el) {
            AccessoAttiElemento::create([
                'accesso_atti_id'  => $accesso->id,
                'tipo'             => $el['tipo'],
                'file_id'          => $el['file_id'] ?? null,
                'file_esterno_path'=> $el['file_esterno_path'] ?? null,
                'pagina_inizio'    => $el['pagina_inizio'],
                'pagina_fine'      => $el['pagina_fine'],
                'ordinamento'      => $el['ordinamento'],
            ]);
        }

        return redirect()->route('accesso-atti.show', $accesso->id)
            ->with('success', 'Fascicolo creato con successo.');
    }

    public function update(Request $request, $id)
    {
        $accesso = AccessoAtti::findOrFail($id);

        $accesso->update([
            'descrizione' => $request->descrizione,
            'note'        => $request->note,   // <-- AGGIUNTO
        ]);

        return redirect()->route('accesso-atti.show', $id)
            ->with('success', 'Informazioni aggiornate.');
    }

    public function saveOrdinamento($id, Request $request)
    {
        $accesso = AccessoAtti::findOrFail($id);

        $ordine = json_decode($request->ordine, true);
        if (!is_array($ordine)) {
            return back()->with('error', 'Formato ordine non valido');
        }

        foreach ($ordine as $pos => $elId) {
            AccessoAttiElemento::where('id', $elId)
                ->update(['ordinamento' => $pos]);
        }

        return redirect()
            ->route('accesso-atti.show', $id)
            ->with('success', 'Ordinamento aggiornato.');
    }

    public function previewInline($id, AccessoAttiPdfService $service)
    {
        $accesso = AccessoAtti::with('elementi.file')->findOrFail($id);

        $pdf = $service->genera($accesso);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function editOrdinamento($id)
    {
        $accesso = AccessoAtti::with('elementi.file')->findOrFail($id);

        return view('accesso_atti.ordinamento', compact('accesso'));
    }

    public function previewFascicolo($id, AccessoAttiPdfService $service)
    {
        $accesso = AccessoAtti::with('elementi.file')->findOrFail($id);

        $pdf = $service->genera($accesso);

        return view('accesso_atti.preview', [
            'pdfBase64' => base64_encode($pdf),
            'accesso'   => $accesso,
        ]);
    }

    public function destroy($id)
    {
        $accesso = AccessoAtti::findOrFail($id);
        $praticaId = $accesso->pratica_id;

        // Elimina elementi collegati
        $accesso->elementi()->delete();

        // Elimina la versione
        $accesso->delete();

        return redirect()
            ->route('pratica.show', $praticaId)
            ->with('success', 'Versione del fascicolo eliminata con successo.');
    }

    public function previewElemento($id, $elementoId, AccessoAttiPdfService $service)
    {
        $accesso  = AccessoAtti::findOrFail($id);
        $elemento = $accesso->elementi()->with('file')->findOrFail($elementoId);

        $pdfContent = $service->generaSingoloElemento($elemento);

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }

    public function show($id)
    {
        $accesso = AccessoAtti::with('elementi.file', 'pratica')->findOrFail($id);

        $tutteVersioni = AccessoAtti::where('pratica_id', $accesso->pratica_id)
            ->orderBy('versione', 'desc')
            ->get();

        return view('accesso_atti.show', compact('accesso', 'tutteVersioni'));
    }

    public function download($id)
    {
        $accesso = AccessoAtti::findOrFail($id);

        $pdf = (new AccessoAttiPdfService())->genera($accesso);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="accesso_atti_v'.$accesso->versione.'.pdf"');
    }
}
