<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FluxVideo extends Model
{
    use HasFactory;

    protected $fillable = ['time', 'url', 'camera_id'];

    public function anomalies()
    {
        return $this->hasMany(Anomalie::class);
    }
}