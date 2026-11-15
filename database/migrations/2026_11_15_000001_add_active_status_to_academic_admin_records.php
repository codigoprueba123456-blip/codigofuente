<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['materia', 'grupo', 'actividad', 'gestionacademica', 'rol', 'empleados'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('activo')->default(true);
            });
        }
    }

    public function down(): void
    {
        foreach (['empleados', 'rol', 'gestionacademica', 'actividad', 'grupo', 'materia'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('activo');
            });
        }
    }
};
