<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la ficha académica y los datos de contacto y documentos del estudiante.
     */
    public function up(): void
    {
        Schema::create('estudiante', function (Blueprint $table) {
            // La ficha comparte el identificador de su persona y no crea una identidad separada.
            $table->unsignedBigInteger('id_persona')->primary();

            $table->date('fecha')->nullable();
            $table->integer('matricula')->nullable();
            $table->boolean('diplomabachiller')->default(false);
            $table->string('nombrecontacto')->nullable();
            $table->integer('celularcontacto')->nullable();
            $table->string('parentescocontacto')->nullable();
            $table->string('nombrecolegio')->nullable();
            $table->string('caractercolegio')->nullable();
            $table->integer('egresocolegio')->nullable();
            
            // Rutas de documentos de identidad, nacimiento, diploma y certificado BTH.
            $table->string('path_ci')->nullable();
            $table->string('path_ci_reverso')->nullable();
            $table->string('path_nacimiento')->nullable();
            $table->string('path_diploma')->nullable();
            $table->string('path_bth')->nullable();

            // Datos administrativos que pueden acompañar las inscripciones del estudiante.
            $table->boolean('becado')->default(false);
            $table->string('deposito')->nullable();
            $table->boolean('traspaso_convalidacion')->default(false);
            
            $table->unsignedBigInteger('id_carrera')->nullable();
            $table->unsignedBigInteger('id_gestionacademica')->nullable();
            $table->unsignedBigInteger('id_postulante')->nullable();

            // La ficha depende de la persona; carrera, gestión y postulación pueden quedar sin vínculo.
            $table->foreign('id_persona')->references('id_persona')->on('personas')->onDelete('cascade');
            $table->foreign('id_gestionacademica')->references('id_gestionacademica')->on('gestionacademica')->onDelete('set null');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('set null');
            $table->foreign('id_postulante')->references('id_postulante')->on('postulante')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Elimina la ficha de estudiante al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiante');
    }
};