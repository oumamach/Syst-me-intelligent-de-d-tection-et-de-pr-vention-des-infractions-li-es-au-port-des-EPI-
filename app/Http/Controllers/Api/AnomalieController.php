<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anomalie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class AnomalieController extends Controller
{
    // GET /api/anomalies — liste des anomalies
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

    // POST /api/detecter-anomalie — appelé par CamerasView.vue pendant le live
    public function detecterAnomalie(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'camera_id' => 'nullable',
        ]);

        $imageBase64 = $request->input('image');
        $cameraId = $request->input('camera_id');

        // 1. Détermination de la zone
        $nomZone = 'entree_principale';
        if ($cameraId) {
            try {
                $camera = DB::table('flux_videos')->where('id', $cameraId)->first() 
                       ?? DB::table('cameras')->where('id', $cameraId)->first();
                if ($camera) {
                    $nomZone = $camera->emplacement ?? $camera->zone ?? $camera->nom ?? "Camera_{$cameraId}";
                } else {
                    $nomZone = "Camera_{$cameraId}";
                }
            } catch (\Exception $e) {
                $nomZone = "Camera_{$cameraId}";
            }
        }

        // 2. Appel dynamique de l'API Hugging Face de détection EPI
        $hfUrl = "https://alaemoussi-ppe-detection-api.hf.space/predict";
        
        $rapportTextuel = "Alerte [CRITIQUE] : Infraction EPI détectée sur {$nomZone}.";
        $typeAnomalie = 'absence_epi';
        $scoreConfiance = 0.92;
        $imageAStocke = $imageBase64; // Par défaut, conserve l'image originale

        try {
            // Requête HTTP vers le Space Hugging Face (timeout de 10s pour prévenir les blocages)
            $responseIA = Http::timeout(10)->post($hfUrl, [
                'image' => $imageBase64
            ]);

            if ($responseIA->successful()) {
                $dataIA = $responseIA->json();

                // Utilisation de l'image annotée avec Bounding Boxes / Heatmap si retournée par l'IA
                if (!empty($dataIA['annotated_image'])) {
                    $imageAStocke = $dataIA['annotated_image'];
                } elseif (!empty($dataIA['heatmap'])) {
                    $imageAStocke = $dataIA['heatmap'];
                }

                // Construction dynamique du rapport selon les détections de l'IA (Casque, Gilet, Bottes, etc.)
                if (!empty($dataIA['report'])) {
                    $rapportTextuel = $dataIA['report'];
                } elseif (!empty($dataIA['description'])) {
                    $rapportTextuel = $dataIA['description'];
                } elseif (!empty($dataIA['detections']) && is_array($dataIA['detections'])) {
                    $infractions = implode(', ', $dataIA['detections']);
                    $rapportTextuel = "Alerte [CRITIQUE] : Infraction EPI ({$infractions}) détectée sur {$nomZone}.";
                }

                if (isset($dataIA['confidence'])) {
                    $scoreConfiance = (float) $dataIA['confidence'];
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur de connexion à l'API Hugging Face : " . $e->getMessage());
        }

        // 3. Enregistrement de l'anomalie en BDD
        $dataAnomalie = [
            'type' => $typeAnomalie,
            'criticite' => 'haute',
            'date_detection' => now(),
            'zone' => $nomZone,
            'score_confiance' => $scoreConfiance,
            'statut' => 'nouvelle'
        ];

        if (Schema::hasColumn('anomalies', 'image_url')) {
            $dataAnomalie['image_url'] = $imageAStocke;
        }

        $anomalie = Anomalie::create($dataAnomalie);

        // 4. Enregistrement dans la table heatmaps
        if (Schema::hasTable('heatmaps')) {
            try {
                $insertData = ['anomalie_id' => $anomalie->id];
                
                if (Schema::hasColumn('heatmaps', 'chemin')) {
                    $insertData['chemin'] = $imageAStocke;
                }
                if (Schema::hasColumn('heatmaps', 'image_url')) {
                    $insertData['image_url'] = $imageAStocke;
                }
                if (Schema::hasColumn('heatmaps', 'created_at')) {
                    $insertData['created_at'] = now();
                    $insertData['updated_at'] = now();
                }

                DB::table('heatmaps')->insert($insertData);
            } catch (\Exception $e) {
                // Évite d'interrompre si la table a des contraintes différentes
            }
        }

        return response()->json([
            'status' => 'success',
            'anomalie_detectee' => true,
            'chemin_heatmap' => $imageAStocke,
            'rapport_textuel' => $rapportTextuel,
            'criticite' => 'HAUTE',
            'anomalie' => $anomalie->load(['heatmap', 'rapportTextuel'])
        ], 200);
    }

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

    public function show(Anomalie $anomalie)
    {
        return response()->json($anomalie->load(['heatmap', 'rapportTextuel', 'alertes']));
    }

    public function statistiques()
    {
        return response()->json([
            'total' => Anomalie::count(),
            'par_criticite' => Anomalie::selectRaw('criticite, count(*) as total')->groupBy('criticite')->get(),
            'par_zone' => Anomalie::selectRaw('zone, count(*) as total')->groupBy('zone')->get(),
        ]);
    }
}