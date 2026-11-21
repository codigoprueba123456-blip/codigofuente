<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la relación entre una inscripción, sus materias y los grupos asignados.
     */
    public function up(): void
    {
        Schema::create('inscrito_materia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_inscrito');
            $table->unsignedBigInteger('id_materia');
            $table->unsignedBigInteger('id_grupo')->nullable();
            $table->timestamps();

            // Una inscripción solo puede tener una fila por materia; el grupo puede quedar pendiente.
            $table->foreign('id_inscrito')->references('id_inscrito')->on('inscrito')->cascadeOnDelete();
            $table->foreign('id_materia')->references('id_materia')->on('materia')->cascadeOnDelete();
            $table->foreign('id_grupo')->references('id_grupo')->on('grupo')->nullOnDelete();
            $table->unique(['id_inscrito', 'id_materia']);
        });
    }

    /**
     * Elimina las asignaciones de materias al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscrito_materia');
    }
};
