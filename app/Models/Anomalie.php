<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anomalie extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'criticite',
        'date_detection',
        'zone',
        'score_confiance',
        'statut',
        'model_i_a_id',
        'flux_video_id',
    ];

    public function heatmap()
    {
        return $this->hasOne(Heatmap::class);
    }

    public function rapportTextuel()
    {
        return $this->hasOne(RapportTextuel::class);
    }

    public function alertes()
    {
        return $this->hasMany(Alerte::class);
    }

    public function modelIA()
    {
        return $this->belongsTo(ModelIA::class, 'model_i_a_id');
    }

    public function fluxVideo()
    {
        return $this->belongsTo(FluxVideo::class);
    }
}