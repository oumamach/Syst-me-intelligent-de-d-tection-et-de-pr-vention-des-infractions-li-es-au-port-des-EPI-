<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anomalie;
use App\Models\Heatmap;
use App\Models\RapportTextuel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnomalieController extends Controller
{
    private string $gradioBaseUrl = 'https://oumamach-ppe-detection-docker.hf.space';

    // Durée (en secondes) pendant laquelle on ignore les nouvelles détections
    // identiques pour éviter de spammer la base de données
    private int $cooldownSecondes = 60;

    // GET /api/anomalies
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
            'zone' => 'nullable|string',
            'camera_id' => 'nullable',
        ]);

        $imageBase64 = $request->input('image');
        $zone = $request->input('zone') ?? ($request->input('camera_id') ? "Camera_{$request->input('camera_id')}" : 'entree_principale');

        try {
            [$imageAnnoteeUrl, $detailsJson, $rapportTexte] = $this->appellerGradio($imageBase64);
        } catch (\Exception $e) {
            Log::error('Erreur appel Gradio: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Le service de détection IA est indisponible.',
            ], 502);
        }

        $estDanger = str_contains($rapportTexte, '🚨') || str_contains(mb_strtolower($rapportTexte), 'absence');

        if (!$estDanger) {
            return response()->json([
                'status' => 'ok',
                'danger' => false,
                'rapport' => $rapportTexte,
                'detections' => $detailsJson,
            ]);
        }

        // Anti-doublon : si une anomalie identique existe déjà récemment pour cette zone, on ne recrée pas d'entrée
        $anomalieRecente = Anomalie::where('zone', $zone)
            ->where('type', 'absence_epi')
            ->where('date_detection', '>=', now()->subSeconds($this->cooldownSecondes))
            ->latest('date_detection')
            ->first();

        if ($anomalieRecente) {
            return response()->json([
                'status' => 'ok',
                'danger' => true,
                'rapport' => $rapportTexte,
                'detections' => $detailsJson,
                'anomalie' => $anomalieRecente->load(['heatmap', 'rapportTextuel']),
                'doublon_ignore' => true,
            ]);
        }

        $score = 0.5;
        if (is_array($detailsJson)) {
            foreach ($detailsJson as $item) {
                if (isset($item['confiance']) && $item['confiance'] > $score) {
                    $score = $item['confiance'];
                }
            }
        }

        $anomalie = Anomalie::create([
            'type' => 'absence_epi',
            'criticite' => 'haute',
            'date_detection' => now(),
            'zone' => $zone,
            'score_confiance' => round($score, 3),
            'statut' => 'nouvelle',
        ]);

        if ($imageAnnoteeUrl) {
            try {
                $imageContenu = Http::timeout(15)->get($imageAnnoteeUrl)->body();
                $nomFichier = 'heatmaps/' . uniqid() . '.jpg';
                Storage::disk('public')->put($nomFichier, $imageContenu);

                Heatmap::create([
                    'anomalie_id' => $anomalie->id,
                    'image_url' => Storage::url($nomFichier),
                ]);
            } catch (\Exception $e) {
                Log::warning('Impossible de récupérer l\'image annotée: ' . $e->getMessage());
            }
        }

        RapportTextuel::create([
            'anomalie_id' => $anomalie->id,
            'contenu' => $rapportTexte,
            'date_generation' => now(),
        ]);

        return response()->json([
            'status' => 'ok',
            'danger' => true,
            'rapport' => $rapportTexte,
            'detections' => $detailsJson,
            'anomalie' => $anomalie->load(['heatmap', 'rapportTextuel']),
        ], 201);
    }

    /**
     * Effectue les 2 étapes d'appel à l'API Gradio (/upload puis /api/predict)
     * et retourne [image_url, details_json, rapport_texte]
     */
    private function appellerGradio(string $imageBase64): array
    {
        $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64);
        $binaire = base64_decode($imageData);

        if ($binaire === false) {
            throw new \Exception('Image base64 invalide');
        }

        // Étape 1 : upload de l'image
        $uploadResponse = Http::timeout(30)
            ->attach('files', $binaire, 'frame.jpg')
            ->post("{$this->gradioBaseUrl}/upload");

        if (!$uploadResponse->successful()) {
            throw new \Exception('Échec upload Gradio: ' . $uploadResponse->body());
        }

        $cheminsUploades = $uploadResponse->json();
        $cheminServeur = $cheminsUploades[0] ?? null;

        if (!$cheminServeur) {
            throw new \Exception('Aucun chemin retourné par l\'upload Gradio');
        }

        // Étape 2 : appel synchrone de /api/predict
        $predictResponse = Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->gradioBaseUrl}/api/predict", [
                'data' => [
                    [
                        'path' => $cheminServeur,
                        'meta' => ['_type' => 'gradio.FileData'],
                    ],
                ],
            ]);

        if (!$predictResponse->successful()) {
            throw new \Exception('Échec appel predict: ' . $predictResponse->body());
        }

        $resultat = $predictResponse->json('data');

        if (!$resultat || count($resultat) < 3) {
            throw new \Exception('Format de résultat Gradio inattendu: ' . $predictResponse->body());
        }

        $imageInfo = $resultat[0];
        $imageUrl = is_array($imageInfo) ? ($imageInfo['url'] ?? null) : null;

        return [$imageUrl, $resultat[1], $resultat[2]];
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