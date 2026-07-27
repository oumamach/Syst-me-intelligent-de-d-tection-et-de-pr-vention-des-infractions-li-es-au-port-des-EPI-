<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RapportTextuel extends Model
{
    use HasFactory;

    protected $fillable = ['contenu', 'date_generation', 'anomalie_id'];

    public function anomalie()
    {
        return $this->belongsTo(Anomalie::class);
    }
}