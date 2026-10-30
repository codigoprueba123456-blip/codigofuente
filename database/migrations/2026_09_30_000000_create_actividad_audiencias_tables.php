<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_carrera', function (Blueprint $table) {
            $table->unsignedBigInteger('id_actividad');
            $table->unsignedBigInteger('id_carrera');
            $table->primary(['id_actividad', 'id_carrera']);
            $table->foreign('id_actividad')->references('id_actividad')->on('actividad')->onDelete('cascade');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('cascade');
        });

        Schema::create('actividad_usuario', function (Blueprint $table) {
            $table->unsignedBigInteger('id_actividad');
            $table->unsignedBigInteger('id_persona');
            $table->primary(['id_actividad', 'id_persona']);
            $table->foreign('id_actividad')->references('id_actividad')->on('actividad')->onDelete('cascade');
            $table->foreign('id_persona')->references('id_persona')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_usuario');
        Schema::dropIfExists('actividad_carrera');
    }
};