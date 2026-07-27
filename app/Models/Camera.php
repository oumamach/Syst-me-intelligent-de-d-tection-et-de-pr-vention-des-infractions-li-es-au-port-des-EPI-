<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'zone', 'statut'];

    public function fluxVideos()
    {
        return $this->hasMany(FluxVideo::class);
    }
}