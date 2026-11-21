<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el estado que permite habilitar o retirar una carrera del uso cotidiano.
     */
    public function up(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('nombre');
        });
    }

    /**
     * Retira la columna de estado al revertir este cambio.
     */
    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
