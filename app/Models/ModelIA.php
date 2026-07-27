<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelIA extends Model
{
    use HasFactory;

    protected $table = 'model_i_a_s';
    protected $fillable = ['nom', 'type', 'version'];

    public function anomalies()
    {
        return $this->hasMany(Anomalie::class, 'model_i_a_id');
    }
}