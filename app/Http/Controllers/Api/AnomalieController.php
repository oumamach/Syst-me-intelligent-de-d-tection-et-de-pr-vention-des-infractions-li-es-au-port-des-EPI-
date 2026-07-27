<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anomalie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

    // POST /api/detecter-anomalie — appelée par Vue.js pour l'analyse en direct de la caméra
    public function detecterAnomalie(Request $request)
    {
        $request->validate([
            'image' => 'required|string', // Image Base64 venant de Vue.js
            'camera_id' => 'nullable',
        ]);

        $imageBase64 = $request->input('image');
        $cameraId = $request->input('camera_id');

        // 1. Détermination dynamique de la zone à partir du camera_id
        $nomZone = 'Zone Inconnue';

        if ($cameraId) {
            // Cherche d'abord dans la table flux_videos
            $camera = DB::table('flux_videos')->where('id', $cameraId)->first();
            
            // Si pas trouvé, cherche dans la table cameras
            if (!$camera) {
                $camera = DB::table('cameras')->where('id', $cameraId)->first();
            }

            if ($camera) {
                $nomZone = $camera->emplacement ?? $camera->zone ?? $camera->nom ?? "Camera_{$cameraId}";
            } else {
                $nomZone = "Camera_{$cameraId}";
            }
        } else {
            $nomZone = 'Quai 3';
        }

        /*
        |--------------------------------------------------------------------------
        | Intégration de l'API Hugging Face
        |--------------------------------------------------------------------------
        | Dès que l'API de ta camarade est prête, décommente ces lignes :
        |
        | $hfUrl = "https://TON_LIEN_SPACE_HUGGING_FACE.hf.space/predict";
        | $responseIA = Http::post($hfUrl, ['image' => $imageBase64]);
        | $dataIA = $responseIA->json();
        */

        // 2. Enregistrement de l'anomalie en base de données avec la zone dynamique
        $anomalie = Anomalie::create([
            'type' => 'absence_epi',
            'criticite' => 'haute',
            'date_detection' => now(),
            'zone' => $nomZone,
            'score_confiance' => 0.94,
            'statut' => 'nouvelle'
        ]);

        return response()->json([
            'status' => 'success',
            'chemin_heatmap' => null,
            'rapport_textuel' => "Alerte [CRITIQUE] : Infraction EPI (absence de casque) détectée sur {$nomZone}.",
            'criticite' => 'HAUTE',
            'anomalie' => $anomalie
        ], 200);
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