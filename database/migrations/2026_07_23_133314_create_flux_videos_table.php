<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flux_videos', function (Blueprint $table) {
            $table->id();
            $table->timestamp('time');
            $table->string('url')->nullable();  // lien vers l'enregistrement, si stocké
            $table->foreignId('camera_id')->nullable(); // on rattachera à la table Camera plus tard
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flux_videos');
    }
};