<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenAI;

class InterpretationController extends Controller
{
    public function interpret(Request $request)
    {
        $text = strtolower(trim($request->query('q')));
        if (!$text) {
            return ['error' => 'Missing text'];
        }

        // 1️⃣ Prima prova: interpretazione locale (gratis)
        $local = $this->localInterpretation($text);
        if ($local !== null) {
            return $local;
        }

        // 2️⃣ Se già interpretato → usa cache (gratis)
        $cacheKey = 'interpretation_' . md5($text);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 3️⃣ Ultima spiaggia: ChatGPT (low-cost)
        try {
            $client = OpenAI::client(env('OPENAI_API_KEY'));

            $prompt = "
Sei un assistente che interpreta richieste vocali sulle pratiche edilizie.
Estrai SOLO un JSON con 'action' e 'filters'.

Esempi:
Utente: 'vorrei la pratica 1467'
Risposta: {\"action\":\"search\",\"filters\":{\"numero_pratica\":1467}}

Utente: 'mi cerchi tutte le pratiche di carletti?'
Risposta: {\"action\":\"search\",\"filters\":{\"cognome\":\"carletti\"}}

Utente: \"$text\"
Rispondi solo JSON.
";

            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json'],
            ]);

            $json = json_decode($response->choices[0]->message->content, true);

            Cache::put($cacheKey, $json, 60 * 60 * 24 * 30); // cache 30 giorni

            return $json;

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // 🔥  Interpretazione locale (gratis + veloce)
    private function localInterpretation(string $text)
    {
        // numero pratica (“pratica 1467”)
        if (preg_match('/pratica\s+(\d+)/', $text, $m)) {
            return [
                "action" => "search",
                "filters" => ["numero_pratica" => intval($m[1])]
            ];
        }

        // frase contenente solo un numero → probabilmente numero pratica
        if (preg_match('/^\d{2,5}$/', $text)) {
            return [
                "action" => "search",
                "filters" => ["numero_pratica" => intval($text)]
            ];
        }

        // anno
        if (preg_match('/(19|20)\d{2}/', $text, $m)) {
            return [
                "action" => "search",
                "filters" => ["anno_presentazione" => intval($m[0])]
            ];
        }

        // “pratiche di Carletti”, “cerco carletti”
        if (preg_match('/di\s+([a-z]+)/', $text, $m) ||
            preg_match('/cerca\s+([a-z]+)/', $text, $m) ||
            preg_match('/cerchi\s+([a-z]+)/', $text, $m))
        {
            return [
                "action" => "search",
                "filters" => ["cognome" => $m[1]]
            ];
        }

        // parola singola (cognome o oggetto)
        if (preg_match('/^[a-zàèìòù]+$/', $text)) {
            return [
                "action" => "search",
                "filters" => ["cognome" => $text] // fallback
            ];
        }

        // Se non capiamo → ritorniamo NULL → si va a ChatGPT
        return null;
    }
}

