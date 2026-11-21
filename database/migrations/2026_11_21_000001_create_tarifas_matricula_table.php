<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea tarifas exactas de matrícula por gestión, carrera y nivel opcional.
     */
    public function up(): void
    {
        Schema::create('tarifas_matricula', function (Blueprint $table) {
            $table->id('id_tarifa_matricula');
            $table->unsignedBigInteger('id_gestionacademica');
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedTinyInteger('nivel')->default(0);
            $table->decimal('monto', 12, 2);
            $table->timestamps();

            $table->foreign('id_gestionacademica')
                ->references('id_gestionacademica')
                ->on('gestionacademica')
                ->cascadeOnDelete();
            $table->foreign('id_carrera')
                ->references('id_carrera')
                ->on('carreras')
                ->cascadeOnDelete();
            $table->unique(
                ['id_gestionacademica', 'id_carrera', 'nivel'],
                'tarifas_matricula_gestion_carrera_nivel_unique'
            );
        });
    }

    /**
     * Elimina las tarifas específicas al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifas_matricula');
    }
};
