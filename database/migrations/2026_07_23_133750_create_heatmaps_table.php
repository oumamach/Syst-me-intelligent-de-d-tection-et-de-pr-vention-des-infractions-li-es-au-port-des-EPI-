<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heatmaps', function (Blueprint $table) {
            $table->id();
            $table->string('image_url');

            $table->foreignId('anomalie_id')->constrained('anomalies')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heatmaps');
    }
};