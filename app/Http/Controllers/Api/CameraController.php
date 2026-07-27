<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use Illuminate\Http\Request;

class CameraController extends Controller
{
    public function index()
    {
        return response()->json(Camera::orderBy('nom')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string',
            'zone' => 'required|string',
            'statut' => 'sometimes|in:actif,inactif,maintenance',
        ]);

        $camera = Camera::create([
            'nom' => $validated['nom'],
            'zone' => $validated['zone'],
            'statut' => $validated['statut'] ?? 'actif',
        ]);

        return response()->json($camera, 201);
    }

    public function destroy(Camera $camera)
    {
        $camera->delete();
        return response()->json(['message' => 'Caméra supprimée.']);
    }
}