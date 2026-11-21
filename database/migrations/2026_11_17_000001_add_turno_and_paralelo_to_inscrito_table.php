<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el turno y el paralelo a la inscripción del estudiante.
     * Estos datos permiten contar a los estudiantes ya inscritos junto con los postulantes
     * al revisar los cupos de un grupo. Las verificaciones evitan intentar crear columnas
     * o un índice que ya existan en una base de datos parcialmente actualizada.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('inscrito', 'turno') || ! Schema::hasColumn('inscrito', 'paralelo')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                // Revisa cada campo por separado para agregar solo el que todavía falte.
                if (! Schema::hasColumn('inscrito', 'turno')) {
                    $table->unsignedTinyInteger('turno')->nullable();
                }
                if (! Schema::hasColumn('inscrito', 'paralelo')) {
                    $table->string('paralelo', 20)->nullable();
                }
            });
        }

        // El índice acelera las consultas de cupos que combinan gestión, carrera, turno y paralelo.
        if (! Schema::hasIndex('inscrito', 'inscrito_disponibilidad_turnos_idx')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->index(
                    ['id_gestionacademica', 'id_carrera', 'turno', 'paralelo'],
                    'inscrito_disponibilidad_turnos_idx'
                );
            });
        }
    }

    /**
     * Revierte la migración: elimina el índice si está presente y luego quita
     * las columnas de turno y paralelo que existan en la tabla de inscripciones.
     */
    public function down(): void
    {
        if (Schema::hasIndex('inscrito', 'inscrito_disponibilidad_turnos_idx')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->dropIndex('inscrito_disponibilidad_turnos_idx');
            });
        }

        // Incluye solo las columnas existentes para que el borrado se haga con los campos disponibles.
        $columnas = array_values(array_filter(
            ['turno', 'paralelo'],
            fn (string $columna): bool => Schema::hasColumn('inscrito', $columna)
        ));

        if ($columnas !== []) {
            Schema::table('inscrito', function (Blueprint $table) use ($columnas): void {
                $table->dropColumn($columnas);
            });
        }
    }
};
