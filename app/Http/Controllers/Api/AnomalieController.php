<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anomalie;
use Illuminate\Http\Request;

class AnomalieController extends Controller
{
    // GET /api/anomalies — liste des anomalies (pour le frontend)
    public function index(Request $request)
    {
        $query = Anomalie::with(['heatmap', 'rapportTextuel']);

        if ($request->filled('criticite')) {
            $query->where('criticite', $request->criticite);
        }
        if ($request->filled('zone')) {
            $query->where('zone', $request->zone);
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_detection', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_detection', '<=', $request->date_fin);
        }

        return response()->json($query->latest('date_detection')->get());
    }

    // POST /api/anomalies — appelé par votre script Python quand une anomalie est détectée
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'criticite' => 'required|in:basse,moyenne,haute',
            'date_detection' => 'required|date',
            'zone' => 'required|string',
            'score_confiance' => 'required|numeric|min:0|max:1',
            'statut' => 'sometimes|in:nouvelle,confirmee,faux_positif',
        ]);

        $anomalie = Anomalie::create($validated);

        return response()->json($anomalie, 201);
    }

    // GET /api/anomalies/{id} — détail d'une anomalie
    public function show(Anomalie $anomalie)
    {
        return response()->json($anomalie->load(['heatmap', 'rapportTextuel', 'alertes']));
    }

    // GET /api/statistiques — pour le tableau de bord décisionnel
    public function statistiques()
    {
        return response()->json([
            'total' => Anomalie::count(),
            'par_criticite' => Anomalie::selectRaw('criticite, count(*) as total')->groupBy('criticite')->get(),
            'par_zone' => Anomalie::selectRaw('zone, count(*) as total')->groupBy('zone')->get(),
        ]);
    }
}
