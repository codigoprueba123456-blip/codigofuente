<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inscrito', 'turno') || ! Schema::hasColumn('inscrito', 'paralelo')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                if (! Schema::hasColumn('inscrito', 'turno')) {
                    $table->unsignedTinyInteger('turno')->nullable();
                }
                if (! Schema::hasColumn('inscrito', 'paralelo')) {
                    $table->string('paralelo', 20)->nullable();
                }
            });
        }

        if (! Schema::hasIndex('inscrito', 'inscrito_disponibilidad_turnos_idx')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->index(
                    ['id_gestionacademica', 'id_carrera', 'turno', 'paralelo'],
                    'inscrito_disponibilidad_turnos_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('inscrito', 'inscrito_disponibilidad_turnos_idx')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->dropIndex('inscrito_disponibilidad_turnos_idx');
            });
        }

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
