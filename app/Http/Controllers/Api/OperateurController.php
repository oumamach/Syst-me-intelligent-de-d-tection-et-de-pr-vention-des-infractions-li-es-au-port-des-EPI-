<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OperateurController extends Controller
{
    // 1. Récupérer uniquement les opérateurs
    public function index()
    {
        return response()->json(
            Utilisateur::where('role', 'operateur')->get()
        );
    }

    // 2. Ajouter un nouvel opérateur
    public function store(Request $request)
    {
        // On assouplit min à 4 pour accepter les mots de passe de test
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:utilisateurs,email',
            'password' => 'required|string|min:4',
        ]);

        $operateur = Utilisateur::create([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'mot_de_passe' => Hash::make($validated['password']),
            'role' => 'operateur', // Doit correspondre exactement au 'where' du index()
        ]);

        return response()->json($operateur, 201);
    }

    // 3. Supprimer un opérateur
    public function destroy($id)
    {
        $operateur = Utilisateur::findOrFail($id);
        $operateur->delete();

        return response()->json(['message' => 'Opérateur supprimé.']);
    }
}