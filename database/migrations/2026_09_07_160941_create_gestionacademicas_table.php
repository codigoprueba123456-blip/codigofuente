<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea los periodos académicos y sus fechas, régimen y estado de vigencia.
     */
    public function up(): void
    {
        Schema::create('gestionacademica', function (Blueprint $table) {
            $table->id('id_gestionacademica');
            $table->integer('gestion');
            $table->integer('periodo');
            $table->integer('regimen');
            $table->date('inicio')->nullable();
            $table->date('fin')->nullable();
            $table->boolean('vigente')->default(true);
            $table->integer('modalidadevaluacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de gestiones académicas al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestionacademica');
    }
};