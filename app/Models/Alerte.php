<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerte extends Model
{
    use HasFactory;

    protected $fillable = ['date_envoi', 'statut', 'anomalie_id', 'operateur_id'];

    public function anomalie()
    {
        return $this->belongsTo(Anomalie::class);
    }

    public function operateur()
    {
        return $this->belongsTo(Utilisateur::class, 'operateur_id');
    }
}