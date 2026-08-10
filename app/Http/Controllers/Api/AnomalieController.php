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
    private string $gradioBaseUrl = 'https://ppe-detection-7bya.onrender.com';

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

        $estDanger = str_contains(mb_strtoupper($rapportTexte), 'DANGER');

        if (!$estDanger) {
            return response()->json([
                'status' => 'ok',
                'danger' => false,
                'rapport' => $rapportTexte,
                'detections' => $detailsJson,
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
     * Effectue les 3 étapes d'appel à l'API Gradio et retourne [image_url, details_json, rapport_texte]
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
            ->post("{$this->gradioBaseUrl}/gradio_api/upload");

        if (!$uploadResponse->successful()) {
            throw new \Exception('Échec upload Gradio: ' . $uploadResponse->body());
        }

        $cheminsUploades = $uploadResponse->json();
        $cheminServeur = $cheminsUploades[0] ?? null;

        if (!$cheminServeur) {
            throw new \Exception('Aucun chemin retourné par l\'upload Gradio');
        }

        // Étape 2 : appel de la fonction /detect
        $callResponse = Http::timeout(30)->post("{$this->gradioBaseUrl}/gradio_api/call/detect", [
            'data' => [
                [
                    'path' => $cheminServeur,
                    'meta' => ['_type' => 'gradio.FileData'],
                ],
            ],
        ]);

        $eventId = $callResponse->json('event_id');
        if (!$eventId) {
            throw new \Exception('Pas d\'event_id retourné par Gradio: ' . $callResponse->body());
        }

        // Étape 3 : récupération du résultat (flux SSE)
        $ch = curl_init("{$this->gradioBaseUrl}/gradio_api/call/detect/{$eventId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $brut = curl_exec($ch);
        $erreurCurl = curl_error($ch);
        curl_close($ch);

        if ($brut === false) {
            throw new \Exception('Erreur curl: ' . $erreurCurl);
        }

        if (!preg_match('/event:\s*complete\s*\ndata:\s*(\[.*\])/s', $brut, $matches)) {
            throw new \Exception('Réponse Gradio non reconnue: ' . substr($brut, 0, 300));
        }

        $resultat = json_decode($matches[1], true);

        if (!$resultat || count($resultat) < 3) {
            throw new \Exception('Format de résultat Gradio inattendu');
        }

        $imageInfo = $resultat[0];
        $imageUrl = null;
        if (is_array($imageInfo) && isset($imageInfo['path'])) {
            $imageUrl = "{$this->gradioBaseUrl}/gradio_api/file=" . $imageInfo['path'];
        } elseif (is_array($imageInfo) && isset($imageInfo['url'])) {
            $imageUrl = $imageInfo['url'];
        }

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