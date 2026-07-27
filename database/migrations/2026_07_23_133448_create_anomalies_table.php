<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->string('type');                    // ex: absence_epi
            $table->enum('criticite', ['basse', 'moyenne', 'haute']);
            $table->timestamp('date_detection');
            $table->string('zone');
            $table->float('score_confiance');
            $table->enum('statut', ['nouvelle', 'confirmee', 'faux_positif'])->default('nouvelle');

            $table->foreignId('model_i_a_id')->nullable()->constrained('model_i_a_s')->nullOnDelete();
            $table->foreignId('flux_video_id')->nullable()->constrained('flux_videos')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomalies');
    }
};