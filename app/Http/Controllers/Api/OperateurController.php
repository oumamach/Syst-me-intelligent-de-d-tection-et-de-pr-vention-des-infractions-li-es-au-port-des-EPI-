<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use Illuminate\Http\Request;

class OperateurController extends Controller
{
    public function index()
    {
        return response()->json(Utilisateur::where('role', 'operateur')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string',
            'email' => 'required|email|unique:utilisateurs,email',
            'password' => 'required|string|min:6',
        ]);

        $operateur = Utilisateur::create([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'mot_de_passe' => bcrypt($validated['password']),
            'role' => 'operateur',
        ]);

        return response()->json($operateur, 201);
    }

    public function destroy(Utilisateur $operateur)
    {
        $operateur->delete();
        return response()->json(['message' => 'Opérateur supprimé.']);
    }
}