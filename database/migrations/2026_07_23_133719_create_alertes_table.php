<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table) {
            $table->id();
            $table->timestamp('date_envoi')->useCurrent();
            $table->enum('statut', ['envoyee', 'lue', 'traitee'])->default('envoyee');

            $table->foreignId('anomalie_id')->constrained('anomalies')->cascadeOnDelete();
            $table->foreignId('operateur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};