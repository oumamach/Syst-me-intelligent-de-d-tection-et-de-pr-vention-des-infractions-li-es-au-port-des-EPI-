<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Heatmap extends Model
{
    use HasFactory;

    protected $fillable = ['image_url', 'anomalie_id'];

    public function anomalie()
    {
        return $this->belongsTo(Anomalie::class);
    }
}