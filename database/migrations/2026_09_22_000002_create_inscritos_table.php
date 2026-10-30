<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscrito', function (Blueprint $table) {
            $table->id('id_inscrito');
            $table->bigInteger('codigo')->nullable();
            $table->date('fecha');
            $table->integer('numero')->nullable();
            $table->integer('tipo')->default(1);
            $table->boolean('becado')->default(false);
            $table->string('deposito')->nullable();
            $table->unsignedBigInteger('id_postulante_origen')->nullable();

            // Llaves foráneas
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedBigInteger('id_persona');
            $table->unsignedBigInteger('id_gestionacademica');

            // Definición de Claves Foráneas
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('cascade');
            $table->foreign('id_persona')->references('id_persona')->on('personas')->onDelete('cascade');
            $table->foreign('id_gestionacademica')->references('id_gestionacademica')->on('gestionacademica')->onDelete('cascade');

            $table->unique(['deposito', 'id_gestionacademica'], 'inscrito_voucher_gestion_unique');
            $table->unique(['id_persona', 'id_carrera', 'id_gestionacademica'], 'inscrito_persona_carrera_gestion_unique');
            $table->unique('id_postulante_origen', 'inscrito_postulante_origen_unique');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscrito');
    }
};