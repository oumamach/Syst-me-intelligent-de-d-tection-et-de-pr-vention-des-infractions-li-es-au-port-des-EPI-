<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapport_textuels', function (Blueprint $table) {
            $table->id();
            $table->text('contenu');
            $table->timestamp('date_generation')->useCurrent();

            $table->foreignId('anomalie_id')->constrained('anomalies')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapport_textuels');
    }
};