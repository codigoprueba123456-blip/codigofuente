<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega a cada postulación el turno y el paralelo que eligió la persona.
     * Ambos campos aceptan null para que los registros anteriores, creados antes de esta migración,
     * puedan seguir existiendo sin que sea necesario asignarles un grupo retroactivamente.
     * También crea un índice para buscar postulaciones por carrera, gestión y grupo.
     */
    public function up(): void
    {
        Schema::table('postulante', function (Blueprint $table): void {
            // El turno guarda un código numérico y el paralelo una letra o nombre corto del grupo.
            $table->unsignedTinyInteger('turno')->nullable();
            $table->string('paralelo', 20)->nullable();

            // Ayuda a consultar las postulaciones de un grupo específico dentro de una gestión y carrera.
            $table->index(
                ['id_carrera', 'id_gestionacademica', 'turno', 'paralelo'],
                'postulante_turno_paralelo_index'
            );
        });
    }

    /**
     * Revierte esta migración quitando primero el índice y luego las dos columnas que agregó.
     */
    public function down(): void
    {
        Schema::table('postulante', function (Blueprint $table): void {
            $table->dropIndex('postulante_turno_paralelo_index');
            $table->dropColumn(['turno', 'paralelo']);
        });
    }
};
