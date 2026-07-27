<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_i_a_s', function (Blueprint $table) {
            $table->id();
            $table->string('nom');        // ex: YOLOv8-EPI
            $table->string('type');       // ex: yolov8, autoencodeur
            $table->string('version');    // ex: v1.0
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_i_a_s');
    }
};