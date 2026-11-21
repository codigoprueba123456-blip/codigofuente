<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea los vínculos entre materias y las materias que son requisito previo.
     */
    public function up(): void
    {
        Schema::create('prerequisito', function (Blueprint $table) {
            $table->unsignedBigInteger('id_materia');
            $table->unsignedBigInteger('id_prerequisito');

            // Evita repetir el mismo requisito para una materia.
            $table->primary(['id_materia', 'id_prerequisito']);

            // Ambos identificadores apuntan a materias: una es la materia cursada y otra su requisito.
            $table->foreign('id_materia')
                  ->references('id_materia')
                  ->on('materia')
                  ->onDelete('cascade');

            $table->foreign('id_prerequisito')
                  ->references('id_materia')
                  ->on('materia')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Elimina las relaciones entre materias al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('prerequisito');
    }
};