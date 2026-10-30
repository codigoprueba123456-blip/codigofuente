<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_persona');
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedBigInteger('id_gestionacademica')->nullable();
            $table->unsignedBigInteger('id_mencion')->nullable();
            $table->integer('nivelinicio')->default(1);

            // Relaciones
            $table->foreign('id_persona')->references('id_persona')->on('personas')->onDelete('cascade');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('cascade');
            $table->foreign('id_gestionacademica')->references('id_gestionacademica')->on('gestionacademica')->nullOnDelete();

            $table->unique(['id_persona', 'id_carrera', 'id_gestionacademica'], 'cursa_persona_carrera_gestion_unique');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursa');
    }
};