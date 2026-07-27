<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'nom',
        'email',
        'mot_de_passe',
        'role',
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    // Laravel s'attend par défaut à une colonne "password" pour l'authentification
    // On lui indique d'utiliser "mot_de_passe" à la place
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    // Méthodes utilitaires pour vérifier le rôle
    public function estOperateur(): bool
    {
        return $this->role === 'operateur';
    }

    public function estManager(): bool
    {
        return $this->role === 'manager';
    }

    // Relations (on les complètera au fur et à mesure des autres migrations)
    public function alertes()
    {
        return $this->hasMany(Alerte::class, 'operateur_id');
    }
}