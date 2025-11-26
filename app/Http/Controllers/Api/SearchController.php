<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pratica;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->query('q');

        if (!$q) {
            return response()->json([]);
        }

        $query = Pratica::query();

        // ricerca su vari campi
        $query->where(function($sub) use ($q) {
            $sub->where('rich_cognome1', 'like', "%$q%")
                ->orWhere('rich_nome1', 'like', "%$q%")
                ->orWhere('rich_cognome2', 'like', "%$q%")
                ->orWhere('rich_nome2', 'like', "%$q%")
                ->orWhere('numero_pratica', 'like', "%$q%")
                ->orWhere('oggetto', 'like', "%$q%")
                ->orWhere('area_circolazione', 'like', "%$q%");
        });

        return $query->limit(20)->get();
    }
}

